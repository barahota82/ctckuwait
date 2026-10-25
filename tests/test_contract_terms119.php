<?php
require __DIR__.'/test_finance_daily103.php';
require __DIR__.'/../hr/app/contract-terms.php';
query('DELETE FROM ep_records');query('DELETE FROM ep_payroll');query('DELETE FROM ep_bank_payroll');$_SERVER['REQUEST_METHOD']='POST';
function terms_save_test119($uid,$profile,$ignored=''){ $s=employee_required($uid);$old=decode($s['profile_json']);db()->beginTransaction();try{contract_save119($uid,$old,$profile,$ignored);query('UPDATE ep_users SET profile_json=? WHERE id=?',[jencode($profile),$uid]);db()->commit();}catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}}
terms_save_test119(3,['contract_commission118'=>true,'contract_staff_rate119'=>2]);
terms_save_test119(2,['contract_agent119'=>true,'contract_agent_rate119'=>0.5]);
terms_save_test119(1,['contract_supervisor119'=>true,'contract_supervisor_rate119'=>0.25]);
// A first setup permits old-date entry, independent of a manually supplied date.
ok(finance_opted118(employee_required(3),'2026-01-01'));ok(contract_at119(employee_required(3),'2026-01-01')['staff']['rate']==2.0);
save_day('contract_staff99','2026-10-03',[['user_id'=>3,'delivered'=>10,'returns'=>['2026-09'=>2]],['user_id'=>0,'company_code'=>'CMP-0001','delivered'=>100]]);
save_day('contract_agents99','2026-10-03',[['user_id'=>2,'delivered'=>10]]);
save_day('contract_staff99','2026-10-17',[['user_id'=>3,'delivered'=>5,'returns'=>['2026-09'=>1]]]);
ok(pay35_commissions(3,'2026-10')['total']===24.0);ok(pay35_commissions(2,'2026-10')['total']===5.0);ok(pay35_commissions(1,'2026-10')['total']===3.0);
$GLOBALS['TEST_NOW120']='2026-10-19';terms_save_test119(3,['contract_commission118'=>true,'contract_staff_rate119'=>3],'2017-01-01');
ok(contract_at119(employee_required(3),'2026-10-18')['staff']['rate']==2.0);ok(contract_at119(employee_required(3),'2026-10-19')['staff']['rate']==3.0);
save_day('contract_staff99','2026-10-19',[['user_id'=>3,'delivered'=>2]]);ok(pay35_commissions(3,'2026-10')['total']===30.0);ok(pay35_commissions(1,'2026-10')['total']===3.5);
$GLOBALS['TEST_NOW120']='2026-11-01';terms_save_test119(3,['contract_commission118'=>false,'contract_staff_rate119'=>3]);ok(!finance_opted118(employee_required(3),'2026-10-01'));ok(pay35_commissions(3,'2026-10')['total']===30.0);
$count=(int)query("SELECT COUNT(*) FROM ep_records WHERE module='contract_terms119'")->fetchColumn();terms_save_test119(3,['contract_commission118'=>false,'contract_staff_rate119'=>3]);ok((int)query("SELECT COUNT(*) FROM ep_records WHERE module='contract_terms119'")->fetchColumn()===$count);
rejected(fn()=>terms_save_test119(3,['contract_commission118'=>true,'contract_staff_rate119'=>0]));
query("INSERT INTO ep_payroll VALUES(10,1,'2026-10','approved','{}')");terms_save_test119(3,['contract_commission118'=>true,'contract_staff_rate119'=>4]);ok(query('SELECT id FROM ep_payroll WHERE id=10')->fetch()!==false);rejected(fn()=>save_day('contract_staff99','2026-10-18',[['user_id'=>3,'delivered'=>1]]));ok(day('contract_staff99','2026-10-18')['values']===[]);
$record=query("SELECT body_json FROM ep_records WHERE module='contract_terms119' ORDER BY id")->fetch();ok(isset(decode($record['body_json'])['recorded_at']));ok(!empty(decode($record['body_json'])['automatic120']));
echo 'PASS automatic settings: no manual effective date, historical first setup, persistent checkbox, automatic price history, net staff-tab supervisor pool, agent/direct exclusion, unchanged save, amount validation, approved payroll preservation'.PHP_EOL;
query('DELETE FROM ep_payroll');$GLOBALS['TEST_NOW120']='2026-11-02';
terms_save_test119(3,['contract_commission118'=>true,'contract_staff_rate119'=>4,'contract_agent119'=>true,'contract_agent_rate119'=>0.5]);
save_day('contract_staff99','2026-11-02',[['user_id'=>3,'delivered'=>5]]);save_day('contract_agents99','2026-11-02',[['user_id'=>3,'delivered'=>20]]);
ok(pay35_commissions(3,'2026-11')['total']===30.0);ok(pay35_commissions(1,'2026-11')['total']===1.25);
echo 'PASS same employee in both tabs: independent sales and contract commissions; supervisor counts staff-tab five contracts only, excluding twenty agent-tab contracts'.PHP_EOL;
