<?php
declare(strict_types=1);
function public_base_url():string{
 $configured=rtrim((string)(settings()['site_url']??''),'/');if($configured!==''&&preg_match('~^https://[^\s]+$~',$configured))return $configured.'/';
 $host=(string)($_SERVER['HTTP_HOST']??'localhost');if(!preg_match('/^[a-zA-Z0-9.-]+(?::[0-9]+)?$/D',$host))$host='localhost';
 $path=str_replace('\\','/',dirname((string)($_SERVER['SCRIPT_NAME']??'/index.php')));if(str_ends_with($path,'/hr'))$path=dirname($path);return 'https://'.$host.($path==='/'?'':rtrim($path,'/')).'/';
}
function public_seo_apply(DOMDocument $document,DOMXPath $xpath,array $page,?array $offer,string $slug,string $language,string $company,bool $preview,bool $receipt):void{
 $base=public_base_url();$url=$base.'index.php?'.http_build_query(['page'=>$slug,'lang'=>$language]);if($offer)$url.='&offer='.(int)$offer['id'];$content=$offer?decode($offer['content_json']):$page;
 $title=pv($content,'title').' — '.(setting_text('public_site_title')?:$company);$description=trim(pv($content,$offer?'subtitle':'description'));if($description==='')$description=mb_substr(strip_tags(pv($content,'body')),0,170);
 $head=$xpath->query('//head')->item(0);foreach($xpath->query('//title')as $node)$node->nodeValue=$title;
 foreach($xpath->query('//meta[@name="description"]')as $node)$node->setAttribute('content',$description);
 $meta=function(string $key,string $value,bool $property=false)use($document,$head){$node=$document->createElement('meta');$node->setAttribute($property?'property':'name',$key);$node->setAttribute('content',$value);$head->appendChild($node);};
 if($preview||$receipt){$meta('robots','noindex,nofollow');return;}
 $canonical=$document->createElement('link');$canonical->setAttribute('rel','canonical');$canonical->setAttribute('href',$url);$head->appendChild($canonical);
 foreach(public_available_languages($slug==='offers'?'offers':'site')as $code=>$label){$node=$document->createElement('link');$node->setAttribute('rel','alternate');$node->setAttribute('hreflang',$code==='fil'?'fil':$code);$node->setAttribute('href',$base.'index.php?'.http_build_query(['page'=>$slug,'lang'=>$code]).($offer?'&offer='.(int)$offer['id']:''));$head->appendChild($node);}
 $meta('og:type','website',true);$meta('og:title',$title,true);$meta('og:description',$description,true);$meta('og:url',$url,true);$meta('og:site_name',$company,true);$meta('twitter:card','summary');$meta('twitter:title',$title);$meta('twitter:description',$description);
 $image=(int)($offer['image_id']??settings()['logo_id']);if($image){$meta('og:image',$base.'hr/file.php?id='.$image,true);$meta('og:image:alt',$offer?pv($content,'title'):$company,true);$meta('twitter:image',$base.'hr/file.php?id='.$image);}
}
