<?php
declare(strict_types=1);
// Minimal OOXML transport. ZIP members stay in memory, never extracted to disk.
function xlsx_zip(array $files): string {
 $data='';$central='';$offset=0;
 foreach($files as $name=>$raw){$packed=gzdeflate($raw,6);$crc=crc32($raw);$len=strlen($packed);$n=strlen($name);$local=pack('VvvvvvVVVvv',0x04034b50,20,0,8,0,0,$crc,$len,strlen($raw),$n,0).$name.$packed;$central.=pack('VvvvvvvVVVvvvvvVV',0x02014b50,20,20,0,8,0,0,$crc,$len,strlen($raw),$n,0,0,0,0,0,$offset).$name;$data.=$local;$offset+=strlen($local);}
 return $data.$central.pack('VvvvvVVv',0x06054b50,0,0,count($files),count($files),strlen($central),strlen($data),0);
}
function xlsx_members(string $raw): array {
 if(strlen($raw)>8*1024*1024)fail('ملف Excel أكبر من 8 ميجابايت.');
 $pos=strrpos($raw,pack('V',0x06054b50));if($pos===false)fail('استخدم ملف XLSX غير مشفر.');$e=unpack('vdisk/vstart/vcountDisk/vcount/Vsize/Voffset/vcomment',substr($raw,$pos+4,18));
 if(!$e||$e['disk']||$e['count']>2500||$e['offset']+$e['size']>$pos)fail('بنية Excel غير مدعومة.');$p=$e['offset'];$out=[];$total=0;
 for($i=0;$i<$e['count'];$i++){
  if(substr($raw,$p,4)!==pack('V',0x02014b50))fail('ملف Excel تالف.');$h=unpack('vmade/vneeded/vflags/vmethod/vtime/vdate/Vcrc/Vpacked/Vsize/vname/vextra/vcomment/vdisk/vinternal/Vexternal/Voffset',substr($raw,$p+4,42));$name=substr($raw,$p+46,$h['name']);$p+=46+$h['name']+$h['extra']+$h['comment'];
  if(($h['flags']&1)||!in_array($h['method'],[0,8],true)||str_contains($name,'..')||str_starts_with($name,'/'))fail('أعضاء Excel غير صالحين.');
  $total+=$h['size'];if($h['size']>20*1024*1024||$total>40*1024*1024)fail('البيانات داخل الملف كبيرة جدًا.');
  if(substr($raw,$h['offset'],4)!==pack('V',0x04034b50))fail('ملف Excel تالف.');$local=unpack('vname/vextra',substr($raw,$h['offset']+26,4));$start=$h['offset']+30+$local['name']+$local['extra'];$bytes=substr($raw,$start,$h['packed']);$value=$h['method']===8?@gzinflate($bytes,$h['size']+1):$bytes;
  if($value===false||strlen($value)!==$h['size']||sprintf('%u',crc32($value))!==sprintf('%u',$h['crc']))fail('فشل التحقق من ملف Excel.');$out[$name]=$value;
 }
 return $out;
}
function xlsx_xml(string $s): DOMDocument {
 if(stripos($s,'<!DOCTYPE')!==false||stripos($s,'<!ENTITY')!==false)fail('XML غير مسموح.');$d=new DOMDocument();$old=libxml_use_internal_errors(true);$ok=$d->loadXML($s,LIBXML_NONET);libxml_clear_errors();libxml_use_internal_errors($old);if(!$ok)fail('ملف Excel تالف.');return $d;
}
function xlsx_text(string $s): string{return htmlspecialchars($s,ENT_QUOTES|ENT_XML1,'UTF-8');}
function xlsx_col(int $n): string{$s='';for($n++;$n>0;$n=intdiv($n-1,26))$s=chr(65+($n-1)%26).$s;return $s;}
function xlsx_export(array $sheets): string {
 $ns='http://schemas.openxmlformats.org/spreadsheetml/2006/main';$files=[];$book='<workbook xmlns="'.$ns.'" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';$rels='<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';$types='<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';$defined='';
 foreach($sheets as $i=>$s){$id=$i+1;$name=xlsx_text(mb_substr($s['name'],0,31));$rows=$s['rows'];$width=max(array_map('count',$rows));$book.='<sheet name="'.$name.'" sheetId="'.$id.'" r:id="rId'.$id.'"/>';$rels.='<Relationship Id="rId'.$id.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$id.'.xml"/>';$types.='<Override PartName="/xl/worksheets/sheet'.$id.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
  $xml='<worksheet xmlns="'.$ns.'"><sheetViews><sheetView workbookViewId="0" rightToLeft="1"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><cols><col min="1" max="'.$width.'" width="22" customWidth="1"/></cols><sheetData>';
  foreach($rows as $ri=>$row){$num=$ri+1;$xml.='<row r="'.$num.'" ht="28" customHeight="1">';foreach($row as $ci=>$value){$cell=xlsx_col($ci).$num;$style=$ri===($s['header_row']??0)?1:0;if(is_int($value)||is_float($value))$xml.='<c r="'.$cell.'" s="'.($ri===($s['header_row']??0)?1:2).'"><v>'.$value.'</v></c>';else$xml.='<c r="'.$cell.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.xlsx_text((string)($value??'')).'</t></is></c>';}$xml.='</row>';}
  $merges='';foreach($s['merge_rows']??[]as $merge)$merges.='<mergeCell ref="A'.($merge+1).':'.xlsx_col($width-1).($merge+1).'"/>';if($merges)$merges='<mergeCells count="'.count($s['merge_rows']).'">'.$merges.'</mergeCells>';$xml.='</sheetData>'.$merges.'<printOptions horizontalCentered="1"/><pageMargins left="0.25" right="0.25" top="0.4" bottom="0.4" header="0.2" footer="0.2"/><pageSetup paperSize="9" orientation="landscape" fitToWidth="1" fitToHeight="0"/></worksheet>';$files['xl/worksheets/sheet'.$id.'.xml']=$xml;
  $defined.='<definedName name="_xlnm.Print_Titles" localSheetId="'.$i.'">\''.str_replace("'","''",$name).'\'!$1:$'.(($s['header_row']??0)+1).'</definedName>';
  $defined.='<definedName name="_xlnm.Print_Area" localSheetId="'.$i.'">\''.str_replace("'","''",$name).'\'!$A$1:$'.xlsx_col($width-1).'$'.count($rows).'</definedName>';
 }
 $files['xl/workbook.xml']=$book.'</sheets><definedNames>'.$defined.'</definedNames></workbook>';$files['xl/_rels/workbook.xml.rels']=$rels.'<Relationship Id="styles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';$files['[Content_Types].xml']=$types.'</Types>';$files['_rels/.rels']='<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
 $files['xl/styles.xml']='<styleSheet xmlns="'.$ns.'"><numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0.000"/></numFmts><fonts count="2"><font><sz val="11"/><name val="Arial"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Arial"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF101E32"/></patternFill></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyAlignment="1"><alignment wrapText="1" vertical="center"/></xf><xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
 return xlsx_zip($files);
}
function xlsx_read(string $raw): array {
 $files=xlsx_members($raw);$work=xlsx_xml($files['xl/workbook.xml']??'');$rels=xlsx_xml($files['xl/_rels/workbook.xml.rels']??'');$targets=[];
 foreach($rels->getElementsByTagName('Relationship')as $r){$target=ltrim($r->getAttribute('Target'),'/');$targets[$r->getAttribute('Id')]=str_starts_with($target,'xl/')?$target:'xl/'.$target;}
 $strings=[];if(isset($files['xl/sharedStrings.xml']))foreach(xlsx_xml($files['xl/sharedStrings.xml'])->getElementsByTagName('si')as $si){$t='';foreach($si->getElementsByTagName('t')as $n)$t.=$n->textContent;$strings[]=$t;}
 $out=[];foreach($work->getElementsByTagName('sheet')as $s){$target=$targets[$s->getAttribute('r:id')]??'';if(!isset($files[$target]))fail('ورقة Excel مفقودة.');$rows=[];foreach(xlsx_xml($files[$target])->getElementsByTagName('row')as $row){if(count($rows)>=5001)fail('الحد 5000 سجل لكل ورقة.');$values=[];foreach($row->getElementsByTagName('c')as $c){if($c->getElementsByTagName('f')->length)fail('القالب يقبل قيمًا فقط؛ انسخ النتائج دون معادلات.');$ref=$c->getAttribute('r');preg_match('/^([A-Z]+)[0-9]+$/',$ref,$m);$idx=0;foreach(str_split($m[1]??'A')as $letter)$idx=$idx*26+ord($letter)-64;$idx--;if($idx>100)fail('أعمدة كثيرة داخل الملف.');$v=$c->getElementsByTagName('v')->item(0)?->textContent??'';$type=$c->getAttribute('t');if($type==='s')$v=$strings[(int)$v]??'';elseif($type==='inlineStr'){$v='';foreach($c->getElementsByTagName('t')as $t)$v.=$t->textContent;}$values[$idx]=$v;}$rows[]=$values;}$out[$s->getAttribute('name')]=$rows;}
 return $out;
}
