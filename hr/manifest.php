<?php
declare(strict_types=1);
require __DIR__.'/app/core.php';
header('Content-Type: application/manifest+json; charset=utf-8');header('Cache-Control: no-cache');header('X-Content-Type-Options: nosniff');
$cfg=settings();$name=trim((string)($cfg['app_name31']??$cfg['company_name_en']??$cfg['company_name']));
$file=query('SELECT mime FROM ep_files WHERE id=?',[(int)$cfg['logo_id']])->fetch();$icons=[];
if($file){if(function_exists('imagecreatefromstring'))foreach([192,512]as $size)$icons[]=['src'=>'app-icon.php?size='.$size.'&v='.(int)$cfg['logo_id'],'sizes'=>$size.'x'.$size,'type'=>'image/png','purpose'=>'any'];else $icons[]=['src'=>'file.php?id='.(int)$cfg['logo_id'],'sizes'=>'any','type'=>$file['mime'],'purpose'=>'any'];}
echo jencode(['id'=>'./','name'=>$name,'short_name'=>$name,'start_url'=>'./','scope'=>'./','display'=>'standalone','background_color'=>'#0f0f0f','theme_color'=>'#0f0f0f','icons'=>$icons]);
