#!/usr/bin/env bash
set -euo pipefail

BRANCH="resource-system-overhaul-v1"
SRC="$HOME/phase1-src"
DEST="$HOME/domains/leaveonelighton.org/public_html/phase1"
BASE_URL="https://phase1.leaveonelighton.org"

fail() {
  printf 'ERROR: %s\n' "$1" >&2
  exit 1
}

[ -d "$SRC/.git" ] || fail "Missing Git clone at $SRC"
[ -d "$DEST" ] || fail "Missing staging directory at $DEST"

# Pull first, then restart into the newly pulled copy of this script once.
# This prevents new copy/check lines from being skipped when the updater updates itself.
if [ "${RESOURCE_UPDATER_SYNCED:-0}" != "1" ]; then
  cd "$SRC"
  git fetch origin "$BRANCH"
  git checkout "$BRANCH"
  git pull --ff-only origin "$BRANCH"
  exec env RESOURCE_UPDATER_SYNCED=1 bash "$SRC/scripts/resource-staging-update.sh"
fi

printf '== Resource-system staging update ==\n'
cd "$SRC"
HEAD_SHA="$(git rev-parse --short HEAD)"
printf 'Branch: %s\nCommit: %s\n' "$BRANCH" "$HEAD_SHA"

printf '\n== Sync resource prototype only ==\n'
mkdir -p "$DEST/css" "$DEST/js" "$DEST/welcome-shelf" "$DEST/prototype" "$DEST/research" "$DEST/books"
cp css/resource-system.css "$DEST/css/resource-system.css"
cp css/resource-print.css "$DEST/css/resource-print.css"
cp css/resource-planner.css "$DEST/css/resource-planner.css"
cp css/resource-foster-care.css "$DEST/css/resource-foster-care.css"
cp css/resource-reading-learning.css "$DEST/css/resource-reading-learning.css"
cp css/resource-navigation-notes.css "$DEST/css/resource-navigation-notes.css"
cp css/evidence-guide-polish.css "$DEST/css/evidence-guide-polish.css"
cp css/story-preservation-workbook.css "$DEST/css/story-preservation-workbook.css"
cp css/community-light-starter-kit.css "$DEST/css/community-light-starter-kit.css"
cp js/golden-state.js "$DEST/js/golden-state.js"
cp welcome-shelf/index.html "$DEST/welcome-shelf/index.html"
cp welcome-shelf/one-light-at-work.html "$DEST/welcome-shelf/one-light-at-work.html"
cp welcome-shelf/community-light-starter-kit.html "$DEST/welcome-shelf/community-light-starter-kit.html"
cp welcome-shelf/foster-care-start-here.html "$DEST/welcome-shelf/foster-care-start-here.html"
cp welcome-shelf/reading-learning-questions.html "$DEST/welcome-shelf/reading-learning-questions.html"
cp welcome-shelf/resource-navigation-notes.html "$DEST/welcome-shelf/resource-navigation-notes.html"
cp welcome-shelf/one-meaningful-step.html "$DEST/welcome-shelf/one-meaningful-step.html"
cp welcome-shelf/story-preservation-workbook.html "$DEST/welcome-shelf/story-preservation-workbook.html"
cp welcome-shelf/discussion-guide.pdf "$DEST/welcome-shelf/discussion-guide.pdf"
cp welcome-shelf/book-club-welcome-kit.pdf "$DEST/welcome-shelf/book-club-welcome-kit.pdf"
cp research/foster-care-101.html "$DEST/research/foster-care-101.html"
cp research/dyslexia-reading-difficulties.html "$DEST/research/dyslexia-reading-difficulties.html"
cp books/the-light-in-the-window-reader-materials.html "$DEST/books/the-light-in-the-window-reader-materials.html"
cp prototype/pdf-qa.html "$DEST/prototype/pdf-qa.html"

printf '\n== HTTP checks ==\n'
check_200() {
  local label="$1"
  local url="$2"
  local code
  code="$(curl -sS -o /dev/null -w '%{http_code}' "$url")"
  printf '%-28s %s\n' "$label" "$code"
  [ "$code" = "200" ] || fail "$label returned $code"
}

check_200 "Welcome Shelf" "$BASE_URL/welcome-shelf/"
check_200 "One Light at Work" "$BASE_URL/welcome-shelf/one-light-at-work.html"
check_200 "Community Light Kit" "$BASE_URL/welcome-shelf/community-light-starter-kit.html"
check_200 "Foster Care Start Here" "$BASE_URL/welcome-shelf/foster-care-start-here.html"
check_200 "Reading & Learning" "$BASE_URL/welcome-shelf/reading-learning-questions.html"
check_200 "Resource Navigation Notes" "$BASE_URL/welcome-shelf/resource-navigation-notes.html"
check_200 "Foster Care evidence guide" "$BASE_URL/research/foster-care-101.html"
check_200 "Reading evidence guide" "$BASE_URL/research/dyslexia-reading-difficulties.html"
check_200 "One Meaningful Step" "$BASE_URL/welcome-shelf/one-meaningful-step.html"
check_200 "Story Preservation" "$BASE_URL/welcome-shelf/story-preservation-workbook.html"
check_200 "Book reader materials" "$BASE_URL/books/the-light-in-the-window-reader-materials.html"
check_200 "Discussion Guide PDF" "$BASE_URL/welcome-shelf/discussion-guide.pdf"
check_200 "Book Club Kit PDF" "$BASE_URL/welcome-shelf/book-club-welcome-kit.pdf"
check_200 "Print QA harness" "$BASE_URL/prototype/pdf-qa.html"

printf '\nRESOURCE STAGING OK\n'
printf 'Commit: %s\n' "$HEAD_SHA"
printf 'Review: %s/welcome-shelf/\n' "$BASE_URL"
