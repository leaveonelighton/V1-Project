document.addEventListener('DOMContentLoaded',async()=>{
  const root=document.querySelector('[data-living-pulse]');if(!root)return;
  const esc=s=>String(s??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
  const categories=[
    ['Encouragement','Offer one sincere word to someone carrying a hard day.'],['Mentoring','Share time, attention, or experience with someone growing.'],['Blood donation','If eligible, schedule or complete a blood donation.'],['Foster care','Support a child, caregiver, or foster family in a practical way.'],['Community service','Join one useful effort already serving your community.'],['Neighbor helping neighbor','Notice one nearby need and respond with dignity.'],['Story shared','Share an honest story that helps another person feel less alone.'],['Other','Choose one practical act of compassion that fits the moment.']
  ];
  const categoryGrid=root.querySelector('[data-category-grid]');
  if(categoryGrid)categoryGrid.innerHTML=categories.map(([name,desc])=>`<a class="category-card" href="/light-board/share.html?category=${encodeURIComponent(name)}"><strong>${esc(name)}</strong><span>${esc(desc)}</span></a>`).join('');
  try{
    const data=await fetch('/api/living-pulse.json',{cache:'no-store'}).then(r=>{if(!r.ok)throw Error('unavailable');return r.json()});
    [['today','today'],['this_week','week'],['this_month','month']].forEach(([key,attr])=>{const el=root.querySelector(`[data-${attr}]`);if(el)el.textContent=Number(data.counts?.[key]||0).toLocaleString()});
    const updated=root.querySelector('[data-updated]');if(updated&&data.updated_at){updated.dateTime=data.updated_at;updated.textContent=new Date(data.updated_at).toLocaleString()}
    const stories=root.querySelector('[data-pulse-stories]');if(stories&&data.recent?.length)stories.innerHTML=data.recent.map(x=>`<article class="pulse-story"><blockquote>“${esc(x.text)}”</blockquote><footer><span class="integrity-mark ${x.integrity==='verified'?'verified':'reported'}">${x.integrity==='verified'?'Verified':'Self-reported'}</span><span>${esc(x.category)}</span>${x.region?`<span class="story-place">${esc(x.region)}</span>`:''}</footer></article>`).join('');
    const map=root.querySelector('[data-light-map]');if(map&&data.map_points?.length){root.querySelector('[data-map-empty]')?.remove();data.map_points.forEach(p=>{const dot=document.createElement('span');dot.className='map-light';dot.style.left=`${Math.max(3,Math.min(97,Number(p.x)))}%`;dot.style.top=`${Math.max(5,Math.min(95,Number(p.y)))}%`;dot.title=`${esc(p.region)} — ${esc(p.category)}`;dot.setAttribute('aria-label',dot.title);map.append(dot)})}
    const status=root.querySelector('[data-pulse-status]');if(status)status.textContent=`Living Pulse loaded with ${Number(data.counts?.this_month||0)} approved lights shared this month.`;
  }catch{
    const status=root.querySelector('[data-pulse-status]');if(status)status.textContent='The Living Pulse is temporarily unavailable. No participation totals have been estimated.';
  }
});
