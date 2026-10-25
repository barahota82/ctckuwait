<?php
declare(strict_types=1);
require_once __DIR__.'/workspace-loading.php';
// Opt-in paging runs only after the existing authorization/result projections.
// Small pickers remain complete; business rows and totals are paged separately.
function page_request195():?array {
 if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET'||!isset($_GET['page195']))return null;
 $raw=(string)$_GET['page195'];if(strlen($raw)>8192)fail('مرشحات العرض غير صالحة.');
 $p=json_decode($raw,true);if(!is_array($p))fail('مرشحات العرض غير صالحة.');
 $size=(string)($p['size']??'50');if(!in_array($size,['50','100','200','all'],true))fail('عدد العرض غير صالح.');
 $page=filter_var($p['page']??0,FILTER_VALIDATE_INT);if($page===false||$page<0||$page>1000000)fail('رقم الصفحة غير صالح.');
 $f=is_array($p['filters']??null)?$p['filters']:[];
 foreach($f as $v)if(!is_scalar($v)&&$v!==null)fail('مرشحات العرض غير صالحة.');
 return ['size'=>$size,'page'=>$page,'filters'=>$f];
}
function page_meta195(int $total,array $p,int $unfiltered=-1):array {
 $size=$p['size'];$count=$size==='all'?max(1,$total):(int)$size;$pages=max(1,(int)ceil($total/$count));$index=min($p['page'],$pages-1);$start=$index*$count;
 return ['size'=>$size,'page'=>$index,'pages'=>$pages,'total'=>$total,'unfiltered'=>$unfiltered<0?$total:$unfiltered,'start'=>$total?$start+1:0,'end'=>min($start+$count,$total),'offset'=>$start,'limit'=>$count];
}
function page_rows195(array $rows,array $p,int $unfiltered=-1):array {$meta=page_meta195(count($rows),$p,$unfiltered);return ['rows195'=>array_slice(array_values($rows),$meta['offset'],$meta['limit']),'page195'=>$meta];}
function page_text195(mixed $value):string {
 if(is_array($value))return implode(' ',array_map('page_text195',array_values($value)));
 return is_scalar($value)?(string)$value:'';
}
function page_query195(array $r,string $q):bool {return $q===''||str_contains(record_search_normalize32(page_text195($r)),$q);}
function page_validity195(array $r,string $today):string {
 if(!empty($r['date_to'])&&substr($r['date_to'],0,10)<$today)return 'expired';
 if(!empty($r['date_from'])&&substr($r['date_from'],0,10)>$today)return 'future';
 return empty($r['date_to'])?'none':'valid';
}
function page_category195(array $r,array $b):string {
 if(($r['module']??'')==='loan')return 'loan';
 $sets=['document'=>['contract','personal','certificates','letters','other'],'custody'=>['electronics','sim','vehicles','keys','tools','other'],'discipline'=>['notice','warning','penalty','decision','appeal','other'],'evaluation'=>['trial','performance','behavior','improvement','other'],'request'=>['salary','letters','data','assets','complaints','other']];
 $value=$b['record_category49']??'other';return in_array($value,$sets[$r['module']??'']??[],true)?$value:'other';
}
function page_status195(array $r,array $b,string $route):string {
 if(in_array($route,['companies','v28_files','v28_doc_types193'],true)){ $value=$route==='v28_files'?($b['active']??true):($r['active']??true);return in_array($value,[false,0,'0'],true)?'disabled':'enabled';}
 if($route==='v28_documents')return page_validity195($r,local_time()->format('Y-m-d'));
 if($route==='custody')return empty($b['return_date'])?'active':'returned';
 $state=(string)($r['status']??'');
 if($route==='proof')return $state==='active'?'awaiting_response':($state==='closed'?'responded':$state);
 if(!empty($b['_auto182']))return $state;
 $meta=modules()[$r['module']??'']??[];
 return (!$state||$state==='active')&&!empty($meta['approval'])?'pending':$state;
}
function page_matches195(array $r,array $f):bool {
 $b=isset($r['body_json'])?decode($r['body_json']):[];$route=(string)($f['route']??$r['module']??'');$employee=(string)($f['employee']??'');
 if($route!=='report'&&$employee!==''&&$employee!=='0'&&(string)($r['user_id']??$r['id']??'')!==$employee)return false;
 if(!empty($f['company'])&&($b['company_code']??$r['company_code']??'')!==$f['company'])return false;
 if(!empty($f['category'])&&$f['category']!=='all'&&empty($b['_auto182'])&&page_category195($r,$b)!==$f['category'])return false;
 if(!empty($f['status'])&&page_status195($r,$b,$route)!==$f['status'])return false;
 if(($f['family']??'')==='review'&&$route!=='report'){
  if(!empty($f['from'])&&substr((string)($r['date_to']??$r['date_from']??$r['created_at']??''),0,10)<$f['from'])return false;
  if(!empty($f['to'])&&substr((string)($r['date_from']??$r['created_at']??''),0,10)>$f['to'])return false;
 }
 if(!empty($f['expiry'])){
  if($route==='document'){$end=($b['record_category49']??'')==='certificates'?'':($b['expiry']??'');$days=$end?(strtotime($end)-strtotime(local_time()->format('Y-m-d')))/86400:null;$state=$days===null?'unknown':($days<0?'expired':($days<=30?'soon':'valid'));if($state!==$f['expiry'])return false;}
  elseif($route==='custody'&&page_status195($r,$b,$route)!==$f['expiry'])return false;
 }
 $q=record_search_normalize32((string)($f['query']??''));$search=$r;unset($search['body_json']);$search['fields195']=$b;return page_query195($search,$q);
}
function page_totals195(array $rows):array {
 $sum=0.0;$approved=0.0;$paid=0.0;foreach($rows as $r){$n=(float)($r['amount']??0);$sum+=$n;if(($r['status']??'')==='approved')$approved+=$n;if(($r['module']??'')==='leave_cash')$paid+=(float)($r['paid_amount198']??0);elseif(($r['status']??'')==='paid')$paid+=$n;}
 return ['count'=>count($rows),'amount'=>$sum,'approved'=>$approved,'paid'=>$paid];
}
function workspace_page195(string $action,array $result):array {
 $p=page_request195();if(!$p)return $result;
 if(isset($GLOBALS['PAGE195_READY']))return ['rows195'=>$result,'page195'=>$GLOBALS['PAGE195_READY'],'totals195'=>$GLOBALS['PAGE195_TOTALS']??[]];
 $f=$p['filters'];$route=(string)($f['route']??'');$family=(string)($f['family']??'');
 if(isset($result['page195'])&&($action==='permissions182'||($action==='v28_info'&&($_GET['kind']??'')==='debt_advance')))return $result;
 if($action==='finance103_info')return finance_page195($result,$p);
 if($action==='notifications')return notice_page195($result,$p);
 if($action==='dashboard'){$rows=$result['staff']??[];$result['summary195']=['staff'=>count($rows),'working'=>count(array_filter($rows,fn($r)=>($r['state']??'')==='working'))];$x=page_rows195($rows,$p);$result['staff']=$x['rows195'];$result['page195']=$x['page195'];return $result;}
 if($action==='payroll_entry127'){$rows=$result['rows']??[];$result['people195']=$rows;$filtered=array_values(array_filter($rows,fn($r)=>page_matches195($r,$f)));$x=page_rows195($filtered,$p,count($rows));$result['rows']=$x['rows195'];$result['page195']=$x['page195'];return $result;}
 if($action==='v28_info'&&($_GET['kind']??'')==='debt_advance'){
  $rows=$result['debt_ledger']??[];$result['people195']=array_map(fn($r)=>array_intersect_key($r,array_flip(['user_id','code','name','name_en','employee_name_en'])),$rows);
  $filtered=array_values(array_filter($rows,fn($r)=>page_matches195($r,$f)));$x=page_rows195($filtered,$p,count($rows));$result['debt_ledger']=$x['rows195'];$result['page195']=$x['page195'];return $result;
 }
 $allowed=['list','report','leave_overview','sick_overview','locations','companies','v26_info','v28_info','v26_video_list','review193_types'];if(!in_array($action,$allowed,true))fail('ترقيم الصفحات غير متاح لهذه العملية.');
 $object=!array_is_list($result);$rows=$object?($result['records']??[]):$result;$original=$rows;
 if(in_array($route,['v28_files','v28_documents','v28_archive'],true)){$old=[];foreach($rows as $r){$prev=(string)($r['previous_id']??decode($r['body_json'])['previous_id']??'');if($prev&&$prev!=='0')$old[$prev]=true;}$rows=array_values(array_filter($rows,fn($r)=>!isset($old[(string)$r['id']])));}
 if($route==='video_auto'||$route==='video_custom')$rows=array_values(array_filter($rows,fn($r)=>(decode($r['body_json'])['mode']??'')===($route==='video_auto'?'automatic':'custom')));
 $unfiltered=count($rows);$filtered=array_values(array_filter($rows,fn($r)=>page_matches195($r,$f)));
 if(($family==='review'&&!in_array($route,['report','tracking','leave_overview','companies','v28_doc_types193','sick_overview'],true))||$route==='request_admin')usort($filtered,fn($a,$b)=>strcmp((string)($b['created_at']??$b['date_from']??''),(string)($a['created_at']??$a['date_from']??''))?:((int)$b['id']<=>(int)$a['id']));
 $x=page_rows195($filtered,$p,$unfiltered);$x['totals195']=page_totals195($filtered);
 if(!$object)return $x;
 $result['records']=$x['rows195'];$result['page195']=$x['page195'];$result['totals195']=$x['totals195'];
 if(in_array($route,['v28_files','v28_documents','v28_archive'],true)){$byId=[];foreach($original as $r)$byId[(string)$r['id']]=$r;$history=[];foreach($result['records']as $r){$prev=(string)($r['previous_id']??'');while(isset($byId[$prev])&&!isset($history[$prev])){$history[$prev]=$byId[$prev];$prev=(string)($byId[$prev]['previous_id']??'');}}$result['history195']=array_values($history);}
 return $result;
}

