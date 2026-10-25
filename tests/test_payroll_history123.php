<?php
declare(strict_types=1);
require __DIR__.'/../hr/app/payroll-history.php';
require __DIR__.'/../hr/app/payroll-export.php';
function decode($s){return json_decode($s,true,512,JSON_THROW_ON_ERROR);}
$v=['salary'=>100,'addition'=>85,'deduction'=>5,'net'=>180,'work_company_code'=>'CMP-0003','parts'=>[['basic_rate'=>100,'friday_amount'=>20,'afaq_commission123'=>30,'afaq_extra_commission123'=>35,'loan_repayment'=>2,'lateness_deduction'=>3]]];$c=pay_history123($v);
if($c['afaq_commission123']!==30.0||$c['afaq_extra_commission123']!==35.0||$c['friday']['amount']!==20.0||$c['net']!==180||array_sum(payroll_display_groups35($c))!==85.0||$c['debt_deduction']!==2.0||$c['contract_lateness_deduction']!==0||$c['friday_lateness_deduction']!==0)throw new RuntimeException('Historical source projection mismatch');
if($v['parts'][0]['afaq_commission123']!==30)throw new RuntimeException('Source mutated');
echo 'PASS historical independent commission fields, exact source net, full Fridays and commissions, separate debt/lateness, display additions reconciled without duplication and read-only source'.PHP_EOL;
