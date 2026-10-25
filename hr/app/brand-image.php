<?php
declare(strict_types=1);
function brand_display_file(string $path,string $mime):array {
 $hash=hash_file('sha256',$path);$seed=json_decode((string)@file_get_contents(__DIR__.'/brand-preview.json'),true);
 if($hash&&hash_equals((string)($seed['sha256']??''),$hash)&&is_file(__DIR__.'/brand-preview.png'))return [__DIR__.'/brand-preview.png','image/png'];
 if(!function_exists('imagecreatefromstring')||!in_array($mime,['image/png','image/jpeg','image/webp'],true))return [$path,$mime];
 $info=@getimagesize($path);if(!$info||$info[0]*$info[1]>24000000||max($info[0],$info[1])<=320)return [$path,$mime];
 $dir=EP_ROOT.'/private/brand-cache';if(!is_dir($dir)&&!@mkdir($dir,0700,true)&&!is_dir($dir))return [$path,$mime];
 $target=$dir.'/'.$hash.'-320.png';if(is_file($target))return [$target,'image/png'];
 $source=@imagecreatefromstring((string)file_get_contents($path));if(!$source)return [$path,$mime];
 $scale=320/max(imagesx($source),imagesy($source));$w=max(1,(int)round(imagesx($source)*$scale));$h=max(1,(int)round(imagesy($source)*$scale));$canvas=imagecreatetruecolor($w,$h);imagealphablending($canvas,false);imagesavealpha($canvas,true);imagefill($canvas,0,0,imagecolorallocatealpha($canvas,0,0,0,127));imagecopyresampled($canvas,$source,0,0,0,0,$w,$h,imagesx($source),imagesy($source));
 $tmp=tempnam($dir,'brand-');$ok=$tmp!==false&&imagepng($canvas,$tmp,6);imagedestroy($canvas);imagedestroy($source);if($ok&&@rename($tmp,$target))return [$target,'image/png'];if($tmp&&is_file($tmp))@unlink($tmp);return [$path,$mime];
}
