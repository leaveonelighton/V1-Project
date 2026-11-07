<?php
declare(strict_types=1);
require dirname(__DIR__) . '/api/light-board/_service.php';
$valid = ['website'=>'', 'started_at'=>(string)(time()-3)];
lb_check_spam($valid);
$cases = [
    'honeypot'=>array_replace($valid,['website'=>'bot-filled']),
    'missing timestamp'=>['website'=>''],
    'zero timestamp'=>array_replace($valid,['started_at'=>'0']),
    'non-numeric timestamp'=>array_replace($valid,['started_at'=>'not-a-time']),
    'array timestamp'=>array_replace($valid,['started_at'=>['1']]),
    'too fast'=>array_replace($valid,['started_at'=>(string)time()]),
    'future timestamp'=>array_replace($valid,['started_at'=>(string)(time()+60)]),
];
foreach ($cases as $label=>$input) {
    try { lb_check_spam($input); } catch (RuntimeException $e) { continue; }
    throw new RuntimeException('FAIL: spam guard accepted '.$label);
}
echo "Eight focused spam checks passed.\n";
