<?php
declare(strict_types=1);
// Receives only the list route's already-authorized employee scope and predicates.
function dashboard_requests140(array $u,string $module,array $ids,string $recordWhere,array $extra=[]):array {
 $pending=0;$latest=[];
 foreach(array_chunk(array_values(array_unique(array_map('intval',$ids))),500)as $chunk){
  $marks=implode(',',array_fill(0,count($chunk),'?'));$args=[$module,...$chunk,...$extra];
  $where="r.module=? AND r.user_id IN ($marks)$recordWhere";
  $pending+=(int)query("SELECT COUNT(*) FROM ep_records r WHERE $where AND r.status='pending'",$args)->fetchColumn();
  $latest=array_merge($latest,query("SELECT TOP (6) r.*,u.name AS employee_name FROM ep_records r JOIN ep_users u ON u.id=r.user_id WHERE $where ORDER BY r.created_at DESC,r.id DESC",$args)->fetchAll());
 }
 usort($latest,fn($a,$b)=>strcmp((string)$b['created_at'],(string)$a['created_at'])?:((int)$b['id']<=>(int)$a['id']));$latest=array_slice($latest,0,6);
 foreach($latest as &$row){$row['_pending_total140']=$pending;if($u['role']!=='admin'){$body=decode($row['body_json']);unset($body['_sick_days']);$row['body_json']=jencode($body);}}unset($row);
 return $latest;
}
