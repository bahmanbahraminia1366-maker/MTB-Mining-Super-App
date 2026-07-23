<?php
/*
 * JoJo V6.9 cumulative installer
 * - جوجه‌روگ، تاس و معدن: کول‌داون ۴ دقیقه
 * - رفع اجرا نشدن فرمان «ادمین پت»
 * - اجرای JoJo در همه گروه‌هایی که ربات در آن‌ها پیام دریافت می‌کند
 * - مناسب گروه‌هایی که ربات در آن‌ها ادمین شده است
 * - بدون تغییر config.php یا آیدی ادمین
 */

declare(strict_types=1);
@ini_set('display_errors','0');
error_reporting(E_ALL);

function v69_h(string $s): string { return htmlspecialchars($s,ENT_QUOTES,'UTF-8'); }
function v69_page(string $title,string $body,bool $ok=false): void
{
    header('Content-Type: text/html; charset=utf-8');
    $color=$ok?'#22c55e':'#ef4444';
    echo '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.v69_h($title).'</title><style>body{margin:0;padding:24px;background:#0f172a;color:#e5e7eb;font-family:tahoma,Arial;line-height:2}.box{max-width:860px;margin:auto;background:#1e293b;border:1px solid #334155;border-radius:20px;padding:24px}h1{margin-top:0;color:'.$color.'}code{direction:ltr;display:inline-block;background:#020617;padding:3px 8px;border-radius:7px}.ok{color:#4ade80}.warn{color:#facc15}</style></head><body><div class="box"><h1>'.v69_h($title).'</h1>'.$body.'</div></body></html>';
}
function v69_fail(string $message): void { throw new RuntimeException($message); }
function v69_write(string $path,string $content): void
{
    $tmp=$path.'.v69.'.getmypid().'.tmp';
    if(@file_put_contents($tmp,$content,LOCK_EX)===false) v69_fail('نوشتن فایل موقت ممکن نشد: '.$path);
    @chmod($tmp,0644);
    if(!@rename($tmp,$path)){
        if(!@copy($tmp,$path)){@unlink($tmp);v69_fail('جایگزینی فایل ممکن نشد: '.$path);}
        @unlink($tmp);
    }
}
function v69_fetch(string $url): string
{
    if(function_exists('curl_init')){
        $ch=curl_init($url);
        curl_setopt_array($ch,[
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_FOLLOWLOCATION=>true,
            CURLOPT_CONNECTTIMEOUT=>15,
            CURLOPT_TIMEOUT=>50,
            CURLOPT_USERAGENT=>'Derak-JoJo-V69-Installer/1.0'
        ]);
        $body=curl_exec($ch);
        $http=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);
        if(is_string($body) && $body!=='' && $http>=200 && $http<300) return $body;
    }
    if((bool)ini_get('allow_url_fopen')){
        $ctx=stream_context_create(['http'=>['timeout'=>50,'follow_location'=>1,'user_agent'=>'Derak-JoJo-V69-Installer/1.0']]);
        $body=@file_get_contents($url,false,$ctx);
        if(is_string($body) && $body!=='') return $body;
    }
    return '';
}
function v69_bootstrap_v68(string $root,string $petFile): void
{
    $pet=(string)@file_get_contents($petFile);
    if(strpos($pet,'JOJO_V68_COOLDOWN_MINES_PRODUCTION')!==false) return;

    $url='https://raw.githubusercontent.com/bahmanbahraminia1366-maker/MTB-Mining-Super-App/a8eb36a3788ba53dc5f452ce97d6da16fde6cddc/downloads/install_derak_jojo_v68_ready.php';
    $code=v69_fetch($url);
    if($code==='' || strpos($code,'JoJo V6.8 cumulative installer')===false){
        v69_fail('نسخه V6.8 نصب نیست و دریافت نصب‌کننده پایه از GitHub ممکن نشد.');
    }
    $tmp=$root.'/.jojo_v68_bootstrap_'.getmypid().'.php';
    if(@file_put_contents($tmp,$code,LOCK_EX)===false) v69_fail('ساخت فایل موقت V6.8 ممکن نشد.');
    @chmod($tmp,0644);
    ob_start();
    try { require $tmp; } finally { ob_end_clean(); @unlink($tmp); }

    $pet=(string)@file_get_contents($petFile);
    if(strpos($pet,'JOJO_V68_COOLDOWN_MINES_PRODUCTION')===false){
        v69_fail('نصب خودکار پایه V6.8 کامل نشد.');
    }
}

