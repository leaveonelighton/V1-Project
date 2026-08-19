#!/usr/bin/env bash
set -euo pipefail

BRANCH="phase-1-private-reply-resource-prototype"
SRC="$HOME/phase1-src"
SITE="$HOME/domains/leaveonelighton.org"
DEST="$SITE/public_html/phase1"
CONFIG="$SITE/private-config/leave-one-light-on-messages.php"
BASE_URL="https://phase1.leaveonelighton.org"

fail() {
  printf 'ERROR: %s\n' "$1" >&2
  exit 1
}

printf '== Phase 1 staging update ==\n'

[ -d "$SRC/.git" ] || fail "Missing Git clone at $SRC"
[ -d "$DEST" ] || fail "Missing staging directory at $DEST"
[ -f "$CONFIG" ] || fail "Missing private config at $CONFIG"

printf '\n== Update branch ==\n'
cd "$SRC"
git fetch origin "$BRANCH"
git checkout "$BRANCH"
git pull --ff-only origin "$BRANCH"
HEAD_SHA="$(git rev-parse --short HEAD)"
printf 'Branch: %s\nCommit: %s\n' "$BRANCH" "$HEAD_SHA"

printf '\n== Sync staging files ==\n'
rsync -a \
  --exclude='.git/' \
  --exclude='config/private-messages.local.php' \
  "$SRC/" "$DEST/"

printf '\n== PHP lint ==\n'
PHP_DIRS=(
  "$DEST/api/messages"
  "$DEST/admin/messages"
  "$DEST/maintenance"
)
find "${PHP_DIRS[@]}" -type f -name '*.php' -print0 | xargs -0 -n1 php -l

printf '\n== Database connection ==\n'
CONFIG_PATH="$CONFIG" php -r '
$c = require getenv("CONFIG_PATH");
try {
    new PDO(
        "mysql:host=".$c["db_host"].";dbname=".$c["db_name"].";charset=utf8mb4",
        $c["db_user"],
        $c["db_pass"],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "DATABASE OK\n";
} catch (Throwable $e) {
    fwrite(STDERR, "DATABASE FAILED\n");
    exit(1);
}
'

printf '\n== HTTP health checks ==\n'
check_http() {
  local label="$1"
  local expected="$2"
  local url="$3"
  local code
  code="$(curl -sS -o /dev/null -w '%{http_code}' "$url")"
  printf '%-24s %s\n' "$label" "$code"
  [ "$code" = "$expected" ] || fail "$label returned $code; expected $expected"
}

check_http "Contact page" "200" "$BASE_URL/contact.html"
check_http "Message form" "200" "$BASE_URL/communicate/"
check_http "Admin protection" "401" "$BASE_URL/admin/messages/"

printf '\nPHASE 1 STAGING OK\n'
printf 'Commit: %s\n' "$HEAD_SHA"
printf 'URL: %s\n' "$BASE_URL"
