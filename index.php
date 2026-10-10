<?php
declare(strict_types=1);
require __DIR__.'/hr/app/core.php';
require __DIR__.'/hr/app/languages.php';
require __DIR__.'/hr/app/customers.php';
require __DIR__.'/hr/app/public-interface.php';
require_once __DIR__.'/hr/app/public-publication.php';
if(!app_installed()){header('Location: hr/install.php');exit;}
if(!secure_request()){http_response_code(403);exit('افتح الموقع باستخدام HTTPS.');}
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; script-src 'self'; style-src 'self' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'");
header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');header('Referrer-Policy: same-origin');
$draft=false;if(!empty($_GET['preview'])){session_init();try{$viewer=current_user();admin_only($viewer);$draft=true;}catch(Throwable $e){http_response_code(403);exit('هذه المعاينة للمشرف العام فقط.');}}
if($_SERVER['REQUEST_METHOD']==='GET'){db()->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');db()->beginTransaction();}
$cfg=settings();$languages=$draft?array_intersect_key(array_map(fn($v)=>$v['name'],language_catalog()),array_flip($cfg['public_languages'])):public_available_languages((($_GET['page']??'home')==='offers')?'offers':'site');
$requestedPage=(string)($_GET['page']??'home');if($requestedPage==='offers')$languages=array_intersect_key($languages,array_flip($cfg['offers_languages']));if(!$languages)$languages=['ar'=>'العربية'];
$defaultLanguage=$requestedPage==='offers'?$cfg['offers_default_language']:$cfg['public_default_language'];
$lang=(string)($_GET['lang']??$_COOKIE['PUBLIC_LANGUAGE']??$defaultLanguage);if(!isset($languages[$lang]))$lang=isset($languages[$defaultLanguage])?$defaultLanguage:array_key_first($languages);
setcookie('PUBLIC_LANGUAGE',$lang,['expires'=>time()+31536000,'path'=>'/','secure'=>true,'httponly'=>true,'samesite'=>'Lax']);
$brand=$lang==='ar'?$cfg['company_name']:$cfg['company_name_en'];
function ph(mixed $v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function pt(array $content,string $field):string{global $lang,$brand;return str_replace(['{{company}}','{{YEAR}}'],[$brand,date('Y')],(string)($content[$lang][$field]??$content['en'][$field]??$content['ar'][$field]??''));}
function pv(array $content,string $field):string{global $lang,$brand;$value=$content[$lang][$field]??'';if($value==='')$value=($content['ar'][$field]??'')?:($content['en'][$field]??'');return str_replace(['{{company}}','{{YEAR}}'],[$brand,date('Y')],(string)$value);}
function setting_text(string $key):string{global $cfg,$lang,$brand;$v=$cfg[$key]??[];return str_replace(['{{company}}','{{YEAR}}'],[$brand,date('Y')],(string)($v[$lang]??$v['ar']??$v['en']??''));}
function page_url(string $slug,string $language):string{global $draft;return ($draft?'hr/public-preview.php?':'index.php?').http_build_query(['page'=>$slug,'lang'=>$language]);}
function single_button_html(array $content):string{$url=public_resolve_link(pv($content,'button_url'));$label=pv(public_content_for_display($content),'button_label');if(!$url||!$label)return '';return '<a class="public-button btn btn--primary" href="'.ph($url).'">'.ph($label).'</a>';}

function button_html(array $content):string{$out=single_button_html($content);$secondary=[];foreach($content as $language=>$fields)$secondary[$language]=['button_label'=>$fields['secondary_button_label']??'','button_url'=>$fields['secondary_button_url']??''];return $out.single_button_html($secondary);}
$slug=(string)($_GET['page']??'home');$page=query('SELECT * FROM ep_cms_pages WHERE slug=?'.($draft?'':' AND visible=1'),[$slug])->fetch();
if(!$draft&&$slug==='offers'&&!public_offers_state()['page_enabled'])$page=false;
if(!$page){http_response_code(404);$page=['id'=>0,'content_json'=>jencode([$lang=>['title'=>$cfg['public_ui'][$lang]['page_unavailable']??$cfg['public_ui']['ar']['page_unavailable']]])];}
$pc=public_content_for_display(decode($page['content_json']));$nav=query('SELECT slug,content_json FROM ep_cms_pages WHERE '.($draft?'in_nav=1':'visible=1 AND in_nav=1').' ORDER BY sort_order,id')->fetchAll();
$sections=public_display_rows(query('SELECT * FROM ep_cms_sections WHERE page_id=?'.($draft?'':' AND visible=1').' ORDER BY sort_order,id',[$page['id']])->fetchAll());
if(!$draft&&!public_offers_state()['page_enabled'])$nav=array_values(array_filter($nav,fn($p)=>$p['slug']!=='offers')); 
if(in_array($slug,['home','about'],true))$sections=array_values(array_filter($sections,function($section){$content=decode($section['content_json']);$url=$content['ar']['button_url']??$content['en']['button_url']??'';parse_str((string)parse_url($url,PHP_URL_QUERY),$params);return ($params['page']??'')!=='leadership';}));
$contact=public_interface_texts($lang);
$departments=query('SELECT * FROM ep_customer_departments WHERE active=1 ORDER BY sort_order,id')->fetchAll();$status='';$error='';$receipt=null;
if(!$draft&&array_filter($sections,fn($s)=>$s['layout']==='contact')&&($cfg['public_contact_enabled']||$cfg['ratings_enabled'])){
 session_name('EP_PUBLIC');session_set_cookie_params(['path'=>'/','secure'=>true,'httponly'=>true,'samesite'=>'Strict']);session_start();
 $_SESSION['contact_csrf']??=bin2hex(random_bytes(32));
 if($_SERVER['REQUEST_METHOD']==='POST')try{
  if(!hash_equals($_SESSION['contact_csrf'],(string)($_POST['csrf']??'')))fail('أعد فتح الصفحة وحاول مرة أخرى.');
  if(!empty($_POST['website']))fail('تعذر قبول الرسالة.');
  if((($_POST['form_type']??'message')==='rating')!==($slug==='ratings'))fail('استخدم الصفحة المخصصة لهذا النموذج.');
  if(($_POST['form_type']??'message')!=='rating'&&!$cfg['public_contact_enabled'])fail('استقبال الرسائل غير متاح.');
  $id=($_POST['form_type']??'message')==='rating'?rating_submit($_POST):customer_submit($_POST);
  $_SESSION['contact_reference']=(($_POST['form_type']??'message')==='rating'?'R-':'M-').$id;
  $_SESSION['contact_csrf']=bin2hex(random_bytes(32));
  $ticket=bin2hex(random_bytes(24));$_SESSION['public_receipt']=['ticket'=>$ticket,'page'=>$slug,'kind'=>$slug==='ratings'?'rating':'message','name'=>mb_substr(trim((string)($_POST['name']??'')),0,150),'reference'=>$_SESSION['contact_reference'],'at'=>time()];unset($_SESSION['contact_reference']);
  header('Location: '.page_url($slug,$lang).'&receipt='.$ticket);exit;
 }catch(Throwable $e){$error=$lang==='ar'&&$e instanceof RuntimeException&&!($e instanceof PDOException)?$e->getMessage():($contact['error_generic']??$cfg['public_ui']['ar']['error_generic']);}
 if(isset($_GET['receipt'])){
  $saved=$_SESSION['public_receipt']??null;
  if(!is_array($saved)||$saved['page']!==$slug||time()-$saved['at']>600||!hash_equals($saved['ticket'],(string)$_GET['receipt'])){header('Location: '.page_url($slug,$lang));exit;}
  $receipt=$saved;unset($_SESSION['public_receipt']);
 }
}
$offerDetail=null;
if(isset($_GET['offer'])){
 if($slug!=='offers'||!preg_match('/^[0-9]+$/D',(string)$_GET['offer'])||!$page['id']){http_response_code(404);}else{
  $offerDetail=query("SELECT c.* FROM ep_cms_cards c JOIN ep_cms_sections s ON s.id=c.section_id WHERE c.id=? AND c.card_type='offer' AND s.page_id=?".($draft?'':' AND c.visible=1 AND s.visible=1'),[(int)$_GET['offer'],$page['id']])->fetch();if(!$offerDetail)http_response_code(404);
 }
 if(!$offerDetail){$offerDetail=null;$sections=[];$page['id']=0;$pc=[$lang=>['title'=>$contact['page_unavailable']??'الصفحة غير متاحة']];}
}
$aboutTexts=$slug==='about'?array_values(array_filter($sections,fn($s)=>$s['layout']==='text')):[];$aboutGroup=array_slice($aboutTexts,0,3);$aboutIds=array_column($aboutGroup,'id');
$homeIntro=$slug==='home'?current(array_filter($sections,fn($s)=>$s['layout']==='text')):false;
$title=setting_text('public_site_title')?:$brand;

require __DIR__.'/hr/app/source-render.php';if(db()->inTransaction())db()->commit();
