<?php
/* JoJo V6.7 ready installer - one file */
declare(strict_types=1);
@ini_set('display_errors','0');
error_reporting(E_ALL);

function ready_fail(string $message): void
{
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{font-family:tahoma,Arial;background:#0f172a;color:#e5e7eb;padding:24px;line-height:2}.box{max-width:760px;margin:auto;background:#1e293b;border-radius:18px;padding:22px}h1{color:#ef4444}</style></head><body><div class="box"><h1>❌ اجرای نصب‌کننده ممکن نشد</h1><p>'.htmlspecialchars($message,ENT_QUOTES,'UTF-8').'</p></div></body></html>';
    exit;
}
function ready_fetch(string $url): string
{
    if(function_exists('curl_init')){
        $ch=curl_init($url);
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_CONNECTTIMEOUT=>15,CURLOPT_TIMEOUT=>40,CURLOPT_USERAGENT=>'Derak-JoJo-V67-Installer/1.0']);
        $body=curl_exec($ch);
        $code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);
        if(is_string($body) && $body!=='' && $code>=200 && $code<300) return $body;
    }
    if((bool)ini_get('allow_url_fopen')){
        $ctx=stream_context_create(['http'=>['timeout'=>40,'follow_location'=>1,'user_agent'=>'Derak-JoJo-V67-Installer/1.0']]);
        $body=@file_get_contents($url,false,$ctx);
        if(is_string($body) && $body!=='') return $body;
    }
    return '';
}

$url='https://raw.githubusercontent.com/bahmanbahraminia1366-maker/MTB-Mining-Super-App/964ce932ae96a672344a5672bbd52f2fb8ff9bf0/downloads/install_derak_jojo_v67_chick_rogue.php';
$code=ready_fetch($url);
if($code==='' || strpos($code,'JOJO_V67_CHICK_ROGUE')===false || strpos($code,'<?php')!==0){ready_fail('هاست نتوانست فایل اصلی آپدیت را از GitHub دریافت کند. دسترسی خروجی cURL یا allow_url_fopen را بررسی کن.');}
$bad="\\\\n\".\\n        \"";
$good="\\\\n\".\n        \"";
$code=str_replace($bad,$good,$code,$fixed);
if($fixed<2){ready_fail('ساختار فایل منبع تغییر کرده و اصلاح ایمن آن ممکن نیست.');}
$tmp=__DIR__.'/.jojo_v67_ready_'.getmypid().'.php';
if(@file_put_contents($tmp,$code,LOCK_EX)===false) ready_fail('ساخت فایل موقت داخل پوشه amir ممکن نشد. سطح دسترسی پوشه را بررسی کن.');
@chmod($tmp,0644);
register_shutdown_function(static function() use ($tmp): void { @unlink($tmp); });
require $tmp;
