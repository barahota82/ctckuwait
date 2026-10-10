<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/app/core.php';require __DIR__.'/app/backups.php';
try {
 if(!app_installed())throw new RuntimeException('Application is not installed.');operation_lock();backup_exclusive();$c=backup_config();backup_clean_temporary();$c['last_worker']=utc_now();backup_private_write(EP_ROOT.'/private/backup-config.json',$c);
 if(!$c['enabled'])exit; $now=local_time();$last=!empty($c['last_scheduled'])?local_time($c['last_scheduled']):null;
 $due=backup_due($c,$now,$last);
 if($due){backup_run(null,'scheduled');fwrite(STDOUT,"Backup completed.\n");}
}catch(Throwable $e){backup_log('scheduled','failed');security_log($e,'EP backup worker');fwrite(STDERR,"Backup failed; check the private server log.\n");exit(1);}
