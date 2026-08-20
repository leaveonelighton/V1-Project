#!/usr/bin/env python3
"""Audit repeatable structural accessibility requirements across public HTML."""

from __future__ import annotations

from collections import Counter
from html.parser import HTMLParser
from pathlib import Path
import re
import sys

ROOT = Path(__file__).resolve().parents[1]
EXCLUDED_PREFIXES = {
    ".github",
    "docs",
    "maintenance",
    "prototype",
    "tests",
    "thelightinthewindowbook.com",
}
HEADING_TAGS = {f"h{level}": level for level in range(1, 7)}
FORM_CONTROLS = {"input", "select", "textarea"}
WHITESPACE_RE = re.compile(r"\s+")


def attrs_dict(attrs: list[tuple[str, str | None]]) -> dict[str, str]:
    return {key.lower(): (value or "").strip() for key, value in attrs}


def visible_text(parts: list[str]) -> str:
    return WHITESPACE_RE.sub(" ", " ".join(parts)).strip()


class PageParser(HTMLParser):
    def __init__(self) -> None:
        super().__init__(convert_charrefs=True)
        self.html_lang = ""
        self.title_parts: list[str] = []
        self.in_title = False
        self.main_count = 0
        self.ids: list[str] = []
        self.headings: list[int] = []
        self.h1_count = 0
        self.images: list[dict[str, str]] = []
        self.iframes: list[dict[str, str]] = []
        self.controls: list[tuple[str, dict[str, str], bool]] = []
        self.labels_for: set[str] = set()
        self.label_depth = 0
        self.interactive_stack: list[dict[str, object]] = []
        self.interactives: list[dict[str, object]] = []
        self.skip_links: list[dict[str, str]] = []
        self.aria_idrefs: list[tuple[str, str, str]] = []

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        tag = tag.lower()
        values = attrs_dict(attrs)

        if tag == "html" and not self.html_lang:
            self.html_lang = values.get("lang", "")
        if tag == "title":
            self.in_title = True
        if tag == "main":
            self.main_count += 1
        if tag == "h1":
            self.h1_count += 1
        if tag in HEADING_TAGS:
            self.headings.append(HEADING_TAGS[tag])

        element_id = values.get("id", "")
        if element_id:
            self.ids.append(element_id)

        for attr_name in ("aria-labelledby", "aria-describedby"):
            raw_refs = values.get(attr_name, "")
            if raw_refs:
                self.aria_idrefs.append((tag, attr_name, raw_refs))

        if tag == "label":
            self.label_depth += 1
            label_for = values.get("for", "")
            if label_for:
                self.labels_for.add(label_for)

        if tag == "img":
            self.images.append(values)
            if self.interactive_stack:
                alt = values.get("alt", "")
                if alt:
                    self.interactive_stack[-1]["image_alts"].append(alt)

        if tag == "iframe":
            self.iframes.append(values)

        if tag in FORM_CONTROLS:
            self.controls.append((tag, values, self.label_depth > 0))

        if tag in {"a", "button"}:
            item: dict[str, object] = {
                "tag": tag,
                "attrs": values,
                "text": [],
                "image_alts": [],
            }
            self.interactive_stack.append(item)
            if tag == "a" and "skip-link" in values.get("class", "").split():
                self.skip_links.append(values)

    def handle_startendtag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        self.handle_starttag(tag, attrs)
        self.handle_endtag(tag)

    def handle_data(self, data: str) -> None:
        if self.in_title:
            self.title_parts.append(data)
        if self.interactive_stack and data.strip():
            self.interactive_stack[-1]["text"].append(data)

    def handle_endtag(self, tag: str) -> None:
        tag = tag.lower()
        if tag == "title":
            self.in_title = False
        if tag == "label" and self.label_depth:
            self.label_depth -= 1
        if tag in {"a", "button"} and self.interactive_stack:
            for index in range(len(self.interactive_stack) - 1, -1, -1):
                if self.interactive_stack[index]["tag"] == tag:
                    item = self.interactive_stack.pop(index)
                    self.interactives.append(item)
                    break



def excluded(path: Path) -> bool:
    relative = path.relative_to(ROOT)
    return bool(relative.parts and relative.parts[0] in EXCLUDED_PREFIXES)


def iter_public_html() -> list[Path]:
    return [path for path in sorted(ROOT.rglob("*.html")) if not excluded(path)]


def has_accessible_name(item: dict[str, object]) -> bool:
    attrs = item["attrs"]
    assert isinstance(attrs, dict)
    if attrs.get("aria-label", "") or attrs.get("aria-labelledby", ""):
        return True
    text = item["text"]
    image_alts = item["image_alts"]
    assert isinstance(text, list)
    assert isinstance(image_alts, list)
    return bool(visible_text([*text, *image_alts]))


