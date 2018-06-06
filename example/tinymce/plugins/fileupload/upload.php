<?php

set_time_limit(0);

header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');	
header('content-type: application/x-javascript; charset=utf-8');

$global_cer_on_done = false;
$global_cer_on_success = false;
$global_cer_on_error = false;

function cer($p = []){
	global $global_cer_on_error, $global_cer_on_success, $global_cer_on_done;
    $p['error'] = (isset($p['error']) && $p['error']) ? true : false;
    
    if($p['error'] && ($global_cer_on_error && is_callable($global_cer_on_error))) call_user_func($global_cer_on_error);
    if(!$p['error'] && ($global_cer_on_success && is_callable($global_cer_on_success))) call_user_func($global_cer_on_success);
    if($global_cer_on_done && is_callable($global_cer_on_done)) call_user_func($global_cer_on_done);	    
    
	$arr['response'] = ['code' => (isset($p['code']) ? $p['code'] : "Y001"), 'message' => (isset($p['message']) ? $p['message'] : "")];
	if(isset($p['src'])) $arr['data'] = $p['src'];
    exit(json_encode($arr));
}
function cerError($msg, $code = "E001"){ cer(['error' => true, 'code' => $code, 'message' => $msg]); }

function api($uri, $data = []){
	global $_cfg;
	$uri = trim($uri, '/');
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'x-key: '.$_cfg['api']['key']]);
	curl_setopt($ch, CURLOPT_URL, 'https://'.$_cfg['api']['url'].'/'.$_cfg['api']['space'].'/'.$uri.'/');
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
	curl_setopt($ch, CURLOPT_HEADER, false);
	$sh_rc = curl_exec($ch);
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$error = curl_errno($ch);
	curl_close($ch);
	
	if($error > 0) return ['error' => ['code' => "E483", 'message' => "Error 00246: Failed to process request."]];
	
	$sh = @json_decode($sh_rc, true);
	if(is_null($sh) || !is_array($sh)) return ['error' => ['code' => "E488", 'message' => "Error 00249: Failed to process request."]];
	
	return array_merge($sh, ['http_code' => $http_code]);
}

define('basedir', dirname(__FILE__));
define('ds', '/');

require_once(basedir.ds.'config.php');

if(!isset($_FILES['upfile'])) cerError("Error 00251: No files uploaded.");

$fuperr = $_FILES['upfile']['error'];
if($fuperr == 1) cerError('Error 00562: File size is higher than allowed.');
if($fuperr != 0) cerError('Error 00524: Failed to upload.');

$i = pathinfo($_FILES['upfile']['name']);
if(!isset($i['extension']) || empty($i['extension'])) cerError('Error 00424: The uploaded file has no extension defined.');

$extension = strtolower($i['extension']);
$file_name = $i['filename'];

$f = md5(uniqid(rand(), true).$file_name).'.'.$extension;
$f = $_cfg['tmp_dir'].ds.$f;

if(!@move_uploaded_file($_FILES['upfile']['tmp_name'], $f)) cerError('Error 00452: Failed to process upload.');

$global_cer_on_done = function(){
	global $f;
	@unlink($f);
};

$file_size = filesize($f);
$file_mime = mime_content_type($f);

/********************************************************************************************************************************************/

$sd = api('dir/add', ['data' => ['name' => $_cfg['api']['directory']]]);
if(array_key_exists('error', $sd)) cerError("Error 00302: Failed to process request.");
if(!in_array($sd['response']['code'], ["Y001", "E380"])) cerError("Error 00306: Failed to create or get the directory \"".$_cfg['api']['directory']."\".");
$ds1 = $sd['data'];

$c_year = date("Y");
$c_month = date("m");

$sd = api('dir/add', ['data' => ['parent_id' => $ds1['id'], 'name' => $c_year]]);
if(array_key_exists('error', $sd)) cerError("Error 00312: Failed to process request.");
if(!in_array($sd['response']['code'], ["Y001", "E380"])) cerError("Error 00316: Failed to create or get the directory \"".$c_year."\".");
$ds2 = $sd['data'];

$sd = api('dir/add', ['data' => ['parent_id' => $ds2['id'], 'name' => $c_month]]);
if(array_key_exists('error', $sd)) cerError("Erro 00321: Failed to process request.");
if(!in_array($sd['response']['code'], ["Y001", "E380"])) cerError("Error 00324: Failed to create or get the directory \"".$c_month."\".");
$ds3 = $sd['data'];

/********************************************************************************************************************************************/

$sd = api('file/upload/auth');
if(array_key_exists('error', $sd)) cerError("Error 00339: Failed to process request.");
if($sd['response']['code'] != "Y001") cerError("Error 00344: Failed to process request.");
$sdd = $sd['data'];

$post_data = [
    'file' => new CURLFile($f, $file_mime, $file_name.'.'.$extension),
    'dir_id' => $ds3['id']
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['x-auth: '.$sdd['auth']]);
curl_setopt($ch, CURLOPT_URL, $sdd['uri']);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'free_uapi-1.0');
$sh_rc = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_errno($ch);
curl_close($ch);

if($error > 0) cerError("Error 00348: Failed to process request.");

$sh = @json_decode($sh_rc, true);
if(is_null($sh) || !is_array($sh)) cerError("Error 00353: Failed to process request.");
if($sh['response']['code'] != "Y001") cerError("Error 00356: Failed to process request.");
$file = $sh['data'];
$src = ['file' => array_merge($file, ['url' => ('http'.(!empty($_SERVER['HTTPS'])?'s':'').'://').$file['url']])];

if(in_array(strtolower($file_mime), ['image/gif', 'image/png', 'image/jpeg'])){
	list($width, $height) = getimagesize($f);
	$src = array_merge($src, ['width' => $width, 'height' => $height]);
}

cer(['src' => $src]);