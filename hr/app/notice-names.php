<?php
declare(strict_types=1);
// Read-only enrichment of already-visible notices, never a broader employee directory.
function notice_names142(array $rows):array {
 $records=[];foreach($rows as $row)if(empty($row['meta']['employee_id'])&&!empty($row['meta']['record_id']))$records[]=(int)$row['meta']['record_id'];$recordUsers=[];
 foreach(array_chunk(array_values(array_unique($records)),500)as $chunk){$marks=implode(',',array_fill(0,count($chunk),'?'));foreach(query("SELECT id,user_id FROM ep_records WHERE id IN ($marks)",$chunk)->fetchAll()as $record)$recordUsers[(int)$record['id']]=(int)$record['user_id'];}
 $ids=[];foreach($rows as $row){$id=(!empty($row['meta']['employee_id'])?(int)$row['meta']['employee_id']:($recordUsers[(int)($row['meta']['record_id']??0)]??0));if($id>0)$ids[]=$id;}$people=[];
 foreach(array_chunk(array_values(array_unique($ids)),500)as $chunk){$marks=implode(',',array_fill(0,count($chunk),'?'));foreach(query("SELECT id,name,profile_json FROM ep_users WHERE id IN ($marks)",$chunk)->fetchAll()as $person)$people[(int)$person['id']]=$person;}
 foreach($rows as &$row){$row['name_bindings142']=[];$id=(!empty($row['meta']['employee_id'])?(int)$row['meta']['employee_id']:($recordUsers[(int)($row['meta']['record_id']??0)]??0));$person=$people[$id]??null;if(!$person)continue;$ar=(string)$person['name'];$en=trim((string)(decode($person['profile_json'])['name_en']??''));$text=(string)$row['title'].' '.(string)$row['body'];if(($ar!==''&&str_contains($text,$ar))||($en!==''&&str_contains($text,$en)))$row['name_bindings142'][]=['id'=>$id,'ar'=>$ar,'en'=>$en];}unset($row);
 return $rows;
}
