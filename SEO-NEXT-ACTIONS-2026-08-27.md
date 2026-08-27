# SEO next actions — August 27, 2026

## Confirmed in repository

- `robots.txt` is valid and permissive (`User-agent: *`) and points to the primary sitemap.
- The homepage already contains a self-referencing canonical for `https://leaveonelighton.org/`.
- The homepage already contains `WebSite`, `Organization`, and `Person` structured data.
- The sitemap already uses `https://leaveonelighton.org/` for the homepage and does not list `/index.html`.

## Highest-priority remaining item

- Enforce one canonical host and one canonical homepage URL at the HTTP layer:
  - `leaveonelight.org/*` -> `https://leaveonelighton.org/*`
  - `www.leaveonelighton.org/*` -> `https://leaveonelighton.org/*` if the www host exists
  - `/index.html` -> `/`

## Validation after deployment

1. Confirm one-hop 301 redirects for the secondary domain and `/index.html`.
2. Confirm no redirect loops.
3. Confirm both HTTP and HTTPS variants end on the primary HTTPS host.
4. Run a sitemap status crawl.
5. Submit/refresh `https://leaveonelighton.org/sitemap.xml` in Google Search Console and Bing Webmaster Tools.
