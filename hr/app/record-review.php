<?php
declare(strict_types=1);
function site_v32_ready():bool{return site_v31_ready()&&(bool)query("SELECT setting_key FROM ep_settings WHERE setting_key='upgrade_v32'")->fetch();}
function migrate32():void {
 if(site_v32_ready())return;
 // Archive exact duplicates, keep their IDs and audit history; never delete differing dated facts.
 $seen=[];$duplicates=[];
 foreach(query("SELECT * FROM ep_records WHERE module IN ('payout_status','service_period','service_terms') AND status='approved' ORDER BY id")->fetchAll()as $r){
  $body=decode($r['body_json']);$source=$body['source']??'';if(!$source&&!isset($body['_v28_key']))continue;
  $semantic=$body;unset($semantic['_v28_key']);ksort($semantic);
  $key=hash('sha256',jencode([$r['module'],(int)$r['user_id'],$r['date_from'],$r['date_to'],(float)$r['amount'],$semantic]));
  if(!isset($seen[$key])){$seen[$key]=(int)$r['id'];continue;}
  $body['_duplicate_of']=$seen[$key];$body['_archived_by_release']='V32';query("UPDATE ep_records SET status='archived',body_json=?,updated_at=? WHERE id=?",[jencode($body),utc_now(),$r['id']]);$duplicates[]=['id'=>(int)$r['id'],'canonical'=>$seen[$key]];audit('duplicate_archived','record:'.$r['id'],['before'=>$r,'canonical'=>$seen[$key]]);
 }
 // Only seed agreed rates if no rules at all have been configured. Preserve any administrative edits.
 if(!query("SELECT id FROM ep_records WHERE module='commission_rule'")->fetch()){
  $map=[];foreach(query("SELECT * FROM ep_users WHERE role<>'admin'")->fetchAll()as $s)$map[employee_key($s)]=$s;
  $names=['EMP-0007'=>'زياد','EMP-0008'=>'سامح','EMP-0009'=>'خالد','EMP-0012'=>'ايمن'];$matches=true;
  foreach($names as $code=>$prefix)if(!isset($map[$code])||!str_starts_with(str_replace(['أ','إ','آ'],['ا','ا','ا'],$map[$code]['name']),$prefix))$matches=false;
  if($matches){$cfg=settings();$from=($cfg['payroll_start_month']??substr(local_time()->format('Y-m-d'),0,7)).'-01';
   foreach($names as $code=>$prefix){$retro=$code==='EMP-0007';v26_insert('commission_rule',(int)$map[$code]['id'],$from,null,['type'=>'sales_agent','mode'=>$retro?'retro':'progressive','rate'=>$retro?9:10,'threshold'=>$retro?99:150,'upper_rate'=>$retro?10:11,'source'=>'agreed_presets_V32']);}
   foreach(['collection'=>['percent',1],'delivery_contract'=>['unit',.1],'sales_referral'=>['unit',2.5],'sales_check'=>['unit',2.5],'sales_full'=>['unit',5]]as $type=>$rate)v26_insert('commission_rule',0,$from,null,['type'=>$type,'mode'=>$rate[0],'rate'=>$rate[1],'threshold'=>0,'upper_rate'=>0,'source'=>'agreed_presets_V32']);
   put_setting31('commission_presets_v24',['from'=>$from,'release'=>'V32']);
  }
 }
 // Exact original-copy substitutions only; preserve administrator-authored content.
 $copy32=json_decode('{"A clear path from interest to delivery—designed to reduce friction.": "A simple, clear process from your first enquiry to delivery.", "रुचि से डिलीवरी तक स्पष्ट मार्ग—घर्षण कम करने के लिए।": "पूछताछ से डिलीवरी तक, हर कदम पर एक सरल और स्पष्ट प्रक्रिया।", "دلچسپی سے ترسیل تک واضح راستہ—رگڑ کم کرنے کے لیے۔": "ابتدائی رابطے سے ترسیل تک، ہر مرحلے پر آسان اور واضح طریقۂ کار۔"}',true);
 foreach(['ep_cms_pages','ep_cms_sections','ep_cms_cards']as $table32)foreach(query("SELECT id,content_json FROM $table32")->fetchAll()as $content32){$old32=decode($content32['content_json']);$new32=$old32;array_walk_recursive($new32,function(&$v)use($copy32){if(is_string($v)&&isset($copy32[$v]))$v=$copy32[$v];});if($new32!==$old32){query("UPDATE $table32 SET content_json=? WHERE id=?",[jencode($new32),$content32['id']]);audit('public_copy_corrected',$table32.':'.$content32['id'],['before'=>$old32,'after'=>$new32]);}}
 if(settings()['annual_leave_days']===null)put_setting31('annual_leave_days',30);
 foreach(query("SELECT * FROM ep_cms_cards WHERE card_type='offer' AND visible=1")->fetchAll()as $offer){$content=decode($offer['content_json']);if(!$offer['image_id']||trim($content['ar']['title']??'')===''||trim($content['ar']['details']??$content['ar']['body']??'')===''){query('UPDATE ep_cms_cards SET visible=0 WHERE id=?',[$offer['id']]);audit('incomplete_offer_draft','card:'.$offer['id'],['visible_before'=>1,'reason'=>'Incomplete publication content']);}}
 unset($GLOBALS['EP_SERVICE_CACHE'],$GLOBALS['V26_ENTITLEMENTS']);put_setting31('upgrade_v32',['at'=>utc_now(),'duplicates'=>$duplicates]);audit('site_migration','V32',['archived_duplicates'=>$duplicates]);
}
function sorted_records32(array $rows,string $kind):array {
 $users=[];if($kind!=='company_file')foreach(query("SELECT * FROM ep_users WHERE role<>'admin'")->fetchAll()as $u)$users[(int)$u['id']]=employee_key($u);
 $fileOrder=[];if($kind==='company_file')foreach($rows as $r){$v=decode($r['body_json']);$key=($v['company_code']??'').'|'.($v['file_number']??$r['id']);$fileOrder[$key]=min($fileOrder[$key]??PHP_INT_MAX,(int)$r['id']);}
 usort($rows,function($a,$b)use($kind,$users,$fileOrder){
  if($kind==='company_file'){$x=decode($a['body_json']);$y=decode($b['body_json']);$c=strnatcasecmp($x['company_code']??'',$y['company_code']??'');if($c)return $c;$c=(int)(($x['type']??'')!=='main')<=>(int)(($y['type']??'')!=='main');if($c)return $c;$xk=($x['company_code']??'').'|'.($x['file_number']??$a['id']);$yk=($y['company_code']??'').'|'.($y['file_number']??$b['id']);return ($fileOrder[$xk]<=>$fileOrder[$yk])?:strcmp($a['date_from']??'',$b['date_from']??'')?:((int)$a['id']<=>(int)$b['id']);}
  return strnatcasecmp($users[(int)$a['user_id']]??'', $users[(int)$b['user_id']]??'')?:strcmp($a['date_from']??'',$b['date_from']??'')?:((int)$a['id']<=>(int)$b['id']);
 });return $rows;
}
function entitlement_overview32(array $actor):array {
 $ids=scope_ids($actor);$rows=[];$today=local_time()->format('Y-m-d');$staff=query("SELECT * FROM ep_users WHERE role<>'admin'")->fetchAll();service_prefetch($staff);
 foreach($staff as $s){if(!in_array((int)$s['id'],$ids,true))continue;$state=service_state($s,$today);$flags=v26_entitlements($s,$today);$rows[]=['user_id'=>(int)$s['id'],'code'=>employee_key($s),'name'=>$s['name'],'from'=>$state['from'],'leave_days'=>$flags['leave_days'],'leave_cash'=>$flags['leave_cash'],'eos'=>$flags['eos'],'employed'=>$state['employed']];}
 usort($rows,fn($a,$b)=>strnatcasecmp($a['code'],$b['code']));return $rows;
}
// Match balances to source IDs through the report's end date; movements retain their actual payment date.
function bank_balances32(array $staff,string $from,string $to,string $company='',string $department=''):array {
 $b=['bank_opening'=>0.0,'recovery_opening'=>0.0,'bank_outstanding'=>0.0,'recovery_outstanding'=>0.0];$paid=[];
 foreach(query("SELECT * FROM ep_records WHERE module='payment' AND user_id=? AND status='approved' AND date_from<=?",[$staff['id'],$to])->fetchAll()as $r){$v=decode($r['body_json']);if(!in_array($v['kind']??'',['bank','recovery'],true))continue;$key=$v['kind'].'|'.$v['source_id'];$paid[$key]['end']=($paid[$key]['end']??0)+(float)$r['amount'];if($r['date_from']<$from)$paid[$key]['open']=($paid[$key]['open']??0)+(float)$r['amount'];}
 foreach(query("SELECT * FROM ep_bank_payroll WHERE user_id=? AND status='approved' AND month_key<=?",[$staff['id'],substr($to,0,7)])->fetchAll()as $r){$snap=decode($r['snapshot_json']);$dated=service_state($staff,(new DateTimeImmutable($r['month_key'].'-01'))->format('Y-m-t'));if(($company!==''&&($snap['calculation']['work_company_code']??$dated['company_code']??'')!==$company)||($department!==''&&($snap['calculation']['department']??$dated['department']??$staff['department'])!==$department))continue;foreach(['bank'=>(float)$r['amount'],'recovery'=>(float)($snap['recovery_due']??0)]as $kind=>$due){$key=$kind.'|'.$r['id'];$b[$kind.'_outstanding']+=max(0,$due-($paid[$key]['end']??0));if($r['month_key']<substr($from,0,7))$b[$kind.'_opening']+=max(0,$due-($paid[$key]['open']??0));}}
 return $b;
}
function display_order32(string $action,mixed $result):mixed {
 if(!is_array($result))return $result;
 if(in_array($action,['list','payroll_list','bank_list','policies','workforce_list'],true)&&array_is_list($result))return sorted_records32($result,'employees');
 if($action==='company_files_choices29'&&isset($result['records']))$result['records']=sorted_records32($result['records'],'company_file');
 if(in_array($action,['leave_overview','locations'],true)&&array_is_list($result)){$mapping=[];foreach(query("SELECT * FROM ep_users WHERE role<>'admin'")->fetchAll()as $s)$mapping[(int)$s['id']]=employee_key($s);usort($result,fn($a,$b)=>strnatcasecmp($mapping[(int)($a['id']??0)]??'',$mapping[(int)($b['id']??0)]??''));}
 if($action==='companies'&&array_is_list($result))foreach($result as &$company32)$company32['active']=(bool)(int)$company32['active'];unset($company32);
 if($action==='groups29_list'&&isset($result['staff']))usort($result['staff'],fn($a,$b)=>strnatcasecmp($a['employee_code'],$b['employee_code']));
 if($action==='commission_info')foreach(['rules','months']as $key)if(isset($result[$key]))$result[$key]=sorted_records32($result[$key],'commissions');
 return $result;
}

