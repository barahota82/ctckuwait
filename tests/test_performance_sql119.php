<?php
require __DIR__.'/test_finance_daily103.php';
require __DIR__.'/fixtures/v119/finance-people-baseline.php';
for($id=10;$id<510;$id++)query("INSERT INTO ep_users VALUES(?,?,'employee','office','مسؤول مبيعات','2017-01-01',NULL,'{}')",[$id,'Test '.$id]);$ids=range(10,509);
$GLOBALS['query_count119']=0;$before=finance_people_baseline119($ids,'contract_sales99','2026-10-01');$qBefore=$GLOBALS['query_count119'];$GLOBALS['query_count119']=0;$after=finance_people103($ids,'contract_sales99','2026-10-01');$qAfter=$GLOBALS['query_count119'];ok($before===$after);ok($qBefore===501&&$qAfter===2);
$GLOBALS['query_count119']=0;finance_people103($ids,'receipt_agents99','2026-10-01');ok($GLOBALS['query_count119']===1);
echo 'PASS performance119 500 employees: sales choices SQL '.$qBefore.' -> '.$qAfter.', identical results; non-sales choices 1 query'.PHP_EOL;

$_SERVER['REQUEST_METHOD']='GET';unset($GLOBALS['EP_EMPLOYEE_REQUEST119']);$GLOBALS['query_count119']=0;for($i=0;$i<100;$i++)employee_required(1);ok($GLOBALS['query_count119']===1);$_SERVER['REQUEST_METHOD']='POST';$GLOBALS['query_count119']=0;for($i=0;$i<3;$i++)employee_required(1);ok($GLOBALS['query_count119']===3);echo 'PASS request-local employee reads 100 -> 1; POST deliberately uncached'.PHP_EOL;
