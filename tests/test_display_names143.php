<?php
declare(strict_types=1);
$pdo=new PDO('sqlite::memory:');$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);$queries=[];
function query($sql,$args=[]){$GLOBALS['queries'][]=[$sql,$args];$q=$GLOBALS['pdo']->prepare($sql);$q->execute($args);return $q;}
function decode($v){return json_decode($v,true)??[];}
function ok143($condition,$message){if(!$condition)throw new RuntimeException($message);}
require __DIR__.'/../hr/app/display-names.php';
query('CREATE TABLE ep_users(id INTEGER,name TEXT,profile_json TEXT)');foreach([[1,'اسم مشترك',['name_en'=>'Employee One','iban'=>'PRIVATE_BANK']],[2,'اسم مشترك',['name_en'=>'Employee Two']],[3,'خارج النطاق',['name_en'=>'OUT_OF_SCOPE']],[4,'بدون ترجمة',[]]]as [$id,$name,$profile])query('INSERT INTO ep_users VALUES(?,?,?)',[$id,$name,json_encode($profile)]);
$before=$pdo->query('SELECT * FROM ep_users ORDER BY id')->fetchAll();$queries=[];
$input=['staff'=>[['id'=>1,'name'=>'اسم مشترك'],['id'=>2,'name'=>'اسم مشترك'],['id'=>4,'name'=>'بدون ترجمة']],'expiring'=>[]];$actual=display_names143('dashboard',$input);
ok143($actual['staff'][0]['employee_locale143']===['id'=>1,'ar'=>'اسم مشترك','en'=>'Employee One'],'first ID');ok143($actual['staff'][1]['employee_locale143']['en']==='Employee Two','namesakes distinct');ok143($actual['staff'][2]['employee_locale143']['en']==='','no invented translation');ok143(!str_contains(json_encode($actual),'PRIVATE_BANK')&&!str_contains(json_encode($actual),'OUT_OF_SCOPE'),'minimal authorized fields');ok143(count($queries)===1&&$queries[0][1]===[1,2,4],'only visible IDs one batch');
$queries=[];ok143(display_names143('dashboard',['staff'=>[]])===['staff'=>[]]&&!$queries,'empty lightweight');
$own=[['id'=>1,'name'=>'اسم مشترك','role'=>'employee','profile'=>['name_en'=>'Already known']]];$out=display_names143('users',$own);ok143(!$queries&&$out[0]['employee_locale143']['en']==='Already known','no directory reread');
$list=[['id'=>501,'user_id'=>2,'employee_name'=>'اسم مشترك','body_json'=>'{"name":"source"}']];$out=display_names143('list',$list);ok143($out[0]['employee_locale143']['id']===2&&$out[0]['body_json']===$list[0]['body_json'],'record ID not employee ID; original body unchanged');
$bank=[['id'=>600,'user_id'=>1,'snapshot_json'=>json_encode(['employee_name'=>'اسم تاريخي'])]];$out=display_names143('bank_list',$bank);ok143($out[0]['employee_locale143']['ar']==='اسم تاريخي'&&$out[0]['snapshot_json']===$bank[0]['snapshot_json'],'snapshot never rewritten');
$queries=[];ok143(display_names143('backup_restore_preview',$input)===$input&&!$queries,'backup excluded');ok143(display_names143('customer_cases',[['id'=>3,'name'=>'اسم العميل']])===[['id'=>3,'name'=>'اسم العميل']]&&!$queries,'customers not employees');ok143(display_names143('company_save',$input)===$input&&!$queries,'writes excluded');
ok143($pdo->query('SELECT * FROM ep_users ORDER BY id')->fetchAll()===$before,'no writes');
// Integration gate order is part of the privacy contract.
$api=file_get_contents(__DIR__.'/../hr/api.php');ok143(strpos($api,'$result=display_names143')>strpos($api,'$result=employee_filter_result73'),'after filter');ok143(strpos($api,'$result=display_names143')>strpos($api,'$result=personal_result34'),'after personal projection');
echo "PASS ID-bound read-only display labels, namesakes, authorized rows only, minimal payload, empty/missing names, snapshot preservation and post-scope integration.\n";
