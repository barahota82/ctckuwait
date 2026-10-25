<?php
function finance_people_baseline119(array $ids,string $key,string $date):array {
 $staff=[];foreach(array_chunk($ids,500)as $chunk){$marks=implode(',',array_fill(0,count($chunk),'?'));$staff=array_merge($staff,query("SELECT * FROM ep_users WHERE id IN ($marks) AND role<>'admin'",$chunk)->fetchAll());}if(function_exists('service_prefetch'))service_prefetch($staff);
 $out=[];foreach($staff as $s){$id=(int)$s['id'];$state=service_state($s,$date);if(!$state['employed'])continue;
 $rule=commission_rule($id,'sales_agent',$date);$sales=$rule&&(int)$rule['user_id']===$id;
 if(preg_match('/(?:مسؤول|مسئول)\s*(?:ال)?مبيعات|sales\s*(?:agent|representative)/iu',(string)($state['job_title']??$s['job_title']??'')))$sales=true;
 $rep=($state['staff_type']??$s['staff_type']??'')==='rep';
 if(($key==='contract_sales99'&&!$sales)||($key==='contract_staff99'&&!finance_opted118($s))||(in_array($key,['contract_agents99','receipt_agents99'],true)&&!$rep))continue;
 $out[]=['user_id'=>$id,'code'=>employee_key($s),'name'=>$s['name'],'name_en'=>decode($s['profile_json']??'{}')['name_en']??'','company_code'=>$state['company_code']??''];
 }usort($out,fn($a,$b)=>strnatcasecmp($a['code'],$b['code']));return $out;
}
