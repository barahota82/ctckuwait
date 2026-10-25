<?php
declare(strict_types=1);
require_once __DIR__.'/company-access.php';
require_once __DIR__.'/personal.php';
require_once __DIR__.'/delegation.php';
function site_v29_ready():bool{return $GLOBALS['READY29']??=site_v26_ready()&&(bool)query("SELECT setting_key FROM ep_settings WHERE setting_key='upgrade_v29'")->fetch();}
function groups_config29():array{return $GLOBALS['GROUPS29']??=settings()['access_groups29']??['groups'=>[],'members'=>[],'exceptions'=>[]];}
function groups_store29(array $v):void{query('MERGE INTO ep_settings WITH (HOLDLOCK) AS target USING (VALUES (?, ?)) AS source (setting_key, value_json) ON target.setting_key=source.setting_key WHEN MATCHED THEN UPDATE SET value_json=source.value_json WHEN NOT MATCHED THEN INSERT (setting_key, value_json) VALUES (source.setting_key, source.value_json);',['access_groups29',jencode($v)]);$GLOBALS['GROUPS29']=$v;}
function groups_migrate29():void{
 if(site_v29_ready())return;$v=groups_config29();foreach(settings()['permissions']??[] as $id=>$grants){if(!$grants)continue;$key='legacy-'.$id;$v['groups'][$key]=['name'=>'صلاحيات محفوظة — '.$id,'grants'=>[]];foreach($grants as $screen=>$ops)$v['groups'][$key]['grants'][$screen]=['allow'=>$ops,'deny'=>[],'scope'=>'team','users'=>[]];$v['members'][(string)$id]=array_values(array_unique([...($v['members'][(string)$id]??[]),$key]));}groups_store29($v);query("INSERT INTO ep_settings(setting_key,value_json) VALUES('upgrade_v29','true')");$GLOBALS['READY29']=true;
}
function permission_rules29(array $u,string $screen):array{
 if(!empty($u['_personal_identity32']))return [];
 $v=groups_config29();$rules=[];$groupIds=$v['members'][(string)$u['id']]??[];foreach($v['groups']??[]as $key=>$group)if(!empty($group['manual_team'])&&(int)($group['manager_user_id']??0)===(int)$u['id'])$groupIds[]=$key;foreach(array_unique($groupIds)as $id){$g=$v['groups'][$id]??null;if(!empty($g['manual_team'])&&(int)($g['manager_user_id']??0)!==(int)$u['id'])continue;if(!$g||!($g['active']??true))continue;$source37=$screen==='collection37'&&!array_key_exists('collection37',$g['grants']??[])?'commissions':$screen;if(!isset($g['grants'][$source37]))continue;$r=$g['grants'][$source37];
 if(($r['company_mode']??'inherit')==='inherit'){$r['company_mode']=$g['company_mode']??'all';$r['companies']=$g['companies']??[];}
 if(($r['scope']??'inherit')==='inherit'){$r['scope']=$g['scope']??'team';$r['users']=$g['users']??[];}
 if($screen==='customer_cases'&&($r['company_mode']??'all')==='selected')$r['allow']=[];
 $r['manual_people']=!empty($g['manual_team'])?array_values(array_unique(array_map('intval',$g['team_users']??[]))):null;$rules[]=$r+['source'=>$g['name']];}
 $exceptionScreen37=$screen==='collection37'&&!array_key_exists('collection37',$v['exceptions'][(string)$u['id']]??[])?'commissions':$screen;
 if(isset($v['exceptions'][(string)$u['id']][$exceptionScreen37])){ $exception=$v['exceptions'][(string)$u['id']][$exceptionScreen37];if($screen==='customer_cases'&&($exception['company_mode']??'all')==='selected')$exception['allow']=[];$rules[]=$exception+['source'=>'استثناء فردي']; }
 if(!site_v29_ready()&&isset(settings()['permissions'][(string)$u['id']][$screen]))$rules[]=['allow'=>settings()['permissions'][(string)$u['id']][$screen],'deny'=>[],'scope'=>'team','users'=>[],'source'=>'صلاحيات سابقة'];return [...$rules,...delegation_rule199($u,$screen)];
}
function permission_effective29(array $u):array{
 $out=[];foreach(permission_catalog()as $screen=>$meta){$allow=[];$deny=[];foreach(permission_rules29($u,$screen)as $rule){$allow=array_merge($allow,$rule['allow']);$deny=array_merge($deny,$rule['deny']);}$out[$screen]=array_values(array_diff(array_unique($allow),array_unique($deny)));if(!in_array('view',$out[$screen],true))$out[$screen]=[];}return $out;
}
function permission_has_team29(array $u):bool{foreach(permission_effective29($u)as $ops)if($ops)return true;return false;}
function permission_scope29(array $u,string $screen,string $op='view'):array{
 if(($u['role']??'')==='admin')return array_map('intval',query("SELECT id FROM ep_users WHERE role<>'admin'")->fetchAll(PDO::FETCH_COLUMN));
 if(($_SESSION['access_mode29']??'team')==='personal')return [(int)$u['id']];
 $all=query("SELECT * FROM ep_users WHERE role<>'admin'")->fetchAll();service_prefetch($all);$ids=[];$rules=permission_rules29($u,$screen);foreach($rules as $rule)if(in_array($op,$rule['deny'],true)||in_array('view',$rule['deny'],true))return [];
 foreach($rules as $rule){if(!in_array($op,$rule['allow'],true)||!in_array('view',$rule['allow'],true))continue;foreach($all as $staff)if((!isset($rule['manual_people'])||in_array((int)$staff['id'],$rule['manual_people'],true))&&rule_company30($rule,staff_company30($staff))&&match($rule['scope']){'all'=>true,'department'=>(string)(service_state($u,local_time()->format('Y-m-d'))['department']??$u['department'])!==''&&(service_state($staff,local_time()->format('Y-m-d'))['department']??$staff['department'])===(service_state($u,local_time()->format('Y-m-d'))['department']??$u['department']),'selected'=>in_array((int)$staff['id'],array_map('intval',$rule['users']??[]),true),'self'=>(int)$staff['id']===(int)$u['id'],default=>(int)$staff['manager_id']===(int)$u['id']})$ids[]=(int)$staff['id'];}return array_values(array_unique($ids));
}
function permission_gate29(string $action,array $u,array $b):void{
 if($u['role']==='admin'||!site_v29_ready()||in_array($action,['companies','logout','change_password','language_select','mode29','groups30_filter','notifications','personal_history','v28_my_debt'],true))return;
 [$screen,$op]=permission_route29($action,$b);$GLOBALS['EP_SCREEN']=$screen;$GLOBALS['EP_OPERATION29']=$op;
 if($screen==='dashboard'&&!in_array($action,['dashboard'],true))return;
 if($action==='permission_punch182'&&personal_mode34($u))return;
 if(in_array($action,['presence_send','presence_ticket'],true)&&personal_mode34($u))return;
 $target=(int)($action==='user_save'?($b['id']??0):($b['user_id']??$_GET['user_id']??$u['id']));$own=$target===(int)$u['id'];if($action==='dashboard'&&!permission_has($u,'dashboard'))return;
 if($action==='record_review'){$row=query('SELECT user_id FROM ep_records WHERE id=?',[(int)($b['id']??0)])->fetch();if(!$row)fail('السجل غير موجود');$target=(int)$row['user_id'];$own=$target===(int)$u['id'];if($own)fail('لا يمكنك اعتماد طلبك الشخصي.',403);}
 if($own&&($op==='view'||($op==='create'&&($screen==='resumption'||!empty(modules()[$screen]['employee']))))&&($_SESSION['access_mode29']??'personal')==='personal')return;
 $rules=permission_rules29($u,$screen);foreach($rules as $rule)if(in_array($op,$rule['deny'],true)||in_array('view',$rule['deny'],true))fail('هذه الصلاحية ممنوعة لحسابك.',403);

 permission_require($u,$screen,$op);if(in_array($action,['record_save','record_review','user_save','v26_save','v26_permit_transfer'],true)&&!in_array($target,permission_scope29($u,$screen,$op),true))fail('الإجراء خارج نطاق الصلاحية.',403);
}
function grants_clean29(array $input):array{
 $catalog=permission_catalog();$out=[];foreach($input as $screen=>$rule){if(!isset($catalog[$screen]))fail('شاشة غير صالحة');$allow=array_values(array_unique($rule['allow']??[]));$deny=array_values(array_unique($rule['deny']??[]));foreach([...$allow,...$deny]as $op)if(!in_array($op,$catalog[$screen]['actions'],true))fail('إجراء غير صالح');if($allow&&!in_array('view',$allow,true))fail('يلزم منح القراءة أولًا');$scope=$rule['scope']??'team';if(!in_array($scope,['inherit','self','team','department','selected','all'],true))fail('نطاق غير صالح');$ids=array_map('intval',$rule['users']??[]);foreach($ids as $id)employee_required($id);if(array_intersect($allow,$deny))fail('لا يمكن السماح والمنع للعملية نفسها');$limit=($rule['company_mode']??'inherit')==='inherit'?['company_mode'=>'inherit','companies'=>[]]:company_limit_clean30($rule['company_mode'],$rule['companies']??[]);$out[$screen]=['allow'=>$allow,'deny'=>$deny,'scope'=>$scope,'users'=>$ids]+$limit;}return $out;
}
function groups_action29(string $action,array $u,array $b):array{
 if($action==='user_choices29'){
  $ids=[(int)$u['id']];$requested=(string)($_GET['screen']??'');
  if(function_exists('finance_kinds103')&&isset(finance_kinds103()[$requested])){$ids=finance_scope103($u,$requested);if(str_starts_with($requested,'receipt_')&&$requested!=='receipt_agents99')return [];$date=valid_date($_GET['date118']??null)??local_time()->format('Y-m-d');$people=finance_people103($ids,$requested,$date);return array_map(fn($p)=>['id'=>$p['user_id'],'employee_code'=>$p['code'],'name'=>$p['name'],'profile'=>['name_en'=>$p['name_en']]],$people);}
  if($u['role']==='admin')$ids=permission_scope29($u,'users');
  elseif(($_SESSION['access_mode29']??'personal')==='team'){
   if($requested!=='')$ids=isset(permission_catalog()[$requested])?permission_scope29($u,$requested):[(int)$u['id']];
   else foreach(permission_effective29($u)as $screen=>$ops)if(in_array('view',$ops,true))$ids=array_merge($ids,permission_scope29($u,$screen));
  }
  $people=[];foreach(array_chunk(array_values(array_unique($ids)),500)as $chunk){$marks=implode(',',array_fill(0,count($chunk),'?'));$people=array_merge($people,query("SELECT * FROM ep_users WHERE id IN ($marks) AND role<>'admin'",$chunk)->fetchAll());}service_prefetch($people);
  $rows=[];foreach($people as $person){$v=array_intersect_key(public_user($person),array_flip(['id','name','employee_code','department','job_title','staff_type','manager_id','active','role']));$v['company_code']=staff_company30($person);$v['profile']=['name_en'=>decode($person['profile_json'])['name_en']??''];$rows[]=$v;}usort($rows,fn($a,$b)=>strnatcasecmp($a['employee_code'],$b['employee_code']));return $rows;
 }
 if($action==='company_files_choices29'){permission_require($u,'work_permit');$rows=query("SELECT id,body_json FROM ep_records WHERE module='company_file' ORDER BY id")->fetchAll();$rows=array_values(array_filter($rows,fn($r)=>company_allowed30($u,'work_permit',(string)(decode($r['body_json'])['company_code']??''))));foreach($rows as &$row)$row['body_json']=jencode(array_intersect_key(decode($row['body_json']),array_flip(['company_code','type','file_number'])));unset($row);return ['records'=>$rows];}
 if($action==='company_choices29'){if($u['role']!=='admin'&&!permission_has($u,'workforce')&&!permission_has($u,'work_permit')&&!permission_has($u,'users'))fail('غير مسموح',403);return company_choices30($u);}
 if($action==='mode29'){$mode=$b['mode']??'';if(!in_array($mode,['personal','team'],true)||($mode==='team'&&!permission_has_team29($u)))fail('عرض غير مسموح',403);$_SESSION['access_mode29']=$mode;return ['ok'=>true];}
 if($action==='profile29'){require_once __DIR__.'/profile.php';return employee_profile33($u,(int)($_GET['user_id']??0));}
 if($action==='batch29'){$batchActor=$u;$batchScreen=($b['kind']??'')==='friday'?'batch_friday29':'batch_addition29';if($u['role']!=='admin'){permission_require($u,$batchScreen,'create');if(($_SESSION['access_mode29']??'personal')!=='team')fail('انتقل إلى عرض الفريق',403);$u['role']='admin';}
 $kind=$b['kind']??'';$month=(string)($b['month']??'');$reason=trim((string)($b['reason']??''));$rows=$b['rows']??[];if(!in_array($kind,['addition','friday'],true)||!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$month)||!$reason||!is_array($rows)||count($rows)<1||count($rows)>100)fail('بيانات الدفعة غير صالحة');$results=[];$seen=[];
 foreach($rows as $row){$id=(int)($row['user_id']??0);try{if($batchActor['role']!=='admin'&&!in_array($id,permission_scope29($batchActor,$batchScreen,'create'),true))fail('الموظف خارج نطاق الصلاحية',403);if(isset($seen[$id]))fail('الموظف مكرر في الدفعة');$seen[$id]=true;employee_required($id);if($kind==='friday')$result=allowance_action('friday_save',$u,['user_id'=>$id,'month'=>$month,'dates'=>$row['dates']??[],'confirmed'=>!empty($b['confirmed']),'note'=>$reason]);else{if(!is_numeric($row['amount']??null)||(float)$row['amount']<=0)fail('المبلغ موجب مطلوب');foreach(query("SELECT amount,body_json FROM ep_records WHERE user_id=? AND module='addition'",[$id])->fetchAll()as $prior){$body=decode($prior['body_json']);if(($body['month']??'')===$month&&($body['reason']??'')===$reason&&abs((float)$prior['amount']-(float)$row['amount'])<.0005)fail('إضافة مماثلة مسجلة بالفعل');}$result=v26_action('v26_save',$u,['kind'=>'addition','user_id'=>$id,'from'=>$month.'-01','amount'=>$row['amount'],'values'=>['month'=>$month,'reason'=>$reason]]);}$results[]=['user_id'=>$id,'ok'=>true]+$result;}catch(Throwable $e){if(db()->inTransaction())db()->rollBack();http_response_code(200);$results[]=['user_id'=>$id,'error'=>$e instanceof PDOException?'تعذر الحفظ؛ راجع سجل الخادم.':$e->getMessage()];}}
 return ['results'=>$results];
 }admin_only($u);if(site_v30_ready()&&$action==='groups29_save')fail('استخدم تابات الصلاحيات الجديدة',409);if(site_v30_ready()&&$action==='groups29_member'&&!isset($b['revision']))fail('تغيرت الصلاحيات؛ أعد تحميل الشاشة قبل الحفظ',409);if($action==='groups29_list'){$v=groups_config29();$staffRows=query("SELECT * FROM ep_users WHERE role<>'admin' ORDER BY id")->fetchAll();service_prefetch($staffRows);$staff=array_map('public_user',$staffRows);$effective=[];foreach($staff as $person)$effective[(string)$person['id']]=permission_effective29($person);return $v+['personal_catalog34'=>personal_catalog34(),'personal_defaults34'=>$v['personal_default34']??personal_defaults34(),'catalog'=>permission_catalog(),'staff'=>$staff,'full_supervisor_ids32'=>function_exists('supervisor_ids31')?supervisor_ids31():[],'effective'=>$effective,'companies'=>company_choices30($u),'revision'=>$v['revision']??0];}
 db()->beginTransaction();query("SELECT setting_key FROM ep_settings WITH (UPDLOCK, HOLDLOCK) WHERE setting_key='access_groups29'");$row=query("SELECT value_json FROM ep_settings WITH (UPDLOCK, HOLDLOCK) WHERE setting_key='access_groups29'")->fetch();$v=$row?decode($row['value_json']):groups_config29();if(isset($b['revision'])&&(int)$b['revision']!==(int)($v['revision']??0))fail('تغيرت الصلاحيات؛ أعد تحميل الشاشة قبل الحفظ',409);$before=$v;
 if($action==='groups29_save'){$id=(string)($b['id']??'');if(!$id)$id=bin2hex(random_bytes(8));if(!preg_match('/^[a-z0-9-]{1,60}$/',$id))fail('كود غير صالح');$name=trim((string)($b['name']??''));if(!$name)fail('اسم المجموعة مطلوب');$v['groups'][$id]=array_replace($v['groups'][$id]??[],['name'=>mb_substr($name,0,100),'grants'=>grants_clean29($b['grants']??[])]);}
 elseif($action==='groups29_member'){$id=(int)($b['user_id']??0);employee_required($id);$groups=array_values(array_unique($b['groups']??[]));foreach($groups as $g)if(!isset($v['groups'][$g]))fail('مجموعة غير موجودة');$v['members'][(string)$id]=$groups;$v['exceptions'][(string)$id]=grants_clean29($b['exceptions']??[]);}
 else fail('عملية غير موجودة',404);
 $v['revision']=(int)($v['revision']??0)+1;groups_store29($v);foreach(query("SELECT * FROM ep_users WHERE role<>'admin'")->fetchAll()as $staff)query('UPDATE ep_users SET role=? WHERE id=?',[permission_has_base_team199($staff)?'manager':'employee',$staff['id']]);audit($action,'access_groups',['before'=>$before,'after'=>$v]);db()->commit();return ['ok'=>true];
}
function permission_denied29(array $u,string $screen,string $op='view'):bool{if($u['role']==='admin')return false;foreach(permission_rules29($u,$screen)as $r)if(in_array('view',$r['deny'],true)||in_array($op,$r['deny'],true))return true;return false;}
function permission_route29(string $action,array $b):array{
 if($action==='payroll_addition198_save')return [(string)($b['key']??''),'view'];
 if(function_exists('workflow_route194')&&($route=workflow_route194($action,$b)))return $route;
 if(str_starts_with($action,'permit148_'))return ['work_permit',$action==='permit148_save'?(in_array(($b['mode']??''),['correct','transfer'],true)?'edit':'create'):'view'];
 if(str_starts_with($action,'incentive114_'))return ['payroll',$action==='incentive114_info'?'view':'create'];
 if(str_starts_with($action,'finance103_'))return [(string)($b['key']??$_GET['key']??''),'view'];
 if($action==='v26_permit_transfer')return ['work_permit','edit'];
 if($action==='punch39_choices')return ['request','create'];
 if(str_starts_with($action,'pay37_'))return [$action==='pay37_vouchers'?'vouchers37':pay37_screen((string)($b['table']??$_GET['table']??'payroll')),$action==='pay37_save'?'create':'view'];
 if($action==='v28_location_save')return ['v28_locations',empty($b['location']['id'])?'create':'edit'];
 if($action==='v28_info'&&($b['kind']??$_GET['kind']??'')==='locations')return ['v28_locations','view'];
 $screen=match(true){str_starts_with($action,'sales36_')=>'commissions',$action==='payroll_list'&&($_GET['tab35']??'')==='review'=>'payroll_review35',$action==='batch29'=>(($b['kind']??'')==='friday'?'batch_friday29':'batch_addition29'),$action==='profile29'=>'users',str_starts_with($action,'payroll_')||str_starts_with($action,'v26_payroll_')||$action==='v28_debt_deduction'=>'payroll',str_starts_with($action,'commission_')=>'commissions',str_starts_with($action,'workforce_')=>'workforce',str_starts_with($action,'allowance_')||str_starts_with($action,'friday_')=>'allowances',$action==='companies'||$action==='company_save'=>'companies',$action==='v28_info'||$action==='v28_record_save'=>(string)($b['kind']??$_GET['kind']??''),$action==='v26_info'||$action==='v26_save'=>(string)($b['kind']??$_GET['kind']??''),str_starts_with($action,'customer_case')=>'customer_cases',default=>$GLOBALS['EP_SCREEN']??'dashboard'};
 $op=match(true){$action==='sales36_cancel'=>'edit',$action==='batch29'=>'create',str_contains($action,'submit')=>'submit',str_contains($action,'return')||str_contains($action,'review')||str_contains($action,'approve')=>'review',str_contains($action,'save')||str_contains($action,'build')||str_contains($action,'send')||$action==='v28_debt_deduction'=>'create',default=>'view'};if(in_array($action,['user_save','workforce_terms'],true))$op='edit';if($action==='workforce_save'&&!empty($b['id']))$op='edit';if(!empty($b['id'])&&in_array($action,['v26_save','v28_record_save','company_save'],true))$op='edit';return [$screen,$op];
}
function delegation29(string $action,array $u,array $b):array{
 if($u['role']==='admin'||!site_v29_ready())return $u;
 [$screen,$op]=permission_route29($action,$b);$allowed=in_array($action,['workforce_list','workforce_save','workforce_terms','company_save','allowance_save','friday_save','v26_permit_transfer'],true)||($action==='v26_save'&&in_array($b['kind']??'',['work_permit','addition'],true));
 if(!$allowed)return $u;permission_require($u,$screen,$op);
 if($screen==='companies'){$global=false;foreach(permission_rules29($u,$screen)as $rule)if($rule['scope']==='all'&&in_array($op,$rule['allow'],true))$global=true;if(!$global)fail('تعديل الشركات يتطلب نطاق كل الشركة',403);}
 elseif($action!=='workforce_list'){if(!in_array((int)($b['user_id']??0),permission_scope29($u,$screen,$op),true))fail('خارج نطاق صلاحية التسجيل',403);}
 $u['_delegate29']=true;$u['role']='admin';return $u;
}
