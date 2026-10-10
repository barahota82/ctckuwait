<?php
declare(strict_types=1);
function public_interface_texts(string $language):array{static $dictionary=null;$dictionary??=decode(file_get_contents(__DIR__.'/public-interface.json'));return array_replace($dictionary['en'],$dictionary[$language]??[]);}
