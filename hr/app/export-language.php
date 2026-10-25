<?php
declare(strict_types=1);
function export_language143():string {return (($_SESSION['login_language']??$_SESSION['language']??'ar')==='en')?'en':'ar';}
function export_name143(array $person):string {
 $ar=(string)($person['name']??'');if(export_language143()!=='en')return $ar;
 $profile=$person['profile']??(isset($person['profile_json'])?decode($person['profile_json']):[]);
 return trim((string)($person['name_en']??$profile['name_en']??''))?:$ar;
}
function export_text143(string $source):string {
 if(export_language143()!=='en')return $source;
 if(isset(['draft'=>'Draft','approved'=>'Approved','submitted'=>'Submitted','returned'=>'Returned for revision','historical'=>'Historical'][$source]))return ['draft'=>'Draft','approved'=>'Approved','submitted'=>'Submitted','returned'=>'Returned for revision','historical'=>'Historical'][$source];
 static $dictionary=null;$dictionary??=decode(file_get_contents(__DIR__.'/ui-en.json'));
 if(isset($dictionary[$source]))return (string)$dictionary[$source];
 return implode('',array_map(fn($part)=>isset($dictionary[trim($part)])?str_replace(trim($part),(string)$dictionary[trim($part)],$part):$part,preg_split('/([0-9]+(?:[.,][0-9]+)*|[·:،—])/u',$source,-1,PREG_SPLIT_DELIM_CAPTURE)?:[$source]));
}
function export_sheets143(array $sheets):array {
 foreach($sheets as &$sheet){$sheet['name']=export_text143((string)$sheet['name']);
  $headers=$sheet['header_rows']??[$sheet['header_row']??0];$textRows=array_unique([...$headers,...($sheet['merge_rows']??[])]);
  foreach($textRows as $index)if(isset($sheet['rows'][$index]))foreach($sheet['rows'][$index]as &$value)if(is_string($value))$value=export_text143($value);unset($value);
 }unset($sheet);return $sheets;
}

function export_data143(string $context,string $source):string {
 if(export_language143()!=='en'||!function_exists('data_context_dictionary137'))return $source;
 static $contexts=null;$contexts??=data_context_dictionary137();return (string)($contexts[$context][$source]??$source);
}


function export_report143(array $report):array {
 require_once __DIR__.'/display-names.php';$bound=display_names143('v26_report',$report);
 foreach($bound['rows']??[]as $i=>$row){if(isset($row['name']))$bound['rows'][$i]['name']=export_name143(['name'=>$row['name'],'name_en'=>$row['employee_locale143']['en']??'']);unset($bound['rows'][$i]['employee_locale143']);}
 return $bound;
}
