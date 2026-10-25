<?php
declare(strict_types=1);
function fail($message){throw new RuntimeException($message);}function decode($s){return json_decode($s,true)??[];}function setting31($key,$default=[]){return $GLOBALS['saved137']??$default;}
require __DIR__.'/../hr/app/data-language.php';
$n=0;function check137($x){$GLOBALS['n']++;if(!$x)throw new LogicException('assertion '.$GLOBALS['n']);}
$c=nationality_catalog137();check137(count($c)===249);$known=[];foreach($c as $r){check137(preg_match('/^[A-Z]{2}$/',$r['code'])===1);foreach(array_merge([$r['code'],$r['ar'],$r['en']],$r['aliases'])as $alias){$k=nationality_key137($alias);check137(!isset($known[$k])||$known[$k]===$r['code']);$known[$k]=$r['code'];}}
foreach(['EG','Egypt','مصري','مصرية','مصرى','Egyptian','مصر']as $v){$p=['nationality'=>$v];nationality_profile137($p);check137($p['nationality']==='Egypt'&&$p['nationality_code137']==='EG');}
$p=['nationality'=>'India'];nationality_profile137($p);check137($p['nationality']==='India'&&$p['nationality_code137']==='IN');
$p=['nationality'=>'Unresolved'];nationality_profile137($p,['nationality'=>'Unresolved']);check137($p['nationality']==='Unresolved');
try{$p=['nationality'=>'Invented'];nationality_profile137($p);throw new LogicException('accepted');}catch(RuntimeException $e){check137($e->getMessage()!=='accepted');}
$p=['nationality'=>''];nationality_profile137($p,['nationality'=>'Egypt','nationality_code137'=>'EG']);check137($p['nationality']===''&&!isset($p['nationality_code137']));
$p=[];nationality_profile137($p,['nationality'=>'Egypt','nationality_code137'=>'EG']);check137($p['nationality']==='Egypt'&&$p['nationality_code137']==='EG');
$GLOBALS['saved137']=[['context'=>'department','source'=>'اختبار','target'=>'Department meaning','status'=>'approved'],['context'=>'job_title','source'=>'اختبار','target'=>'Job meaning','status'=>'approved'],['context'=>'job_title','source'=>'محاسب','target'=>'Reviewed accountant','status'=>'approved'],['context'=>'additional_job','source'=>'غير معتمد','target'=>'Unreviewed','status'=>'review']];
$context=data_context_dictionary137();check137($context['department']['اختبار']==='Department meaning');check137($context['job_title']['اختبار']==='Job meaning');check137($context['job_title']['محاسب']==='Reviewed accountant');check137(!isset($context['additional_job']['غير معتمد']));$flat=data_dictionary31($context);check137(!isset($flat['اختبار']));check137($flat['محاسب']==='Reviewed accountant');check137($flat['الادارة']==='Management');
echo 'PASS '.$n.' V137 nationality catalogue and alias integrity, original preservation, rejection of new unknown values, approved contextual precedence and conflict isolation. No database migration.'.PHP_EOL;