def control_is_labelled(
    tag: str,
    attrs: dict[str, str],
    wrapped_in_label: bool,
    labels_for: set[str],
) -> bool:
    if tag == "input":
        input_type = attrs.get("type", "text").lower()
        if input_type == "hidden":
            return True
        if input_type in {"button", "submit", "reset"}:
            return bool(attrs.get("value", "") or attrs.get("aria-label", ""))
        if input_type == "image":
            return bool(attrs.get("alt", "") or attrs.get("aria-label", ""))
    if wrapped_in_label:
        return True
    if attrs.get("aria-label", "") or attrs.get("aria-labelledby", ""):
        return True
    element_id = attrs.get("id", "")
    return bool(element_id and element_id in labels_for)


def audit_page(path: Path) -> tuple[list[str], list[str], Counter[str]]:
    relative = path.relative_to(ROOT)
    parser = PageParser()
    parser.feed(path.read_text(encoding="utf-8", errors="replace"))

    errors: list[str] = []
    warnings: list[str] = []
    counts: Counter[str] = Counter()

    if not parser.html_lang:
        errors.append(f"{relative}: <html> is missing a non-empty lang attribute")
    if not visible_text(parser.title_parts):
        errors.append(f"{relative}: document <title> is missing or empty")
    if parser.main_count != 1:
        errors.append(f"{relative}: expected exactly one <main>; found {parser.main_count}")
    if parser.h1_count < 1:
        errors.append(f"{relative}: page has no <h1>")

    duplicate_ids = sorted(item for item, count in Counter(parser.ids).items() if count > 1)
    for duplicate in duplicate_ids:
        errors.append(f"{relative}: duplicate id={duplicate!r}")

    id_set = set(parser.ids)
    for tag, attr_name, raw_refs in parser.aria_idrefs:
        for ref in raw_refs.split():
            if ref not in id_set:
                errors.append(
                    f"{relative}: <{tag}> {attr_name} references missing id {ref!r}"
                )

    for image in parser.images:
        counts["images"] += 1
        if "alt" not in image:
            src = image.get("src", "")
            errors.append(f"{relative}: image {src!r} is missing alt attribute")

    for iframe in parser.iframes:
        counts["iframes"] += 1
        if not iframe.get("title", ""):
            src = iframe.get("src", "")
            errors.append(f"{relative}: iframe {src!r} is missing a non-empty title")

    for tag, attrs, wrapped in parser.controls:
        counts["form_controls"] += 1
        if not control_is_labelled(tag, attrs, wrapped, parser.labels_for):
            identifier = attrs.get("id", "") or attrs.get("name", "") or attrs.get("type", "")
            errors.append(f"{relative}: <{tag}> {identifier!r} has no accessible label")

    for item in parser.interactives:
        tag = item["tag"]
        attrs = item["attrs"]
        assert isinstance(tag, str)
        assert isinstance(attrs, dict)
        if tag == "a" and not attrs.get("href", ""):
            continue
        counts["interactive_elements"] += 1
        if not has_accessible_name(item):
            destination = attrs.get("href", "") if tag == "a" else attrs.get("type", "")
            errors.append(f"{relative}: <{tag}> {destination!r} has no accessible name")

    for skip in parser.skip_links:
        href = skip.get("href", "")
        if not href.startswith("#") or len(href) == 1:
            errors.append(f"{relative}: skip link has invalid fragment target {href!r}")
            continue
        target = href[1:]
        if target not in id_set:
            errors.append(f"{relative}: skip link target #{target} does not exist")

    if not parser.skip_links:
        warnings.append(f"{relative}: no .skip-link found")

    previous = None
    for level in parser.headings:
        if previous is not None and level > previous + 1:
            warnings.append(
                f"{relative}: heading level jumps from h{previous} to h{level}"
            )
        previous = level

    counts["pages"] += 1
    return errors, warnings, counts


def main() -> int:
    errors: list[str] = []
    warnings: list[str] = []
    totals: Counter[str] = Counter()

    for page in iter_public_html():
        page_errors, page_warnings, counts = audit_page(page)
        errors.extend(page_errors)
        warnings.extend(page_warnings)
        totals.update(counts)

    print(
        "Accessibility inventory: "
        f"{totals['pages']} public HTML files; "
        f"{totals['images']} images; "
        f"{totals['form_controls']} form controls; "
        f"{totals['interactive_elements']} links/buttons; "
        f"{totals['iframes']} iframes."
    )

    if warnings:
        print("ACCESSIBILITY WARNINGS")
        for warning in warnings:
            print(f"- {warning}")

    if errors:
        print("ACCESSIBILITY AUDIT FAILED", file=sys.stderr)
        for error in errors:
            print(f"- {error}", file=sys.stderr)
        return 1

    print("STRUCTURAL ACCESSIBILITY BASELINE OK")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
