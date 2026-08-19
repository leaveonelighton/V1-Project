#!/usr/bin/env bash
set -euo pipefail

SITE="$HOME/domains/leaveonelighton.org"
CONFIG="$SITE/private-config/leave-one-light-on-messages.php"
BASE_URL="https://phase1.leaveonelighton.org"

[ -f "$CONFIG" ] || { echo "Missing private config." >&2; exit 1; }

run_case() {
  local method="$1"
  local contact="$2"
  local label="$3"
  local response
  local reference

  response="$(curl -sS \
    -H 'Accept: application/json' \
    -X POST \
    --data-urlencode 'nickname=Phase 1 QA' \
    --data-urlencode 'topic=general' \
    --data-urlencode "message=Phase 1 response-choice smoke test: $label" \
    --data-urlencode "response_method=$method" \
    --data-urlencode "contact=$contact" \
    "$BASE_URL/api/messages/create.php")"

  reference="$(RESPONSE_JSON="$response" php -r '
$j=json_decode((string)getenv("RESPONSE_JSON"),true);
if(!is_array($j)||empty($j["ok"])||empty($j["reference"])){
    fwrite(STDERR,"CREATE FAILED\n");
    exit(1);
}
echo $j["reference"];
')"

  METHOD="$method" CONTACT="$contact" REFERENCE="$reference" CONFIG_PATH="$CONFIG" php -r '
$c=require getenv("CONFIG_PATH");
$p=new PDO(
    "mysql:host=".$c["db_host"].";dbname=".$c["db_name"].";charset=utf8mb4",
    $c["db_user"],
    $c["db_pass"],
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
);
$s=$p->prepare("SELECT response_method,contact_ciphertext FROM conversations WHERE public_reference=? LIMIT 1");
$s->execute([getenv("REFERENCE")]);
$r=$s->fetch();
if(!$r || $r["response_method"] !== getenv("METHOD")){
    fwrite(STDERR,"METHOD VERIFY FAILED\n"); exit(1);
}
$expected=(string)getenv("CONTACT");
$cipher=(string)($r["contact_ciphertext"] ?? "");
if(getenv("METHOD") === "none"){
    if($cipher !== "") { fwrite(STDERR,"NO-REPLY CONTACT SHOULD BE EMPTY\n"); exit(1); }
    exit(0);
}
if($cipher === "" || $cipher === $expected){
    fwrite(STDERR,"CONTACT ENCRYPTION FAILED\n"); exit(1);
}
$_SERVER["DOCUMENT_ROOT"] = dirname(dirname(getenv("CONFIG_PATH"))) . "/public_html/phase1";
require $_SERVER["DOCUMENT_ROOT"] . "/api/messages/_bootstrap.php";
if(lol_decrypt_contact($cipher) !== $expected){
    fwrite(STDERR,"CONTACT DECRYPTION FAILED\n"); exit(1);
}
'

  printf '%-12s %-22s %s\n' "$label" "$reference" "OK"
}

printf '== Phase 1 response-choice smoke test ==\n'
run_case email 'phase1-test@example.invalid' 'Email'
run_case text '+12025550124' 'Text'
run_case phone '+12025550125' 'Phone'
run_case none '' 'No reply'
printf '\nRESPONSE CHOICES OK\n'
printf 'The four public references above are test records and can be removed with maintenance/private-message-cleanup.php.\n'
