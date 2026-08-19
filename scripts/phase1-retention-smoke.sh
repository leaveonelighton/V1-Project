#!/usr/bin/env bash
set -euo pipefail

SITE="$HOME/domains/leaveonelighton.org"
DEST="$SITE/public_html/phase1"
CONFIG="$SITE/private-config/leave-one-light-on-messages.php"

[ -f "$CONFIG" ] || { echo "Missing private config." >&2; exit 1; }
[ -f "$DEST/maintenance/private-message-retention.php" ] || { echo "Missing retention script." >&2; exit 1; }

printf '== Phase 1 retention smoke test ==\n'

SETUP_JSON="$(CONFIG_PATH="$CONFIG" php -r '
$c=require getenv("CONFIG_PATH");
$p=new PDO(
    "mysql:host=".$c["db_host"].";dbname=".$c["db_name"].";charset=utf8mb4",
    $c["db_user"],
    $c["db_pass"],
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]
);
$inactive=max(1,(int)($c["inactive_close_days"]??180));
$delete=max(1,(int)($c["closed_delete_days"]??90));
$suffix=strtoupper(bin2hex(random_bytes(3)));
$closeRef="QA-CLOSE-".$suffix;
$deleteRef="QA-DELETE-".$suffix;
$insert=$p->prepare("INSERT INTO conversations (secret_hash,public_reference,topic,nickname,response_method,contact_ciphertext,status,created_at,last_activity_at,closed_at) VALUES (?,?,?,?,?,?,?,UTC_TIMESTAMP(),?,?,?)");
$oldActivity=gmdate("Y-m-d H:i:s", time()-(($inactive+1)*86400));
$oldClosed=gmdate("Y-m-d H:i:s", time()-(($delete+1)*86400));
$insert->execute([hash("sha256",random_bytes(32)),$closeRef,"general","Retention QA","private",null,"open",$oldActivity,null]);
$insert->execute([hash("sha256",random_bytes(32)),$deleteRef,"general","Retention QA","private",null,"closed",$oldClosed,$oldClosed]);
echo json_encode(["close_ref"=>$closeRef,"delete_ref"=>$deleteRef]);
')"

CLOSE_REF="$(SETUP_JSON="$SETUP_JSON" php -r '$j=json_decode(getenv("SETUP_JSON"),true); echo $j["close_ref"]??"";')"
DELETE_REF="$(SETUP_JSON="$SETUP_JSON" php -r '$j=json_decode(getenv("SETUP_JSON"),true); echo $j["delete_ref"]??"";')"

[ -n "$CLOSE_REF" ] && [ -n "$DELETE_REF" ] || { echo "SETUP FAILED" >&2; exit 1; }

printf 'Synthetic close case:  %s\n' "$CLOSE_REF"
printf 'Synthetic delete case: %s\n' "$DELETE_REF"

php "$DEST/maintenance/private-message-retention.php"

CLOSE_REF="$CLOSE_REF" DELETE_REF="$DELETE_REF" CONFIG_PATH="$CONFIG" php -r '
$c=require getenv("CONFIG_PATH");
$p=new PDO(
    "mysql:host=".$c["db_host"].";dbname=".$c["db_name"].";charset=utf8mb4",
    $c["db_user"],
    $c["db_pass"],
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
);
$s=$p->prepare("SELECT status,closed_at FROM conversations WHERE public_reference=? LIMIT 1");
$s->execute([getenv("CLOSE_REF")]);
$close=$s->fetch();
if(!$close || $close["status"] !== "closed" || empty($close["closed_at"])){
    fwrite(STDERR,"RETENTION FAILED: inactive conversation was not closed\n"); exit(1);
}
$s->execute([getenv("DELETE_REF")]);
if($s->fetch()){
    fwrite(STDERR,"RETENTION FAILED: expired closed conversation was not deleted\n"); exit(1);
}
$d=$p->prepare("DELETE FROM audit_events WHERE conversation_id IN (SELECT id FROM conversations WHERE public_reference=?)");
$d->execute([getenv("CLOSE_REF")]);
$d=$p->prepare("DELETE FROM conversations WHERE public_reference=?");
$d->execute([getenv("CLOSE_REF")]);
echo "RETENTION CLOSE OK\nRETENTION DELETE OK\nSYNTHETIC TEST DATA CLEANED\n";
'

printf '\nRETENTION SMOKE OK\n'
