<?php
declare(strict_types=1);
// Read-only projection of immutable imported payroll facts; never recomputes a source net.
function pay_history123(array $v):array {
 $parts=$v['parts']??[];$sum=static fn(string $k):float=>round(array_sum(array_map(fn($p)=>(float)($p[$k]??0),$parts)),3);
 $rates=[];foreach(['telephone','transport','housing']as $k)$rates[$k]=$parts?(float)($parts[count($parts)-1][$k.'_rate']??0):0;
 $lines=[];foreach(['contract_commission'=>'delivery_contract','employee_sales_commission'=>'staff_contract119']as $field=>$type)if($sum($field))$lines[]=['type'=>$type,'base'=>0,'amount'=>$sum($field)];foreach($v['historical_commissions106']??[]as $c)$lines[]=['type'=>$c['type'],'base'=>$c['base']??0,'amount'=>$c['gross']];
 return $v+['historical123'=>true,'reference_salary35'=>$v['basic_rate']??($parts[count($parts)-1]['basic_rate']??$v['salary']),'reference_allowances35'=>$rates,'allowances'=>$rates,'friday'=>['dates'=>[],'amount'=>$sum('friday_amount'),'lines'=>[]],'commission_lines35'=>$lines,'afaq_commission123'=>$sum('afaq_commission123'),'afaq_extra_commission123'=>$sum('afaq_extra_commission123'),'debt_deduction'=>$v['loan_repayment']??$sum('loan_repayment'),'absence_deduction'=>$sum('absence_deduction'),'sick_deduction'=>$sum('sick_deduction'),'attendance_deduction'=>round($sum('lateness_deduction')+$sum('absence_deduction')+$sum('sick_deduction'),3),'extra_amount'=>0,'penalty35'=>0,'leave_cash_items'=>[],'friday_lateness_deduction'=>0,'contract_lateness_deduction'=>0,'flags'=>[]];
}