function scoped_people195(array $ids):array {
 $rows=[];foreach(array_chunk(array_values(array_unique(array_map('intval',$ids))),500)as $chunk){if(!$chunk)continue;$marks=implode(',',array_fill(0,count($chunk),'?'));$rows=array_merge($rows,query("SELECT * FROM ep_users WHERE id IN ($marks) AND role<>'admin'",$chunk)->fetchAll());}if(($_SERVER['REQUEST_METHOD']??'')==='GET'&&!db()->inTransaction())foreach($rows as $row)$GLOBALS['EP_EMPLOYEE_REQUEST119'][(int)$row['id']]=$row;service_prefetch($rows);usort($rows,fn($a,$b)=>strnatcasecmp(employee_key($a),employee_key($b)));return $rows;
}
function leave_prefetch195(array $people,string $to):void {
 foreach(array_chunk($people,500)as $chunk){
  $ids=array_map('intval',array_column($chunk,'id'));if(!$ids)continue;$marks=implode(',',array_fill(0,count($ids),'?'));$byCode=[];
  foreach($chunk as $s){$uid=(int)$s['id'];$byCode[employee_key($s)]=$uid;$GLOBALS['LEAVE_ROWS195'][$uid]=['leave'=>[],'leave_cash'=>[],'payment'=>[]];$GLOBALS['V26_ENTITLEMENTS'][$uid]=[];$GLOBALS['HISTORY195'][$uid]=['leave_history'=>[],'leave_settlement'=>[],'leave_balance'=>[],'salary_history'=>[]];$GLOBALS['EP_SALARY_CACHE'][employee_key($s)]=[];}
  foreach(query("SELECT * FROM ep_records WHERE user_id IN ($marks) AND module IN ('leave','leave_cash','payment','entitlement') AND (date_from<=? OR (module='leave' AND status IN ('pending','revision'))) ORDER BY id",[...$ids,$to])->fetchAll()as $r){$uid=(int)$r['user_id'];if($r['module']==='entitlement'){if($r['status']==='approved')$GLOBALS['V26_ENTITLEMENTS'][$uid][]=$r;}else $GLOBALS['LEAVE_ROWS195'][$uid][$r['module']][]=$r;}
  foreach($ids as $uid)usort($GLOBALS['V26_ENTITLEMENTS'][$uid],fn($a,$b)=>strcmp($a['date_from'],$b['date_from'])?:((int)$a['id']<=>(int)$b['id']));
  $codes=array_keys($byCode);$codeMarks=implode(',',array_fill(0,count($codes),'?'));
  foreach(query("SELECT * FROM ep_history WHERE employee_code IN ($codeMarks) AND kind IN ('leave_history','leave_settlement','leave_balance','salary_history') ORDER BY period_key,id",$codes)->fetchAll()as $r){$GLOBALS['HISTORY195'][$byCode[$r['employee_code']]][$r['kind']][]=$r;if($r['kind']==='salary_history')$GLOBALS['EP_SALARY_CACHE'][$r['employee_code']][]=$r;}
  foreach($codes as $code)$GLOBALS['EP_SALARY_CACHE'][$code]=array_reverse($GLOBALS['EP_SALARY_CACHE'][$code]);
 }
}
function leave_rows195(int $uid,string $module,string $to,array $statuses,bool $ordered=false):?array {
 if(!isset($GLOBALS['LEAVE_ROWS195'][$uid][$module]))return null;
 $rows=array_values(array_filter($GLOBALS['LEAVE_ROWS195'][$uid][$module],fn($r)=>in_array($r['status'],$statuses,true)&&$r['date_from']<=$to));
 if($ordered)usort($rows,fn($a,$b)=>strcmp($a['date_from'],$b['date_from'])?:((int)$a['id']<=>(int)$b['id']));return $rows;
}
function leave_opening195(int $uid,string $from,string $to):array|false|null {
 if(!isset($GLOBALS['HISTORY195'][$uid]['leave_balance']))return null;
 foreach(array_reverse($GLOBALS['HISTORY195'][$uid]['leave_balance'])as $r)if($r['period_key']>=$from&&$r['period_key']<=$to)return $r;return false;
}

