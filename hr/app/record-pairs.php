<?php
declare(strict_types=1);
require_once __DIR__.'/text-pairs.php';
function record_modules173():array{return ['document','custody','discipline','evaluation'];}
function record_fields173(string $module):array{return ['document'=>['document_type'],'custody'=>['asset_type','handover','return_notes'],'discipline'=>['decision_type','reason','employee_response'],'evaluation'=>['criteria','strengths','improvement','recommendation','results','cooperation','communication','issues','goals','actions','support']][$module]??[];}
function record_revision173(array $row):string{$v=[];foreach(['id','module','user_id','title','status','body_json','review_note','date_from','date_to','amount','file_id','updated_at','reviewed_by']as $k)$v[$k]=$row[$k]??null;return hash('sha256',jencode($v));}
function record_pair_values173(array $values,array $contexts,array $prior,mixed $metadata):array{
 $meta=text_pairs_meta170($metadata)??['language'=>'ar','edited'=>[],'languages'=>[]];$items=[];
 foreach($contexts as $key=>$context){$value=(string)($values[$key]??'');if(mb_strlen($value)>5000)fail('النص أطول من الحد المسموح.');$items[$key]=['value'=>$value,'context'=>$context,'pair'=>$prior[$key]??null,'edited'=>in_array($key,$meta['edited'],true),'language'=>$meta['languages'][$key]??$meta['language']];}
 $pairs=text_pairs_build170($items,$meta['language']);$canonical=[];foreach($pairs as $k=>$p)$canonical[$k]=$p['ar'];return ['values'=>$canonical,'pairs'=>$pairs];
}
// Authorization is checked before sending private text to the provider. Snapshot is
// checked again under the original writer's locks, after translation has finished.
function record_prepare173(string $action,array &$b,array $actor,array $writer):?array{
 if(!in_array($action,['record_save','record_review','record_reply'],true))return null;
 $row=null;$module=(string)($b['module']??'');
 if($action!=='record_save'){$row=query('SELECT * FROM ep_records WHERE id=?',[(int)($b['id']??0)])->fetch();if(!$row)return null;$module=$row['module'];}
 if(!in_array($module,record_modules173(),true))return null;
 if($row&&!empty($b['revision173'])&&!hash_equals(record_revision173($row),(string)$b['revision173']))fail('تغير السجل؛ أعد فتحه قبل الحفظ.',409);
 $target=(int)($row['user_id']??$b['user_id']??$writer['id']);
 if($action==='record_save'){employee_required($target);if($writer['role']==='employee'||!can_view($writer,$target))fail('الموظف خارج صلاحياتك.',403);if(in_array($module,['document','discipline'],true)&&$writer['role']==='manager'&&$target===(int)$writer['id'])fail('لا يمكن إنشاء قرار إداري لنفسك.');}
 elseif($action==='record_review'){if(!can_manage($writer,$target)||!in_array($row['status'],['pending','revision','active'],true))fail('السجل غير متاح للمراجعة.',403);}
 elseif($target!==(int)$writer['id'])fail('السجل خارج صلاحياتك.',403);
 $prior=$row?decode($row['body_json']):[];$contexts=[];$values=[];
 if($action==='record_save'){$body=$b['body']??[];if(is_string($body))$body=decode($body);$contexts['title']='record_title:'.$module;$values['title']=$b['title']??modules()[$module]['label'];foreach(record_fields173($module)as $k){$contexts[$k]='record_text:'.$module.':'.$k;$values[$k]=$body[$k]??'';}}
 else{$key=$action==='record_review'?'review_note':'_employee_reply';$contexts[$key]='record_text:'.$module.':'.$key;$values[$key]=$b[$action==='record_review'?'note':'reply']??'';}
 $state=record_pair_values173($values,$contexts,$prior['_text170']??[],$b['_edit170']??null);
 if($action==='record_save'){$b['title']=$state['values']['title'];if(mb_strlen($b['title'])>180)fail('عنوان السجل أطول من الحد المسموح.');foreach(record_fields173($module)as $k)$body[$k]=$state['values'][$k];$b['body']=$body;}
 else $b[$action==='record_review'?'note':'reply']=reset($state['values']);
 return $state+['base'=>$row?record_revision173($row):null];
}
function record_check173(?array $state,array $row):void{if($state&&$state['base']&&!hash_equals($state['base'],record_revision173($row)))fail('تغير السجل أثناء الحفظ؛ أعد فتحه قبل المحاولة.',409);}
function record_attach173(array $body,?array $state):array{if(!$state)return $body;$pairs=$state['pairs'];if(isset($pairs['title'])){$pairs['@title']=$pairs['title'];unset($pairs['title']);}$body['_text170']=array_replace($body['_text170']??[],$pairs);text_pairs_index170($state['pairs']);return $body;}
function policy_prepare173(array &$b,array $actor):?array{
 if(!(int)($b['user_id']??0)||(($b['schedule_kind']??'')==='temporary'&&empty($b['_edit170'])))return null;
 policy_access32($actor,(int)$b['user_id'],empty($b['manage_mode'])||($b['manage_mode']??'')==='version'?'create':'edit');
 $old=empty($b['id'])?null:query('SELECT * FROM ep_policies WHERE id=?',[(int)$b['id']])->fetch();if($old&&(int)$old['user_id']!==(int)$b['user_id'])fail('السياسة خارج الصلاحيات.',403);
 $prior=$old?decode($old['value_json']):[];$state=record_pair_values173(['label'=>$b['label']??'سياسة دوام','change_reason'=>$b['reason']??''],['label'=>'policy_label164','change_reason'=>'record_text:policy:change_reason'],$prior['_text170']??[],$b['_edit170']??null);
 if(mb_strlen($state['values']['label'])>150)fail('اسم السياسة أطول من الحد المسموح.');$b['label']=$state['values']['label'];$b['reason']=$state['values']['change_reason'];return $state;
}
