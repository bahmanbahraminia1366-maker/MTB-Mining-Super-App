<?php
/* JoJo V6.8 ready installer - one file */
declare(strict_types=1);
@ini_set('display_errors','0');
error_reporting(E_ALL);

function v68ready_fail(string $message): void
{
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{font-family:tahoma,Arial;background:#0f172a;color:#e5e7eb;padding:24px;line-height:2}.box{max-width:760px;margin:auto;background:#1e293b;border-radius:18px;padding:22px}h1{color:#ef4444}</style></head><body><div class="box"><h1>❌ اجرای نصب‌کننده ممکن نشد</h1><p>'.htmlspecialchars($message,ENT_QUOTES,'UTF-8').'</p></div></body></html>';
    exit;
}
function v68ready_fetch(string $url): string
{
    if(function_exists('curl_init')){
        $ch=curl_init($url);
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_CONNECTTIMEOUT=>15,CURLOPT_TIMEOUT=>50,CURLOPT_USERAGENT=>'Derak-JoJo-V68-Ready/1.0']);
        $body=curl_exec($ch);$http=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
        if(is_string($body) && $body!=='' && $http>=200 && $http<300) return $body;
    }
    if((bool)ini_get('allow_url_fopen')){
        $ctx=stream_context_create(['http'=>['timeout'=>50,'follow_location'=>1,'user_agent'=>'Derak-JoJo-V68-Ready/1.0']]);
        $body=@file_get_contents($url,false,$ctx);
        if(is_string($body) && $body!=='') return $body;
    }
    return '';
}

$url='https://raw.githubusercontent.com/bahmanbahraminia1366-maker/MTB-Mining-Super-App/9bd481f3fa7fc7c019a62bcd8265d31b7183d602/downloads/install_derak_jojo_v68_cooldown_mines_production.php';
$code=v68ready_fetch($url);
if($code==='' || strpos($code,'JOJO_V68_COOLDOWN_MINES_PRODUCTION')===false || strpos($code,'<?php')!==0){
    v68ready_fail('هاست نتوانست فایل اصلی V6.8 را از GitHub دریافت کند. دسترسی cURL یا allow_url_fopen را بررسی کن.');
}

$old='https://raw.githubusercontent.com/bahmanbahraminia1366-maker/MTB-Mining-Super-App/jojo-v67-ready/downloads/install_derak_jojo_v67_chick_rogue_ready.php';
$new='https://raw.githubusercontent.com/bahmanbahraminia1366-maker/MTB-Mining-Super-App/ee39871fef330778873620d953a2b955443bea4c/downloads/install_derak_jojo_v67_chick_rogue_ready.php';
$code=str_replace($old,$new,$code,$fixed);
if($fixed!==1) v68ready_fail('ساختار فایل اصلی V6.8 تغییر کرده و اصلاح ایمن آن ممکن نیست.');

$tmp=__DIR__.'/.jojo_v68_ready_'.getmypid().'.php';
if(@file_put_contents($tmp,$code,LOCK_EX)===false) v68ready_fail('ساخت فایل موقت داخل پوشه amir ممکن نشد. سطح دسترسی پوشه را بررسی کن.');
@chmod($tmp,0644);
register_shutdown_function(static function() use ($tmp): void { @unlink($tmp); });
require $tmp;
