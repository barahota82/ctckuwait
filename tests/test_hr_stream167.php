<?php
declare(strict_types=1);
$n167=0;function ok167($v,$m){global $n167;$n167++;if(!$v)throw new LogicException($m);}
class StreamStatement167 extends PDOStatement {
 public int $i=0;public bool $closed=false;public bool $fail=false;public ?PDOException $driverError=null;
 public function execute(?array $params=null):bool{return true;}
 public function fetch(int $mode=PDO::FETCH_DEFAULT,int $orientation=PDO::FETCH_ORI_NEXT,int $offset=0):mixed{if($this->driverError)throw $this->driverError;if($this->fail)throw new RuntimeException('row failure');if($this->i++>=25000)return false;return ['id'=>$this->i,'body_json'=>str_repeat('x',1024)];}
 public function fetchAll(int $mode=PDO::FETCH_DEFAULT,mixed ...$args):array{throw new LogicException('Buffered full fetch is forbidden');}
 public function closeCursor():bool{$this->closed=true;return true;}
}
class StreamPDO167 extends PDO {
 public array $options=[];public StreamStatement167 $stmt;
 public function __construct(){$this->stmt=new StreamStatement167();}
 public function getAttribute(int $a):mixed{return 'sqlsrv';}
 public function prepare(string $sql,array $options=[]):PDOStatement|false{$this->options=$options;return $this->stmt;}
}
$pdo167=new StreamPDO167();function db(){global $pdo167;return $pdo167;}function query($sql){throw new LogicException('Shared buffered query should not be used for SQLSRV inventory');}
$s=file_get_contents(__DIR__.'/../hr/app/data-language.php');$a=strpos($s,'function data_translation_rows167(');$b=strpos($s,'function translation_inventory31(');eval(substr($s,$a,$b-$a));
$start=memory_get_usage(true);$rows=0;$peak=$start;foreach(data_translation_rows167('SELECT id,body_json FROM ep_records')as $row){$rows++;$peak=max($peak,memory_get_usage(true));}
ok167($rows===25000,'all 25000 rows, no truncation');ok167($pdo167->options===[PDO::ATTR_CURSOR=>PDO::CURSOR_FWDONLY],'actual SQLSRV branch requests forward-only cursor');ok167($pdo167->stmt->closed,'cursor released after exhaustion');ok167($peak-$start<8*1024*1024,'25MB result processed below 8MB additional row-scan memory');
$pdo167=new StreamPDO167();$it=data_translation_rows167('SELECT body_json FROM ep_history');$it->rewind();unset($it);ok167($pdo167->stmt->closed,'cursor released on abandoned iterator');
$pdo167=new StreamPDO167();$pdo167->stmt->fail=true;try{foreach(data_translation_rows167('SELECT body_json FROM ep_history')as $row){}throw new LogicException('failure expected');}catch(RuntimeException $e){}ok167($pdo167->stmt->closed,'cursor released after fetch failure');
$log167='/tmp/hr-stream167-'.bin2hex(random_bytes(4)).'.log';ini_set('error_log',$log167);$pdo167=new StreamPDO167();$driver167=new PDOException('secret example must not be logged');$driver167->errorInfo=['IMSSP',-59,'Memory limit exceeded for buffered query; password=DO_NOT_LOG'];$pdo167->stmt->driverError=$driver167;
try{foreach(data_translation_rows167('SELECT body_json FROM ep_records')as $row){}throw new LogicException('driver error expected');}catch(PDOException $error){ok167($error===$driver167,'original PDO exception propagates');}
$logged167=file_get_contents($log167);ok167(str_contains($logged167,'source=ep_records state=IMSSP native=-59 kind=buffer_limit'),'sanitized driver classification includes source/state/native');ok167(!str_contains($logged167,'DO_NOT_LOG')&&!str_contains($logged167,'SELECT')&&!str_contains($logged167,'secret example'),'diagnostic excludes raw SQL/messages/credentials');ok167($pdo167->stmt->closed,'cursor closes on PDO driver failure');@unlink($log167);
$inventory=substr($s,strpos($s,'function translation_inventory31('),strpos($s,'function translation_seed31(')-strpos($s,'function translation_inventory31('));ok167(substr_count($inventory,'data_translation_rows167(')===10,'all ten SQL inventory scans stream');ok167(!str_contains($inventory,'fetchAll'),'inventory has no whole-result materialization');
echo 'PASS '.$n167." forward-only SQLSRV branch,25000rows/25MB, bounded scan memory, no full fetch, exception/abandonment cleanup and complete inventory checks; driver boundary simulated, not live SQL Server\n";
