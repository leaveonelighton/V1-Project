#!/usr/bin/env python3
from pathlib import Path
from html.parser import HTMLParser
import json, re, sys, xml.etree.ElementTree as ET

class P(HTMLParser): pass
ROOT=Path.cwd(); errors=[]
article=ROOT/'research/the-gift-i-cannot-give.html'
if not article.exists(): errors.append('missing article')
else:
    t=article.read_text(encoding='utf-8'); p=P(); p.feed(t); p.close()
    checks={
      'one h1': len(re.findall(r'<h1\b',t,re.I))==1,
      'title': '<title>The Gift I Cannot Give | Leave One Light On</title>' in t,
      'canonical': 'https://leaveonelighton.org/research/the-gift-i-cannot-give.html' in t,
      'closing': t.count('Perhaps today, you can leave one light on for someone.')==1,
      'author': '"name":"Theodore Johnson"' in t,
      'no wrong domain': 'leavealighton.org' not in t,
      'no localhost': 'localhost' not in t,
      'no forced 57 claim': not re.search(r'(organ|kidney|donor|transplant)[^<]{0,80}\b57\b|\b57\b[^<]{0,80}(organ|kidney|donor|transplant)', t, re.I),
      'privacy': all(x.lower() not in t.lower() for x in ['Clint Lankford','2127 Travis','Jennifer Ann Johnson']),
      'no matching form': '<form' not in t.lower(),
    }
    for k,v in checks.items():
        if not v: errors.append(k)
    m=re.search(r'<script type="application/ld\+json">(.*?)</script>',t,re.S)
    if not m: errors.append('missing json-ld')
    else:
        try: json.loads(m.group(1))
        except Exception as e: errors.append(f'bad json-ld: {e}')
for path,needle in [
 ('research.html','research/the-gift-i-cannot-give.html'),
 ('get-involved.html','/research/the-gift-i-cannot-give.html'),
 ('get-involved/give-blood.html','/research/the-gift-i-cannot-give.html'),
 ('sitemap.xml','https://leaveonelighton.org/research/the-gift-i-cannot-give.html')]:
    f=ROOT/path
    if not f.exists() or needle not in f.read_text(encoding='utf-8'): errors.append(f'missing integration: {path}')
try: ET.parse(ROOT/'sitemap.xml')
except Exception as e: errors.append(f'bad sitemap: {e}')
if errors:
    print('FAIL')
    for e in errors: print('-',e)
    sys.exit(1)
print('PASS: article, metadata, privacy, integration, HTML and XML checks')
