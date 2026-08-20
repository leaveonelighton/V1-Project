#!/usr/bin/env python3
from urllib.error import HTTPError
from urllib.request import HTTPRedirectHandler, Request, build_opener

HTTPS = "https://leaveonelighton.org"
HTTP = "http://leaveonelighton.org"
UA = "LeaveOneLightOn-LiveSecurityDiagnostic/1.0"

class NoRedirect(HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None

follow = build_opener()
no_redirect = build_opener(NoRedirect())


def fetch(url, *, redirects=True, headers=None):
    req_headers = {"User-Agent": UA}
    if headers:
        req_headers.update(headers)
    req = Request(url, headers=req_headers)
    opener = follow if redirects else no_redirect
    try:
        with opener.open(req, timeout=20) as r:
            return r.status, r.headers, r.read(100000)
    except HTTPError as e:
        return e.code, e.headers, e.read(100000)


def require(condition, message):
    if not condition:
        raise SystemExit(message)


status, headers, _ = fetch(f"{HTTPS}/")
print("HTTPS root status:", status)
require(status == 200, f"HTTPS root expected 200, got {status}")

csp = headers.get("Content-Security-Policy", "")
print("CSP:", csp)
require(csp, "Content-Security-Policy header is missing")
for directive in [
    "default-src 'self'",
    "base-uri 'self'",
    "object-src 'none'",
    "frame-ancestors 'self'",
    "frame-src 'none'",
    "form-action 'self'",
    "upgrade-insecure-requests",
]:
    require(directive in csp, f"CSP missing {directive!r}")
script_src = next((part.strip() for part in csp.split(';') if part.strip().startswith('script-src ')), '')
require(script_src, "CSP script-src directive missing")
require("'unsafe-inline'" not in script_src, "script-src unexpectedly allows unsafe-inline")
require("'unsafe-eval'" not in script_src, "script-src unexpectedly allows unsafe-eval")
print("CSP enforcement: PASS")

for path in ["/", "/books.html", "/welcome-shelf/community-light-starter-kit.html"]:
    status, h, _ = fetch(f"{HTTP}{path}", redirects=False)
    location = h.get("Location", "")
    print(f"HTTP {path}: status={status} location={location}")
    require(status in (301, 308), f"HTTP {path} expected permanent redirect, got {status}")
    require(location.startswith("https://leaveonelighton.org/"), f"HTTP {path} did not redirect to canonical HTTPS host: {location}")

print("HTTP-to-HTTPS permanent redirect: PASS")
print("LIVE SECURITY DIAGNOSTIC OK")
