#!/usr/bin/env python3
"""Smoke-test the live Leave One Light On production site.

This audit complements repository-level QA by checking what Hostinger actually
serves over HTTPS: core pages, crawler files, canonical redirects, and the
Welcome Shelf HTTP canonical headers configured in .htaccess.
"""

from __future__ import annotations

from dataclasses import dataclass
from email.message import Message
from http.client import HTTPResponse
from urllib.error import HTTPError, URLError
from urllib.request import HTTPRedirectHandler, Request, build_opener
import sys
import time
import xml.etree.ElementTree as ET

BASE = "https://leaveonelighton.org"
TIMEOUT_SECONDS = 15
RETRIES = 3
RETRY_DELAY_SECONDS = 3
USER_AGENT = "LeaveOneLightOn-ProductionSmoke/1.0"


@dataclass
class Result:
    url: str
    status: int
    headers: Message
    body: bytes


class NoRedirect(HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):  # noqa: ANN001
        return None


FOLLOW = build_opener()
NO_REDIRECT = build_opener(NoRedirect())


def fetch(url: str, *, follow_redirects: bool = True) -> Result:
    opener = FOLLOW if follow_redirects else NO_REDIRECT
    last_error: Exception | None = None

    for attempt in range(1, RETRIES + 1):
        request = Request(
            url,
            headers={
                "User-Agent": USER_AGENT,
                "Accept": "text/html,application/xml,text/xml,text/plain,*/*;q=0.8",
                "Cache-Control": "no-cache",
            },
        )
        try:
            with opener.open(request, timeout=TIMEOUT_SECONDS) as response:
                status = getattr(response, "status", response.getcode())
                body = response.read(3_000_000)
                if status >= 500 and attempt < RETRIES:
                    time.sleep(RETRY_DELAY_SECONDS)
                    continue
                return Result(url, status, response.headers, body)
        except HTTPError as exc:
            body = exc.read(3_000_000)
            if exc.code >= 500 and attempt < RETRIES:
                time.sleep(RETRY_DELAY_SECONDS)
                continue
            return Result(url, exc.code, exc.headers, body)
        except (URLError, TimeoutError, OSError) as exc:
            last_error = exc
            if attempt < RETRIES:
                time.sleep(RETRY_DELAY_SECONDS)
                continue

    raise RuntimeError(f"Unable to fetch {url} after {RETRIES} attempts: {last_error}")


def add_error(errors: list[str], condition: bool, message: str) -> None:
    if not condition:
        errors.append(message)


def check_200(path: str, errors: list[str], *, contains: str | None = None) -> None:
    url = f"{BASE}{path}"
    result = fetch(url)
    add_error(errors, result.status == 200, f"Expected 200 for {url}; got {result.status}")
    if contains and result.status == 200:
        text = result.body.decode("utf-8", errors="replace")
        add_error(errors, contains in text, f"Expected production content marker {contains!r} in {url}")


def check_redirect(path: str, target: str, errors: list[str]) -> None:
    url = f"{BASE}{path}"
    result = fetch(url, follow_redirects=False)
    location = result.headers.get("Location", "")
    add_error(errors, result.status == 301, f"Expected 301 for {url}; got {result.status}")
    add_error(errors, location == target, f"Unexpected redirect for {url}: {location!r}; expected {target!r}")


def check_robots(errors: list[str]) -> None:
    url = f"{BASE}/robots.txt"
    result = fetch(url)
    add_error(errors, result.status == 200, f"Expected 200 for {url}; got {result.status}")
    if result.status != 200:
        return

    text = result.body.decode("utf-8", errors="replace")
    lines = {line.strip() for line in text.splitlines() if line.strip()}
    required = {
        "User-agent: *",
        "Allow: /",
        f"Sitemap: {BASE}/sitemap.xml",
    }
    for line in sorted(required):
        add_error(errors, line in lines, f"robots.txt missing required line: {line}")
    add_error(errors, "Disallow: /" not in lines, "robots.txt unexpectedly blocks the entire site")


def check_sitemap(errors: list[str]) -> None:
    url = f"{BASE}/sitemap.xml"
    result = fetch(url)
    add_error(errors, result.status == 200, f"Expected 200 for {url}; got {result.status}")
    if result.status != 200:
        return

    try:
        root = ET.fromstring(result.body)
    except ET.ParseError as exc:
        errors.append(f"Live sitemap is not valid XML: {exc}")
        return

    urls = [node.text.strip() for node in root.iter() if node.tag.endswith("loc") and node.text]
    add_error(errors, len(urls) == len(set(urls)), "Live sitemap contains duplicate <loc> URLs")

    required = {
        f"{BASE}/",
        f"{BASE}/books.html",
        f"{BASE}/give-grace.html",
        f"{BASE}/research/when-does-a-second-chance-become-real.html",
        f"{BASE}/research/dyslexia-reading-difficulties.html",
        f"{BASE}/resources/learning-differences.html",
        f"{BASE}/welcome-shelf/story-preservation-workbook.html",
    }
    for expected in sorted(required):
        add_error(errors, expected in urls, f"Live sitemap missing required URL: {expected}")

    print(f"Live sitemap inventory: {len(urls)} URLs")


def check_welcome_shelf_canonicals(errors: list[str]) -> None:
    slugs = [
        "community-light-starter-kit.html",
        "foster-care-start-here.html",
        "one-light-at-work.html",
        "one-meaningful-step.html",
        "reading-learning-questions.html",
        "resource-navigation-notes.html",
        "story-preservation-workbook.html",
    ]

    for slug in slugs:
        url = f"{BASE}/welcome-shelf/{slug}"
        result = fetch(url)
        add_error(errors, result.status == 200, f"Expected 200 for {url}; got {result.status}")
        if result.status != 200:
            continue
        expected = f'<{url}>; rel="canonical"'
        link_header = result.headers.get("Link", "")
        add_error(
            errors,
            expected in link_header,
            f"Missing live canonical Link header for {url}; got {link_header!r}",
        )


def main() -> int:
    errors: list[str] = []

    # Core public surfaces.
    check_200("/", errors, contains="Leave One Light On")
    for path in [
        "/books.html",
        "/give-grace.html",
        "/research/when-does-a-second-chance-become-real.html",
        "/research/dyslexia-reading-difficulties.html",
        "/resources/learning-differences.html",
        "/welcome-shelf/",
    ]:
        check_200(path, errors)

    check_robots(errors)
    check_sitemap(errors)

    # Canonical redirects defined in production .htaccess.
    check_redirect("/book.html", f"{BASE}/books.html", errors)
    check_redirect("/books", f"{BASE}/books.html", errors)
    check_redirect("/books/", f"{BASE}/books.html", errors)
    check_redirect(
        "/when-does-a-second-chance-become-real.html",
        f"{BASE}/research/when-does-a-second-chance-become-real.html",
        errors,
    )

    check_welcome_shelf_canonicals(errors)

    if errors:
        print("PRODUCTION SMOKE AUDIT FAILED", file=sys.stderr)
        for error in errors:
            print(f"- {error}", file=sys.stderr)
        return 1

    print("PRODUCTION SMOKE OK")
    print("Core pages, crawler files, redirects, and Welcome Shelf canonical headers are live.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