// SQL paging for the normal record-list path. Free-text search retains the
// existing Unicode-aware PHP matcher; its response is still paged afterwards.
function record_sql_page195(string $module,array $actor,array $ids,string $recordWhere,array $recordArgs):?array {
 $p=page_request195();if(!$p||!empty($_GET['requests195'])||!empty($_GET['record_id'])||!empty($_GET['summary140']))return null;
 $f=$p['filters'];if(trim((string)($f['query']??''))!=='')return null;
 $marks=$ids?implode(',',array_fill(0,count($ids),'?')):'NULL';$args=[$module,...$ids,...$recordArgs];
 $base="r.module=? AND r.user_id IN ($marks)$recordWhere";
 if($actor['role']!=='admin'&&in_array($module,['discipline','adjustment'],true)){$base.=" AND (r.user_id<>? OR r.status='approved')";$args[]=(int)$actor['id'];}
 if($module==='evaluation'&&($_GET['source182']??'')==='manager')$base.=" AND COALESCE(JSON_VALUE(r.body_json,'$._auto182'),'false')<>'true'";
 $where=$base;$filteredArgs=$args;$json=fn($name)=>"JSON_VALUE(r.body_json,'$.".$name."')";
 if(!empty($f['employee'])){$where.=' AND r.user_id=?';$filteredArgs[]=(int)$f['employee'];}
 if(!empty($f['category'])&&$f['category']!=='all'){$sets=['document'=>['contract','personal','certificates','letters'],'custody'=>['electronics','sim','vehicles','keys','tools'],'discipline'=>['notice','warning','penalty','decision','appeal'],'evaluation'=>['trial','performance','behavior','improvement'],'request'=>['salary','letters','data','assets','complaints']];$known=implode(',',array_map(fn($v)=>"'".$v."'",$sets[$module]??[]));$category=$known?"CASE WHEN ".$json('record_category49')." IN ($known) THEN ".$json('record_category49')." ELSE 'other' END":"'other'";$where.=" AND ($category)=?";$filteredArgs[]=$f['category'];}
 if(!empty($f['status'])){
  if($module==='custody')$state="CASE WHEN COALESCE(".$json('return_date').",'')='' THEN 'active' ELSE 'returned' END";
  elseif($module==='proof')$state="CASE WHEN r.status='active' THEN 'awaiting_response' WHEN r.status='closed' THEN 'responded' ELSE r.status END";
  elseif(!empty(modules()[$module]['approval']))$state="CASE WHEN r.status IN ('active','') OR r.status IS NULL THEN 'pending' ELSE r.status END";
  else $state='r.status';
  $where.=" AND ($state)=?";$filteredArgs[]=$f['status'];
 }
 if(($f['family']??'')==='review')foreach(['from','to']as $edge)if(!empty($f[$edge])){$where.=$edge==='from'?' AND COALESCE(r.date_to,r.date_from,CONVERT(varchar(10),r.created_at,23))>=?':' AND COALESCE(r.date_from,CONVERT(varchar(10),r.created_at,23))<=?';$filteredArgs[]=$f[$edge];}
 if(!empty($f['expiry'])){
  if($module==='document'){$date="CASE WHEN ".$json('record_category49')."='certificates' THEN NULL ELSE NULLIF(".$json('expiry').",'') END";$today=local_time()->format('Y-m-d');$soon=local_time()->modify('+30 days')->format('Y-m-d');
   if($f['expiry']==='unknown')$where.=" AND ($date) IS NULL";
   elseif($f['expiry']==='expired'){$where.=" AND ($date)<?";$filteredArgs[]=$today;}
   elseif($f['expiry']==='soon'){$where.=" AND ($date)>=? AND ($date)<=?";array_push($filteredArgs,$today,$soon);}
   elseif($f['expiry']==='valid'){$where.=" AND ($date)>?";$filteredArgs[]=$soon;}
  }elseif($module==='custody'){$where.=" AND CASE WHEN COALESCE(".$json('return_date').",'')='' THEN 'active' ELSE 'returned' END=?";$filteredArgs[]=$f['expiry'];}
 }
 $paidJoin198=$module==='leave_cash'?leave_paid_join198():'';
 $joined=' FROM ep_records r JOIN ep_users u ON u.id=r.user_id '.$paidJoin198.' WHERE ';
 $paidExpr198=$module==='leave_cash'?'COALESCE(SUM(cashPaid198.amount),0)':"COALESCE(SUM(CASE WHEN r.status='paid' THEN r.amount ELSE 0 END),0)";
 $stats=query("SELECT COUNT_BIG(*) AS total,COALESCE(SUM(r.amount),0) AS amount,COALESCE(SUM(CASE WHEN r.status='approved' THEN r.amount ELSE 0 END),0) AS approved,{$paidExpr198} AS paid".$joined.$where,$filteredArgs)->fetch();
 $total=(int)($stats['total']??0);$unfiltered=$where===$base?$total:(int)query('SELECT COUNT_BIG(*)'.$joined.$base,$args)->fetchColumn();$meta=page_meta195($total,$p,$unfiltered);
 $order=($f['family']??'')==='review'?'r.created_at DESC,r.id DESC':"COALESCE(JSON_VALUE(u.profile_json,'$.employee_code'),u.username),r.date_from,r.id";
 $limit=$p['size']==='all'?'':" OFFSET ".$meta['offset']." ROWS FETCH NEXT ".$meta['limit']." ROWS ONLY";
 $paidSelect198=$module==='leave_cash'?',COALESCE(cashPaid198.amount,0) AS paid_amount198':'';
 $rows=query("SELECT r.*{$paidSelect198},u.name AS employee_name,COALESCE(JSON_VALUE(u.profile_json,'$.employee_code'),u.username) AS employee_code".$joined.$where.' ORDER BY '.$order.$limit,$filteredArgs)->fetchAll();
 $GLOBALS['PAGE195_READY']=$meta;$GLOBALS['PAGE195_TOTALS']=['count'=>$total,'amount'=>(float)($stats['amount']??0),'approved'=>(float)($stats['approved']??0),'paid'=>(float)($stats['paid']??0)];return $rows;
}

