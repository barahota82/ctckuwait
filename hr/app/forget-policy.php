<?php
declare(strict_types=1);
/** One-time dated policy activation. Caller owns transaction and exclusive upgrade lock. */
function forget_ready361():bool{
 return (bool)query("SELECT setting_key FROM ep_settings WHERE setting_key='forget_policy_v361'")->fetch();
}
function policy_editor_defaults361():array{
 $p=default_policy();$p['forget_escalation_enabled']=false;
 if(forget_ready361()){$p['forget_enabled']=true;$p['missing_minutes']=120;}
 return $p;
}
function forget_values361(array $p):array{
 // Enabling the base must not unexpectedly enable old, dormant escalation tiers.
 $p['forget_escalation_enabled']=!empty($p['forget_enabled'])&&($p['forget_escalation_enabled']??!empty($p['forget_tiers']));
 $p['forget_enabled']=true;$p['missing_minutes']=120;return $p;
}
function forget_migrate361():array{
 $saved=query("SELECT value_json FROM ep_settings WITH (UPDLOCK,HOLDLOCK) WHERE setting_key='forget_policy_v361'")->fetchColumn();
 if($saved)return decode($saved)+['already_applied'=>true];
 policy_lock_scope32(0);
 $from=local_time()->modify('+1 day')->format('Y-m-d');
 // Protect all existing payroll snapshots, including submitted and draft months.
 $month=query('SELECT MAX(month_key) FROM ep_payroll')->fetchColumn();
 if($month&&preg_match('/^\d{4}-\d{2}$/',$month))$from=max($from,(new DateTimeImmutable($month.'-01'))->modify('first day of next month')->format('Y-m-d'));
 $event=query('SELECT MAX(work_date) FROM ep_events')->fetchColumn();
 if($event)$from=max($from,(new DateTimeImmutable($event))->modify('+1 day')->format('Y-m-d'));
 $rows=query('SELECT * FROM ep_policies WITH (UPDLOCK,HOLDLOCK) WHERE effective_to IS NULL OR effective_to>=? ORDER BY effective_from,id',[$from])->fetchAll();
 $changed=0;$general=[];
 foreach($rows as $r){
  $next=forget_values361(array_replace(default_policy(),decode($r['value_json'])));
  $start=max($from,$r['effective_from']);$id=(int)$r['id'];
  if($r['effective_from']<$from){
   query('UPDATE ep_policies SET effective_to=? WHERE id=?',[(new DateTimeImmutable($from))->modify('-1 day')->format('Y-m-d'),$id]);
   query('INSERT INTO ep_policies(user_id,effective_from,effective_to,label,value_json,created_at) VALUES(?,?,?,?,?,?)',[$r['user_id'],$start,$r['effective_to'],$r['label'],jencode($next),utc_now()]);
   $id=(int)db()->lastInsertId();
  }else query('UPDATE ep_policies SET value_json=? WHERE id=?',[jencode($next),$id]);
  audit('policy_forget_activation','policy:'.$id,['before'=>$r,'effective_from'=>$start,'effective_to'=>$r['effective_to'],'after'=>$next]);$changed++;
  if(!$r['user_id'])$general[]=['from'=>$start,'to'=>$r['effective_to']];
 }
 // Fill only genuine global gaps; never overwrite times, locations or employee overrides.
 $cursor=$from;$gaps=[];
 foreach($general as $span){
  if($span['from']>$cursor)$gaps[]=[$cursor,(new DateTimeImmutable($span['from']))->modify('-1 day')->format('Y-m-d')];
  if(!$span['to']){$cursor=null;break;}
  $cursor=max($cursor,(new DateTimeImmutable($span['to']))->modify('+1 day')->format('Y-m-d'));
 }
 if($cursor)$gaps[]=[$cursor,null];
 foreach($gaps as [$start,$end]){
  $p=forget_values361(default_policy()); // configured=false: no invented/approved work schedule.
  query('INSERT INTO ep_policies(user_id,effective_from,effective_to,label,value_json,created_at) VALUES(NULL,?,?,?,?,?)',[$start,$end,'جزاء نسيان البصمة',jencode($p),utc_now()]);
  audit('policy_forget_default','policy:'.db()->lastInsertId(),['from'=>$start,'to'=>$end,'values'=>$p]);
 }
 $result=['effective_from'=>$from,'policy_versions'=>$changed,'default_periods'=>count($gaps)];
 query('INSERT INTO ep_settings(setting_key,value_json) VALUES(?,?)',['forget_policy_v361',jencode($result)]);
 audit('forget_policy_v361','attendance',$result);unset($GLOBALS['REPORT_POLICIES32']);return $result;
}
