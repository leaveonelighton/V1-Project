#!/usr/bin/env python3
"""Audit outbound links from public Leave One Light On HTML pages.

A 404/410 is treated as definitively broken only when the same remote host also
successfully serves at least one other outbound link in the same run. Host-wide
404 patterns can be anti-bot behavior, so they are reported for review instead
of failing CI. Authentication, rate limits, timeouts, and upstream failures are
also warnings rather than proof that a visitor-facing link is dead.
"""

from __future__ import annotations

from concurrent.futures import ThreadPoolExecutor, as_completed
from dataclasses import dataclass
from html.parser import HTMLParser
from pathlib import Path
from urllib.error import HTTPError, URLError
from urllib.parse import urldefrag, urlparse
from urllib.request import Request, build_opener
import socket
import sys
import time

ROOT = Path(__file__).resolve().parents[1]
BASE_HOSTS = {"leaveonelighton.org", "www.leaveonelighton.org"}
EXCLUDED_PREFIXES = {
    ".github",
    "docs",
    "maintenance",
    "prototype",
    "tests",
    "thelightinthewindowbook.com",
}

TIMEOUT_SECONDS = 12
RETRIES = 2
RETRY_DELAY_SECONDS = 2
MAX_WORKERS = 8
USER_AGENT = "Mozilla/5.0 (compatible; LeaveOneLightOn-LinkHealth/1.0; +https://leaveonelighton.org/)"
DEAD_STATUSES = {404, 410}
WARNING_STATUSES = {400, 401, 403, 405, 408, 409, 423, 425, 426, 429, 451}


class LinkParser(HTMLParser):
    def __init__(self) -> None:
        super().__init__(convert_charrefs=True)
        self.links: list[str] = []

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        if tag.lower() != "a":
            return
        values = {key.lower(): (value or "").strip() for key, value in attrs}
        href = values.get("href", "")
        if href:
            self.links.append(href)


@dataclass(frozen=True)
class Probe:
    url: str
    status: int | None
    final_url: str | None
    warning: str | None = None


def excluded(path: Path) -> bool:
    relative = path.relative_to(ROOT)
    return bool(relative.parts and relative.parts[0] in EXCLUDED_PREFIXES)


def iter_public_html() -> list[Path]:
    return [path for path in sorted(ROOT.rglob("*.html")) if not excluded(path)]


def normalize_external(raw: str) -> str | None:
    value = raw.strip()
    if not value or value.startswith("#"):
        return None
    if value.startswith("//"):
        value = f"https:{value}"

    parsed = urlparse(value)
    if parsed.scheme.lower() not in {"http", "https"}:
        return None

    host = (parsed.hostname or "").lower()
    if not host or host in BASE_HOSTS:
        return None

    clean, _fragment = urldefrag(value)
    return clean


def collect_links() -> tuple[dict[str, set[str]], int]:
    sources: dict[str, set[str]] = {}
    page_count = 0

    for page in iter_public_html():
        page_count += 1
        parser = LinkParser()
        parser.feed(page.read_text(encoding="utf-8", errors="replace"))
        relative = str(page.relative_to(ROOT))
        for raw in parser.links:
            url = normalize_external(raw)
            if not url:
                continue
            sources.setdefault(url, set()).add(relative)

    return sources, page_count


def probe_once(url: str) -> Probe:
    request = Request(
        url,
        headers={
            "User-Agent": USER_AGENT,
            "Accept": "text/html,application/xhtml+xml,application/pdf,*/*;q=0.8",
            "Cache-Control": "no-cache",
        },
    )

    try:
        with build_opener().open(request, timeout=TIMEOUT_SECONDS) as response:
            response.read(2048)
            status = getattr(response, "status", response.getcode())
            return Probe(url=url, status=status, final_url=response.geturl())
    except HTTPError as exc:
        try:
            exc.read(2048)
        except Exception:
            pass
        return Probe(url=url, status=exc.code, final_url=exc.geturl())
    except (URLError, TimeoutError, socket.timeout, OSError) as exc:
        return Probe(url=url, status=None, final_url=None, warning=str(exc))


def probe(url: str) -> Probe:
    last = Probe(url=url, status=None, final_url=None, warning="not attempted")
    for attempt in range(1, RETRIES + 1):
        last = probe_once(url)
        if last.status is not None and last.status < 500 and last.status != 429:
            return last
        if attempt < RETRIES:
            time.sleep(RETRY_DELAY_SECONDS)
    return last


def host_for(url: str) -> str:
    return (urlparse(url).hostname or "").lower()


def classify(result: Probe, verified_hosts: set[str]) -> str:
    if result.status in DEAD_STATUSES:
        return "broken" if host_for(result.url) in verified_hosts else "warning"
    if result.status is None:
        return "warning"
    if 200 <= result.status < 400:
        return "ok"
    if result.status in WARNING_STATUSES or result.status >= 500:
        return "warning"
    return "warning"


def source_summary(paths: set[str]) -> str:
    return ", ".join(sorted(paths))


def warning_detail(result: Probe, verified_hosts: set[str]) -> str:
    if result.status in DEAD_STATUSES and host_for(result.url) not in verified_hosts:
        return f"HTTP {result.status}; host returned no successful probes, so this may be anti-bot/soft blocking"
    if result.status is not None:
        return f"HTTP {result.status}"
    return result.warning or "network error"


def main() -> int:
    links, page_count = collect_links()
    urls = sorted(links)
    results: dict[str, Probe] = {}

    with ThreadPoolExecutor(max_workers=MAX_WORKERS) as pool:
        futures = {pool.submit(probe, url): url for url in urls}
        for future in as_completed(futures):
            url = futures[future]
            try:
                results[url] = future.result()
            except Exception as exc:  # defensive: one remote host must not crash the inventory
                results[url] = Probe(url=url, status=None, final_url=None, warning=repr(exc))

    verified_hosts = {
        host_for(result.url)
        for result in results.values()
        if result.status is not None and 200 <= result.status < 400
    }

    broken: list[Probe] = []
    warnings: list[Probe] = []
    ok = 0

    for url in urls:
        result = results[url]
        state = classify(result, verified_hosts)
        if state == "ok":
            ok += 1
        elif state == "broken":
            broken.append(result)
        else:
            warnings.append(result)

    print(
        "External link inventory: "
        f"{page_count} public HTML files; {len(urls)} unique outbound URLs; "
        f"{ok} verified; {len(warnings)} unverified/warnings; {len(broken)} definitively broken."
    )

    if warnings:
        print("\nUNVERIFIED / WARNING RESPONSES")
        for result in warnings:
            print(
                f"- {result.url} [{warning_detail(result, verified_hosts)}] — "
                f"{source_summary(links[result.url])}"
            )

    if broken:
        print("\nDEFINITIVELY BROKEN EXTERNAL LINKS", file=sys.stderr)
        for result in broken:
            print(
                f"- {result.url} [HTTP {result.status}] — {source_summary(links[result.url])}",
                file=sys.stderr,
            )
        return 1

    print("\nNO DEFINITIVE DEAD EXTERNAL LINKS FOUND")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