function request_loans195(array $actor):array {
 if(personal_mode34($actor)){if(!personal_has34($actor,'loan'))return [];$ids=[(int)$actor['id']];}
 else{if(!permission_has($actor,'loan'))return [];$ids=permission_scope29($actor,'loan');}
 $ids=employee_scope69($ids,max(0,(int)($_GET['employee69']??0)));$out=[];
 foreach(array_chunk($ids,500)as $chunk){$marks=implode(',',array_fill(0,count($chunk),'?'));$out=array_merge($out,query("SELECT r.*,u.name AS employee_name,COALESCE(JSON_VALUE(u.profile_json,'$.employee_code'),u.username) AS employee_code FROM ep_records r JOIN ep_users u ON u.id=r.user_id WHERE r.module='loan' AND r.user_id IN ($marks)",$chunk)->fetchAll());}
 return $out;
}

function finance_page195(array $result,array $p):array {
 $f=$p['filters'];$key=(string)($_GET['key']??'');$company=in_array($key,['receipt_company99','receipt_click99','receipt_afaq99'],true);$contract=str_starts_with($key,'contract_');$groups=[];
 $identity=fn($r)=>(int)($r['user_id']??0)?(string)$r['user_id']:'company|'.($r['company_code']??'');
 foreach($result['rows']??[]as $r){$id=$identity($r);if(!isset($groups[$id]))$groups[$id]=$r+['moves'=>[]];$groups[$id]['moves'][]=$r;}
 if(empty($f['employee'])&&($company||$key==='contract_staff99'))foreach($result['companies119']??[]as $c){$id='company|'.$c['code'];$groups[$id]??=['user_id'=>0,'company_code'=>$c['code'],'company_name'=>$c['name'],'code'=>'','name'=>$key==='contract_staff99'?'مبيعات مباشرة':(['receipt_company99'=>'تحصيل الشركة','receipt_click99'=>'تحصيل أونلاين كليك','receipt_afaq99'=>'تحصيل أونلاين آفاق'][$key]??''),'moves'=>[]];}
 foreach($result['people118']??[]as $person){$id=$identity($person);$groups[$id]??=$person+['moves'=>[]];}
 $start=(string)($_GET['period_from115']??$_GET['from']??'2017-01-01');$summaries=[];
 foreach($groups as $r){if(!page_matches195($r,$f))continue;$opening=0;$incoming=0;$returned=0;usort($r['moves'],fn($a,$b)=>strcmp($a['date'],$b['date'])?:strnatcasecmp((string)$a['id'],(string)$b['id']));
  foreach($r['moves']as $m){$inc=$contract?(int)$m['delivered']:(int)round((float)$m['amount']*1000);$ret=$contract?array_sum($m['returns']??[]):0;if($m['date']<$start)$opening+=$inc-$ret;else{$incoming+=$inc;$returned+=$ret;}}
  $r['opening']=$opening;$r['incoming']=$incoming;$r['returned']=$returned;$r['closing']=$opening+$incoming-$returned;$summaries[]=$r;
 }
 $x=page_rows195($summaries,$p,count($groups));$result['page195']=$x['page195'];$result['summaries195']=$x['rows195'];
 // Full movements are returned only for the selected summary page; complete
 // small choice lists and entry context remain available to the existing editor.
 $result['rows']=[];
 return $result;
}

