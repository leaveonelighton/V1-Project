"""Release checks for participation paths and privacy boundaries; stdlib only."""
from html.parser import HTMLParser
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]
class Page(HTMLParser):
    def __init__(self, text):
        super().__init__(); self.ids=[]; self.labels=[]; self.controls=[]; self.links=[]; self.h1=0; self.inline_script=False
        self.feed(text)
    def handle_starttag(self, tag, attrs):
        a=dict(attrs)
        if 'id' in a:self.ids.append(a['id'])
        if tag=='label' and 'for' in a:self.labels.append(a['for'])
        if tag in ('input','select','textarea') and a.get('type') not in ('hidden','checkbox') and a.get('name')!='website':self.controls.append(a)
        if tag=='a':self.links.append(a.get('href',''))
        if tag=='h1':self.h1+=1
        if tag=='script' and 'src' not in a:self.inline_script=True

pages=['light-board/index.html','light-board/archive.html','light-board/share.html','light-board/my-story.html','light-board/methodology.html','one-light-each-month.html']
for path in pages:
    text=(ROOT/path).read_text(); page=Page(text)
    assert page.h1==1, (path,'one main heading')
    assert len(page.ids)==len(set(page.ids)),(path,'duplicate IDs')
    assert all(label in page.ids for label in page.labels),(path,'broken label target')
    assert not page.inline_script,(path,'CSP-compatible external scripts')
    assert 'mailto:info.org' not in text,(path,'broken email destination')
    assert '/light-board/share.html' in text,(path,'direct participation link')
    assert all(a.get('id') in page.labels for a in page.controls),(path,'unlabelled form field')
share=(ROOT/'light-board/share.html').read_text()
assert all(f'value="{p}"' in share for p in ['received','witnessed','offered'])
assert not re.search(r'name="(?:email|nickname|location|phone)"',share),'no identifying fields collected'
assert '<form class="light-form" hidden' in share, 'intake must stay hidden until server readiness is confirmed'
assert 'data-intake-status' in share and '/light-board/share-basic.php' in share
assert 'name="website_consent" value="yes" required' in share
assert 'name="reuse_consent" value="yes"' in share and 'name="reuse_consent" value="yes" checked' not in share
status=(ROOT/'light-board/my-story.html').read_text()
assert 'method="post"' in status and 'name="receipt"' in status
service=(ROOT/'api/light-board/_service.php').read_text()
assert "state = 'approved' AND website_consent = 1" in service
assert "'pending'" in service and "'withdrawn'" in service
script=(ROOT/'js/light-stories.js').read_text()
assert 'innerHTML' not in script and 'localStorage' not in script and 'sessionStorage' not in script
assert 'lol_verify_csrf();' in (ROOT/'admin/light-board/index.php').read_text()
assert "require dirname(__DIR__) . '/messages/_admin.php';" in (ROOT/'admin/light-board/index.php').read_text()
assert "'light_board_enabled' => false" in (ROOT/'config/private-messages.example.php').read_text()
print(f'Light-story structure and privacy checks passed for {len(pages)} pages.')
