<?php
// Execute real discovery, queue, scoped projection and immutable-source checks.
$httpMode='ok';$during=null;put_setting31('data_translation_budget160',10000000);
$profile166=['additional_job'=>'مهمة إضافية للمشرف 166','address'=>'عنوان للمشرف شارع 166','bank_name'=>'مصرف المشرف 166','bank_account'=>'حساب لا يترجم 166'];
query('INSERT INTO ep_users VALUES(?,?,?,?,?)',[166,'admin','قسم المشرف 166','وظيفة المشرف 166',jencode($profile166)]);
$GLOBALS['groups166']=['groups'=>['g166'=>['name'=>'مجموعة يدوية جديدة 166','description'=>'وصف مجموعة جديد 166']]];
$GLOBALS['settings166']=['locations'=>[['id'=>'L166','name'=>'مكتب يدوي جديد 166','latitude'=>29.33,'longitude'=>48.02,'radius'=>50]]];
$cases166=decode(file_get_contents(__DIR__.'/manual_text_cases166.json'));$expected166=[];$id166=20000;
foreach($cases166 as $case166){
 $body166=[$case166['field']=>$case166['source']];if($case166['module']==='video_receipt')$body166['mode']='custom';
 $row166=['id'=>++$id166,'module'=>$case166['module'],'title'=>'record-'.$id166,'body_json'=>jencode($body166)];
 query('INSERT INTO ep_records(id,module,title,body_json) VALUES(?,?,?,?)',array_values($row166));
 $expected166[]=[data_record_context162($case166['module'],$case166['field']),$case166['source'],$row166];
}
foreach(['department'=>'قسم المشرف 166','job_title'=>'وظيفة المشرف 166',...array_intersect_key($profile166,array_flip(['additional_job','address','bank_name'])),'group_name'=>'مجموعة يدوية جديدة 166','group_description'=>'وصف مجموعة جديد 166','location'=>'مكتب يدوي جديد 166']as $context166=>$source166)$expected166[]=[$context166,$source166,null];
$inventory166=translation_inventory31();foreach($expected166 as [$context166,$source166])ok160(isset($inventory166[language_key31($context166,$source166)]),'manual source discovered '.$context166);
$before166=[];foreach(['ep_users','ep_records','ep_history','ep_companies']as $table166)$before166[$table166]=hash('sha256',jencode(query('SELECT * FROM '.$table166)->fetchAll()));
for($try166=0;$try166<160;$try166++){ $ready166=data_context_dictionary137();$missing166=array_filter($expected166,fn($p)=>!isset($ready166[$p[0]][$p[1]]));if(!$missing166)break;data_translation_process160(); }
foreach($expected166 as [$context166,$source166,$row166]){ok160(isset(data_context_dictionary137()[$context166][$source166]),'manual source queued and published '.$context166);if($row166){$scope166=data_translation_scope160([$row166],'list');ok160(isset($scope166['dictionary'][$context166][$source166]),'authorized field result projected '.$context166);ok160(count($scope166['dictionary'])===1,'field result excludes unrelated private contexts');}}
$groupsScope166=data_translation_scope160($GLOBALS['groups166'],'groups29_list');ok160(isset($groupsScope166['dictionary']['group_name']['مجموعة يدوية جديدة 166'],$groupsScope166['dictionary']['group_description']['وصف مجموعة جديد 166']),'group name and description sidecar refresh');
foreach(['v28_info','policy_editor_context']as $action166){$scope166=data_translation_scope160($GLOBALS['settings166'],$action166);ok160(isset($scope166['dictionary']['location']['مكتب يدوي جديد 166']),'location projection '.$action166);ok160(!isset($scope166['dictionary']['address']),'location response excludes admin address');}
$scope166=data_translation_scope160(['groups'=>$GLOBALS['groups166']['groups'],'locations'=>$GLOBALS['settings166']['locations']],'notifications');ok160(!isset($scope166['dictionary']['location'],$scope166['dictionary']['group_name']),'other action does not treat arbitrary names as group/location metadata');
foreach($before166 as $table166=>$hash166)ok160($hash166===hash('sha256',jencode(query('SELECT * FROM '.$table166)->fetchAll())),'manual source remains unchanged '.$table166);
$proof166=[];foreach($expected166 as [$context166,$source166])$proof166[]=['context'=>$context166,'source'=>$source166,'target'=>data_context_dictionary137()[$context166][$source166]];
file_put_contents(__DIR__.'/manual_translation166_proof.json',jencode(['mock_http_only'=>true,'sources'=>$proof166,'business_tables_unchanged'=>true]));
