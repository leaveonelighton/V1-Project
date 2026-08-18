(()=>{if(!document.querySelector('link[data-reading-friendly]')){const link=document.createElement('link');link.rel='stylesheet';link.href='/css/reading-friendly.css';link.dataset.readingFriendly='true';document.head.appendChild(link);}})();document.addEventListener('DOMContentLoaded', () => {
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
