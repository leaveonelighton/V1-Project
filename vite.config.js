import { defineConfig } from "vite";
import { resolve } from "node:path";

const pages = [
  "index.html",
  "story.html",
  "house.html",
  "gormans.html",
  "books.html",
  "walk-with-us.html",
  "looking-for-hope.html",
  "keep-one-light-on.html",
  "support.html",
  "thank-you.html",
  "contact.html"
];

export default defineConfig({
  server: {
    host: "0.0.0.0",
    allowedHosts: ["terminal.local"]
  },
  build: {
    rollupOptions: {
      input: Object.fromEntries(
        pages.map((page) => [page.replace(".html", ""), resolve(__dirname, page)])
      )
    }
  }
});
