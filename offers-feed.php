<?php
declare(strict_types=1);
require __DIR__.'/hr/app/core.php';
require __DIR__.'/hr/app/languages.php';
require __DIR__.'/hr/app/public-interface.php';
require __DIR__.'/hr/app/public-publication.php';
require __DIR__.'/hr/app/offers-feed.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
try {
 if(!secure_request()||!app_installed())fail('Unavailable',403);
 $lang=(string)($_GET['lang']??'ar');$slug='offers';$draft=false;
 if(!isset(public_available_languages('offers')[$lang])||!public_offers_state()['page_enabled'])fail('Unavailable',404);
 $section=filter_var($_GET['section']??'',FILTER_VALIDATE_INT);$order=filter_var($_GET['after_order']??'',FILTER_VALIDATE_INT);$id=filter_var($_GET['after_id']??'',FILTER_VALIDATE_INT);$ceiling=filter_var($_GET['ceiling']??'',FILTER_VALIDATE_INT);
 if(!$section||$order===false||$id===false||$id<0||!$ceiling)fail('Invalid cursor',400);
 if(!query("SELECT s.id FROM ep_cms_sections s JOIN ep_cms_pages p ON p.id=s.page_id WHERE s.id=? AND s.visible=1 AND p.visible=1 AND p.slug='offers'",[$section])->fetch())fail('Unavailable',404);
 $batch=offers_batch($section,$order,$id,$ceiling);$texts=public_interface_texts($lang);$brand=$lang==='ar'?settings()['company_name']:settings()['company_name_en'];
 $text=fn($content,$field)=>str_replace('{{company}}',$brand,(string)($content[$lang][$field]??$content['ar'][$field]??''));
 $batch['items']=array_map(function($r)use($text,$texts,$lang){$c=public_content_for_display(decode($r['content_json']));return ['id'=>(int)$r['id'],'title'=>$text($c,'title'),'subtitle'=>$text($c,'subtitle'),'alt'=>$text($c,'alt'),'image'=>$r['image_id']?'hr/file.php?id='.(int)$r['image_id']:null,'href'=>'index.php?'.http_build_query(['page'=>'offers','lang'=>$lang,'offer'=>(int)$r['id']]),'label'=>$texts['offer_details']];},$batch['rows']);unset($batch['rows']);echo jencode($batch);
}catch(Throwable $e){if(http_response_code()<400)http_response_code(503);echo jencode(['error'=>'Offers could not be loaded.']);}
