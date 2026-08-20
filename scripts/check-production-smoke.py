#!/usr/bin/env python3
"""Smoke-test the live Leave One Light On production site.

This audit complements repository-level QA by checking what Hostinger actually
serves over HTTPS: core pages, crawler files, canonical redirects, Welcome
Shelf HTTP canonical headers, and (after deployment) server hardening headers,
compression, caching, and Content Security Policy behavior.
"""

from __future__ import annotations

from dataclasses import dataclass
from email.message import Message
from urllib.error import HTTPError, URLError
from urllib.request import HTTPRedirectHandler, Request, build_opener
import os
import re
import sys
import time
import xml.etree.ElementTree as ET

BASE = "https://leaveonelighton.org"
TIMEOUT_SECONDS = 15
RETRIES = 3
RETRY_DELAY_SECONDS = 3
USER_AGENT = "LeaveOneLightOn-ProductionSmoke/1.0"
PRINT_HANDLER_HASH = "sha256-MguIPR6qNR8D3B+eAlK+bIRTZe8t3wkOY4B/56Me9FU="


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


def fetch(
    url: str,
    *,
    follow_redirects: bool = True,
    extra_headers: dict[str, str] | None = None,
) -> Result:
    opener = FOLLOW if follow_redirects else NO_REDIRECT
    last_error: Exception | None = None

    for attempt in range(1, RETRIES + 1):
        headers = {
            "User-Agent": USER_AGENT,
            "Accept": "text/html,application/xml,text/xml,text/plain,*/*;q=0.8",
            "Cache-Control": "no-cache",
        }
        if extra_headers:
            headers.update(extra_headers)

        request = Request(url, headers=headers)
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


def max_age(cache_control: str) -> int | None:
    match = re.search(r"(?:^|,)\s*max-age=(\d+)", cache_control, re.IGNORECASE)
    return int(match.group(1)) if match else None


def check_csp(headers: Message, url: str, errors: list[str]) -> None:
    csp = headers.get("Content-Security-Policy", "")
    add_error(errors, bool(csp), f"Missing Content-Security-Policy on {url}")
    if not csp:
        return

    required_fragments = [
        "default-src 'self'",
        "base-uri 'self'",
        "object-src 'none'",
        "frame-ancestors 'self'",
        "frame-src 'none'",
        "form-action 'self'",
        "script-src 'self'",
        f"script-src-attr 'unsafe-hashes' '{PRINT_HANDLER_HASH}'",
        "connect-src 'self'",
        "https://fonts.googleapis.com",
        "https://fonts.gstatic.com",
        "upgrade-insecure-requests",
    ]
    for fragment in required_fragments:
        add_error(errors, fragment in csp, f"CSP missing {fragment!r} on {url}: {csp!r}")

    add_error(
        errors,
        "unsafe-eval" not in csp,
        f"CSP unexpectedly permits unsafe-eval on {url}: {csp!r}",
    )
    script_directive = next(
        (piece.strip() for piece in csp.split(";") if piece.strip().startswith("script-src ")),
        "",
    )
    add_error(
        errors,
        "'unsafe-inline'" not in script_directive,
        f"CSP script-src unexpectedly permits unsafe-inline on {url}: {script_directive!r}",
    )


def check_server_hardening(errors: list[str]) -> None:
    page_url = f"{BASE}/"
    page = fetch(page_url)
    add_error(errors, page.status == 200, f"Expected 200 for {page_url}; got {page.status}")
    if page.status == 200:
        expected_headers = {
            "X-Content-Type-Options": "nosniff",
            "Referrer-Policy": "strict-origin-when-cross-origin",
            "X-Frame-Options": "SAMEORIGIN",
        }
        for name, expected in expected_headers.items():
            actual = page.headers.get(name, "")
            add_error(errors, actual.lower() == expected.lower(), f"Unexpected {name} on {page_url}: {actual!r}")

        permissions = page.headers.get("Permissions-Policy", "").replace(" ", "").lower()
        for directive in ["camera=()", "microphone=()", "geolocation=()"]:
            add_error(errors, directive in permissions, f"Permissions-Policy missing {directive} on {page_url}")

        html_cache = page.headers.get("Cache-Control", "").lower()
        add_error(errors, "no-cache" in html_cache, f"HTML is missing no-cache policy: {html_cache!r}")
        add_error(errors, "max-age=0" in html_cache, f"HTML is missing max-age=0 policy: {html_cache!r}")
        check_csp(page.headers, page_url, errors)

    css_url = f"{BASE}/css/v3.css"
    css = fetch(css_url, extra_headers={"Accept-Encoding": "gzip"})
    add_error(errors, css.status == 200, f"Expected 200 for {css_url}; got {css.status}")
    if css.status == 200:
        encoding = css.headers.get("Content-Encoding", "").lower()
        add_error(errors, encoding == "gzip", f"Expected gzip compression for {css_url}; got {encoding!r}")
        css_cache = css.headers.get("Cache-Control", "")
        css_age = max_age(css_cache)
        add_error(errors, css_age is not None and css_age >= 3600, f"CSS cache lifetime too short: {css_cache!r}")

    image_url = f"{BASE}/images/books/leave-one-light-on.jpg"
    image = fetch(image_url)
    add_error(errors, image.status == 200, f"Expected 200 for {image_url}; got {image.status}")
    if image.status == 200:
        image_cache = image.headers.get("Cache-Control", "")
        image_age = max_age(image_cache)
        add_error(
            errors,
            image_age is not None and image_age >= 604800,
            f"Image cache lifetime too short: {image_cache!r}",
        )

    print("Live server hardening: security headers, CSP, HTML revalidation, gzip CSS, and asset caching checked.")


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

    # Pull-request runs inspect the currently deployed site, which does not yet
    # contain the branch's .htaccess changes. Main-push, scheduled, and manual
    # runs verify the hardening after it is eligible to be live.
    event_name = os.environ.get("GITHUB_EVENT_NAME", "")
    if event_name and event_name != "pull_request":
        check_server_hardening(errors)
    else:
        print("Server-hardening checks skipped before deployment.")

    if errors:
        print("PRODUCTION SMOKE AUDIT FAILED", file=sys.stderr)
        for error in errors:
            print(f"- {error}", file=sys.stderr)
        return 1

    print("PRODUCTION SMOKE OK")
    print("Core pages, crawler files, redirects, canonical headers, and eligible server checks are live.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
