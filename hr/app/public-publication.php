<?php
declare(strict_types=1);
/* Publication dependencies are calculated on each request; stored text is never deleted. */
function public_offers_state():array{
 static $state=null;if($state!==null)return $state;
 $page=query("SELECT id,visible FROM ep_cms_pages WHERE slug='offers'")->fetch();
 $section=$page?(bool)query('SELECT TOP (1) id FROM ep_cms_sections WHERE page_id=? AND visible=1',[$page['id']])->fetchColumn():false;
 $published=$page&&(bool)$page['visible']&&$section;
 $cards=$published?(int)query("SELECT COUNT(*) FROM ep_cms_cards c JOIN ep_cms_sections s ON s.id=c.section_id WHERE s.page_id=? AND s.visible=1 AND c.visible=1 AND c.card_type='offer'",[$page['id']])->fetchColumn():0;
 return $state=['page_enabled'=>(bool)$published,'has_offers'=>$cards>0];
}
function public_offers_references(string $text):bool{
 return (bool)preg_match('/(?:عروض|\\boffers?\\b|\\bpromos?\\b|\\balok\\b|ऑफ़र|ऑफर|पेशकश|پیشکش|آفر)/iu',$text);
}
function public_content_for_display(array $content):array{
 global $draft,$slug;static $cleanup=null;$cleanup??=decode(file_get_contents(__DIR__.'/customer-copy-cleanup.json'));
 foreach($content as $language=>&$fields){if(!is_array($fields))continue;foreach($fields as $field=>&$value){if(!is_string($value))continue;
  if(in_array($value,$cleanup['removed'][$language]??[],true))$value='';
  $value=$cleanup['replaced'][$language][$value]??$value;
  if($draft||$slug==='offers'||public_offers_state()['has_offers']||str_ends_with($field,'_url')||str_ends_with($field,'_without_offers')||in_array($field,['alt','requires_offers','nav_label'],true))continue;
  if(public_offers_references($value)){$alternate=(string)($fields[$field.'_without_offers']??'');$value=public_offers_references($alternate)?'':$alternate;}
 }unset($value);}unset($fields);return $content;
}

function public_row_displayable(array $row):bool{
 global $draft,$slug;if($draft||public_offers_state()['has_offers']||($row['_slug']??$slug)==='offers')return true;
 $content=decode($row['content_json']);if(($content['ar']['requires_offers']??'0')==='1')return false;
 foreach(['ar','en','fil','hi','ur']as $language)if(public_offers_references((string)($content[$language]['title']??'')))return false;
 return true;
}
function public_display_rows(array $rows):array{return array_values(array_filter($rows,'public_row_displayable'));}
function public_page_visible(string $slug):bool{
 if($slug==='offers'&&!public_offers_state()['page_enabled'])return false;
 return (bool)query('SELECT id FROM ep_cms_pages WHERE slug=? AND visible=1',[$slug])->fetchColumn();
}
function public_local_link_target(string $url):?array{
 $parts=parse_url(html_entity_decode($url,ENT_QUOTES,'UTF-8'));if($parts===false)return null;
 $host=strtolower((string)($parts['host']??''));$own=strtolower((string)parse_url((string)(settings()['site_url']??''),PHP_URL_HOST));$request=strtolower(explode(':',(string)($_SERVER['HTTP_HOST']??''))[0]);
 if($host!==''&&!in_array($host,[$own,$request],true))return null;
 $path=rawurldecode((string)($parts['path']??''));$configuredPath=(string)parse_url((string)(settings()['site_url']??''),PHP_URL_PATH);$base=rtrim($configuredPath,'/');if($base===''){$base=str_replace('\\','/',dirname((string)($_SERVER['SCRIPT_NAME']??'/index.php')));if(str_ends_with($base,'/hr'))$base=dirname($base);$base=$base==='/'?'':$base;}
 if(str_starts_with($path,'/')){if($base!==''&&!str_starts_with($path,$base.'/'))return null;$path=ltrim(substr($path,strlen($base)),'/');}
 while(str_starts_with($path,'./'))$path=substr($path,2);
 $routes=['index.html'=>'home','default.html'=>'home','about.html'=>'about','mission.html'=>'about','values.html'=>'about','how-it-works.html'=>'how-it-works','offers.html'=>'offers','contact.html'=>'contact'];
 parse_str((string)($parts['query']??''),$params);
 if(isset($routes[$path]))$target=$routes[$path];elseif($path==='index.php'||$path==='hr/public-preview.php'||($path===''&&isset($params['page'])))$target=(string)($params['page']??'home');else return null;
 return ['page'=>$target,'offer'=>$params['offer']??null,'language'=>isset(language_catalog()[(string)($params['lang']??'')])?(string)$params['lang']:null,'fragment'=>$parts['fragment']??''];
}
function public_resolve_link(string $url,bool $navigation=false):?string{
 global $draft,$lang;$target=public_local_link_target($url);if(!$target)return $url;
 if(!$draft&&(!public_page_visible($target['page'])||($target['page']==='offers'&&!$navigation&&!public_offers_state()['has_offers'])))return null;
 if($target['offer']!==null){if($target['page']!=='offers'||!preg_match('/^[0-9]+$/D',(string)$target['offer']))return null;
  if(!$draft&&!query("SELECT c.id FROM ep_cms_cards c JOIN ep_cms_sections s ON s.id=c.section_id JOIN ep_cms_pages p ON p.id=s.page_id WHERE c.id=? AND c.card_type='offer' AND p.slug='offers' AND c.visible=1 AND s.visible=1 AND p.visible=1",[(int)$target['offer']])->fetchColumn())return null;
 }
 return page_url($target['page'],$target['language']??$lang).($target['offer']!==null?'&offer='.(int)$target['offer']:'').($target['fragment']!==''?'#'.rawurlencode($target['fragment']):'');
}
