<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/core.php';
if(!app_installed()){fwrite(STDERR,"Install first.\n");exit(1);}
$cfg=settings();$days=max(1,(int)$cfg['retention_days']);
$cutoff=gmdate('Y-m-d H:i:s',time()-$days*86400);
db()->beginTransaction();
$count=query('DELETE FROM ep_locations WHERE received_at<? AND proof_id IS NULL',[$cutoff])->rowCount();
query('DELETE FROM ep_login_limits WHERE blocked_until<?',[time()-86400]);
audit('maintenance','tracking',['removed'=>$count,'cutoff'=>$cutoff]);db()->commit();
echo "Completed. Removed periodic tracking points: ".$count."\n";
