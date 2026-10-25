<?php
declare(strict_types=1);
// HR-only transport. Never read public translation.php, credentials, jobs or cache.
// Google Translate transport follows the owner's ORANGE reference; no API key is read.
function hr_translation_config160():array{return ['key'=>'','plan'=>'google'];}
function hr_translation_key160():string{return 'google-no-key';} // Legacy readiness callers only; not a credential.
function hr_translation_key_save160(array $u,array $b):array {owner_only31($u);fail('ترجمة Google لا تتطلب مفتاحًا.');}
function hr_translation_google171(string $text,string $from,string $to):string {
 $url='https://translate.googleapis.com/translate_a/single?client=gtx&sl='.rawurlencode($from).'&tl='.rawurlencode($to).'&dt=t&q='.rawurlencode($text);
 if(function_exists('curl_init')){$h=curl_init($url);curl_setopt_array($h,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>15,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);$raw=curl_exec($h);$status=(int)curl_getinfo($h,CURLINFO_HTTP_CODE);curl_close($h);}
 else{$ctx=stream_context_create(['http'=>['timeout'=>15,'ignore_errors'=>true,'follow_location'=>0],'ssl'=>['verify_peer'=>true,'verify_peer_name'=>true]]);$raw=@file_get_contents($url,false,$ctx);preg_match('/^HTTP\/\S+ (\d+)/',$http_response_header[0]??'',$m);$status=(int)($m[1]??0);}
 if($status!==200||!is_string($raw)||strlen($raw)>262144)throw new RuntimeException($status===429?'hr_translation_rate_limit':'hr_translation_unreachable');
 try{$json=decode($raw);}catch(Throwable $e){throw new RuntimeException('hr_translation_invalid_response');}
 if(!is_array($json[0]??null))throw new RuntimeException('hr_translation_invalid_response');$out='';foreach($json[0]as $part)if(is_array($part)&&is_string($part[0]??null))$out.=$part[0];if(trim($out)==='')throw new RuntimeException('hr_translation_invalid_response');return $out;
}
function hr_translation_http160(array $payload):array {
 $from=strtolower((string)$payload['source_lang']);$to=strtolower((string)$payload['target_lang']);if($to==='en-gb')$to='en';$translations=[];
 foreach($payload['text']as $xml){
  $doc=new DOMDocument();if(!@$doc->loadXML($xml,LIBXML_NONET)||$doc->documentElement?->tagName!=='p')throw new RuntimeException('hr_translation_invalid_xml');
  $keep=[];foreach(iterator_to_array($doc->getElementsByTagName('keep'))as $node){$marker='ZXQHRKEEP'.bin2hex(random_bytes(6)).'QXZ';$keep[$marker]=$node->textContent;$node->parentNode->replaceChild($doc->createTextNode($marker),$node);}
  $text=$doc->documentElement->textContent;$result='';$length=mb_strlen($text);$offset=0;
  while($offset<$length){$chunk=mb_substr($text,$offset,1000);if($offset+mb_strlen($chunk)<$length){$cut=mb_strrpos($chunk,' ');if($cut!==false&&$cut>500)$chunk=mb_substr($chunk,0,$cut+1);$start=mb_strrpos($chunk,'ZXQHRKEEP');if($start!==false&&!str_contains(mb_substr($chunk,$start),'QXZ'))$chunk=mb_substr($chunk,0,$start);}
   if($chunk==='')throw new RuntimeException('hr_translation_invalid_response');preg_match('/^\s*/u',$chunk,$lead);preg_match('/\s*$/u',$chunk,$tail);$result.=trim($chunk)===''?$chunk:($lead[0]??'').hr_translation_google171(trim($chunk),$from,$to).($tail[0]??'');$offset+=mb_strlen($chunk);}
  foreach($keep as $marker=>$value){if(substr_count($result,$marker)!==1)throw new RuntimeException('hr_translation_protected_value_changed');$result=str_replace($marker,$value,$result);}
  $translations[]=['text'=>'<p>'.htmlspecialchars($result,ENT_QUOTES|ENT_XML1,'UTF-8').'</p>'];
 }return ['translations'=>$translations];
}
function hr_translation_tokens160(string $text):array {preg_match_all('/[0-9٠-٩]+(?:[.,:\/-][0-9٠-٩]+)*|\{\{[^{}]+\}\}/u',$text,$matches);$tokens=$matches[0];sort($tokens);return $tokens;}
function hr_translation_batch160(array $texts,string $context,string $sourceLanguage='AR',string $targetLanguage='EN-GB',bool $allowExistingEnglish170=false):array {
 if(!in_array([$sourceLanguage,$targetLanguage],[['AR','EN-GB'],['EN','AR']],true))throw new RuntimeException('hr_translation_invalid_direction');
 $size=0;$xml=[];foreach($texts as $source){$size+=mb_strlen($source);$escaped=htmlspecialchars($source,ENT_QUOTES|ENT_XML1,'UTF-8');$xml[]='<p>'.preg_replace('/(\{\{[^{}]+\}\}|[0-9٠-٩]+(?:[.,:\/-][0-9٠-٩]+)*)/u','<keep>$1</keep>',$escaped).'</p>';}
 // Do not hold the site's shared backup lock while waiting on an external service.
 $released160=false;$operation160=$GLOBALS['EP_OPERATION_LOCK']??null;
 if(is_resource($operation160)&&empty($GLOBALS['EP_OPERATION_EXCLUSIVE'])){flock($operation160,LOCK_UN);fclose($operation160);unset($GLOBALS['EP_OPERATION_LOCK']);$released160=true;}
 try{$result=hr_translation_http160(['text'=>$xml,'source_lang'=>$sourceLanguage,'target_lang'=>$targetLanguage,'context'=>'Kuwait employee administration. Professional '.($targetLanguage==='AR'?'Arabic':'English').'. Text type: '.$context.'. Preserve numbers, dates and placeholders. Do not invent details.','tag_handling'=>'xml','ignore_tags'=>['keep'],'preserve_formatting'=>true]);}finally{if($released160)operation_lock();}
 if(count($result['translations']??[])!==count($texts))throw new RuntimeException('hr_translation_incomplete_response');$out=[];$i=0;
 foreach($texts as $id=>$source){$xmlText=$result['translations'][$i++]['text']??null;if(!is_string($xmlText)||preg_match('/<!DOCTYPE|<!ENTITY/i',$xmlText))throw new RuntimeException('hr_translation_invalid_response');$doc=new DOMDocument();if(!@$doc->loadXML($xmlText,LIBXML_NONET)||$doc->documentElement?->tagName!=='p')throw new RuntimeException('hr_translation_invalid_xml');$target=trim($doc->textContent);if($target===''||($target===trim($source)&&!($allowExistingEnglish170&&$targetLanguage==='EN-GB'&&!preg_match('/\p{Arabic}/u',$source)))||hr_translation_tokens160($source)!==hr_translation_tokens160($target))throw new RuntimeException('hr_translation_protected_value_changed');$out[$id]=$target;}
 return $out;
}