function notice_page195(array $rows,array $p):array {
 $f=$p['filters'];$counts=['all'=>0];$types=[];$filtered=[];$query=mb_strtolower(trim((string)($f['query']??'')));
 foreach($rows as $r){$m=$r['meta']??[];$category=$m['category']??'';if(empty($r['read_at'])){$counts['all']++;$counts[$category]=($counts[$category]??0)+1;}
  if(($f['category']??'all')==='all'||$category===$f['category'])$types[$m['type']??'']=true;
  if(($f['category']??'all')!=='all'&&$category!==$f['category'])continue;
  if(($f['type']??'all')!=='all'&&($m['type']??'')!==$f['type'])continue;
  if(($f['status']??'all')==='unread'&&!empty($r['read_at'])||($f['status']??'all')==='read'&&empty($r['read_at']))continue;
  if($query!==''&&!str_contains(mb_strtolower(page_text195([$r['title']??'',$r['body']??'',$r['recipient_name']??'',$r['recipient_name_en']??'',$r['name_bindings142']??[],$m['text_pair169']??[]])),$query))continue;
  $filtered[]=$r;
 }
 $groups=[];foreach($filtered as $r){$m=$r['meta']??[];$key=($m['batch']??'')?:(in_array($m['type']??'',['balance','renewal'],true)?$m['type'].substr($r['created_at'],0,16):'id:'.$r['id']);$groups[$key][]=$r;}
 uasort($groups,fn($a,$b)=>max(array_column($b,'id'))<=>max(array_column($a,'id')));
 $ordered=[];foreach($groups as $group){usort($group,function($a,$b){if(($a['meta']['type']??'')==='balance'&&($b['meta']['type']??'')==='balance')return (float)($b['meta']['balance']??0)<=>(float)($a['meta']['balance']??0)?:strnatcasecmp($a['meta']['employee_code']??'',$b['meta']['employee_code']??'')?:((int)$a['id']<=>(int)$b['id']);if(($a['meta']['type']??'')==='renewal'&&($b['meta']['type']??'')==='renewal')return strcmp($a['meta']['due']??'9999',$b['meta']['due']??'9999')?:((int)$a['id']<=>(int)$b['id']);return (int)$b['id']<=>(int)$a['id'];});array_push($ordered,...$group);}
 if(($_GET['box']??'')==='sent'){$batches=[];foreach($ordered as $r)$batches[($r['meta']['batch']??'')?:'id:'.$r['id']][]=$r;$x=page_rows195(array_values($batches),$p);$selected=[];foreach($x['rows195']as $batch)array_push($selected,...$batch);$x['rows195']=$selected;}
 else $x=page_rows195($ordered,$p);
 return $x+['counts195'=>$counts,'types195'=>array_keys($types)];
}
