<?php
declare(strict_types=1);
require_once __DIR__.'/hr-translation-provider.php';
require_once __DIR__.'/text-pairs.php';
function data_text_fields162():array {static $fields=null;return $fields??=decode(file_get_contents(__DIR__.'/hr-text-fields.json'));}
function data_record_context162(string $module,string $key):string {
 if($key==='commission_deduction_note36')return 'record_text:payroll_input35:commission_deduction_note36';
 if(in_array($key,['department','job_title','additional_job','address','bank_name','occupation'],true))return $key;
 return match(true){$module==='work_permit'&&$key==='reason'=>'permit_note159',$module==='salary_history'&&$key==='note'=>'salary_note160',$module==='allowance_terms'&&$key==='note'=>'allowance_note160',default=>'record_text:'.$module.':'.$key};
}
function data_customer_department164(array &$out,array $row):void {
 $c=is_array($row['content_json']??null)?$row['content_json']:decode($row['content_json']??'{}');
 // Preserve supplied public translations. This HR fallback never writes the shared content.
 if(trim((string)($c['en']['title']??''))==='')data_source_add160($out,'customer_department164',$c['ar']['title']??null);
}
function data_notice_texts162(array &$out,array $row):void {
 $meta=is_array($row['meta']??null)?$row['meta']:decode($row['notice_meta']??'{}');
 // Paired notices own their translations; never enqueue them in the retired-for-notices catalog path.
 if(isset($meta['text_pair169']))return;
 // Only human-authored internal messages; generated notice/name templates remain deterministic.
 if(empty($meta['sender'])||!in_array($meta['type']??'',['announcement','circular','message','decision'],true))return;
 foreach(['title','body']as $key)data_source_add160($out,'notice_text:'.$key,$row[$key]??null);
}
// A context/source key changes whenever text changes. Business records always retain their source.
function data_source_add160(array &$out,string $context,mixed $source):void {
 if(!is_string($source))return;$source=trim($source);if($source===''||!preg_match('/\p{Arabic}/u',$source))return;
 static $fixed162=null;$fixed162??=decode(file_get_contents(__DIR__.'/ui-en.json'));if(is_string($fixed162[$source]??null)&&trim($fixed162[$source])!=='')return;
 $id=language_key31($context,$source);$out[$id]=['id'=>$id,'context'=>$context,'source'=>$source];
}
function data_body_texts164(array &$out,string $module,array $body,int $depth=0,string $parent=''):void {
 if($depth>16)return;
 foreach($body as $key=>$value){
  $key=(string)$key;
  if(in_array($key,['snapshot','before','after','author','source','_location','_punch39','text_pair169','_text170'],true))continue;
  if(is_array($value)){data_body_texts164($out,$module,$value,$depth+1,$key==='_revisions'?'_revisions':$parent);continue;}
  if(!in_array($key,data_text_fields162(),true))continue;
  if(text_pair_valid170($body['_text170'][$key]??null)&&$body['_text170'][$key]['ar']===$value)continue;
  if($key==='name'&&!in_array($module,['document','company_file','corporate_document','attachment_archive','debt_advance','debt_opening'],true))continue;
  if($key==='text'&&$module!=='video_receipt')continue;
  $field=$parent==='_revisions'&&$key==='details'?'_revision_details':$key;
  if($field==='name'&&in_array($module,['debt_opening','debt_advance'],true)&&is_string($value))$value=trim(preg_replace('/\s*[—–-]?\s*debt_(?:opening|advance)\b/iu','',$value));
  data_source_add160($out,data_record_context162($module,$field),$value);
 }
}
function data_record_texts160(array &$out,array $row,string $module=''):void {
 $module=(string)($row['module']??$row['kind']??$module);if($module==='')return;
 $titleBody170=is_array($row['body']??null)?$row['body']:(isset($row['body_json'])?decode($row['body_json']):[]);
 if(!text_pair_valid170($titleBody170['_text170']['@title']??null)||$titleBody170['_text170']['@title']['ar']!==($row['title']??null))data_source_add160($out,'record_title:'.$module,$row['title']??null);
 $body=is_array($row['body']??null)?$row['body']:(isset($row['body_json'])?decode($row['body_json']):[]);
 if($module==='video_receipt'&&($body['mode']??'')==='automatic')unset($body['text']);
 data_body_texts164($out,$module,$body);
 if(!text_pair_valid170($body['_text170']['review_note']??null)||$body['_text170']['review_note']['ar']!==($row['review_note']??null))data_source_add160($out,data_record_context162($module,'review_note'),$row['review_note']??null);
}
function data_row_stores164(array &$out,array $node):void {
 if(isset($node['calculation_json'])&&is_string($node['calculation_json']))data_body_texts164($out,'payroll',decode($node['calculation_json']));
 if(isset($node['snapshot_json'])&&is_string($node['snapshot_json']))data_body_texts164($out,'bank_payroll',decode($node['snapshot_json']));
 if(isset($node['label'])&&(isset($node['value_json'])||array_key_exists('effective_from',$node)||isset($node['policy_id']))){
  $pv173=is_string($node['value_json']??null)?decode($node['value_json']):[];if(!text_pair_valid170($pv173['_text170']['label']??null)||$pv173['_text170']['label']['ar']!==$node['label'])data_source_add160($out,'policy_label164',$node['label']);
  if(is_string($node['value_json']??null))data_body_texts164($out,'policy',decode($node['value_json']));
 }
}
function data_result_sources160(mixed $result,string $action=''):array {
 $out=[];
 // Read only metadata already returned by the authorized endpoint, never the global settings store.
 if($action==='groups29_list'&&is_array($result))foreach($result['groups']??[]as $group166)if(is_array($group166)){data_source_add160($out,'group_name',$group166['name']??null);data_source_add160($out,'group_description',$group166['description']??null);}
 if(in_array($action,['v28_info','policy_editor_context'],true)&&is_array($result))foreach($result['locations']??[]as $location166)if(is_array($location166))data_source_add160($out,'location',$location166['name']??null);
 $walk=function(mixed $node,string $key='')use(&$walk,&$out,$action):void{
  if(!is_array($node))return;if(array_is_list($node)){foreach($node as $row)$walk($row,$key);return;}
  // Deliberately never recurse into authorization/configuration or client-side translation catalogs.
  if(in_array($key,['companies','company_choices30'],true)){data_source_add160($out,'company_name',$node['name']??null);data_source_add160($out,'company_legal',$node['legal_name']??null);}
  foreach(['department','job_title','additional_job','address','bank_name','occupation','company_name','company_legal']as $name){$profile170=is_array($node['profile']??null)?$node['profile']:(isset($node['profile_json'])?decode($node['profile_json']):[]);$pair170=$node['_text170'][$name]??$profile170['_text170'][$name]??null;if(!text_pair_valid170($pair170)||$pair170['ar']!==($node[$name]??null))data_source_add160($out,$name,$node[$name]??null);}
  foreach(['profile','profile_json']as $name){$profile=$node[$name]??null;if(is_string($profile))$profile=decode($profile);if(is_array($profile))foreach(['additional_job','address','bank_name']as $field){$pair170=$profile['_text170'][$field]??null;if(!text_pair_valid170($pair170)||$pair170['ar']!==($profile[$field]??null))data_source_add160($out,$field,$profile[$field]??null);}}
  if(in_array($action,['customer_departments','customer_cases','customer_case_detail'],true)&&$key==='departments'&&isset($node['content_json']))data_customer_department164($out,$node);
  data_row_stores164($out,$node);
  if(is_array($node['calculation']??null))data_body_texts164($out,'payroll',$node['calculation']);
  foreach(['inputs','input35']as $inputKey164)if(is_array($node[$inputKey164]??null))data_body_texts164($out,'payroll_input35',$node[$inputKey164]);
  if(in_array($node['kind']??'',['debt_opening','debt_advance'],true)&&!isset($node['body_json']))data_body_texts164($out,$node['kind'],['name'=>$node['name95']??null,'reason'=>$node['reason']??null]);
  data_record_texts160($out,$node,$key==='allowance_terms393'?'allowance_terms':'');
  $normalized162=['leave_history'=>'leave_history','sick_history'=>'sick_summary','cash_history'=>'leave_settlement'];if(isset($normalized162[$key]))data_record_texts160($out,['module'=>$normalized162[$key],'body'=>$node]);
  if($key==='leave_requests'&&is_array($node['details']??null))data_record_texts160($out,['module'=>'leave','body'=>$node['details']]);
  if($key==='salary'&&(!text_pair_valid170($node['_text170']['note']??null)||$node['_text170']['note']['ar']!==($node['note']??null)))data_source_add160($out,'salary_note160',$node['note']??null);
  foreach($node as $name=>$value)if(is_array($value)&&!in_array($name,['localization','settings','permissions','profile','employee_locale143','data_context137','before','after','snapshot','author','source','_text170'],true))$walk($value,(string)$name);
 };
 if($action==='notifications'&&is_array($result))foreach(($result['rows195']??$result) as $row)if(is_array($row))data_notice_texts162($out,$row);
 if($action==='customer_case_detail')foreach($result['events']??[]as $event)if(!empty($event['user_id'])&&($event['event_type']??'')!=='received')data_source_add160($out,'customer_internal_note162',$event['note']??null);
 if(in_array($action,['pay37_sheet','pay35_info','pay37_vouchers'],true)&&is_array($result))data_body_texts164($out,'payroll_input35',$result);
 $walk(in_array($action,['companies','company_choices29'],true)?($result['rows195']??$result):$result,in_array($action,['companies','company_choices29'],true)?'companies':'');return $out;
}
function data_translation_ready160(array $row):bool{return in_array($row['status']??'',['approved','translated'],true)&&is_string($row['target']??null)&&trim($row['target'])!=='';}
function data_translation_scope160(mixed $result,string $action=''):array {
 $paired170=text_pairs_dictionary170($result);$sources=data_result_sources160($result,$action);if(!$sources&&!$paired170&&$action!=='notifications')return ['dictionary'=>[],'states'=>[]];
 $saved=setting31('data_translation31',[]);$contexts=data_context_dictionary137($saved);$dictionary=[];$states=[];
 if($action==='notifications'&&is_array($result)){$fragments164=[];foreach(($result['rows195']??$result) as $row)if(is_array($row))foreach(['title','body']as $field)foreach(preg_split('/[—·:]/u',(string)($row[$field]??''))?:[]as $part)$fragments164[trim($part)]=true;foreach($contexts as $context=>$terms)foreach($terms as $source=>$target)if(isset($fragments164[$source]))$sources[language_key31($context,$source)]=['context'=>$context,'source'=>$source];}
 foreach($sources as $id=>$r){$target=$contexts[$r['context']][$r['source']]??null;if(is_string($target)&&$target!=='')$dictionary[$r['context']][$r['source']]=$target;$states[$r['context']][$r['source']]=isset($target)?'translated':($saved[$id]['status']??'pending');}
 foreach($paired170 as $context170=>$values170)foreach($values170 as $ar170=>$en170){$dictionary[$context170][$ar170]=$en170;$states[$context170][$ar170]='translated';}
 return ['dictionary'=>$dictionary,'states'=>$states,'revision162'=>(int)setting31('data_translation_revision31',0)];
}
function data_translation_counts160(array $inventory,array $items,array $catalog):array {
 $counts=['total'=>count($inventory),'translated'=>0,'pending'=>0,'failed'=>0,'running'=>0];
 foreach($inventory as $id=>$r){if(isset($catalog[$r['context']][$r['source']])||data_translation_ready160($items[$id]??[])){$counts['translated']++;continue;}$counts['pending']++;$state=$items[$id]['status']??'pending';if(in_array($state,['failed','running'],true))$counts[$state]++;}return $counts;
}
function data_translation_process160():array {
 if(hr_translation_key160()==='')return ['processed'=>0,'state'=>'unconfigured'];
 if(db()->inTransaction())throw new LogicException('Translation must run outside the business transaction.');
 $inventory=translation_inventory31();$catalog=data_context_dictionary137();$now=utc_now();$token=bin2hex(random_bytes(16));$batch=[];$context=null;$bytes160=0;
 db()->beginTransaction();try{
  query("SELECT value_json FROM ep_settings WITH (UPDLOCK,HOLDLOCK) WHERE setting_key='data_translation31'")->fetch();$items=setting31('data_translation31',[]);
  foreach($inventory as $id=>$r){
   if(isset($catalog[$r['context']][$r['source']])||data_translation_ready160($items[$id]??[]))continue;
   $prior=$items[$id]??[];if(($prior['status']??'')==='running'&&($prior['lease_until']??'')>$now)continue;if(($prior['retry_at']??'')>$now)continue;
   if(mb_strlen($r['source'])>15000){$items[$id]=$r+['status'=>'failed','target'=>'','error'=>'text_too_long','updated_at'=>$now,'retry_at'=>'9999-12-31 00:00:00'];continue;}
   if($context!==null&&$context!==$r['context'])continue;if(count($batch)>=8)break;$cost160=strlen(htmlspecialchars($r['source'],ENT_QUOTES|ENT_XML1,'UTF-8'))+256;if($bytes160+$cost160>110000)continue;$bytes160+=$cost160;$context=$r['context'];$batch[$id]=$r;
   $items[$id]=array_replace($prior,$r,['status'=>'running','target'=>'','claim160'=>$token,'lease_until'=>gmdate('Y-m-d H:i:s',time()+120),'updated_at'=>$now,'attempts'=>(int)($prior['attempts']??0)+1]);
  }
  put_setting31('data_translation31',$items);db()->commit();
 }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}
 if(!$batch){$counts160=data_translation_counts160($inventory,$items,$catalog);return ['processed'=>0,'state'=>$counts160['pending']?'waiting':'idle','counts'=>$counts160];}
 $failed=null;$translated=[];try{if(session_status()===PHP_SESSION_ACTIVE)session_write_close();$translated=hr_translation_batch160(array_map(fn($r)=>$r['source'],$batch),(string)$context);}
 catch(Throwable $e){$failed=$e instanceof RuntimeException?$e->getMessage():'hr_translation_failed';}
 // Re-check the live source after the external request. A deleted/edited value cannot receive an obsolete result.
 if(function_exists('operation_lock'))operation_lock();
 $current=translation_inventory31();$count=0;$updates160=[];db()->beginTransaction();try{
  query("SELECT value_json FROM ep_settings WITH (UPDLOCK,HOLDLOCK) WHERE setting_key='data_translation31'")->fetch();$items=setting31('data_translation31',[]);
  foreach($batch as $id=>$r){$entry=$items[$id]??[];if(($entry['claim160']??'')!==$token||($entry['status']??'')!=='running')continue;
   unset($entry['claim160'],$entry['lease_until']);$entry['updated_at']=utc_now();
   if(!isset($current[$id])){$entry['status']='superseded';$entry['target']='';}
   elseif($failed!==null){$entry['status']='failed';$entry['target']='';$entry['error']=$failed;$entry['retry_at']=gmdate('Y-m-d H:i:s',time()+min(3600,30*(2**min(7,(int)($entry['attempts']??1)))));}
   else{$entry['status']='translated';$entry['origin']='machine';$entry['target']=trim($translated[$id]);unset($entry['error'],$entry['retry_at']);$updates160[$r['context']][$r['source']]=$entry['target'];$count++;}
   $items[$id]=$entry;
  }
  put_setting31('data_translation31',$items);put_setting31('data_translation_revision31',(int)setting31('data_translation_revision31',0)+1);audit('data_translation_batch160','metadata',['processed'=>$count,'failed'=>$failed!==null,'context'=>$context]);db()->commit();
 }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();throw $e;}
 return ['processed'=>$count,'state'=>$failed!==null?'failed':'translated','error'=>$failed,'counts'=>data_translation_counts160($current,$items,data_context_dictionary137($items)),'dictionary160'=>$updates160];
}

// Kept for older clients; never start canceled background work.
function data_translation_tick161():array {return ['processed'=>0,'state'=>'on_save_only'];}
function data_translation_after_response161():void {}
