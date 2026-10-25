<?php
declare(strict_types=1);
// Shared single/bulk calculation preserves the existing annual sick summary policy.
function sick_summary_values(array $staff,array $cfg,array $records,array $history,string $today):array {
 [$from,$to]=sick_year_bounds($today,$staff,$cfg);$count=0;$full=0;$half=0;$zero=0;
 foreach($records as $r)foreach(decode($r['body_json'])['_sick_days']??[] as $d)if($d['date']>=$from&&$d['date']<=min($to,$today)){
  $count++;if($d['paid']==1)$full++;elseif($d['paid']==0.5)$half++;else $zero++;
 }
 foreach($history as $r)if((string)$r['period_key']===substr($from,0,4)){
  $h=decode($r['body_json']);$count+=(int)$h['full']+(int)$h['half']+(int)$h['unpaid'];$full+=(int)$h['full'];$half+=(int)$h['half'];$zero+=(int)$h['unpaid'];break;
 }
 return ['used'=>$count,'full'=>$full,'half'=>$half,'unpaid'=>$zero,'year_from'=>$from,'year_to'=>$to];
}
function sick_overview_rows(array $actor,array $cfg):array {
 if($actor['role']!=='admin'&&($actor['role']!=='manager'||($_SESSION['access_mode29']??'personal')!=='team'))fail('انتقل إلى عرض الفريق',403);
 permission_require($actor,'sick','view');$ids=scope_ids($actor);if(!$ids)return [];
 $result=[];$today=local_time()->format('Y-m-d');
 foreach(array_chunk($ids,500) as $chunk){
  $marks=implode(',',array_fill(0,count($chunk),'?'));
  $staff=query("SELECT * FROM ep_users WHERE id IN ($marks) AND role<>'admin' ORDER BY id",$chunk)->fetchAll();if(!$staff)continue;
  $activeIds=array_map('intval',array_column($staff,'id'));$activeMarks=implode(',',array_fill(0,count($activeIds),'?'));
  $records=[];foreach(query("SELECT user_id,body_json FROM ep_records WHERE user_id IN ($activeMarks) AND module='sick' AND status='approved'",$activeIds)->fetchAll()as $r)$records[(int)$r['user_id']][]=$r;
  $codes=array_map('employee_key',$staff);$codeMarks=implode(',',array_fill(0,count($codes),'?'));$history=[];
  foreach(query("SELECT employee_code,period_key,body_json FROM ep_history WHERE kind='sick_summary' AND employee_code IN ($codeMarks) ORDER BY period_key",$codes)->fetchAll()as $r)$history[$r['employee_code']][]=$r;
  foreach($staff as $s){$row=['id'=>(int)$s['id'],'name'=>$s['name'],'show_sick'=>(bool)$s['show_sick'],'code'=>employee_key($s)];
   if($actor['role']!=='admin'&&(int)$s['id']===(int)$actor['id']&&!$s['show_sick'])$row['error']='الإدارة لم تفتح عرض عدد المرضيات لحسابك.';
   else try{$summary=sick_summary_values($s,$cfg,$records[(int)$s['id']]??[],$history[employee_key($s)]??[],$today);if($actor['role']!=='admin'&&(int)$s['id']===(int)$actor['id'])$summary=['used'=>$summary['used']];$row+=$summary;}catch(RuntimeException $e){$row['error']=$e->getMessage();}
   $result[]=$row;
  }
 }
 usort($result,fn($a,$b)=>strnatcasecmp($a['code'],$b['code']));return $result;
}
