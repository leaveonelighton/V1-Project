(() => {
  const printButtons = document.querySelectorAll("[data-print-resource]");
  for (const button of printButtons) {
    button.addEventListener("click", () => window.print());
  }
})();
