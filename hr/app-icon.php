<?php
declare(strict_types=1);
require __DIR__.'/app/core.php';
$cfg=settings();$size=(int)($_GET['size']??192);if(!in_array($size,[180,192,512],true))$size=192;
$file=query('SELECT * FROM ep_files WHERE id=?',[(int)($cfg['logo_id']??0)])->fetch();
$path=$file?EP_ROOT.'/private/files/'.$file['disk_name']:'';
if(!$file||!is_file($path)||!in_array($file['mime'],['image/png','image/jpeg','image/webp'],true)){http_response_code(404);exit;}
header('X-Content-Type-Options: nosniff');header('Cache-Control: public,max-age=3600');
if(!function_exists('imagecreatefromstring')){header('Content-Type: '.$file['mime']);readfile($path);exit;}
$source=@imagecreatefromstring(file_get_contents($path));if(!$source){http_response_code(404);exit;}
$canvas=imagecreatetruecolor($size,$size);imagefill($canvas,0,0,imagecolorallocate($canvas,15,15,15));$scale=min($size*.8/imagesx($source),$size*.8/imagesy($source));$w=max(1,(int)round(imagesx($source)*$scale));$h=max(1,(int)round(imagesy($source)*$scale));imagecopyresampled($canvas,$source,(int)(($size-$w)/2),(int)(($size-$h)/2),0,0,$w,$h,imagesx($source),imagesy($source));header('Content-Type: image/png');imagepng($canvas);imagedestroy($canvas);imagedestroy($source);