// Search is applied after authorization, before pagination; no change to access scope.
function record_search_normalize32(string $text):string {
 $text=preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u','',$text)??$text;
 return mb_strtolower(trim(strtr($text,['أ'=>'ا','إ'=>'ا','آ'=>'ا','ٱ'=>'ا','ى'=>'ي','ة'=>'ه'])),'UTF-8');
}
function record_search_rows32(array $rows,string $search):array {
 $q=record_search_normalize32($search);if($q==='')return $rows;
 $people=[];foreach(query('SELECT id,name,username,profile_json FROM ep_users')->fetchAll()as $u){$p=decode($u['profile_json']);$people[(int)$u['id']]=[$u['name'],$p['name_en']??'',$p['employee_code']??$u['username']];}
 $dictionary=json_decode(file_get_contents(__DIR__.'/ui-en.json'),true)?:[];
 $enum=['approved'=>'معتمد','pending'=>'بانتظار المراجعة','rejected'=>'مرفوض','revision'=>'مطلوب تعديل','active'=>'نشط','closed'=>'مغلق','cancelled'=>'ملغي','draft'=>'مسودة','submitted'=>'مرسل للمراجعة','returned'=>'مرجع للتعديل','main'=>'رئيسي','branch'=>'فرع','yes'=>'نعم','no'=>'لا'];foreach($enum as $key=>$label)$dictionary[$key]=$label.' '.($dictionary[$label]??'');
 $values=function($value)use(&$values,$dictionary):string {if(is_array($value))return implode(' ',array_map($values,array_values($value)));if(is_bool($value))return $value?'yes نعم':'no لا';$s=(string)$value;$j=json_decode($s,true);if(is_array($j))return $values($j);$date=preg_replace('/\b(\d{4})-(\d{2})-(\d{2})\b/','$3/$2/$1',$s);return $s.' '.$date.' '.($dictionary[$s]??'');};
 return array_values(array_filter($rows,fn($r)=>str_contains(record_search_normalize32($values($r).' '.$values($people[(int)($r['user_id']??0)]??[])),$q)));
}
