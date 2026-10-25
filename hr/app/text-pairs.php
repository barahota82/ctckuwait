<?php
declare(strict_types=1);
require_once __DIR__.'/hr-translation-provider.php';
function text_pair_valid170(mixed $p):bool{return is_array($p)&&is_string($p['ar']??null)&&is_string($p['en']??null)&&is_string($p['context']??null)&&hash_equals(hash('sha256',$p['ar']),$p['revision']??'');}
function text_pairs_build170(array $items,string $language):array {
 if(db()->inTransaction())throw new LogicException('Translate before the business transaction.');
 if(!in_array($language,['ar','en'],true))fail('لغة النص غير صالحة.');$out=[];$batch=[];
 foreach($items as $key=>$v){$value=(string)$v['value'];$old=$v['pair']??null;$lang=!empty($v['edited'])?($v['language']??$language):'ar';if(!in_array($lang,['ar','en'],true))fail('لغة النص غير صالحة.');
  if(text_pair_valid170($old)&&$value===$old[$lang]){$out[$key]=$old;continue;}
  if(trim($value)===''){$out[$key]=['ar'=>$value,'en'=>$value,'context'=>$v['context'],'revision'=>hash('sha256',$value)];continue;}
  $batch[$lang][$key]=$value;
 }
 foreach($batch as $lang=>$texts){try{$translated=hr_translation_batch160($texts,'Employee and work permit free text',$lang==='ar'?'AR':'EN',$lang==='ar'?'EN-GB':'AR',true);}catch(Throwable $e){error_log('HR paired text translation unavailable');fail('تعذرت ترجمة البيانات؛ لم تُحفظ التغييرات. أعد المحاولة.');}
  foreach($texts as $key=>$value){$ar=$lang==='ar'?$value:$translated[$key];$en=$lang==='en'?$value:$translated[$key];$out[$key]=['ar'=>$ar,'en'=>$en,'context'=>$items[$key]['context'],'revision'=>hash('sha256',$ar),'source_language'=>$lang];}
 }return $out;
}
function text_pairs_meta170(mixed $v):?array {if(is_string($v))$v=decode($v);if(!is_array($v))return null;return ['language'=>($v['language']??'')==='en'?'en':'ar','edited'=>is_array($v['edited']??null)?$v['edited']:[],'languages'=>is_array($v['languages']??null)?$v['languages']:[]];}
function text_user_prepare170(array &$b,array $actor):?array {
 $meta=text_pairs_meta170($b['_edit170']??null);if(!$meta)return null;$id=(int)($b['id']??0);
 if($actor['role']!=='admin'){permission_require($actor,'users','edit');if(!$id||!can_manage($actor,$id)||!in_array($id,permission_scope29($actor,'users','edit'),true))fail('الموظف خارج الصلاحيات.',403);}
 $old=$id?query('SELECT * FROM ep_users WHERE id=?',[$id])->fetch():null;if($id&&(!$old||$old['role']==='admin'))fail('الموظف خارج الصلاحيات.',403);
 $profile=$old?decode($old['profile_json']):[];$pairs=$profile['_text170']??[];$items=[];$paths=[];
 $add=function($key,$value,$context,$name,$path)use(&$items,&$paths,$pairs,$meta){$items[$key]=['value'=>$value,'context'=>$context,'pair'=>$pairs[$key]??null,'edited'=>in_array($name,$meta['edited'],true),'language'=>$meta['languages'][$name]??$meta['language']];$paths[$key]=$path;};
 foreach(['department','job_title']as $k)if(array_key_exists($k,$b))$add($k,$b[$k],$k,$k,[$k]);
 if($actor['role']==='admin'){
  foreach(['additional_job','address','bank_name']as $k)if(array_key_exists($k,$b['profile']??[]))$add($k,$b['profile'][$k],$k,$k,['profile',$k]);
  if(array_key_exists('salary_note',$b))$add('salary_note',$b['salary_note'],'salary_note160','salary_note',['salary_note']);
  if(isset($b['editor134']['allowance']))$add('allowance_note',$b['editor134']['allowance']['note']??'','allowance_note160','editor_allowance_note',['editor134','allowance','note']);
  foreach(['title','stage','checklist','details']as $k)if(array_key_exists($k,$b['editor134']['employment']??[]))$add('employment_'.$k,$b['editor134']['employment'][$k],$k==='title'?'record_title:employment':'record_text:employment:'.$k,'service143_'.$k,['editor134','employment',$k]);
  if(($b['editor134']['service']['kind']??'')==='rehire')foreach(['department','job_title']as $k)if(array_key_exists($k,$b['editor134']['service']['data']['values']??[]))$add('rehire_'.$k,$b['editor134']['service']['data']['values'][$k],$k,'service143_'.$k,['editor134','service','data','values',$k]);
 }
 if($old&&isset($items['salary_note'])){$salary170=query("SELECT body_json FROM ep_history WHERE employee_code=? AND kind='salary_history' AND period_key=?",[employee_key($old),(string)($b['salary_effective_from']??(employee_editor_dates130($old)['salary']['from']??''))])->fetch();if($salary170)$items['salary_note']['pair']=decode($salary170['body_json'])['_text170']['note']??null;}
 $built=text_pairs_build170($items,$meta['language']);foreach($built as $key=>$pair){$target=&$b;foreach($paths[$key]as $part)$target=&$target[$part];$target=$pair['ar'];unset($target);}
 foreach(['department','job_title']as $key)if(isset($b[$key])&&mb_strlen($b[$key])>100)fail('الترجمة أطول من الحد المسموح للحقل.');
 if($actor['role']==='admin'){$b['profile']['_text170']=$pairs;foreach($built as $k=>$p)if(in_array($k,['department','job_title','additional_job','address','bank_name'],true))$b['profile']['_text170'][$k]=$p;}
 return ['pairs'=>array_replace($pairs,$built),'base'=>$old?hash('sha256',jencode($old)):null];
}
function text_user_check170(?array $state,array $row):void {if($state&&$state['base']&&!hash_equals($state['base'],hash('sha256',jencode($row))))fail('تغيرت بيانات الموظف؛ أعد تحميله قبل الحفظ.',409);}
function text_pairs_attach170(array $body,array $pairs,string $module,string $title=''):array {
 $stored=$body['_text170']??[];$map=['department'=>'department','job_title'=>'job_title','additional_job'=>'additional_job','address'=>'address','bank_name'=>'bank_name','occupation'=>'occupation'];
 if($module==='salary_history')$map['note']='salary_note160';if($module==='allowance_terms')$map['note']='allowance_note160';if($module==='work_permit')$map['reason']='permit_note159';
 if($module==='employment'){foreach(['stage','checklist','details']as $k)$map[$k]='record_text:employment:'.$k;$map['@title']='record_title:employment';}
 foreach($map as $key=>$context){$value=$key==='@title'?$title:($body[$key]??null);if(!is_string($value))continue;
  if(isset($stored[$key])&&(!text_pair_valid170($stored[$key])||$stored[$key]['ar']!==$value))unset($stored[$key]);
  foreach($pairs as $p)if(text_pair_valid170($p)&&$p['context']===$context&&$p['ar']===$value){$stored[$key]=$p;break;}
 }if($stored)$body['_text170']=$stored;else unset($body['_text170']);return $body;
}
// Compatibility index for existing readers/exports that project only the canonical text.
// It is derived from saved counterparts, not a queue or an independent translation job.
function text_pairs_index170(array $pairs):void {
 if(!$pairs)return;
 if(!db()->inTransaction())throw new LogicException('Counterpart index requires transaction.');
 query("SELECT value_json FROM ep_settings WITH (UPDLOCK,HOLDLOCK) WHERE setting_key='data_translation31'")->fetch();$saved=setting31('data_translation31',[]);$changed=false;
 foreach($pairs as $p){if(!text_pair_valid170($p)||trim($p['ar'])===''||trim($p['en'])==='')continue;$id=language_key31($p['context'],$p['ar']);$entry=['id'=>$id,'context'=>$p['context'],'source'=>$p['ar'],'target'=>$p['en'],'status'=>'translated','origin'=>'saved_counterpart170'];if(($saved[$id]['target']??null)===$p['en']&&($saved[$id]['origin']??'')==='saved_counterpart170')continue;$saved[$id]=$entry;$changed=true;}
 if($changed){put_setting31('data_translation31',$saved);put_setting31('data_translation_revision31',(int)setting31('data_translation_revision31',0)+1);}
}
function text_user_persist170(int $id,?array $state):void {
 if(!$state)return;if(!db()->inTransaction())throw new LogicException('Paired persistence requires transaction.');
 text_pairs_index170($state['pairs']);
 $staff=query('SELECT * FROM ep_users WHERE id=?',[$id])->fetch();$profile=decode($staff['profile_json']);$mainPairs170=[];foreach(['department','job_title','additional_job','address','bank_name']as $field170){$current170=in_array($field170,['department','job_title'],true)?($staff[$field170]??''):($profile[$field170]??'');foreach($state['pairs']as $pair170)if(text_pair_valid170($pair170)&&$pair170['context']===$field170&&$pair170['ar']===$current170){$mainPairs170[$field170]=$pair170;break;}}$profile['_text170']=$mainPairs170;query('UPDATE ep_users SET profile_json=? WHERE id=?',[jencode($profile),$id]);
 foreach(query("SELECT id,module,title,body_json FROM ep_records WHERE user_id=? AND module IN ('service_period','service_terms','employment','allowance_terms')",[$id])->fetchAll()as $r){$body=decode($r['body_json']);$next=text_pairs_attach170($body,$state['pairs'],$r['module'],$r['title']);if($next!==$body)query('UPDATE ep_records SET body_json=? WHERE id=?',[jencode($next),$r['id']]);}
 foreach(query("SELECT id,kind,body_json FROM ep_history WHERE employee_code=? AND kind IN ('salary_history','bank_terms')",[employee_key($staff)])->fetchAll()as $r){$body=decode($r['body_json']);$next=text_pairs_attach170($body,$state['pairs'],$r['kind']);if($next!==$body)query('UPDATE ep_history SET body_json=? WHERE id=?',[jencode($next),$r['id']]);}
}
function text_permit_prepare170(array &$request,array $actor,array $ids):array {
 $meta=text_pairs_meta170($request['values']['_edit170']??null);if(!$meta||($request['mode']??'')==='transfer')return [];
 $uid=(int)($request['user_id']??0);if(!in_array($uid,$ids,true))fail('الموظف خارج الصلاحيات.',403);$old=null;
 if(($request['mode']??'')!=='new'){$old=query("SELECT * FROM ep_records WHERE id=? AND user_id=? AND module='work_permit' AND status='approved'",[(int)($request['id']??0),$uid])->fetch();if(!$old||!record_company_allowed30($actor,'work_permit',$old,($request['mode']??'')==='correct'?'edit':'view'))fail('الإذن خارج الصلاحيات.',403);if(!hash_equals(permit_revision148($old),(string)($request['revision']??'')))fail('تغيرت بيانات الإذن؛ أعد تحميله.',409);}
 $prior=$old?decode($old['body_json']):[];$items=[];foreach(['occupation'=>'occupation','reason'=>'permit_note159']as $k=>$context)$items[$k]=['value'=>$request['values'][$k]??'','context'=>$context,'pair'=>$prior['_text170'][$k]??null,'edited'=>in_array($k,$meta['edited'],true),'language'=>$meta['languages'][$k]??$meta['language']];
 $pairs=text_pairs_build170($items,$meta['language']);foreach($pairs as $k=>$p)$request['values'][$k]=$p['ar'];return $pairs;
}
function text_pairs_dictionary170(mixed $node):array {
 $out=[];$walk=function($v)use(&$walk,&$out){if(!is_array($v))return;foreach($v['_text170']??[]as $p)if(text_pair_valid170($p))$out[$p['context']][$p['ar']]=$p['en'];foreach($v as $key=>$value){if(in_array($key,['_text170','before','after','snapshot','settings','permissions','localization'],true))continue;if(is_string($value)&&in_array($key,['profile_json','body_json','value_json'],true)){$value=decode($value);}if(is_array($value))$walk($value);}};$walk($node);return $out;
}
