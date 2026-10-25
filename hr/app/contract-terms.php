<?php
declare(strict_types=1);
// Automatically timestamped rate history; initial setup also covers historical entry. No manual effective-date gate.
function contract_fields119():array {return ['staff'=>['contract_commission118','contract_staff_rate119'],'agent'=>['contract_agent119','contract_agent_rate119'],'supervisor'=>['contract_supervisor119','contract_supervisor_rate119']];}
function contract_profile119(array $p):array {$out=[];foreach(contract_fields119()as $type=>[$flag,$rate])$out[$type]=['enabled'=>($p[$flag]??false)===true,'rate'=>round((float)($p[$rate]??0),3)];return $out;}
function contract_records119():array {
 $cache=true;if(isset($GLOBALS['CONTRACT_RECORDS119']))return $GLOBALS['CONTRACT_RECORDS119'];$out=[];
 foreach(query("SELECT * FROM ep_records WHERE module='contract_terms119' AND status='approved' ORDER BY date_from,id")->fetchAll()as $r){$r['_parsed119']=decode($r['body_json']);$out[(int)$r['user_id']][]=$r;}if($cache)$GLOBALS['CONTRACT_RECORDS119']=$out;return $out;
}
function contract_at119(array $staff,string $date):array {
 $records=contract_records119()[(int)$staff['id']]??[];$terms=$records?$records[0]['_parsed119']['baseline']:contract_profile119(decode($staff['profile_json']??'{}'));
 foreach($records as $r){if($r['date_from']>$date)break;$terms=$r['_parsed119']['terms'];}return $terms;
}
function contract_save119(int $uid,array $old,array $profile,string $from=''):void {
 $before=contract_profile119($old);$after=contract_profile119($profile);if($before===$after)return;
 $records=contract_records119()[$uid]??[];$date=local_time()->format('Y-m-d');foreach($after as $v)if($v['enabled']&&(!is_finite($v['rate'])||$v['rate']<=0||$v['rate']>999999.999))fail('مبلغ العقد مطلوب للاستحقاق المفعّل وبحد أقصى ثلاث خانات عشرية.');
 commission_rules_lock();$targets=[$uid];$allStaff=query("SELECT * FROM ep_users WHERE role<>'admin'")->fetchAll();
 // Staff contract changes also affect every supervisor entitlement; protect approved results.
 if($before['staff']!==$after['staff']||$before['supervisor']!==$after['supervisor'])foreach($allStaff as $s){$has=false;foreach(contract_records119()[(int)$s['id']]??[]as $r)if(!empty(decode($r['body_json'])['terms']['supervisor']['enabled']))$has=true;if($has||!empty(decode($s['profile_json'])['contract_supervisor119']))$targets[]=(int)$s['id'];}
 foreach(array_unique($targets)as $id)financial_invalidate($id,$date);
 $records=contract_records119()[$uid]??[];$baseline=$records?$records[0]['_parsed119']['baseline']:$after;
 v26_insert('contract_terms119',$uid,$date,null,['terms'=>$after,'baseline'=>$baseline,'recorded_at'=>utc_now(),'recorded_by'=>(int)(current_user()['id']??0),'supervisor_scope'=>'staff_only','automatic120'=>true],'approved');unset($GLOBALS['CONTRACT_RECORDS119'],$GLOBALS['CONTRACT_MONTH119']);audit('contract_terms119','user:'.$uid,['before'=>$before,'after'=>$after,'effective_from'=>$date]);
}
function contract_supervisors119():array {
 $ids=[];foreach(query("SELECT * FROM ep_users WHERE role<>'admin'")->fetchAll()as $s){$id=(int)$s['id'];if(!empty(decode($s['profile_json'])['contract_supervisor119']))$ids[]=$id;foreach(contract_records119()[$id]??[]as $r)if(!empty($r['_parsed119']['terms']['supervisor']['enabled'])){$ids[]=$id;break;}}return array_values(array_unique($ids));
}
function contract_month_data119(string $month):array {
 $cache=true;if($cache&&isset($GLOBALS['CONTRACT_MONTH119'][$month]))return $GLOBALS['CONTRACT_MONTH119'][$month];
 $staff=array_column(query("SELECT * FROM ep_users WHERE role<>'admin'")->fetchAll(),null,'id');$rows=query("SELECT * FROM ep_records WHERE module='finance_daily103' AND status='approved' AND user_id>0 AND date_from>=? AND date_from<=? ORDER BY date_from,id",[$month.'-01',pay35_month($month)])->fetchAll();$value=['staff'=>$staff,'rows'=>$rows];if($cache)$GLOBALS['CONTRACT_MONTH119'][$month]=$value;return $value;
}
function contract_lines119(int $uid,string $month):array {
 $data=contract_month_data119($month);$staff=$data['staff'][$uid]??null;if(!$staff)return ['lines'=>[],'flags'=>[],'managed_agent'=>false,'managed_staff'=>false];$history=contract_records119()[$uid]??[];$p=contract_profile119(decode($staff['profile_json']??'{}'));$baseline120=$history?($history[0]['_parsed119']['baseline']??[]):$p;$managedAgent=!empty($baseline120['agent']['enabled']);$managedStaff=!empty($baseline120['staff']['enabled']);
 foreach($history as $r)if($r['date_from']<=pay35_month($month)){$v=decode($r['body_json']);if($v['terms']['agent']!==$v['baseline']['agent'])$managedAgent=true;if($v['terms']['staff']!==$v['baseline']['staff'])$managedStaff=true;}
 $relevant119=(bool)array_filter($p,fn($v)=>$v['enabled']);foreach($history as $r)if($r['date_from']<=pay35_month($month)&&array_filter($r['_parsed119']['terms'],fn($v)=>$v['enabled']))$relevant119=true;if(!$relevant119)return ['lines'=>[],'flags'=>[],'managed_agent'=>$managedAgent,'managed_staff'=>$managedStaff];
 $totals=[];$flags=[];foreach($data['rows'] as $r198)if((int)$r198['user_id']===$uid&&(decode($r198['body_json'])['kind']??'')==='delivery_contract')$managedAgent=true;
 foreach($data['rows']as $r){$body=decode($r['body_json']);$kind=$body['kind']??'';$net=(int)($body['delivered']??0)-array_sum($body['returns']??[]);$date=$r['date_from'];$source=$data['staff'][(int)$r['user_id']]??null;if(!$source)continue;$terms=contract_at119($source,$date);$mine=(int)$r['user_id']===$uid;
  $type=$kind==='staff_contract'?'staff':($kind==='delivery_contract'?'agent':null);
  if($type&&$mine){$own=$terms[$type];if($own['enabled']&&$own['rate']>0){$name=$type==='staff'?'staff_contract119':'delivery_contract';$totals[$name]['amount']=($totals[$name]['amount']??0)+$net*$own['rate'];$totals[$name]['base']=($totals[$name]['base']??0)+$net;$totals[$name]['rates'][$date]=$own['rate'];}elseif($net)$flags[]='راجع استحقاق ومبلغ عمولة العقود في بيانات الموظف.';}
  if($kind==='staff_contract'&&$terms['staff']['enabled']){$supervisor=contract_at119($staff,$date)['supervisor'];if($supervisor['enabled']&&$supervisor['rate']>0&&service_state($staff,$date)['employed']){$type='supervisor_contract119';$totals[$type]['amount']=($totals[$type]['amount']??0)+$net*$supervisor['rate'];$totals[$type]['base']=($totals[$type]['base']??0)+$net;$totals[$type]['rates'][$date]=$supervisor['rate'];}}
 }
 $lines=[];foreach($totals as $type=>$t)$lines[]=['type'=>$type,'base'=>$t['base'],'amount'=>round($t['amount'],3),'rates119'=>$t['rates'],'source'=>'contract_terms119','supervisor_scope'=>$type==='supervisor_contract119'?'staff_only':null];
 return ['lines'=>$lines,'flags'=>array_values(array_unique($flags)),'managed_agent'=>$managedAgent,'managed_staff'=>$managedStaff];
}
