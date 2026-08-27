(()=>{if(!document.querySelector('link[data-reading-friendly]')){const link=document.createElement('link');link.rel='stylesheet';link.href='/css/reading-friendly.css';link.dataset.readingFriendly='true';document.head.appendChild(link);}})();

document.addEventListener('DOMContentLoaded', async () => {
  const grid = document.querySelector('[data-org-grid]');
  const filterWrap = document.querySelector('.org-filters');

  const formatDate = (value) => {
    if (!value) return '';
    const date = new Date(`${value}T12:00:00`);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
  };

  const appendTextElement = (parent, tag, text, className) => {
    const element = document.createElement(tag);
    if (className) element.className = className;
    element.textContent = text;
    parent.appendChild(element);
    return element;
  };

  const renderResource = (resource) => {
    if (!grid || !resource?.name || !resource?.url) return;

    const article = document.createElement('article');
    article.className = 'org-card';
    article.dataset.orgCard = '';
    article.dataset.category = resource.category || 'Memory & Neurological Support';
    article.dataset.search = [
      resource.name,
      resource.mission,
      resource.benefits,
      resource.category,
      resource.search_keywords
    ].filter(Boolean).join(' ');

    appendTextElement(article, 'p', resource.category || 'Memory & Neurological Support', 'card-label');
    appendTextElement(article, 'h2', resource.name);
    appendTextElement(article, 'h3', 'What it does');
    appendTextElement(article, 'p', resource.mission || '');
    appendTextElement(article, 'h3', 'Why it aligns');
    appendTextElement(article, 'p', resource.alignment || '');
    appendTextElement(article, 'h3', 'Who may benefit');
    appendTextElement(article, 'p', resource.benefits || '');

    const link = document.createElement('a');
    link.className = 'external-org';
    link.href = resource.url;
    link.target = '_blank';
    link.rel = 'noopener noreferrer';
    link.append(document.createTextNode(`${resource.link_label || 'Visit the official website'} `));
    const arrow = document.createElement('span');
    arrow.setAttribute('aria-hidden', 'true');
    arrow.textContent = '↗';
    link.appendChild(arrow);
    const sr = document.createElement('span');
    sr.className = 'sr-only';
    sr.textContent = ' (opens an independent external website in a new tab)';
    link.appendChild(sr);
    article.appendChild(link);

    const details = document.createElement('details');
    details.className = 'review-details';
    appendTextElement(details, 'summary', 'Stewardship review information');
    const dl = document.createElement('dl');
    [
      ['Last reviewed', resource.reviewed, formatDate(resource.reviewed)],
      ['Next review', resource.next_review, formatDate(resource.next_review)],
      ['Content owner', null, resource.owner || 'Resources Steward']
    ].forEach(([label, dateTime, display]) => {
      const row = document.createElement('div');
      appendTextElement(row, 'dt', label);
      const dd = document.createElement('dd');
      if (dateTime) {
        const time = document.createElement('time');
        time.dateTime = dateTime;
        time.textContent = display;
        dd.appendChild(time);
      } else {
        dd.textContent = display;
      }
      row.appendChild(dd);
      dl.appendChild(row);
    });
    details.appendChild(dl);
    article.appendChild(details);
    grid.appendChild(article);
  };

  try {
    const response = await fetch('/data/health-care-support.json', { cache: 'no-store' });
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    const payload = await response.json();
    const resources = Array.isArray(payload?.resources) ? payload.resources : [];

    if (resources.length) {
      const category = 'Memory & Neurological Support';
      if (filterWrap && !filterWrap.querySelector(`[data-org-filter="${category}"]`)) {
        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.orgFilter = category;
        button.textContent = category;
        const communityButton = filterWrap.querySelector('[data-org-filter="Community Service"]');
        filterWrap.insertBefore(button, communityButton || null);
      }

      if (!document.querySelector(`[data-category-intro="${category}"]`)) {
        const tools = document.querySelector('.org-tools');
        const statusNode = document.querySelector('[data-org-status]');
        if (tools && statusNode) {
          const intro = document.createElement('section');
          intro.className = 'source-note';
          intro.dataset.categoryIntro = category;
          intro.hidden = true;
          intro.setAttribute('aria-label', 'Memory and neurological support introduction');
          appendTextElement(intro, 'p', 'Leave One Light On is not a medical provider. These government and established national resources are included as trustworthy starting points for information, caregiver support, local services, and disease-specific help.');
          tools.insertBefore(intro, statusNode);
        }
      }

      resources.forEach(renderResource);
    }
  } catch (error) {
    console.warn('Trusted health resources could not be loaded.', error);
  }

  const input = document.querySelector('[data-org-search]');
  const cards = [...document.querySelectorAll('[data-org-card]')];
  const status = document.querySelector('[data-org-status]');
  const intros = [...document.querySelectorAll('[data-category-intro]')];
  const filters = [...document.querySelectorAll('[data-org-filter]')];
  let category = '';

  const run = () => {
    const query = (input?.value || '').trim().toLowerCase();
    let visibleCount = 0;

    cards.forEach((card) => {
      const categoryMatches = !category || card.dataset.category === category;
      const searchText = (card.dataset.search || '').toLowerCase();
      const searchMatches = !query || searchText.includes(query);
      const show = categoryMatches && searchMatches;
      card.hidden = !show;
      if (show) visibleCount += 1;
    });

    intros.forEach((intro) => {
      intro.hidden = intro.dataset.categoryIntro !== category;
    });

    if (status) {
      status.textContent = `${visibleCount} ${visibleCount === 1 ? 'organization' : 'organizations'} shown.`;
    }
  };

  input?.addEventListener('input', run);

  filters.forEach((button) => {
    button.addEventListener('click', () => {
      category = button.dataset.orgFilter || '';
      filters.forEach((filter) => {
        const isActive = filter === button;
        filter.classList.toggle('active', isActive);
        filter.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });
      run();
    });
  });

  filters.forEach((filter) => {
    const isAllFilter = (filter.dataset.orgFilter || '') === '';
    filter.classList.toggle('active', isAllFilter);
    filter.setAttribute('aria-pressed', isAllFilter ? 'true' : 'false');
  });

  run();
});
