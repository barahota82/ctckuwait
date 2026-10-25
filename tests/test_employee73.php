<?php
require (is_dir(__DIR__.'/../SITE_FILES')?__DIR__.'/../SITE_FILES':__DIR__.'/..').'/hr/app/employee-filter.php';
function query($sql,$args=[]){return new class {function fetch(){return ['id'=>23,'code'=>'EMP-0023'];}};}
function employee_key($s){return $s['code'];}
$n=0;function eq73($a,$b){global $n;if($a!==$b)throw new Exception('Filter mismatch '.json_encode([$a,$b]));$n++;}
$rows=[['user_id'=>23,'id'=>900],['user_id'=>39,'id'=>901]];
foreach(['list','workforce_list','bank_list','payroll_list','v26_video_list']as $a){eq73(employee_filter_result73($a,$rows,23),[$rows[0]]);eq73(employee_filter_result73($a,$rows,99),[]);eq73(employee_filter_result73($a,$rows,0),$rows);}
eq73(employee_filter_result73('locations',[['id'=>23],['id'=>39]],23),[['id'=>23]]);
foreach(['v26_payments'=>['records','leave_requests'],'allowance_info'=>['terms','fridays'],'v28_info'=>['records','debt_ledger'],'commission_info'=>['rules','months']]as $a=>$keys){$r=['untouched'=>123];foreach($keys as $k)$r[$k]=$rows;$out=employee_filter_result73($a,$r,23);foreach($keys as $k)eq73($out[$k],[$rows[0]]);eq73($out['untouched'],123);}
eq73(employee_filter_result73('v26_historical',[['employee_code'=>'EMP-0023'],['employee_code'=>'EMP-0039']],23),[['employee_code'=>'EMP-0023']]);
$r=employee_filter_result73('v26_info',['records'=>$rows,'inherited'=>[['code'=>'EMP-0023'],['code'=>'EMP-0039']],'total'=>1,'page'=>0],23);eq73($r['records'],[$rows[0]]);eq73($r['inherited'],[['code'=>'EMP-0023']]);eq73($r['total'],1);
eq73(employee_filter_result73('user_choices29',[['id'=>23],['id'=>39]],23),[['id'=>23],['id'=>39]]);
echo "PASS $n read-only intersection, empty scope, response shape and historical employee-code tests\n";
