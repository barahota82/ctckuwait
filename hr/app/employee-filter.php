<?php
declare(strict_types=1);
// A read-only intersection of already-authorized results. Never used by writes.
function employee_filter_rows73(array $rows,int $id,string $key='user_id'):array {
 return $id>0?array_values(array_filter($rows,fn($r)=>is_array($r)&&(int)($r[$key]??0)===$id)):$rows;
}
function employee_filter_result73(string $action,array $result,int $id):array {
 if($id<=0)return $result;
 if(in_array($action,['list','workforce_list','bank_list','payroll_list','v26_video_list'],true))return employee_filter_rows73($result,$id);
 if($action==='locations')return employee_filter_rows73($result,$id,'id');
 if($action==='v26_historical'){
  $staff=query('SELECT * FROM ep_users WHERE id=?',[$id])->fetch();$code=$staff?employee_key($staff):'';
  return $code?array_values(array_filter($result,fn($r)=>(string)$r['employee_code']===(string)$code)):[];
 }
 $keys=match($action){'v26_info'=>['records'],'v28_info'=>['records','debt_ledger'],'v26_payments'=>['records','leave_requests'],'allowance_info'=>['terms','fridays'],'commission_info'=>['rules','months'],default=>[]};
 if($action==='v26_info'&&isset($result['inherited'])){$staff=query('SELECT * FROM ep_users WHERE id=?',[$id])->fetch();$code=$staff?employee_key($staff):'';$result['inherited']=array_values(array_filter($result['inherited'],fn($r)=>$code!==''&&($r['code']??'')===$code));}
 foreach($keys as $key)if(isset($result[$key])&&is_array($result[$key]))$result[$key]=employee_filter_rows73($result[$key],$id);
 return $result;
}
