<?php
declare(strict_types=1);
// Real notification pairing function with an isolated translation boundary; no credentials/network.
function fail($message,$code=422){throw new RuntimeException($message,$code);}
function jencode($value){return json_encode($value,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);}
$calls=[];$mode='ok';
function hr_translation_batch160(array $texts,string $context,string $from='AR',string $to='EN-GB'):array{global $calls,$mode;$calls[]=[$texts,$from,$to];if($mode==='fail')throw new RuntimeException('hr_translation_unreachable');return $to==='AR'?['title'=>'عنوان','body'=>'رسالة']:['title'=>'Title','body'=>'Message'];}
$source=file_get_contents(__DIR__.'/../hr/app/notifications.php');$source=preg_replace('/^require_once .*;$/m','',$source);eval(substr($source,5));
function ok169($v){if(!$v)throw new LogicException('FAILED');}
$p=notice_pair169('عنوان','رسالة','ar');ok169($p['ar']['title']==='عنوان'&&$p['en']['title']==='Title'&&$calls[0][1]==='AR'&&$calls[0][2]==='EN-GB');
$q=notice_pair169('Title','Message','en');ok169($q['en']['body']==='Message'&&$q['ar']['body']==='رسالة'&&$calls[1][1]==='EN'&&$calls[1][2]==='AR');
ok169($p['source_revision']!==$q['source_revision']);$n=count($calls);try{notice_pair169('A','B','fr');throw new LogicException('allowed');}catch(RuntimeException $e){ok169(count($calls)===$n);}
$mode='fail';try{notice_pair169('عنوان','رسالة','ar');throw new LogicException('allowed');}catch(RuntimeException $e){ok169(str_contains($e->getMessage(),'لم يتم إرساله'));}
echo "PASS bidirectional pairing, exact source retention, revision binding, invalid direction and provider failure before dispatch.\n";
// Execute the real send path with authorization/storage/recipient boundaries isolated.
function notice_manage_test169($u){if(empty($u['allowed']))fail('denied',403);}
function notice_options_test169($u){return ['people'=>[['id'=>7,'company'=>'click','department'=>'Sales']],'groups'=>[]];}
class Memory169 {public bool $open=false;function beginTransaction(){$this->open=true;}function commit(){$this->open=false;}}
$db169=new Memory169;$notices169=[];$audits169=[];
function db(){return $GLOBALS['db169'];}function notice($id,$title,$body,$ack,$meta){$GLOBALS['notices169'][]=[$id,$title,$body,$ack,$meta];}function audit($a,$s,$b){$GLOBALS['audits169'][]=[$a,$s,$b];}
$a=strpos($source,'function notice_send32(');$b=strpos($source,'function notice_read32(',$a);$send=substr($source,$a,$b-$a);$send=str_replace(['function notice_send32(','notice_manage32(','notice_options32('],['function notice_send_test169(','notice_manage_test169(','notice_options_test169('],$send);eval($send);
$input=['title'=>'Title','body'=>'Message','source_language169'=>'en','recipients'=>[7],'require_ack'=>1,'type'=>'message','title_ar169'=>'FORGED'];
$n=count($calls);try{notice_send_test169(['id'=>1],$input);throw new LogicException('allowed');}catch(RuntimeException $e){ok169($e->getCode()===403&&count($calls)===$n&&!$notices169);}
try{notice_send_test169(['id'=>1,'allowed'=>true],$input);throw new LogicException('allowed');}catch(RuntimeException $e){ok169(!$db169->open&&!$notices169);}
$mode='ok';$r=notice_send_test169(['id'=>1,'allowed'=>true],$input);ok169($r['count']===1&&count($notices169)===1&&!$db169->open);ok169($notices169[0][1]==='Title'&&$notices169[0][4]['text_pair169']['ar']['title']==='عنوان');ok169($notices169[0][4]['sender']===1&&$audits169[0][0]==='notice_send');
echo "PASS real send ordering: authorization before provider, no dispatch on failure, untrusted hidden target ignored, paired metadata and audit committed.\n";
