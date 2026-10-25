<?php
declare(strict_types=1);
// Request-local only. No shared/stale cache and no change to attendance rules.
function attendance_prefetch113(array $people,string $from,string $to):void {
 $from=substr($from,0,7).'-01';$users=[];
 foreach($people as $u)if(($u['role']??'')!=='admin'){$users[(int)$u['id']]=$u;$GLOBALS['SCHEDULE_STAFF38'][(int)$u['id']]=$u;}
 foreach(array_chunk($users,500,true) as $chunk){
  $ids=array_keys($chunk);$marks=implode(',',array_fill(0,count($ids),'?'));$codes=[];$byCode=[];
  foreach($chunk as $id=>$u){$code=employee_key($u);$codes[]=$code;$byCode[$code]=$id;$GLOBALS['ATTENDANCE_INPUT113'][$id.'|'.$from.'|'.$to]=['user'=>$u,'policies'=>[],'leave'=>[],'history'=>[],'events'=>[],'decisions'=>[]];}
  $policies=query("SELECT * FROM ep_policies WHERE (user_id IN ($marks) OR user_id IS NULL) AND effective_from<=? AND (effective_to IS NULL OR effective_to>=?) ORDER BY CASE WHEN user_id IS NOT NULL THEN 1 ELSE 0 END DESC,effective_from DESC,id DESC",[...$ids,$to,$from])->fetchAll();
  foreach($chunk as $id=>$u)$GLOBALS['ATTENDANCE_INPUT113'][$id.'|'.$from.'|'.$to]['policies']=array_values(array_filter($policies,fn($r)=>$r['user_id']===null||(int)$r['user_id']===$id));
  foreach(query("SELECT * FROM ep_records WHERE user_id IN ($marks) AND module IN ('leave','sick','request') AND status='approved' AND date_from<=? ORDER BY id",[...$ids,$to])->fetchAll() as $r){$k=(int)$r['user_id'].'|'.$from.'|'.$to;if($r['module']==='request'){if($r['date_from']>=$from)$GLOBALS['ATTENDANCE_INPUT113'][$k]['decisions'][]=$r;}else $GLOBALS['ATTENDANCE_INPUT113'][$k]['leave'][]=$r;}
  $codeMarks=implode(',',array_fill(0,count($codes),'?'));
  foreach(query("SELECT * FROM ep_history WHERE employee_code IN ($codeMarks) AND (kind IN ('leave_history','sick_summary') OR (kind='attendance' AND period_key>=? AND period_key<=?)) ORDER BY period_key",[...$codes,$from,$to])->fetchAll() as $r){$id=$byCode[$r['employee_code']];$GLOBALS['ATTENDANCE_INPUT113'][$id.'|'.$from.'|'.$to]['history'][$r['kind']][]=$r;}
  foreach(query("SELECT * FROM ep_events WHERE user_id IN ($marks) AND work_date>=? AND work_date<=? ORDER BY received_at,id",[...$ids,$from,$to])->fetchAll() as $r)$GLOBALS['ATTENDANCE_INPUT113'][(int)$r['user_id'].'|'.$from.'|'.$to]['events'][]=$r;
 }
}
