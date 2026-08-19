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

printf '== Resource-system staging update ==\n'

[ -d "$SRC/.git" ] || fail "Missing Git clone at $SRC"
[ -d "$DEST" ] || fail "Missing staging directory at $DEST"

cd "$SRC"
git fetch origin "$BRANCH"
git checkout "$BRANCH"
git pull --ff-only origin "$BRANCH"
HEAD_SHA="$(git rev-parse --short HEAD)"
printf 'Branch: %s\nCommit: %s\n' "$BRANCH" "$HEAD_SHA"

printf '\n== Sync resource prototype only ==\n'
mkdir -p "$DEST/css" "$DEST/welcome-shelf" "$DEST/prototype"
cp css/resource-system.css "$DEST/css/resource-system.css"
cp css/resource-print.css "$DEST/css/resource-print.css"
cp css/resource-planner.css "$DEST/css/resource-planner.css"
cp css/resource-foster-care.css "$DEST/css/resource-foster-care.css"
cp css/story-preservation-workbook.css "$DEST/css/story-preservation-workbook.css"
cp css/community-light-starter-kit.css "$DEST/css/community-light-starter-kit.css"
cp welcome-shelf/index.html "$DEST/welcome-shelf/index.html"
cp welcome-shelf/one-light-at-work.html "$DEST/welcome-shelf/one-light-at-work.html"
cp welcome-shelf/community-light-starter-kit.html "$DEST/welcome-shelf/community-light-starter-kit.html"
cp welcome-shelf/one-meaningful-step.html "$DEST/welcome-shelf/one-meaningful-step.html"
cp welcome-shelf/story-preservation-workbook.html "$DEST/welcome-shelf/story-preservation-workbook.html"
cp welcome-shelf/foster-care-start-here.html "$DEST/welcome-shelf/foster-care-start-here.html"
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
check_200 "One Meaningful Step" "$BASE_URL/welcome-shelf/one-meaningful-step.html"
check_200 "Story Preservation" "$BASE_URL/welcome-shelf/story-preservation-workbook.html"
check_200 "Print QA harness" "$BASE_URL/prototype/pdf-qa.html"

printf '\nRESOURCE STAGING OK\n'
printf 'Commit: %s\n' "$HEAD_SHA"
printf 'Review: %s/welcome-shelf/\n' "$BASE_URL"
