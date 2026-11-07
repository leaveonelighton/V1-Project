from pathlib import Path
import re,json
r=Path(__file__).resolve().parents[1]
d=r/'docs/gorman-legacy-review'
a=(d/'page-286.original.html').read_text(); b=(d/'page-286.proposed.html').read_text()
for p in re.findall(r'<p\b[^>]*>.*?</p>',a,re.S):assert p in b,'Existing paragraph lost'
assert 'Making room for first-person stories' in b
assert 'THE OTHER 56' in b and 'first official foster child' in b
assert 'gorman-story.php' not in b,'Do not link closed public intake yet'
x=json.loads((d/'wordpress-review-manifest.json').read_text());assert x['targets'][0]['id']==286
f=(r/'light-board/gorman-story.php').read_text()
for name in ['contributor_name','private_contact','attribution_mode','story','website_consent','website','started_at']:assert f'name="{name}"' in f
assert 'name="website_consent" value="yes">' in f,'Consent must default unchecked'
assert 'lb_gorman_enabled()' in f and 'if (!$ready)' in f
assert "'gorman_stories_enabled' => false" in (r/'config/private-messages.example.php').read_text()
print('Gorman same-page preservation, draft link and closed-intake structure checks passed.')
