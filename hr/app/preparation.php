<?php
declare(strict_types=1);
// Enabled only by the owner's initial snapshot; operational data ends this allowance.
function preparation_entry40():bool {
 $cfg=settings();if(empty($cfg['preparation_entry40']))return false;
 foreach(['operation_start','attendance_start','payroll_start','payroll_start_month']as $key)if(!empty($cfg[$key]))return false;
 foreach(['ep_events','ep_payroll','ep_bank_payroll']as $table)if(query('SELECT TOP (1) id FROM '.$table.' WITH (HOLDLOCK)')->fetch())return false;
 return true;
}
function preparation_effective40(array $staff,string $kind):?string {
 if(!preparation_entry40())return null;
 $date=query('SELECT TOP (1) period_key FROM ep_history WHERE kind=? AND employee_code=? AND period_key<=? ORDER BY period_key DESC,id DESC',[$kind,employee_key($staff),local_time()->format('Y-m-d')])->fetchColumn();
 return $date?:($staff['hired_on']??null);
}
