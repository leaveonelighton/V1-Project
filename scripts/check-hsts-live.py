#!/usr/bin/env python3
"""Verify staged HSTS behavior on deployed Leave One Light On production."""

from email.message import Message
from urllib.error import HTTPError, URLError
from urllib.request import HTTPRedirectHandler, Request, build_opener
import os
import re
import sys

HTTPS_BASE = "https://leaveonelighton.org"
HTTP_BASE = "http://leaveonelighton.org"
USER_AGENT = "LeaveOneLightOn-HSTSCheck/1.0"
TIMEOUT = 15
EXPECTED_MAX_AGE = 86400


class NoRedirect(HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):  # noqa: ANN001
        return None


FOLLOW = build_opener()
NO_REDIRECT = build_opener(NoRedirect())


def fetch(url: str, *, follow_redirects: bool = True) -> tuple[int, Message]:
    opener = FOLLOW if follow_redirects else NO_REDIRECT
    request = Request(url, headers={"User-Agent": USER_AGENT, "Cache-Control": "no-cache"})
    try:
        with opener.open(request, timeout=TIMEOUT) as response:
            return getattr(response, "status", response.getcode()), response.headers
    except HTTPError as exc:
        return exc.code, exc.headers
    except (URLError, TimeoutError, OSError) as exc:
        raise RuntimeError(f"Unable to fetch {url}: {exc}") from exc


def main() -> int:
    if os.environ.get("GITHUB_EVENT_NAME") == "pull_request":
        print("HSTS live checks skipped before deployment.")
        return 0

    errors: list[str] = []

    status, headers = fetch(f"{HTTPS_BASE}/")
    hsts = headers.get("Strict-Transport-Security", "")
    print(f"HTTPS /: status={status} HSTS={hsts!r}")
    if status != 200:
        errors.append(f"HTTPS root expected 200; got {status}")
    match = re.search(r"(?:^|;)\s*max-age=(\d+)", hsts, re.IGNORECASE)
    if not match or int(match.group(1)) < EXPECTED_MAX_AGE:
        errors.append(f"HTTPS HSTS max-age must be at least {EXPECTED_MAX_AGE}; got {hsts!r}")
    if "includesubdomains" in hsts.lower():
        errors.append("Initial HSTS ramp-up must not include includeSubDomains")
    if "preload" in hsts.lower():
        errors.append("Initial HSTS ramp-up must not include preload")

    for path in ["/", "/books.html", "/welcome-shelf/community-light-starter-kit.html"]:
        status, headers = fetch(f"{HTTP_BASE}{path}", follow_redirects=False)
        location = headers.get("Location", "")
        insecure_hsts = headers.get("Strict-Transport-Security", "")
        print(f"HTTP {path}: status={status} location={location!r} HSTS={insecure_hsts!r}")
        if status not in (301, 308):
            errors.append(f"HTTP {path} expected permanent redirect; got {status}")
        if location != f"{HTTPS_BASE}{path}":
            errors.append(f"HTTP {path} redirected to {location!r}; expected {HTTPS_BASE}{path!s}")
        if insecure_hsts:
            errors.append(f"HTTP {path} must not send HSTS over insecure transport; got {insecure_hsts!r}")

    if errors:
        print("HSTS LIVE CHECK FAILED", file=sys.stderr)
        for error in errors:
            print(f"- {error}", file=sys.stderr)
        return 1

    print("HSTS LIVE CHECK OK")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
