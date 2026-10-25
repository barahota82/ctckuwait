<?php
declare(strict_types=1);
require __DIR__.'/test_company_incentive114.php';
require __DIR__.'/../hr/app/employee-editor.php';
query('DELETE FROM ep_records');query('DELETE FROM ep_payroll');query('DELETE FROM ep_bank_payroll');
function check134($value){if(!$value)throw new RuntimeException('inline employee transaction assertion');}
function reject134($f){try{$f();}catch(Throwable $e){return;}throw new RuntimeException('expected rejection');}
$expected=allowance_at(employee_required(1),'2026-10-18');
$extra=['allowance'=>['date'=>'2026-10-01','amounts'=>['telephone'=>10,'transport'=>20,'housing'=>30,'other'=>7],'expected393'=>$expected],'incentives'=>[['company_code'=>'B','amount'=>25,'from'=>'2026-10-01']]];
reject134(fn()=>employee_editor_extras134($admin,1,$extra));
// Mimic the outer user_save transaction: every child must leave it open until its owner commits.
db()->beginTransaction();query("UPDATE ep_users SET name='New Name' WHERE id=1");employee_editor_extras134($admin,1,$extra);check134(db()->inTransaction());check134((int)query("SELECT COUNT(*) FROM ep_records WHERE module IN ('allowance_terms','company_incentive114')")->fetchColumn()===2);db()->rollBack();check134(query('SELECT name FROM ep_users WHERE id=1')->fetchColumn()==='A');check134((int)query('SELECT COUNT(*) FROM ep_records')->fetchColumn()===0);
unset($GLOBALS['EP_ALLOWANCE_CACHE'][1]);
// A failure in the final incentive must roll back employee and earlier allowance writes together.
db()->beginTransaction();query("UPDATE ep_users SET name='Must Roll Back' WHERE id=1");$bad=$extra;$bad['incentives'][0]['company_code']='A';try{employee_editor_extras134($admin,1,$bad);throw new LogicException('expected invalid primary company');}catch(RuntimeException $e){check134(db()->inTransaction());db()->rollBack();}
check134(query('SELECT name FROM ep_users WHERE id=1')->fetchColumn()==='A');check134((int)query('SELECT COUNT(*) FROM ep_records')->fetchColumn()===0);
unset($GLOBALS['EP_ALLOWANCE_CACHE'][1]);db()->beginTransaction();employee_editor_extras134($admin,1,$extra);check134(db()->inTransaction());db()->commit();check134((int)query('SELECT COUNT(*) FROM ep_records')->fetchColumn()===2);
// Existing financial protections remain authoritative.
query("INSERT INTO ep_payroll VALUES(10,1,'2026-10','approved','{}')");unset($GLOBALS['EP_ALLOWANCE_CACHE'][1]);$protected=$extra;$protected['allowance']['expected393']=allowance_at(employee_required(1),'2026-10-18');$protected['allowance']['amounts']['telephone']=99;db()->beginTransaction();reject134(fn()=>employee_editor_extras134($admin,1,$protected));db()->rollBack();check134((int)query('SELECT COUNT(*) FROM ep_records')->fetchColumn()===2);query('DELETE FROM ep_payroll');
db()->beginTransaction();reject134(fn()=>employee_editor_extras134(['role'=>'employee'],1,[]));reject134(fn()=>employee_editor_extras134($admin,1,['unexpected'=>1]));reject134(fn()=>employee_editor_extras134($admin,1,['incentives'=>array_fill(0,21,[])]));db()->rollBack();
$api=file_get_contents(__DIR__.'/../hr/api.php');$start=strpos($api,"case 'user_save':");$finish=strpos($api,"case 'settings_save':",$start);$save=substr($api,$start,$finish-$start);check134(strpos($save,'employee_editor_extras134')<strrpos($save,'db()->commit()'));check134(substr_count($save,'db()->commit()')===2); // independent delegated and owner branches, one commit per executed branch
echo "\nPASS inline employee atomic transaction: production child writers, unchanged values omitted by client, shared commit, final-child rollback, approved payroll protection, authority and input guards. SQLite, not live SQL Server.\n";
