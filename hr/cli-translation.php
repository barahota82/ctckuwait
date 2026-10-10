<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/app/core.php';require __DIR__.'/app/languages.php';
// A scheduled invocation performs one bounded translation batch.
echo jencode(translation_process()).PHP_EOL;
