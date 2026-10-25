<?php
declare(strict_types=1);
// Unauthenticated reachability probe: no session, employee data or database access.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');
$nonce=(string)($_GET['nonce']??'');
if($_SERVER['REQUEST_METHOD']!=='GET'||!preg_match('/^[a-zA-Z0-9.-]{1,100}$/',$nonce)){http_response_code(400);echo '{"ok":false}';exit;}
echo json_encode(['ok'=>true,'nonce'=>$nonce],JSON_THROW_ON_ERROR);
