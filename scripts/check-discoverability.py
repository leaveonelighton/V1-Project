#!/usr/bin/env python3
"""Validate public HTML discoverability signals against sitemap.xml.

The production repository contains a few classes of HTML that are intentionally
not part of Leave One Light On search discovery (prototype/docs/test material,
the separately hosted book-site source, and pages explicitly marked noindex).
Everything else that is indexable must declare a canonical URL either in HTML
or through an Apache HTTP Link header, and Leave One Light On canonicals must
appear in the sitemap.
"""

from __future__ import annotations

from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urlparse
import sys
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parents[1]
BASE_URL = "https://leaveonelighton.org"
SITEMAP = ROOT / "sitemap.xml"
HTACCESS = ROOT / ".htaccess"

# Non-production/supporting HTML trees. Pages marked noindex do not need to be
# listed here; the audit recognizes that signal directly.
EXCLUDED_PREFIXES = {
    ".github",
    "docs",
    "maintenance",
    "prototype",
    "tests",
    # Separate-domain source retained in this repository. Its pages canonicalize
    # to https://thelightinthewindowbook.com and are not LOLO sitemap entries.
    "thelightinthewindowbook.com",
}


class PageSignals(HTMLParser):
    def __init__(self) -> None:
        super().__init__(convert_charrefs=True)
        self.canonical: str | None = None
        self.noindex = False

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        values = {key.lower(): (value or "") for key, value in attrs}
        tag = tag.lower()
        if tag == "link":
            rel_tokens = {token.lower() for token in values.get("rel", "").split()}
            href = values.get("href", "").strip()
            if "canonical" in rel_tokens and href:
                self.canonical = href
        elif tag == "meta":
            name = values.get("name", "").lower()
            content = values.get("content", "").lower()
            if name in {"robots", "googlebot", "bingbot"} and "noindex" in content:
                self.noindex = True


def page_signals(path: Path) -> PageSignals:
    parser = PageSignals()
    parser.feed(path.read_text(encoding="utf-8", errors="replace"))
    return parser


def url_for_path(relative: Path) -> str:
    if relative.name == "index.html":
        if relative.parent == Path("."):
            return f"{BASE_URL}/"
        return f"{BASE_URL}/{relative.parent.as_posix().strip('/')}/"
    return f"{BASE_URL}/{relative.as_posix()}"


def path_for_url(url: str) -> Path | None:
    parsed = urlparse(url)
    if parsed.scheme != "https" or parsed.netloc != "leaveonelighton.org":
        return None
    route = parsed.path
    if route == "/":
        return ROOT / "index.html"
    if route.endswith("/"):
        return ROOT / route.lstrip("/") / "index.html"
    return ROOT / route.lstrip("/")


def canonical_headers() -> dict[str, str]:
    """Return basename -> canonical URL for Apache <Files> Link headers."""
    mapping: dict[str, str] = {}
    current_file: str | None = None
    for raw in HTACCESS.read_text(encoding="utf-8").splitlines():
        line = raw.strip()
        if line.startswith('<Files "') and line.endswith('">'):
            current_file = line[len('<Files "') : -len('">')]
            continue
        if line == "</Files>":
            current_file = None
            continue
        if current_file and "Header set Link" in line and "canonical" in line:
            start = line.find("<https://")
            end = line.find(">", start + 1)
            if start >= 0 and end > start:
                canonical = line[start + 1 : end]
                if current_file in mapping and mapping[current_file] != canonical:
                    raise ValueError(
                        f"Multiple canonical Link headers for {current_file}: "
                        f"{mapping[current_file]} and {canonical}"
                    )
                mapping[current_file] = canonical
    return mapping


def sitemap_urls() -> list[str]:
    tree = ET.parse(SITEMAP)
    root = tree.getroot()
    namespace = {"sm": "http://www.sitemaps.org/schemas/sitemap/0.9"}
    urls: list[str] = []
    for node in root.findall("sm:url/sm:loc", namespace):
        if node.text and node.text.strip():
            urls.append(node.text.strip())
    return urls


def excluded(relative: Path) -> bool:
    return bool(relative.parts and relative.parts[0] in EXCLUDED_PREFIXES)


def main() -> int:
    errors: list[str] = []
    headers = canonical_headers()
    sitemap = sitemap_urls()
    sitemap_set = set(sitemap)

    duplicates = sorted({url for url in sitemap if sitemap.count(url) > 1})
    for url in duplicates:
        errors.append(f"Duplicate sitemap URL: {url}")

    # Every LOLO sitemap URL should resolve to a repository page and must not be
    # explicitly excluded from indexing.
    for url in sitemap:
        local = path_for_url(url)
        if local is None:
            errors.append(f"Unexpected sitemap host or scheme: {url}")
            continue
        if not local.is_file():
            errors.append(f"Sitemap URL has no repository page: {url} -> {local.relative_to(ROOT)}")
            continue
        if local.suffix.lower() == ".html":
            signals = page_signals(local)
            if signals.noindex:
                errors.append(f"noindex page appears in sitemap: {url}")

    audited = 0
    internal = 0
    external = 0
    noindex = 0

    for path in sorted(ROOT.rglob("*.html")):
        relative = path.relative_to(ROOT)
        if excluded(relative):
            continue

        signals = page_signals(path)
        if signals.noindex:
            noindex += 1
            normal_url = url_for_path(relative)
            if normal_url in sitemap_set:
                errors.append(f"noindex page appears in sitemap: {relative} -> {normal_url}")
            continue

        audited += 1
        canonical = signals.canonical
        source = "HTML"
        if not canonical:
            header_canonical = headers.get(relative.name)
            if header_canonical:
                canonical = header_canonical
                source = "HTTP Link header"

        if not canonical:
            errors.append(f"Indexable page has no canonical signal: {relative}")
            continue

        parsed = urlparse(canonical)
        if parsed.scheme != "https":
            errors.append(f"Canonical is not HTTPS: {relative} -> {canonical}")
            continue

        if parsed.netloc == "leaveonelighton.org":
            internal += 1
            if canonical not in sitemap_set:
                errors.append(
                    f"Internal canonical missing from sitemap: {relative} -> {canonical} ({source})"
                )
            if source == "HTTP Link header":
                expected = url_for_path(relative)
                if canonical != expected:
                    errors.append(
                        f"HTTP canonical does not match page path: {relative} -> {canonical}; expected {expected}"
                    )
        else:
            external += 1

    print(
        "Discoverability inventory: "
        f"{audited} indexable HTML pages audited; "
        f"{internal} LOLO canonicals; {external} external canonicals; "
        f"{noindex} noindex pages skipped; {len(sitemap)} sitemap URLs."
    )

    if errors:
        print("DISCOVERABILITY AUDIT FAILED", file=sys.stderr)
        for error in errors:
            print(f"- {error}", file=sys.stderr)
        return 1

    print("SITEWIDE DISCOVERABILITY OK")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
