<?php
declare(strict_types=1);
/** Country codes identify nationality choices; legacy text is retained until explicitly resolved. */
function nationality_catalog137():array{
 static $catalog=null;
 return $catalog??=json_decode(file_get_contents(__DIR__.'/nationalities.json'),true,512,JSON_THROW_ON_ERROR);
}
function nationality_key137(string $value):string{
 $value=mb_strtolower(trim($value),'UTF-8');$value=preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u','',$value);
 return strtr($value,['أ'=>'ا','إ'=>'ا','آ'=>'ا','ٱ'=>'ا','ى'=>'ي','ة'=>'ه']);
}
function nationality_find137(string $value):?array{
 if(trim($value)==='')return null;$key=nationality_key137($value);
 foreach(nationality_catalog137()as $r)foreach(array_merge([$r['code'],$r['ar'],$r['en']],$r['aliases']??[])as $alias)if(nationality_key137($alias)===$key)return $r;
 return null;
}
function nationality_profile137(array &$profile,array $previous=[]):void{
 if(!array_key_exists('nationality',$profile)){if(array_key_exists('nationality',$previous))$profile['nationality']=$previous['nationality'];if(isset($previous['nationality_code137']))$profile['nationality_code137']=$previous['nationality_code137'];return;}
 if(!is_string($profile['nationality']))fail('اختر الجنسية من القائمة.');
 $raw=trim($profile['nationality']);if($raw===''){$profile['nationality']='';unset($profile['nationality_code137']);return;}
 $row=nationality_find137($raw);
 if($row){$profile['nationality']=$row['en'];$profile['nationality_code137']=$row['code'];return;}
 // Never guess or erase an unresolved existing nationality on an unrelated edit.
 if($raw===trim((string)($previous['nationality']??''))){$profile['nationality']=$previous['nationality'];unset($profile['nationality_code137']);return;}
 fail('اختر الجنسية من القائمة.');
}
