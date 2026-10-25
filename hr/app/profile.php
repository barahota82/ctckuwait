<?php
declare(strict_types=1);
// Read-only profile projection. No history or operational rows are changed.
function profile_access33(array $actor,int $id,string $screen):bool {
 if(personal_mode34($actor)){if($id!==(int)$actor['id'])return false;$map=['users'=>'users','workforce'=>'profile_service','payroll'=>'profile_salary','bank_details'=>'profile_bank','work_permit'=>'profile_permit','leave'=>'profile_leave','leave_cash'=>'leave_finance','document'=>'profile_documents'];return personal_has34($actor,$map[$screen]??$screen);}
 return $actor['role']==='admin'||$id===(int)$actor['id']||(!permission_denied29($actor,$screen)&&permission_has($actor,$screen)&&in_array($id,permission_scope29($actor,$screen),true));
}
function employee_profile33(array $actor,int $id):array {
 $GLOBALS['EP_SCREEN']='users';
 if(!profile_access33($actor,$id,'users')||!can_view($actor,$id))fail('خارج النطاق',403);
 $staff=query('SELECT * FROM ep_users WHERE id=?',[$id])->fetch();if(!$staff)fail('الموظف غير موجود',404);
 require_once __DIR__.'/employee-fields.php';
 $editor140=!empty($_GET['editor140'])&&$actor['role']==='admin';$history140=!empty($_GET['history140'])&&$actor['role']==='admin';
 $out=['user'=>personal_public_user34(public_user($staff),$actor),'tabs'=>[]];$code=employee_key($staff);
 if($actor['role']!=='admin'&&$id!==(int)$actor['id']){
  unset($out['user']['salary']);
  $out['user']['profile']=array_intersect_key($out['user']['profile'],array_flip(['name_en','phone','email','nationality','address','company_code','residence_company_code']));
 }
 foreach(['service'=>'workforce','salary'=>'payroll','bank'=>'bank_details','permit'=>'work_permit','leave'=>'leave','leave_cash'=>'leave_cash','documents'=>'document']as $tab=>$screen){
  if(($editor140&&!in_array($tab,['service','permit'],true))||($history140&&in_array($tab,['service','permit'],true)))continue;
  if(!profile_access33($actor,$id,$screen))continue;
  if($tab==='salary'||$tab==='bank'){
   $rows=query('SELECT * FROM ep_history WHERE employee_code=? AND kind=? ORDER BY period_key DESC,id DESC',[$code,$tab==='salary'?'salary_history':'bank_terms'])->fetchAll();
   if($tab==='bank'&&!$rows){$body=array_intersect_key(decode($staff['profile_json']),array_flip(['bank_name','bank_account','iban','bank_effective_from']));if(array_filter($body))$rows=[['body_json'=>jencode($body),'period_key'=>$body['bank_effective_from']??null]];}
  }else{
   $module=match($tab){'service'=>'service_period','permit'=>'work_permit','leave'=>'leave','leave_cash'=>'leave_cash',default=>'document'};
   $rows=query("SELECT * FROM ep_records WHERE user_id=? AND module=?".($tab==='permit'?'':" AND status<>'archived'").' ORDER BY date_from DESC,id DESC',[$id,$module])->fetchAll();
  }
  if($tab==='service'){
   $terms=query("SELECT * FROM ep_records WHERE user_id=? AND module='service_terms' AND status='approved' ORDER BY date_from DESC,id DESC",[$id])->fetchAll();
   foreach($terms as $term){$attached=false;foreach($rows as &$row)if($term['date_from']>=$row['date_from']&&$term['date_from']<=($row['date_to']??'9999-12-31')){$row['_terms33'][]=$term;$attached=true;break;}unset($row);if(!$attached)$rows[]=$term;}
  }
  if(in_array($tab,['leave','leave_cash'],true)){
   $history=query("SELECT * FROM ep_history WHERE employee_code=? AND kind=? ORDER BY period_key DESC,id DESC",[$code,$tab==='leave'?'leave_history':'leave_settlement'])->fetchAll();
   // Imported operational shadows and their historical source represent one event.
   foreach($history as $h){$shadow=null;foreach($rows as $n=>$r)if((int)(decode($r['body_json'])['history_id']??0)===(int)$h['id']){$shadow=$n;break;}
    if($shadow!==null){$rows[$shadow]['_historical33']=$h;continue;}$rows[]=$h+['status'=>'approved'];
   }
   if(!profile_access33($actor,$id,'payroll')||(personal_mode34($actor)&&!personal_has34($actor,'leave_finance')))foreach($rows as &$r){unset($r['amount']);$body=decode($r['body_json']);unset($body['amount'],$body['_valuation']);$r['body_json']=jencode($body);if(isset($r['_historical33'])){$hb=decode($r['_historical33']['body_json']);unset($hb['amount'],$hb['_valuation']);$r['_historical33']['body_json']=jencode($hb);}}unset($r);
  }
  if($tab==='leave_cash'&&personal_mode34($actor))foreach($rows as &$cash198)if(($cash198['status']??'')!=='approved'){unset($cash198['amount']);$body198=decode($cash198['body_json']);unset($body198['amount'],$body198['_valuation']);$cash198['body_json']=jencode($body198);}unset($cash198);
  $out['tabs'][$tab]=$rows;
 }
 if(!$history140&&profile_access33($actor,$id,'payroll')){$out['allowances393']=allowance_at($staff,local_time()->format('Y-m-d'));$out['allowance_terms393']=query("SELECT date_from,date_to,body_json FROM ep_records WHERE user_id=? AND module='allowance_terms' AND status='approved' ORDER BY date_from DESC,id DESC",[$id])->fetchAll();$out['allowances_edit393']=!personal_mode34($actor)&&($actor['role']==='admin'||(permission_has($actor,'allowances','create')&&in_array($id,permission_scope29($actor,'allowances','create'),true)));}
 if(!$history140&&$actor['role']==='admin')$out['employment136']=query("SELECT * FROM ep_records WHERE user_id=? AND module='employment' AND status<>'archived' ORDER BY date_from DESC,id DESC",[$id])->fetchAll();
 if(!$history140&&$actor['role']==='admin')$out['contract_terms119']=query("SELECT date_from,body_json,created_at FROM ep_records WHERE user_id=? AND module='contract_terms119' AND status='approved' ORDER BY date_from DESC,id DESC",[$id])->fetchAll();
 if(!$history140&&$actor['role']==='admin')$out['editor_dates130']=employee_editor_dates130($staff);
 if($editor140){require_once __DIR__.'/benefits.php';$out['benefits198']=employee_benefits198($staff);$out['history_deferred140']=true;}
 return $out;
}
