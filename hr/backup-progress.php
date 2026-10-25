<?php
declare(strict_types=1);
// Read-only, short-lived capability receipt. No session/database locks or restore action.
ini_set('display_errors','0');header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
function progress_response107(int $status,array $body):never{http_response_code($status);echo json_encode($body,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
if(($_SERVER['REQUEST_METHOD']??'')!=='GET')progress_response107(405,['error'=>'Method not allowed']);
if((empty($_SERVER['HTTPS'])||$_SERVER['HTTPS']==='off')&&(int)($_SERVER['SERVER_PORT']??0)!==443)progress_response107(403,['error'=>'HTTPS required']);
$token=(string)($_SERVER['HTTP_X_EP_RESTORE_PROGRESS']??'');if(!preg_match('/^[a-f0-9]{64}$/D',$token))progress_response107(404,['error'=>'Not found']);
$path=__DIR__.'/private/restore-progress107-'.$token.'.json';$raw=@file_get_contents($path);$data=$raw===false?null:json_decode($raw,true);
$phases=['start','validate','preflight','safety_backup','delete','insert','files','session_epoch','commit','complete','failed'];
if(!is_array($data)||($data['expires']??0)<time()||!in_array($data['phase']??'',$phases,true)||!is_int($data['done']??null)||!is_int($data['total']??null)||$data['done']<0||$data['total']<0||($data['total']&&$data['done']>$data['total']))progress_response107(404,['error'=>'Not found']);
progress_response107(200,['phase'=>$data['phase'],'done'=>$data['done'],'total'=>$data['total']]);
