<?php
declare(strict_types=1);
require_once __DIR__.'/public-publication.php';
function cms_seed(PDO $pdo,bool $identity=true): void{
 if((int)$pdo->query('SELECT COUNT(*) FROM ep_cms_pages')->fetchColumn())return;
 $seed=decode(file_get_contents(__DIR__.'/cms-seed.json'));
 $put=function(string $sql,array $args)use($pdo){$s=$pdo->prepare($sql);$s->execute($args);return (int)$pdo->lastInsertId();};
 foreach($seed['pages']as $page){
  $pid=$put('INSERT INTO ep_cms_pages(slug,content_json,sort_order,visible,in_nav) VALUES(?,?,?,?,?)',[$page['slug'],jencode($page['content']),$page['order'],$page['visible']??1,$page['in_nav']??1]);
  foreach($page['sections']as $section){
   $sid=$put('INSERT INTO ep_cms_sections(page_id,layout,theme,columns_count,content_json,sort_order,template_key,visible) VALUES(?,?,?,?,?,?,?,?)',[$pid,$section['layout'],$section['theme'],$section['columns'],jencode($section['content']),$section['order'],$section['template_key']??'', $section['visible']??1]);
   foreach($section['cards']as $card)$put('INSERT INTO ep_cms_cards(section_id,card_type,content_json,sort_order,visible,template_key) VALUES(?,?,?,?,?,?)',[$sid,$card['type'],jencode($card['content']),$card['order'],$card['visible']??1,$card['template_key']??'']);
  }
 }
 if($identity){
  $q=$pdo->prepare('MERGE INTO ep_settings WITH (HOLDLOCK) AS target USING (VALUES (?, ?)) AS source (setting_key, value_json) ON target.setting_key=source.setting_key WHEN MATCHED THEN UPDATE SET value_json=source.value_json WHEN NOT MATCHED THEN INSERT (setting_key, value_json) VALUES (source.setting_key, source.value_json);');
  foreach($seed['settings']as $k=>$v)$q->execute([$k,jencode($v)]);
  $path=__DIR__.'/seed-logo.png';$name=bin2hex(random_bytes(24)).'.bin';$dest=EP_ROOT.'/private/files/'.$name;
  if(is_file($path)&&copy($path,$dest)){
   $id=$put('INSERT INTO ep_files(user_id,disk_name,original_name,mime,size_bytes,sha256,created_at) VALUES(?,?,?,?,?,?,?)',[1,$name,'company-logo.png',(new finfo(FILEINFO_MIME_TYPE))->file($path),filesize($path),hash_file('sha256',$path),utc_now()]);
   $q->execute(['logo_id',jencode($id)]);
  }
 }
}
function cms_image_public(int $id): bool{
 try{$rows=query('SELECT c.content_json,s.content_json section_content,p.slug _slug FROM ep_cms_cards c JOIN ep_cms_sections s ON s.id=c.section_id JOIN ep_cms_pages p ON p.id=s.page_id WHERE c.image_id=? AND c.visible=1 AND s.visible=1 AND p.visible=1',[$id])->fetchAll();foreach($rows as $row)if(public_row_displayable($row)&&public_row_displayable(['content_json'=>$row['section_content'],'_slug'=>$row['_slug']]))return true;return false;}catch(PDOException $e){return false;}
}

