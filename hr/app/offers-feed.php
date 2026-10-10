<?php
declare(strict_types=1);
/** Five visible cards plus one look-ahead, selected on SQL Server rather than hidden in the browser. */
function offers_batch(int $section,int $afterOrder=-2147483648,int $afterId=0,int $ceiling=0):array {
 if(!$ceiling)$ceiling=(int)query("SELECT COALESCE(MAX(id),0) FROM ep_cms_cards WHERE section_id=? AND card_type='offer'",[$section])->fetchColumn();
 $rows=query("SELECT TOP (6) * FROM ep_cms_cards WHERE section_id=? AND card_type='offer' AND visible=1 AND id<=? AND (sort_order>? OR (sort_order=? AND id>?)) ORDER BY sort_order,id",[$section,$ceiling,$afterOrder,$afterOrder,$afterId])->fetchAll();
 $more=count($rows)>5;$rows=array_slice($rows,0,5);$last=$rows?end($rows):null;
 return ['rows'=>$rows,'more'=>$more,'ceiling'=>$ceiling,'after_order'=>$last?(int)$last['sort_order']:$afterOrder,'after_id'=>$last?(int)$last['id']:$afterId];
}
