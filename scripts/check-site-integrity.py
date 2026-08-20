#!/usr/bin/env python3
"""Validate public-site links, assets, and CSP compatibility."""

from __future__ import annotations

from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import unquote, urlparse
import re
import sys

ROOT = Path(__file__).resolve().parents[1]
BASE_HOSTS = {"leaveonelighton.org", "www.leaveonelighton.org"}

# Keep this aligned with the discoverability audit: these trees are repository
# support material rather than Leave One Light On public-site pages.
EXCLUDED_PREFIXES = {
    ".github",
    "docs",
    "maintenance",
    "prototype",
    "tests",
    "thelightinthewindowbook.com",
}

URL_ATTRS = {
    "a": ("href",),
    "audio": ("src",),
    "form": ("action",),
    "iframe": ("src",),
    "img": ("src", "srcset"),
    "link": ("href",),
    "object": ("data",),
    "script": ("src",),
    "source": ("src", "srcset"),
    "video": ("src", "poster"),
}

SKIP_SCHEMES = {"mailto", "tel", "javascript", "data", "blob", "about"}
CSS_URL_RE = re.compile(r"url\(\s*(['\"]?)(.*?)\1\s*\)", re.IGNORECASE)
SAFE_DATA_SCRIPT_TYPES = {"application/ld+json", "application/json"}


class ReferenceParser(HTMLParser):
    def __init__(self, relative_path: str) -> None:
        super().__init__(convert_charrefs=True)
        self.relative_path = relative_path
        self.references: list[tuple[str, str, str]] = []
        self.csp_issues: list[str] = []

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        tag = tag.lower()
        values = {key.lower(): (value or "").strip() for key, value in attrs}

        for key, value in values.items():
            if key.startswith("on") and value:
                self.csp_issues.append(
                    f"inline event handler {key}={value!r} is not CSP-safe"
                )

        if tag == "script":
            src = values.get("src", "")
            script_type = values.get("type", "").split(";", 1)[0].strip().lower()
            if not src and script_type not in SAFE_DATA_SCRIPT_TYPES:
                self.csp_issues.append(
                    "executable inline <script> block is not allowed by the production CSP"
                )

        wanted = URL_ATTRS.get(tag)
        if not wanted:
            return
        for attr in wanted:
            value = values.get(attr, "")
            if not value:
                continue
            if value.lower().startswith("javascript:"):
                self.csp_issues.append(
                    f"javascript: URL in <{tag} {attr}> is not allowed by the production CSP"
                )
            if attr == "srcset":
                for item in value.split(","):
                    candidate = item.strip().split()[0] if item.strip() else ""
                    if candidate:
                        self.references.append((tag, attr, candidate))
            else:
                self.references.append((tag, attr, value))


def excluded(path: Path) -> bool:
    relative = path.relative_to(ROOT)
    return bool(relative.parts and relative.parts[0] in EXCLUDED_PREFIXES)


def iter_public_html() -> list[Path]:
    return [
        path
        for path in sorted(ROOT.rglob("*.html"))
        if not excluded(path)
    ]


def iter_public_css() -> list[Path]:
    return [
        path
        for path in sorted(ROOT.rglob("*.css"))
        if not excluded(path)
    ]


def normalize_target(candidate: Path) -> Path:
    """Resolve directory-style routes to index.html without accepting redirects."""
    if candidate.is_dir():
        return candidate / "index.html"
    return candidate


def local_target(source: Path, raw_url: str) -> tuple[Path | None, str | None]:
    """Map a public-site URL/reference to a repository path.

    Returns (path, warning). External URLs and ignorable schemes return (None, None).
    """
    value = raw_url.strip()
    if not value or value.startswith("#"):
        return None, None
    if "{{" in value or "{%" in value:
        return None, None

    parsed = urlparse(value)
    scheme = parsed.scheme.lower()
    if scheme in SKIP_SCHEMES:
        return None, None

    warning = None
    if scheme in {"http", "https"}:
        host = parsed.netloc.lower().split(":", 1)[0]
        if host not in BASE_HOSTS:
            return None, None
        if scheme != "https":
            warning = f"internal URL is not HTTPS: {value}"
        route = unquote(parsed.path or "/")
        candidate = ROOT / route.lstrip("/")
    elif value.startswith("//"):
        host = parsed.netloc.lower().split(":", 1)[0]
        if host not in BASE_HOSTS:
            return None, None
        route = unquote(parsed.path or "/")
        candidate = ROOT / route.lstrip("/")
    else:
        route = unquote(parsed.path)
        if not route:
            return None, warning
        if route.startswith("/"):
            candidate = ROOT / route.lstrip("/")
        else:
            candidate = source.parent / route

    try:
        candidate = candidate.resolve()
        candidate.relative_to(ROOT.resolve())
    except (OSError, ValueError):
        return None, f"reference escapes repository root: {value}"

    if (parsed.path or value).endswith("/"):
        candidate = candidate / "index.html"
    else:
        candidate = normalize_target(candidate)
    return candidate, warning


def parse_html(path: Path) -> ReferenceParser:
    relative = path.relative_to(ROOT).as_posix()
    parser = ReferenceParser(relative)
    parser.feed(path.read_text(encoding="utf-8", errors="replace"))
    return parser


def css_references(path: Path) -> list[str]:
    text = path.read_text(encoding="utf-8", errors="replace")
    return [match.group(2).strip() for match in CSS_URL_RE.finditer(text) if match.group(2).strip()]


def main() -> int:
    errors: list[str] = []
    html_count = 0
    css_count = 0
    html_refs = 0
    css_refs = 0

    for page in iter_public_html():
        html_count += 1
        relative = page.relative_to(ROOT)
        parser = parse_html(page)

        for issue in parser.csp_issues:
            errors.append(f"{relative}: {issue}")

        for tag, attr, value in parser.references:
            target, warning = local_target(page, value)
            if warning:
                errors.append(f"{relative}: <{tag} {attr}> {warning}")
            if target is None:
                continue
            html_refs += 1
            if not target.is_file():
                try:
                    target_display = target.relative_to(ROOT)
                except ValueError:
                    target_display = target
                errors.append(
                    f"{relative}: broken <{tag} {attr}> reference {value!r} -> {target_display}"
                )

    for stylesheet in iter_public_css():
        css_count += 1
        relative = stylesheet.relative_to(ROOT)
        for value in css_references(stylesheet):
            target, warning = local_target(stylesheet, value)
            if warning:
                errors.append(f"{relative}: CSS {warning}")
            if target is None:
                continue
            css_refs += 1
            if not target.is_file():
                try:
                    target_display = target.relative_to(ROOT)
                except ValueError:
                    target_display = target
                errors.append(
                    f"{relative}: broken CSS url() reference {value!r} -> {target_display}"
                )

    print(
        "Site integrity inventory: "
        f"{html_count} public HTML files; {html_refs} internal HTML references; "
        f"{css_count} CSS files; {css_refs} local CSS asset references; "
        "0 inline executable event handlers allowed."
    )

    if errors:
        print("SITE INTEGRITY AUDIT FAILED", file=sys.stderr)
        for error in errors:
            print(f"- {error}", file=sys.stderr)
        return 1

    print("SITEWIDE LINKS, ASSETS, AND CSP COMPATIBILITY OK")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