function cms_clean(mixed $input): array{
 if(!is_array($input))fail('المحتوى غير صالح.');$out=[];
 foreach(array_keys(language_catalog())as $lang){$v=$input[$lang]??[];if(!is_array($v))fail('محتوى اللغة غير صالح.');$out[$lang]=[];
  foreach(['title','subtitle','body','details','terms','button_label','button_url','secondary_button_label','secondary_button_url','alt','nav_label','description','initials','icon','eyebrow','note','mission_eyebrow','mission_title','mission_body','vision_eyebrow','vision_title','vision_body','note_title','note_body','note_button_label','note_button_url','body_without_offers']as $key){$text=mb_substr(trim((string)($v[$key]??'')),0,in_array($key,['body','details','terms','note','mission_body','vision_body','note_body','body_without_offers'],true)?12000:500);
   if(in_array($key,['button_url','secondary_button_url','note_button_url'],true)&&$text!==''&&!preg_match('~^(?:https://[^\s]+|mailto:[^\s]+|tel:[+0-9 -]+|(?:\./)?(?:index\.php|hr/index\.php|offers\.html)(?:\?[^\s]*)?|\?page=[^\s]*|/[^\s]*|#[A-Za-z0-9_-]+)$~',$text))fail('رابط الزر غير صالح؛ استخدم HTTPS أو رابط صفحة محلية.');
   $out[$lang][$key]=$text;
  }
 }
 foreach(array_keys(language_catalog())as $code)$out[$code]['initials']=mb_substr((string)($input['ar']['initials']??''),0,30);
 foreach(array_keys(language_catalog())as $code)$out[$code]['requires_offers']=($input['ar']['requires_offers']??'0')==='1'?'1':'0';
 if(!$out['ar']['title']&&!$out['en']['title'])fail('عنوان بالعربية أو الإنجليزية مطلوب.');return $out;
}
function cms_action(string $action,array $u,array $b): array{
 if($u['role']!=='admin'){
  if(($_SESSION['access_mode29']??'personal')!=='team')fail('انتقل إلى عرض الفريق',403);
  $op=$action==='cms_list'?'view':($action==='cms_upload'||empty($b['id'])?'create':'edit');
  if(!in_array($action,['cms_list','cms_upload','cms_save'],true))fail('هذه العملية للمشرف العام.',403);
  $screen=permission_has($u,'cms',$op)?'cms':'offers';permission_require($u,$screen,$op);
  if($screen==='offers'&&$action==='cms_save'){
   $entity=$b['entity']??'';$id=(int)($b['id']??0);if($entity!=='card'||($b['card_type']??'')!=='offer')fail('هذه الصلاحية لكروت العروض فقط.',403);
   $parent=(int)($b['section_id']??0);if(cms_language_scope('section',$parent)!=='offers'||($id&&cms_language_scope('card',$id)!=='offers'))fail('المحتوى خارج نطاق العروض.',403);
  }
 }
 if($action==='cms_import_source')fail('المحتوى الأصلي مجهز في قاعدة البيانات؛ عدله من شاشة محتوى الموقع.',410);
 if($action==='cms_list'&&$u['role']!=='admin'&&!permission_has($u,'cms','view')){$pages=query("SELECT * FROM ep_cms_pages WHERE slug='offers'")->fetchAll();$ids=array_column($pages,'id');$sections=array_values(array_filter(query('SELECT * FROM ep_cms_sections ORDER BY sort_order,id')->fetchAll(),fn($r)=>in_array($r['page_id'],$ids)));$sids=array_column($sections,'id');$cards=array_values(array_filter(query('SELECT * FROM ep_cms_cards ORDER BY sort_order,id')->fetchAll(),fn($r)=>in_array($r['section_id'],$sids)));return ['pages'=>$pages,'sections'=>$sections,'cards'=>$cards,'field_contracts'=>decode(file_get_contents(__DIR__.'/cms-field-contracts.json'))];}
 if($action==='cms_list')return ['pages'=>query('SELECT * FROM ep_cms_pages ORDER BY sort_order,id')->fetchAll(),'sections'=>query('SELECT * FROM ep_cms_sections ORDER BY sort_order,id')->fetchAll(),'cards'=>query('SELECT * FROM ep_cms_cards ORDER BY sort_order,id')->fetchAll(),'field_contracts'=>decode(file_get_contents(__DIR__.'/cms-field-contracts.json'))];
 if($action==='cms_ratings_page'){
  $lock=fopen(EP_ROOT.'/private/cms-ratings.lock','c+');if(!$lock||!flock($lock,LOCK_EX))fail('تعذر تجهيز الصفحة.');
  try{if($existing=query("SELECT id FROM ep_cms_pages WHERE slug='ratings'")->fetchColumn())return ['id'=>(int)$existing,'existing'=>true];
   $seed=decode(file_get_contents(__DIR__.'/cms-seed.json'));$page=current(array_filter($seed['pages'],fn($p)=>$p['slug']==='ratings'));db()->beginTransaction();
   query('INSERT INTO ep_cms_pages(slug,content_json,sort_order,visible) VALUES(?,?,?,?)',['ratings',jencode(cms_clean($page['content'])),$page['order'],0]);$pid=(int)db()->lastInsertId();
   foreach($page['sections'] as $section)query('INSERT INTO ep_cms_sections(page_id,layout,theme,columns_count,content_json,sort_order,template_key,visible) VALUES(?,?,?,?,?,?,?,?)',[$pid,$section['layout'],$section['theme'],$section['columns'],jencode(cms_clean($section['content'])),$section['order'],$section['template_key']??'',1]);
   public_languages_invalidate(['site']);audit('cms_ratings_page','page:'.$pid);db()->commit();return ['id'=>$pid,'existing'=>false];
  }finally{flock($lock,LOCK_UN);fclose($lock);}
 }
 if($action==='cms_messages')return customer_action('customer_cases',$u,[]);
 if($action==='cms_message_status')return customer_action('customer_case_update',$u,$b);
 if($action==='cms_upload'){$id=upload_file($_FILES['file']??[],(int)$u['id'],true);query('INSERT INTO ep_cms_media(file_id,created_at) VALUES(?,?)',[$id,utc_now()]);return ['id'=>$id];}
 $entity=$b['entity']??'';$tables=['page'=>'ep_cms_pages','section'=>'ep_cms_sections','card'=>'ep_cms_cards'];$table=$tables[$entity]??null;if(!$table)fail('نوع المحتوى غير صالح.');$id=(int)($b['id']??0);
 $previous=$id?query("SELECT * FROM $table WHERE id=?",[$id])->fetch():null;$previousScope=$id?cms_language_scope($entity,$id):null;
 if($id&&!$previous)fail('المحتوى غير موجود.',404);
 db()->beginTransaction();
 if($action==='cms_delete'){
  public_languages_invalidate([cms_language_scope($entity,$id)]);
  if($entity==='page'){
   if(query('SELECT slug FROM ep_cms_pages WHERE id=?',[$id])->fetchColumn()==='home')fail('لا تحذف الصفحة الرئيسية؛ عدل أقسامها.');
   query('DELETE c FROM ep_cms_cards c JOIN ep_cms_sections s ON s.id=c.section_id WHERE s.page_id=?',[$id]);query('DELETE FROM ep_cms_sections WHERE page_id=?',[$id]);
  }elseif($entity==='section')query('DELETE FROM ep_cms_cards WHERE section_id=?',[$id]);
  query("DELETE FROM $table WHERE id=?",[$id]);audit('cms_delete',$entity.':'.$id);db()->commit();return ['ok'=>true];
 }
 if($action!=='cms_save')fail('العملية غير موجودة.',404);
 $content=cms_clean($b['content']??[]);if(($b['card_type']??'')==='offer')foreach($content as &$language){if($language['details']===''&&$language['body']!=='')$language['details']=$language['body'];$language['body']='';}unset($language);if(settings()['translation_enabled']){$old=$id?decode(query("SELECT content_json FROM $table WHERE id=?",[$id])->fetchColumn()):[];if((cms_clean($old)['ar']??[])!==$content['ar'])foreach(settings()['public_languages'] as $target)if($target!=='ar')$content[$target]=[];}$order=(int)($b['sort_order']??0);$visible=empty($b['visible'])?0:1;
 if($entity==='page'){
  $slug=trim((string)($b['slug']??''));if($id){$original=query('SELECT slug FROM ep_cms_pages WHERE id=?',[$id])->fetchColumn();if(in_array($original,['home','about','leadership','how-it-works','offers','contact','ratings','privacy'],true)&&$slug!==$original)fail('عنوان الرابط ثابت لهذه الصفحة؛ عدّل اسمها في القائمة.');}if(!preg_match('/^[a-z0-9-]{1,80}$/',$slug))fail('رمز الصفحة أحرف إنجليزية وأرقام وشرطة.');
  if($id&&query('SELECT slug FROM ep_cms_pages WHERE id=?',[$id])->fetchColumn()==='home'&&$slug!=='home')fail('رمز الصفحة الرئيسية يجب أن يبقى home.');
  $inNav=array_key_exists('in_nav',$b)?(int)!empty($b['in_nav']):($id?(int)query('SELECT in_nav FROM ep_cms_pages WHERE id=?',[$id])->fetchColumn():1);$args=[$slug,jencode($content),$order,$visible,$inNav];$columns='slug=?,content_json=?,sort_order=?,visible=?,in_nav=?';
 }elseif($entity==='section'){
  $parent=(int)($b['page_id']??0);if(!query('SELECT id FROM ep_cms_pages WHERE id=?',[$parent])->fetch())fail('اختر صفحة موجودة.');
  $layout=$b['layout']??'grid';$theme=$b['theme']??'ivory';$count=(int)($b['columns_count']??3);
  if(!in_array($layout,['hero','grid','text','contact'],true)||!in_array($theme,['light','navy','ivory'],true)||$count<1||$count>4)fail('قالب القسم غير صالح.');
  $existing=$id?query('SELECT * FROM ep_cms_sections WHERE id=?',[$id])->fetch():null;if($existing&&$existing['template_key']!==''&&($parent!==(int)$existing['page_id']||$layout!==$existing['layout']||$theme!==$existing['theme']||$count!==(int)$existing['columns_count']))fail('قالب هذا القسم ثابت للحفاظ على التصميم؛ أضف قسمًا جديدًا لقالب مختلف.');$args=[$parent,$layout,$theme,$count,jencode($content),$order,$visible];$columns='page_id=?,layout=?,theme=?,columns_count=?,content_json=?,sort_order=?,visible=?';
 }else{
  $parent=(int)($b['section_id']??0);if(!query('SELECT id FROM ep_cms_sections WHERE id=?',[$parent])->fetch())fail('اختر قسمًا موجودًا.');
  $type=$b['card_type']??'text';if(!in_array($type,['person','image','service','offer','text'],true))fail('قالب الكارت غير صالح.');
  $image=(int)($b['image_id']??0);if($image&&(!query('SELECT f.id FROM ep_files f JOIN ep_cms_media m ON m.file_id=f.id WHERE f.id=? AND f.mime LIKE ?',[$image,'image/%'])->fetch()))fail('استخدم صورة رفعت عبر إدارة الموقع التعريفي.');
  if($type==='offer'&&$visible&&(!$image||trim($content['ar']['title']??'')===''||trim($content['ar']['details']??$content['ar']['body']??'')===''))fail('أكمل صورة العرض وعنوانه وتفاصيله قبل النشر؛ يمكنك حفظه مسودة.');
  $args=[$parent,$type,$image?:null,jencode($content),$order,$visible];$columns='section_id=?,card_type=?,image_id=?,content_json=?,sort_order=?,visible=?';
 }
 if($id)query("UPDATE $table SET $columns WHERE id=?",[...$args,$id]);else{ $names=implode(',',array_map(fn($x)=>str_replace('=?','',$x),explode(',',$columns)));$marks=implode(',',array_fill(0,count($args),'?'));query("INSERT INTO $table($names) VALUES($marks)",$args);$id=(int)db()->lastInsertId(); }
 $changed=!$previous||cms_clean(decode($previous['content_json']))!==$content||($entity==='page'&&$previous['slug']!==$slug)||($entity==='section'&&(int)$previous['page_id']!==$parent)||($entity==='card'&&(int)$previous['section_id']!==$parent);if($changed)public_languages_invalidate(array_values(array_unique(array_filter([$previousScope,cms_language_scope($entity,$id)]))));translation_enqueue($entity,$id,$content['ar']);audit('cms_save',$entity.':'.$id,['content'=>$content,'visible'=>$visible]);db()->commit();return ['ok'=>true,'id'=>$id];
}