try{
    $root=__DIR__;
    $petFile=$root.'/modules/pet.php';
    $ambotFile=$root.'/ambot.php';
    if(!is_file($petFile)) v69_fail('فایل modules/pet.php پیدا نشد. نصب‌کننده را مستقیم داخل پوشه amir بگذار.');
    if(!is_file($ambotFile)) v69_fail('فایل ambot.php پیدا نشد.');

    v69_bootstrap_v68($root,$petFile);

    $pet=(string)@file_get_contents($petFile);
    $ambot=(string)@file_get_contents($ambotFile);
    if($pet==='' || $ambot==='') v69_fail('خواندن فایل‌های ربات ممکن نشد.');

    if(strpos($pet,'JOJO_V69_FOUR_MINUTES')!==false && strpos($ambot,'DERAK_JOJO_V69_ALL_GROUP_ROUTER')!==false){
        v69_page('✅ قبلاً نصب شده','<p>آپدیت JoJo V6.9 از قبل نصب است.</p>',true);
        exit;
    }

    $backupDir=$root.'/backup';
    if(!is_dir($backupDir) && !@mkdir($backupDir,0755,true)) v69_fail('ساخت پوشه backup ممکن نشد.');
    $stamp=date('Ymd_His');
    $petBackup=$backupDir.'/pet_before_v69_'.$stamp.'.php';
    $ambotBackup=$backupDir.'/ambot_before_v69_'.$stamp.'.php';
    if(@file_put_contents($petBackup,$pet,LOCK_EX)===false) v69_fail('ساخت بکاپ pet.php ممکن نشد.');
    if(@file_put_contents($ambotBackup,$ambot,LOCK_EX)===false) v69_fail('ساخت بکاپ ambot.php ممکن نشد.');

    if(strpos($pet,'JOJO_V69_FOUR_MINUTES')===false){
        $pet=preg_replace('/^<\?php\s*/',"<?php\n/* JOJO_V69_FOUR_MINUTES */\n",$pet,1,$count);
        if($count!==1) v69_fail('هدر pet.php معتبر نیست.');
    }

    /* تاس و معدن: ۴ دقیقه */
    $pet=str_replace(
        "return in_array(\$game,['dice','mine'],true)?300:180;",
        "return in_array(\$game,['dice','mine'],true)?240:180;",
        $pet,
        $casinoCooldownCount
    );
    if($casinoCooldownCount<1 && strpos($pet,"?240:180")===false){
        v69_fail('محل کول‌داون تاس و معدن پیدا نشد.');
    }

    /* جوجه‌روگ: ۴ دقیقه */
    $pet=str_replace(
        "return max(0,300-(time()-\$last));",
        "return max(0,240-(time()-\$last));",
        $pet,
        $rogueCooldownCount
    );
    if($rogueCooldownCount<1 && strpos($pet,'return max(0,240-(time()-$last));')===false){
        v69_fail('محل کول‌داون جوجه‌روگ پیدا نشد.');
    }

    /* مسیریابی یکپارچه JoJo برای خصوصی و همه گروه‌ها */
    $universalRouter=<<<'PHP'
/* DERAK_JOJO_V69_ALL_GROUP_ROUTER
   JoJo در خصوصی و تمام گروه‌هایی که تلگرام پیام را به ربات تحویل می‌دهد اجرا می‌شود.
   ادمین‌کردن ربات در گروه باعث دریافت کامل پیام‌ها و فرمان «ادمین پت» می‌شود. */
if (!function_exists('pet_handle') && is_file(__DIR__.'/modules/pet.php')) {
    require_once __DIR__.'/modules/pet.php';
}
if ($callback && is_string($data) && (strpos($data,'jojo:')===0 || strpos($data,'jj:')===0) && function_exists('pet_callback_handle')) {
    if (pet_callback_handle($update,$users,$states,$config)) {
        goto SAVE_AND_EXIT;
    }
}
if ($msg && function_exists('pet_handle')) {
    if (pet_handle((int)$cid,(int)$uid,(string)$txt,is_array($msg)?$msg:[],$users,$states,$config)) {
        goto SAVE_AND_EXIT;
    }
}
/* DERAK_JOJO_V69_ALL_GROUP_ROUTER_END */
PHP;

    if(strpos($ambot,'DERAK_JOJO_V69_ALL_GROUP_ROUTER')===false){
        $pattern='~\/\* DERAK_JOJO_V100_PRIVATE_ROUTER_START.*?\/\* DERAK_JOJO_V100_PRIVATE_ROUTER_END \*\/~s';
        if(preg_match($pattern,$ambot)){
            $ambot=preg_replace_callback($pattern,static fn(): string => $universalRouter,$ambot,1,$routerCount);
            if(!is_string($ambot) || $routerCount!==1) v69_fail('جایگزینی مسیر JoJo ناموفق بود.');
        }else{
            $anchor='/* ========= CURRENCY CALLBACK ========= */';
            $pos=strpos($ambot,$anchor);
            if($pos===false) v69_fail('محل افزودن مسیر گروه JoJo در ambot.php پیدا نشد.');
            $ambot=substr_replace($ambot,$universalRouter."\n\n",$pos,0);
        }
    }

    foreach([
        'JOJO_V69_FOUR_MINUTES',
        '?240:180',
        'return max(0,240-(time()-$last));',
        'DERAK_JOJO_V69_ALL_GROUP_ROUTER',
        "function_exists('pet_handle')",
        "strpos(\$data,'jojo:')===0"
    ] as $needle){
        $source=strpos($needle,'DERAK_')===0 || strpos($needle,"function_exists('pet_handle')")!==false || strpos($needle,"strpos(\$data")!==false ? $ambot : $pet;
        if(strpos($source,$needle)===false) v69_fail('بررسی نهایی ناموفق بود: '.$needle);
    }

    v69_write($petFile,$pet);
    v69_write($ambotFile,$ambot);

    if(function_exists('opcache_invalidate')){
        @opcache_invalidate($petFile,true);
        @opcache_invalidate($ambotFile,true);
    }

    v69_page('✅ JoJo V6.9 نصب شد',
        '<p class="ok"><b>اصلاحات جدید با موفقیت فعال شد.</b></p>'.
        '<p>✅ جوجه‌روگ، تاس و معدن هرکدام کول‌داون مستقل <b>۴ دقیقه‌ای</b> دارند.</p>'.
        '<p>✅ فرمان <code>ادمین پت</code> دوباره به پنل مدیریت JoJo وصل شد.</p>'.
        '<p>✅ JoJo و دکمه‌های آن در همه گروه‌هایی که ربات پیام دریافت می‌کند اجرا می‌شوند.</p>'.
        '<p>✅ برای دریافت کامل پیام‌های گروه، ربات را داخل همان گروه ادمین نگه دار.</p>'.
        '<p>✅ config.php و آیدی ادمین تغییر نکردند.</p>'.
        '<p>بکاپ‌ها:<br><code>'.v69_h($petBackup).'</code><br><code>'.v69_h($ambotBackup).'</code></p>'.
        '<p class="warn">بعد از اطمینان از عملکرد ربات، فایل نصب‌کننده را از پوشه amir حذف کن.</p>',true);
}catch(Throwable $e){
    v69_page('❌ نصب ناموفق','<p>'.v69_h($e->getMessage()).'</p><p class="warn">فایل‌های اصلی عمداً به‌صورت ناقص جایگزین نشدند؛ بکاپ‌ها داخل پوشه backup هستند.</p>');
}
