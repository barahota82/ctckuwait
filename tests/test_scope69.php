<?php
foreach([__DIR__.'/../site',__DIR__.'/../SITE_FILES',__DIR__.'/..']as $root)if(is_file($root.'/hr/app/operations.php')){require $root.'/hr/app/operations.php';break;}
$cases=[[[1,3,8],3,[3]],[[1,3,8],99,[]],[[],3,[]],[[1,3,8],0,[1,3,8]],[['3',8],3,['3']]];
foreach($cases as [$ids,$selected,$expected])if(employee_scope69($ids,$selected)!==$expected)throw new Exception('scope mismatch');echo "PASS 5 authorized scope intersection checks\n";
