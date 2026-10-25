<?php
declare(strict_types=1);
// Dated projection only. Existing legacy records are retained for dates before the first permit.
function employee_residence134(int $uid,string $date):?array {
 if(!isset($GLOBALS['EMP_RESIDENCE134'][$uid]))$GLOBALS['EMP_RESIDENCE134'][$uid]=query("SELECT * FROM ep_records WHERE user_id=? AND module='work_permit' AND status='approved' ORDER BY date_from DESC,id DESC",[$uid])->fetchAll();
 $selected=null;$fileFrom=null;$priorDifferent134=false;foreach($GLOBALS['EMP_RESIDENCE134'][$uid] as $r){$b=decode($r['body_json']);$from=valid_date($b['effective_from']??null)??$r['date_from'];if(!$from||$from>$date)continue;if(!$selected){$selected=['company_code'=>(string)($b['company_code']??''),'file_id'=>(int)($b['file_id']??0),'from'=>$from,'record'=>$r];$fileFrom=$from;continue;}if((string)($b['company_code']??'')!==$selected['company_code']||(int)($b['file_id']??0)!==$selected['file_id']){$priorDifferent134=true;break;}$fileFrom=$from;}
 if(!$selected)return null;
 if(!isset($GLOBALS['EMP_RESIDENCE_FILES134'][$uid]))$GLOBALS['EMP_RESIDENCE_FILES134'][$uid]=query("SELECT * FROM ep_records WHERE user_id=? AND module='residence_file' AND status='approved' ORDER BY date_from DESC,id DESC",[$uid])->fetchAll();
 foreach(($priorDifferent134?[]:$GLOBALS['EMP_RESIDENCE_FILES134'][$uid])as $legacy134){if($legacy134['date_from']>$fileFrom)continue;$v134=decode($legacy134['body_json']);if(($v134['company_code']??'')===$selected['company_code']&&(int)($v134['file_id']??0)===$selected['file_id'])$fileFrom=$legacy134['date_from'];break;}
 $selected['file_from']=$selected['file_id']?$fileFrom:null;return $selected;
}
function employee_bank134(array $staff,string $date):?array {
 foreach(query("SELECT * FROM ep_history WHERE employee_code=? AND kind='bank_terms' AND period_key<=? ORDER BY period_key DESC,id DESC",[employee_key($staff),$date])->fetchAll()as $r){$b=decode($r['body_json']);if(!empty($b['effective_to'])&&$b['effective_to']<$date)continue;return ['from'=>$r['period_key'],'to'=>$b['effective_to']??'','values'=>$b];}return null;
}
function employee_bank_unchanged134(array $old,array $profile,array $request):bool {
 $date=local_time()->format('Y-m-d');if(!service_state($old,$date)['employed']){$last134=query("SELECT TOP (1) * FROM ep_records WHERE module='service_period' AND user_id=? AND status='approved' AND date_from<=? ORDER BY date_from DESC,id DESC",[$old['id'],$date])->fetch();if($last134&&!empty($last134['date_to']))$date=$last134['date_to'];}$bank=employee_bank134($old,$date);if(!$bank)return false;
 if((string)($request['bank_effective_from']??'')!==$bank['from']||(string)($request['bank_effective_to']??'')!==$bank['to'])return false;
 foreach(['bank_name','bank_account','iban']as $k)if((string)($profile[$k]??'')!==(string)($bank['values'][$k]??''))return false;
 // Identity edits alone do not rewrite an old bank snapshot.
 return true;
}
function employee_job134(array $actor,array $before,array $after):void {
 $date=local_time()->format('Y-m-d');$state=service_state($before,$date);$changes=[];foreach(['department','job_title','staff_type']as $k)if((string)($state[$k]??$before[$k]??'')!==(string)($after[$k]??''))$changes[$k]=$after[$k];if(!$changes)return;
 if(empty($state['period_id']))return;
 financial_invalidate((int)$before['id'],$date);$same=query("SELECT * FROM ep_records WHERE module='service_terms' AND user_id=? AND date_from=? AND status='approved'",[$before['id'],$date])->fetch();
 if($same){$body=array_replace(decode($same['body_json']),$changes);query('UPDATE ep_records SET body_json=?,updated_at=? WHERE id=?',[jencode($body),utc_now(),$same['id']]);}
 else v26_insert('service_terms',(int)$before['id'],$date,null,$changes);
 unset($GLOBALS['EP_SERVICE_CACHE'][(int)$before['id']]);audit('employee_job134','user:'.$before['id'],['changes'=>$changes,'date'=>$date,'actor'=>$actor['id']]);
}
