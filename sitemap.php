<?php
declare(strict_types=1);
require __DIR__.'/hr/app/core.php';require __DIR__.'/hr/app/languages.php';require __DIR__.'/hr/app/public-publication.php';require __DIR__.'/hr/app/public-seo.php';
if(!app_installed()){http_response_code(503);exit;}
header('Content-Type: application/xml; charset=utf-8');header('Cache-Control: public,max-age=300');
$base=public_base_url();$escape=fn($v)=>htmlspecialchars($v,ENT_XML1|ENT_QUOTES,'UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
foreach(query('SELECT id,slug FROM ep_cms_pages WHERE visible=1 ORDER BY id')->fetchAll()as $page){if($page['slug']==='offers'&&!public_offers_state()['page_enabled'])continue;$scope=$page['slug']==='offers'?'offers':'site';foreach(public_available_languages($scope)as $lang=>$label){$url=$base.'index.php?'.http_build_query(['page'=>$page['slug'],'lang'=>$lang]);echo '<url><loc>'.$escape($url).'</loc></url>';if($scope==='offers')foreach(query("SELECT c.id FROM ep_cms_cards c JOIN ep_cms_sections s ON s.id=c.section_id WHERE s.page_id=? AND s.visible=1 AND c.visible=1 AND c.card_type='offer'",[$page['id']])->fetchAll()as $card)echo '<url><loc>'.$escape($url.'&offer='.$card['id']).'</loc></url>';}}
echo '</urlset>';
