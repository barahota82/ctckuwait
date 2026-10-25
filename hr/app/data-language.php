<?php
declare(strict_types=1);
require_once __DIR__.'/nationality.php';
require_once __DIR__.'/data-translation.php';
function language_key31(string $context,string $source):string{return hash('sha256',$context."\n".trim($source));}
/** HR inventory streams forward-only rows; never buffer the entire history in the SQLSRV client. */
function data_translation_rows167(string $sql):Generator {
 $pdo=db();$statement=null;
 try{
  if($pdo->getAttribute(PDO::ATTR_DRIVER_NAME)==='sqlsrv'){$statement=$pdo->prepare($sql,[PDO::ATTR_CURSOR=>PDO::CURSOR_FWDONLY]);$statement->execute();}
  else $statement=query($sql);
  while(($row=$statement->fetch(PDO::FETCH_ASSOC))!==false)yield $row;
 }catch(PDOException $error){
  $info=$error->errorInfo??[];$message=strtolower((string)($info[2]??$error->getMessage()));
  $kind=str_contains($message,'buffer')&&(str_contains($message,'memory')||str_contains($message,'limit'))?'buffer_limit':(in_array((int)($info[1]??0),[207,208],true)?'schema_mismatch':'driver_error');
  preg_match('/\bFROM\s+(ep_[a-z_]+)\b/i',$sql,$table);
  error_log('EP HR translation inventory source='.($table[1]??'inventory').' state='.preg_replace('/[^A-Z0-9]/','',strtoupper((string)($info[0]??$error->getCode()))).' native='.(int)($info[1]??0).' kind='.$kind);
  throw $error;
 }finally{if($statement)$statement->closeCursor();}
}
function translation_inventory31():array{
 $out=[];$add=function(string $context,string $source)use(&$out){data_source_add160($out,$context,$source);};
 foreach(data_translation_rows167("SELECT department,job_title,profile_json FROM ep_users")as $r){$profile=decode($r['profile_json']??'{}');foreach(['department','job_title','additional_job','address','bank_name']as $key){$value=(string)(in_array($key,['department','job_title'],true)?($r[$key]??''):($profile[$key]??''));$pair=$profile['_text170'][$key]??null;if(!text_pair_valid170($pair)||$pair['ar']!==$value)$add($key,$value);}}
 foreach(data_translation_rows167('SELECT module,title,body_json,review_note FROM ep_records')as $r)data_record_texts160($out,$r);
 foreach(data_translation_rows167("SELECT kind,body_json FROM ep_history")as $r)data_record_texts160($out,$r);
 foreach(data_translation_rows167("SELECT n.title,n.body,m.body_json AS notice_meta FROM ep_notifications n JOIN ep_records m ON m.module='notification_meta' AND m.title=CONCAT('notice:',n.id)")as $row)data_notice_texts162($out,$row);
 foreach(data_translation_rows167("SELECT note FROM ep_message_events WHERE user_id IS NOT NULL AND event_type<>'received'")as $row)data_source_add160($out,'customer_internal_note162',$row['note']??null);
 foreach(data_translation_rows167('SELECT content_json FROM ep_customer_departments')as $row)data_customer_department164($out,$row);
 foreach(data_translation_rows167('SELECT calculation_json FROM ep_payroll')as $row)data_row_stores164($out,$row);
 foreach(data_translation_rows167('SELECT snapshot_json FROM ep_bank_payroll')as $row)data_row_stores164($out,$row);
 foreach(data_translation_rows167('SELECT label,value_json FROM ep_policies')as $row)data_row_stores164($out,$row);
 foreach(data_translation_rows167('SELECT name,legal_name,bank_name FROM ep_companies')as $r){$add('company_name',$r['name']);$add('company_legal',$r['legal_name']);$add('bank_name',(string)($r['bank_name']??''));}
 foreach(groups_config29()['groups']??[]as $r){$add('group_name',$r['name']);$add('group_description',$r['description']??'');}
 foreach(settings()['locations']??[]as $r)$add('location',$r['name']??'');
 return $out;
}
function translation_seed31():void{
 $items=setting31('data_translation31',[]);foreach(decode(file_get_contents(__DIR__.'/data-language-seed.json'))as $context=>$terms)foreach($terms as $source=>$target){$id=language_key31($context,$source);$items[$id]??=['id'=>$id,'context'=>$context,'source'=>$source,'target'=>$target,'status'=>'approved','origin'=>'v31_reviewed','updated_at'=>utc_now()];}put_setting31('data_translation31',$items);
}
function data_context_dictionary137(?array $saved160=null):array{
 $out=decode(file_get_contents(__DIR__.'/data-language-seed.json'));
 foreach(($saved160??setting31('data_translation31',[]))as $r)if(data_translation_ready160($r)&&isset($r['context'],$r['source']))$out[$r['context']][$r['source']]=$r['target'];
 return $out;
}
function data_dictionary31(?array $contexts=null):array{
 $global137=array_intersect_key($contexts??data_context_dictionary137(),array_flip(['department','job_title','company_name','company_legal','group_name','group_description','location']));
 $out=[];$conflict=[];foreach($global137 as $terms)foreach($terms as $source=>$target){if(isset($out[$source])&&$out[$source]!==$target)$conflict[$source]=true;else $out[$source]=$target;}
 foreach($conflict as $key=>$_)unset($out[$key]);return $out;
}
function data_language_action31(string $action,array $u,array $b):array{
 admin_only($u);if($action==='translation31_key160')fail('ترجمة Google لا تتطلب مفتاحًا.');$inventory=translation_inventory31();$saved=setting31('data_translation31',[]);$catalog160=data_context_dictionary137();
 if($action==='translation31_list')return ['revision'=>(int)setting31('data_translation_revision31',0),'automatic'=>true,'key_ready'=>hr_translation_key160()!=='','plan'=>hr_translation_config160()['plan'],'budget'=>(int)setting31('data_translation_budget160',100000),'usage'=>setting31('hr_translation_usage160',[]),'items'=>array_values(array_map(fn($r)=>array_replace($r,['target'=>$catalog160[$r['context']][$r['source']]??'','status'=>isset($catalog160[$r['context']][$r['source']])?'approved':'pending'],(data_translation_ready160($saved[$r['id']]??[])?$saved[$r['id']]:(isset($catalog160[$r['context']][$r['source']])?[]:($saved[$r['id']]??[])))), $inventory))];
 if($action==='translation31_config'){
  owner_only31($u);if(!isset($b['budget'])||filter_var($b['budget'],FILTER_VALIDATE_INT)===false||(int)$b['budget']<1||(int)$b['budget']>10000000)fail('حد ترجمة الموظفين غير صالح.');put_setting31('data_translation_budget160',(int)$b['budget']);audit('data_translation_budget','hr-translation',['budget'=>(int)$b['budget']]);return ['ok'=>true];
 }
 if($action==='translation31_approve'){
  if(empty($b['reviewed']))fail('راجع النصوص قبل اعتمادها.');$items=$b['items']??[];if(!is_array($items)||!$items||count($items)>500)fail('اختر الترجمات المراد اعتمادها.');
  db()->beginTransaction();query("SELECT value_json FROM ep_settings WITH (UPDLOCK,HOLDLOCK) WHERE setting_key='data_translation31'")->fetch();$revision=(int)setting31('data_translation_revision31',0);if((int)($b['revision']??-1)!==$revision)fail('تغيرت الترجمات؛ أعد تحميل الشاشة.',409);
  $inventory=translation_inventory31();$saved=setting31('data_translation31',[]);$changes=[];
  foreach($items as $item){$id=(string)($item['id']??'');$r=$inventory[$id]??null;$target=trim((string)($item['target']??''));if(!$r||!hash_equals($r['source'],(string)($item['source']??'')))fail('تغير النص الأصلي؛ أعد مراجعة الترجمة.',409);if($target===''||mb_strlen($target)>20000)fail('الترجمة مطلوبة ولا تتجاوز 20000 حرف.');if(hr_translation_tokens160($r['source'])!==hr_translation_tokens160($target))fail('يجب الحفاظ على الأرقام والحقول المحمية في الترجمة.');$changes[$id]=['before'=>$saved[$id]??null,'after'=>$r+['target'=>$target,'status'=>'approved','origin'=>'administrator','updated_at'=>utc_now(),'approved_by'=>$u['id']]];$saved[$id]=$changes[$id]['after'];}
  put_setting31('data_translation31',$saved);put_setting31('data_translation_revision31',$revision+1);audit('data_translation_approve','metadata',$changes);db()->commit();return ['ok'=>true];
 }
 fail('عملية غير موجودة',404);
}
