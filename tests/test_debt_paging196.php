<?php
declare(strict_types=1);
require __DIR__.'/../hr/app/workspace-page.php';
$calls=[];$staff=[];foreach(range(1,251)as $i)$staff[]=['id'=>$i,'name'=>'Employee '.$i,'profile_json'=>'{}'];
function query($sql,$args=[]){return new class($sql){function __construct(private string $sql){}function fetchAll(){return str_contains($this->sql,'ep_users')?$GLOBALS['staff']:[];}};}
function employee_key($s){return 'EMP-'.str_pad((string)$s['id'],4,'0',STR_PAD_LEFT);}function decode($s){return json_decode($s,true)??[];}function record_search_normalize32($s){return strtolower(trim($s));}function fail($s){throw new Exception($s);}
function v28_debt_ledger($uid,$records=false){$GLOBALS['calls'][]=$uid;return ['user_id'=>$uid,'code'=>employee_key(['id'=>$uid]),'name'=>'Employee '.$uid,'movements'=>[['kind'=>'debt_opening','amount'=>12.345,'date'=>'2017-01-01'],['kind'=>'repayment','amount'=>1.005,'month'=>'2026-10','reason'=>$uid===251?'needle-only-in-movement':'']],'remaining'=>11.340,'records'=>$records?['complete']:[]];}
$checks=0;function ok196($v,$why){if(!$v)throw new Exception($why);$GLOBALS['checks']++;}
$_SERVER['REQUEST_METHOD']='GET';$_GET=['kind'=>'debt_advance','ledger_only32'=>1,'page195'=>json_encode(['size'=>'50','page'=>1,'filters'=>['family'=>'finance','route'=>'v28_debt']])];
$r=debt_workspace196();ok196(count($calls)===50,'only selected 50 ledgers computed');ok196($calls[0]===51&&$calls[49]===100,'employee ordering stable');ok196(count($r['people195'])===251&&$r['page195']['total']===251,'picker and count complete');ok196(count($r['debt_ledger'][0]['movements'])===2&&$r['debt_ledger'][0]['remaining']===11.340,'full history and balance preserved');ok196(workspace_page195('v28_info',$r)===$r,'pre-paged debt not sliced again');
$calls=[];$_GET['page195']=json_encode(['size'=>'50','page'=>0,'filters'=>['employee'=>251]]);$r=debt_workspace196();ok196($calls===[251]&&$r['page195']['total']===1,'employee narrowing before ledger calculation');
$calls=[];$_GET['page195']=json_encode(['size'=>'50','page'=>0,'filters'=>['query'=>'needle-only-in-movement']]);$r=debt_workspace196();ok196(count($calls)===251&&count($r['debt_ledger'])===1&&$r['debt_ledger'][0]['user_id']===251,'movement text search remains complete');
$calls=[];$_GET['page195']=json_encode(['size'=>'all','page'=>9,'filters'=>[]]);$r=debt_workspace196();ok196(count($r['debt_ledger'])===251&&$r['page195']['page']===0,'all and out-of-range page');
echo 'PASS '.$checks." debt paging checks; default page computes 50 of 251 ledgers with complete history\n";
