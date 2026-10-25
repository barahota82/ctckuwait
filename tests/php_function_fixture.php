<?php
declare(strict_types=1);
/** Load selected unchanged production functions while keeping the test's DB stubs. */
function load_production_functions(string $path,array $names):void {
 $tokens=token_get_all(file_get_contents($path));$wanted=array_fill_keys($names,true);$loaded=[];
 for($i=0,$n=count($tokens);$i<$n;$i++){
  if(!is_array($tokens[$i])||$tokens[$i][0]!==T_FUNCTION)continue;
  $start=$i;$name=null;
  for($j=$i+1;$j<$n;$j++){
   if(is_array($tokens[$j])&&$tokens[$j][0]===T_WHITESPACE)continue;
   if(is_array($tokens[$j])&&$tokens[$j][0]===T_STRING)$name=$tokens[$j][1];
   break;
  }
  if(!isset($wanted[$name]))continue;
  $body='';$depth=0;$begun=false;
  for($j=$start;$j<$n;$j++){
   $token=$tokens[$j];$body.=is_array($token)?$token[1]:$token;
   if($token==='{'){$depth++;$begun=true;}
   elseif(is_array($token)&&in_array($token[0],[T_CURLY_OPEN,T_DOLLAR_OPEN_CURLY_BRACES],true))$depth++;
   elseif($token==='}'&&--$depth===0&&$begun)break;
  }
  eval($body);$loaded[$name]=true;$i=$j;
 }
 if(array_diff_key($wanted,$loaded))throw new RuntimeException('Missing production function fixture');
}
