<?php
declare(strict_types=1);
// Explicit correction of the existing historical source; never creates daily duplicates.
function finance_history_save114(array $u,string $key,string $date,array $day,array $rows):array {
 $kind=finance_kinds103()[$key];$ids=permission_has($u,$key,'edit')?finance_scope103($u,$key,'edit'):[];$before=[];$after=[];$seen=[];$month=substr($date,0,7);
 foreach($rows as $row){if(!is_array($row)||!isset($row['user_id'])||!is_numeric($row['user_id'])||(int)$row['user_id']!=(float)$row['user_id']||(int)$row['user_id']<0||!is_array($row['returns']??[]))fail('صف غير صالح.');$uid=(int)($row['user_id']??0);if(!$uid)continue;if(isset($seen[$uid]))fail('صف مكرر.');$seen[$uid]=true;
  $old=array_values(array_filter($day['legacy'],fn($v)=>(int)$v['user_id']===$uid));
  $delivered=(int)finance_number103($row['delivered']??0,true);$amount=finance_number103($row['amount']??0);$returns=[];
  foreach($row['returns']??[]as $origin=>$qty){pay35_month((string)$origin);if($origin>$month||$origin<'2017-01')fail('شهر العقد الأصلي غير صالح.');$qty=(int)finance_number103($qty,true);if($qty)$returns[$origin]=$qty;}
  if(!$old){
   $daily=array_values(array_filter($day['values'],fn($v)=>(int)$v['user_id']===$uid));if(count($daily)>1)fail('تسجيل يومي متداخل.');$previous=$daily[0]??null;$zero=!$delivered&&!$amount&&!array_sum($returns);if(!$previous&&$zero)continue;
   $op=$previous?'edit':'create';if(!in_array($uid,finance_scope103($u,$key,$op),true)||!in_array($uid,array_column($day['people'],'user_id'),true))fail('الموظف خارج صلاحية التسجيل.',403);
   $staff=employee_required($uid);$company=(string)(service_state($staff,$date)['company_code']??'');if(!company_allowed30($u,$key,$company,$op))fail('الشركة خارج الصلاحية.',403);
   pay35_lock($uid,$month);if(finance_legacy103([$uid],$key,$month.'-01',pay35_month($month)))fail('يوجد مصدر شهري سابق للموظف؛ لا تضف مصدرًا مكررًا.');
   $values=['key'=>$key,'kind'=>$kind,'company_code'=>$company,'delivered'=>$kind==='collection'?0:$delivered,'returns'=>$kind==='collection'?[]:$returns,'amount'=>$kind==='collection'?$amount:0];if($previous&&array_intersect_key($previous,$values)==$values)continue;financial_invalidate($uid,$month.'-01');
   if($previous){query('UPDATE ep_records SET body_json=?,amount=?,status=?,updated_at=? WHERE id=?',[jencode($values),$values['amount'],$zero?'cancelled':'approved',utc_now(),$previous['id']]);$id=$previous['id'];$before[]=$previous;}else $id=v26_insert('finance_daily103',$uid,$date,null,$values,'approved',$values['amount']);$after[]=['id'=>$id,'body'=>$values];continue;
  }
  if(count($old)!==1)fail('توجد عدة مصادر تاريخية لهذا الموظف؛ يلزم تصحيح كل مصدر دون دمج صامت.');$old=$old[0];
  if(($kind==='collection'&&(float)$old['amount']===$amount)||($kind!=='collection'&&(int)$old['delivered']===$delivered&&($old['returns']??[])==$returns))continue;
  if(!in_array($uid,$ids,true))fail('الموظف خارج صلاحية التعديل.',403);
  if(query("SELECT id FROM ep_payroll WHERE user_id=? AND month_key>=? AND status IN ('approved','submitted')",[$uid,$month])->fetch()||query("SELECT id FROM ep_bank_payroll WHERE user_id=? AND month_key>=? AND status='approved'",[$uid,$month])->fetch())fail('يوجد كشف تشغيلي معتمد؛ أعده للتعديل قبل تصحيح المصدر.');
  if(!preg_match('/^month-(\d+)-/',$old['id'],$match))fail('نوع المصدر السابق لا يتيح تصحيحًا آمنًا من هذه النافذة.');
  $record=query("SELECT * FROM ep_records WHERE id=? AND module='commission_month' AND user_id=?",[(int)$match[1],$uid])->fetch();if(!$record)fail('مصدر غير موجود.');$v=decode($record['body_json']);$company=(string)($v['work_company_code']??service_state(employee_required($uid),$date)['company_code']??'');if(!company_allowed30($u,$key,$company,'edit'))fail('الشركة خارج صلاحية التعديل.',403);$matched=false;$grossDelta=0.0;$netDelta=0.0;
  foreach($v['lines']as &$line){if(($line['type']??'')!==$kind)continue;if($matched)fail('مصدر تاريخي متداخل.');$matched=true;$component=$line['historical_component106']??null;if(!$component||!isset($component['rate']))fail('سعر العمولة التاريخي غير متوفر للتصحيح الآمن.');
   $newBase=$kind==='collection'?$amount:$delivered-array_sum($returns);if($newBase<0)fail('المرتجع يتجاوز العقود المسلمة.');
   $gross=round((float)$line['amount']+($newBase-(float)$line['base'])*(float)$component['rate']/($kind==='collection'?100:1),3);$loan=(float)$component['loan_repayment'];if($gross<0||$gross<$loan)fail('العمولة بعد التصحيح أقل من سداد السلفة المسجل؛ راجع التسوية المالية.');
   $grossDelta=round($gross-(float)$component['gross'],3);$netDelta=$grossDelta;$component['base']=$newBase;$component['gross']=$gross;$component['net']=round($gross-$loan,3);$component['paid_amount']=(float)($component['paid_amount']??0);$component['paid']=abs($component['net']-$component['paid_amount'])<0.0005;$component['unpaid_amount']=max(0,round($component['net']-$component['paid_amount'],3));$component['overpaid_amount']=max(0,round($component['paid_amount']-$component['net'],3));$component['payment_status']=$component['paid']?'paid_in_full':($component['overpaid_amount']>0?'overpaid':'partial');$component['source_total_preserved']=false;
   if($kind!=='collection'){$component['delivered']=$delivered;$component['returned']=array_sum($returns);$component['returns']=$returns;}
   $component['corrected_at114']=utc_now();$line['base']=$newBase;$line['amount']=$gross;$line['source_total_preserved']=false;$line['historical_component106']=$component;
   $staff=employee_required($uid);$h=query("SELECT * FROM ep_history WHERE kind='payroll' AND employee_code=? AND period_key=?",[employee_key($staff),$month])->fetch();if(!$h)fail('كشف الشهر التاريخي المرتبط غير موجود.');$hv=decode($h['body_json']);$found=false;
   foreach($hv['historical_commissions106']??[]as $i=>$hc)if(($hc['type']??'')===$kind&&((int)($hc['commission_record_id']??0)===(int)$record['id'])){$hv['historical_commissions106'][$i]=$component+['commission_record_id'=>(int)$record['id']];$found=true;}
   if(!$found)fail('ربط العمولة بكشفها التاريخي غير مكتمل؛ لم يحفظ التصحيح.');
   foreach(['addition','net']as $f)$hv[$f]=round((float)($hv[$f]??0)+$grossDelta,3);
   $hv['unpaid_amount']=max(0,round((float)$hv['net']-(float)($hv['paid_amount']??0),3));$hv['overpaid_amount']=max(0,round((float)($hv['paid_amount']??0)-(float)$hv['net'],3));$hv['paid']=abs((float)$hv['net']-(float)($hv['paid_amount']??0))<0.0005;$hv['payment_status']=$hv['paid']?'paid_in_full':($hv['overpaid_amount']>0?'overpaid':'partial');
   if(abs((float)$hv['salary']+(float)$hv['addition']-(float)$hv['deduction']-(float)$hv['net'])>0.0005)fail('كشف الشهر غير متوازن.');
   query('UPDATE ep_history SET body_json=? WHERE id=?',[jencode($hv),$h['id']]);
  }unset($line);
  if(!$matched)fail('نوع العمولة غير موجود بالمصدر.');$v['total']=round((float)$v['total']+$grossDelta,3);$v['net']=round((float)$v['net']+$netDelta,3);$v['unpaid_amount']=max(0,round((float)$v['net']-(float)($v['paid_amount']??0),3));$v['overpaid_amount']=max(0,round((float)($v['paid_amount']??0)-(float)$v['net'],3));$v['paid']=abs((float)$v['net']-(float)($v['paid_amount']??0))<0.0005;$v['payment_status']=$v['paid']?'paid_in_full':($v['overpaid_amount']>0?'overpaid':'partial');
  query('UPDATE ep_records SET body_json=?,amount=?,updated_at=? WHERE id=?',[jencode($v),$v['total'],utc_now(),$record['id']]);financial_invalidate($uid,$month.'-01');$before[]=$record;$after[]=['id'=>$record['id'],'body'=>$v];
 }
 foreach([...$day['legacy'],...$day['values']]as $old)if(!isset($seen[(int)$old['user_id']]))fail('صف سابق مفقود؛ أعد الاستعلام.');
 if($after)audit('finance_history_correct114',$key.':'.$date,['before'=>$before,'after'=>$after]);return ['ok'=>true,'changed'=>count($after)];
}
