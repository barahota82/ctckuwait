<?php
declare(strict_types=1);
function decode($s){return json_decode($s,true,512,JSON_THROW_ON_ERROR);}
function mock_fetch171($url,$unused,$ctx){global $http_response_header;$http_response_header=['HTTP/1.1 '.$GLOBALS['status171']];$GLOBALS['urls171'][]=$url;parse_str(parse_url($url,PHP_URL_QUERY),$q);if($GLOBALS['invalid171'])return '{}';$text=$q['q'];$target=strtr($text,['مدير مبيعات'=>'Sales manager','ملاحظة'=>'Note','Sales manager'=>'مدير مبيعات']);return json_encode([[[$target,$text]]],JSON_UNESCAPED_UNICODE);}
$s=file_get_contents(__DIR__.'/../hr/app/hr-translation-provider.php');$s=str_replace("function_exists('curl_init')",'false',$s);$s=str_replace('@file_get_contents($url,false,$ctx)','mock_fetch171($url,false,$ctx);$http_response_header=$GLOBALS[\'http_response_header\']',$s);eval(substr($s,5));
$status171=200;$invalid171=false;$urls171=[];$n171=0;function check171($v,$msg){if(!$v)throw new LogicException($msg);$GLOBALS['n171']++;}
check171(hr_translation_batch160(['مدير مبيعات 25.000 {{employee}}'],'test')['0']==='Sales manager 25.000 {{employee}}','AR to EN protected values');
check171(hr_translation_batch160(['Sales manager'],'test','EN','AR')[0]==='مدير مبيعات','EN to AR');
$long=str_repeat('ملاحظة ',400).' 12/03/2026';$out=hr_translation_batch160([$long],'test')[0];check171(str_contains($out,'12/03/2026')&&substr_count($out,'Note')===400,'chunked long text');
foreach($urls171 as $url){parse_str(parse_url($url,PHP_URL_QUERY),$q);check171(parse_url($url,PHP_URL_HOST)==='translate.googleapis.com'&&$q['client']==='gtx'&&!isset($q['key'])&&mb_strlen($q['q'])<=1000,'fixed Google host, no key, bounded chunks');}
$status171=429;try{hr_translation_batch160(['ملاحظة'],'test');throw new LogicException('expected rate limit');}catch(RuntimeException $e){check171($e->getMessage()==='hr_translation_rate_limit','429 is not fake success');}
$status171=200;$invalid171=true;try{hr_translation_batch160(['ملاحظة'],'test');throw new LogicException('expected invalid response');}catch(RuntimeException $e){check171($e->getMessage()==='hr_translation_invalid_response','invalid response rejected');}
echo 'PASS '.$n171.' Google transport parsing, no-key URL, bidirection, protected values, chunking and failure checks'.PHP_EOL;
