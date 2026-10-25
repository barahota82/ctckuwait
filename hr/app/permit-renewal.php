<?php
declare(strict_types=1);
// A renewal replaces its immediate predecessor from its own start date; original documents remain intact.
function permit_renewal_pair156(array $a,array $b):bool {
 $child=$a;$parent=$b;if((int)(decode($child['body_json'])['renewed_from156']??0)!==(int)$parent['id']){$child=$b;$parent=$a;}
 return (int)($parent['id']??0)>0&&(int)(decode($child['body_json'])['renewed_from156']??0)===(int)$parent['id']&&!empty($parent['date_to'])&&!empty($parent['date_from'])&&!empty($child['date_from'])&&$child['date_from']>$parent['date_from']&&$child['date_from']>=(new DateTimeImmutable($parent['date_to']))->modify('-90 days')->format('Y-m-d');
}
function permit_effective_end156(array $record,array $records):string {
 $end=$record['date_to']??'9999-12-31';
 foreach($records as $child){if((int)(decode($child['body_json'])['renewed_from156']??0)!==(int)$record['id']||!permit_renewal_pair156($child,$record))continue;$end=min($end,(new DateTimeImmutable($child['date_from']))->modify('-1 day')->format('Y-m-d'));}
 return $end;
}
