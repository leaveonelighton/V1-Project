(() => {
  const pages = [
    ["index.html", "Home"],
    ["story.html", "Our Story"],
    ["house.html", "The House"],
    ["gormans.html", "The Gorman Legacy"],
    ["books.html", "Books"],
    ["walk-with-us.html", "Walk With Us"],
    ["looking-for-hope.html", "Looking for Hope"],
    ["support.html", "Support the Story"],
    ["contact.html", "Contact"]
  ];

  const current = location.pathname.split("/").pop() || "index.html";
  const links = pages.map(([href, label]) => {
    const active = current === href;
    const helpClass = href === "looking-for-hope.html" ? " class=\"help-link\"" : "";
    return `<a${helpClass} href="${href}"${active ? ' aria-current="page"' : ""}>${label}</a>`;
  }).join("");

  const header = document.querySelector("header");
  if (header) {
    header.className = "site-header v2-shared-header";
    header.innerHTML = `
      <a class="brand" href="index.html" aria-label="Leave One Light On home">
        <span class="brand-name">LEAVE ONE LIGHT ON</span>
        <span class="brand-tagline">Inspired by <em>The Light in the Window</em></span>
      </a>
      <nav class="desktop-nav" aria-label="Main navigation">${links}</nav>
      <details class="mobile-nav">
        <summary>Menu</summary>
        <nav aria-label="Mobile navigation">${links}</nav>
      </details>`;
  }

  const footer = document.querySelector("footer");
  if (footer) {
    footer.className = "site-footer v2-shared-footer";
    footer.innerHTML = `
      <div class="footer-grid">
        <div><h2>LEAVE ONE LIGHT ON</h2><p>A movement of hope, hospitality, and everyday compassion.</p></div>
        <div><h3>Explore</h3><a href="story.html">Our Story</a><a href="house.html">The House</a><a href="gormans.html">The Gorman Legacy</a><a href="books.html">Books</a></div>
        <div><h3>Take a Step</h3><a href="walk-with-us.html">Walk With Us</a><a href="looking-for-hope.html">Looking for Hope</a><a href="support.html">Support the Story</a><a href="contact.html">Contact</a></div>
      </div>
      <div class="footer-bottom"><span>© 2026 Leave One Light On</span><span>Steward Well. Leave One Light On.</span></div>`;
  }
})();
