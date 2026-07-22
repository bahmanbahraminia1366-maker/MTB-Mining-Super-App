<?php
/*
 * JoJo V6.6 hotfix installer
 * - fresh panels for help/hunt/transfer
 * - reply to the user's «جیک» message
 * - «انتقال میویی» quick amount menu
 * - independent 3-minute casino cooldowns
 * - group JoJo routing without slowing unrelated group messages
 * - avoids rewriting fast_index.json on unchanged/read-only requests
 */

declare(strict_types=1);
header('Content-Type: text/html; charset=utf-8');
@ini_set('display_errors', '0');
error_reporting(E_ALL);

function v66_h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function v66_page(string $title, string $body, bool $ok=false): void {
    $color=$ok?'#1f9d55':'#c0392b';
    echo '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.v66_h($title).'</title><style>body{font-family:tahoma,Arial;background:#111827;color:#f9fafb;margin:0;padding:24px}.box{max-width:820px;margin:auto;background:#1f2937;border-radius:18px;padding:24px;line-height:2;box-shadow:0 15px 50px #0008}h1{color:'.$color.'}code{background:#0f172a;padding:3px 7px;border-radius:6px;direction:ltr;display:inline-block}.ok{color:#34d399}.warn{color:#fbbf24}</style></head><body><div class="box"><h1>'.v66_h($title).'</h1>'.$body.'</div></body></html>';
}
function v66_fail(string $message): void { throw new RuntimeException($message); }
function v66_write(string $path,string $content): void {
    $tmp=$path.'.v66.'.getmypid().'.tmp';
    if(@file_put_contents($tmp,$content,LOCK_EX)===false) v66_fail('نوشتن فایل موقت ممکن نشد: '.$path);
    @chmod($tmp,0644);
    if(!@rename($tmp,$path)){
        if(!@copy($tmp,$path)){@unlink($tmp);v66_fail('جایگزینی فایل ممکن نشد: '.$path);}
        @unlink($tmp);
    }
}
function v66_replace_once(string $code,string $find,string $replace,string $label,bool $alreadyOkay=false): string {
    if($alreadyOkay || strpos($code,$replace)!==false) return $code;
    $pos=strpos($code,$find);
    if($pos===false) v66_fail('محل اصلاح «'.$label.'» در فایل پیدا نشد؛ احتمالاً نسخه فایل با V6.5 متفاوت است.');
    return substr_replace($code,$replace,$pos,strlen($find));
}

try {
    $root=__DIR__;
    $petFile=$root.'/modules/pet.php';
    $ambotFile=$root.'/ambot.php';
    if(!is_file($petFile)) v66_fail('فایل modules/pet.php پیدا نشد. نصب‌کننده را داخل پوشه اصلی ربات (amir) قرار بده.');
    if(!is_file($ambotFile)) v66_fail('فایل ambot.php پیدا نشد.');

    $pet=(string)@file_get_contents($petFile);
    $ambot=(string)@file_get_contents($ambotFile);
    if($pet==='' || $ambot==='') v66_fail('خواندن فایل‌های ربات ممکن نشد.');

    if(strpos($pet,'JOJO_V66_REPLY_TRANSFER_CASINO')!==false && strpos($ambot,'DERAK_JOJO_V66_GROUP_ROUTER')!==false){
        v66_page('✅ قبلاً نصب شده','<p>JoJo V6.6 از قبل روی این ربات نصب شده است.</p>',true);
        exit;
    }

    $backupDir=$root.'/backup_jojo_v66';
    if(!is_dir($backupDir) && !@mkdir($backupDir,0755,true)) v66_fail('ساخت پوشه بکاپ ممکن نشد.');
    $stamp=date('Ymd_His');
    $petBackup=$backupDir.'/pet_before_v66_'.$stamp.'.php';
    $ambotBackup=$backupDir.'/ambot_before_v66_'.$stamp.'.php';
    if(@file_put_contents($petBackup,$pet,LOCK_EX)===false || @file_put_contents($ambotBackup,$ambot,LOCK_EX)===false) v66_fail('ساخت بکاپ ممکن نشد.');

    // Marker
    if(strpos($pet,'JOJO_V66_REPLY_TRANSFER_CASINO')===false){
        $pet=preg_replace('/^<\?php\s*/',"<?php\n/* JOJO_V66_REPLY_TRANSFER_CASINO */\n",$pet,1,$count);
        if($count!==1) v66_fail('هدر pet.php معتبر نیست.');
    }

    // Do not rewrite the fast index for read-only requests.
    $pet=v66_replace_once($pet,
"    if ((\$GLOBALS['JOJO_DB_LOADED_HASH']??'')===\$newHash) {\n        jojo_fast_index_write(\$db);\n        return true;\n    }",
"    if ((\$GLOBALS['JOJO_DB_LOADED_HASH']??'')===\$newHash) {\n        // درخواست فقط خواندنی است؛ نوشتن دوباره fast_index فقط دیسک و CPU را درگیر می‌کرد.\n        return true;\n    }",
'کاهش نوشتن دیسک');

    // Make transfer aliases recognizable by the fast command guard.
    $pet=v66_replace_once($pet,
"    if (\$t==='') return false;\n",
"    if (\$t==='') return false;\n    if (in_array(\$t,['انتقال میویی','انتقال میو','انتقال جیک'],true)) return true;\n    if (preg_match('~^(?:انتقال میویی|انتقال میو)(?:\\s|$)~u',\$t)) return true;\n",
'شناخت فرمان انتقال میویی');

    // Commands that must always create a fresh panel.
    $pet=v66_replace_once($pet,
"    if(jojo_is_pet_name_call(\$txt,\$u)) return true;\n    return in_array(\$txt,[",
"    if(jojo_is_pet_name_call(\$txt,\$u)) return true;\n    if(in_array(\$txt,['راهنما جیک','راهنمای جیک','❓ راهنمای JoJo','شکار','شکار کرم','🎣 شکار کرم','انتقال میویی','انتقال میو','انتقال جیک'],true)) return true;\n    if(preg_match('~^(?:انتقال میویی|انتقال میو|انتقال جیک)(?:\\s|$)~u',\$txt)) return true;\n    return in_array(\$txt,[",
'پنل تازه راهنما، شکار و انتقال');

    // Reply the mission panel to the original «جیک» message.
    $pet=v66_replace_once($pet,
"    \$isFreshEntry = jojo_is_fresh_entry_command(\$txt, \$db['users'][(string)\$uid] ?? []);\n    if (\$isFreshEntry) {",
"    \$isFreshEntry = jojo_is_fresh_entry_command(\$txt, \$db['users'][(string)\$uid] ?? []);\n    if (\$isFreshEntry && in_array(\$txt,['جیک','💰 جیک'],true) && !empty(\$msg['message_id'])) {\n        // فقط اولین پیام پنل مأموریت به پیام «جیک» کاربر ریپلای می‌شود.\n        \$GLOBALS['JOJO_REPLY_TO_MESSAGE_ID']=(int)\$msg['message_id'];\n    }\n    if (\$isFreshEntry) {",
'ریپلای جیک');

    // Apply reply_to_message_id only to the next newly sent text panel.
    $pet=v66_replace_once($pet,
"    \$p=['chat_id'=>\$cid,'text'=>\$text,'parse_mode'=>\$parse,'disable_web_page_preview'=>true];\n    if (\$keyboard!==null) \$p['reply_markup']=\$keyboardJson;\n    \$res=bot('sendMessage',\$p);",
"    \$p=['chat_id'=>\$cid,'text'=>\$text,'parse_mode'=>\$parse,'disable_web_page_preview'=>true];\n    if (\$keyboard!==null) \$p['reply_markup']=\$keyboardJson;\n    \$replyId=(int)(\$GLOBALS['JOJO_REPLY_TO_MESSAGE_ID']??0);\n    if(\$replyId>0){\n        \$p['reply_to_message_id']=\$replyId;\n        \$p['allow_sending_without_reply']=true;\n        unset(\$GLOBALS['JOJO_REPLY_TO_MESSAGE_ID']);\n    }\n    \$res=bot('sendMessage',\$p);",
'اعمال ریپلای روی پیام تازه');

    // Quick transfer menu.
    $transferFunctions=<<<'PHP'
function jojo_transfer_quick_menu(int $cid,int $uid,array &$db,int $target,array $targetFrom=[]): void
{
    if($target<=0){
        jojo_send($cid,"🔄 <b>انتقال میویی</b>\n\nروی پیام بازیکن ریپلای کن و دوباره بنویس <code>انتقال میویی</code>.\n\nیا مستقیم بنویس: <code>انتقال میویی 500 آیدی‌عددی</code>");
        return;
    }
    if($target===$uid){jojo_send($cid,'❌ نمی‌توانی برای خودت میویی انتقال بدهی.');return;}
    if(!isset($db['users'][(string)$target]) && $targetFrom) jojo_touch_user($db,$target,$targetFrom);
    if(!isset($db['users'][(string)$target])){jojo_send($cid,'❌ گیرنده هنوز حساب JoJo ندارد.');return;}
    $receiver=htmlspecialchars(jojo_user_name($db['users'][(string)$target]),ENT_QUOTES,'UTF-8');
    jojo_send($cid,
        "╭─ 🔄 <b>انتقال میویی</b> ─╮\n".
        "👤 گیرنده: <b>{$receiver}</b>\n".
        "🆔 آیدی: <code>{$target}</code>\n\n".
        "مبلغ را انتخاب کن:\n".
        "╰─ ୨ৎ ─────── ୨ৎ ─╯",
        jojo_inline([
            [
                ['text'=>'100 JP','callback_data'=>'jojo:transfer:quick:'.$target.':100'],
                ['text'=>'500 JP','callback_data'=>'jojo:transfer:quick:'.$target.':500']
            ],
            [
                ['text'=>'1K JP','callback_data'=>'jojo:transfer:quick:'.$target.':1000'],
                ['text'=>'5K JP','callback_data'=>'jojo:transfer:quick:'.$target.':5000']
            ],
            [['text'=>'❌ بستن','callback_data'=>'jojo:transfer:cancel']]
        ])
    );
}

PHP;
    $pet=v66_replace_once($pet,"function jojo_transfer_prepare(int \$cid,int \$uid,array &\$db,int \$target,int \$amount,array \$targetFrom=[]): void\n{",$transferFunctions."function jojo_transfer_prepare(int \$cid,int \$uid,array &\$db,int \$target,int \$amount,array \$targetFrom=[]): void\n{",'تابع منوی انتقال');

    // Route exact «انتقال میویی» and amount aliases.
    $transferRoute=<<<'PHP'
    if (in_array($txt,['انتقال میویی','انتقال میو','انتقال جیک'],true)) {
        $target=0;$targetFrom=[];
        if(!empty($msg['reply_to_message']['from'])){
            $targetFrom=(array)$msg['reply_to_message']['from'];
            $target=(int)($targetFrom['id']??0);
        }
        jojo_transfer_quick_menu($cid,$uid,$db,$target,$targetFrom);
        return true;
    }
    if (preg_match('~^(?:انتقال میویی|انتقال میو)\s+(\d+)(?:\s+(?:به\s+)?(\d+))?$~u',$txt,$m)) {
        $amount=(int)$m[1];$target=(int)($m[2]??0);$targetFrom=[];
        if($target<=0 && !empty($msg['reply_to_message']['from'])){
            $targetFrom=(array)$msg['reply_to_message']['from'];
            $target=(int)($targetFrom['id']??0);
        }
        jojo_transfer_prepare($cid,$uid,$db,$target,$amount,$targetFrom);
        return true;
    }
PHP;
    $pet=v66_replace_once($pet,
"    if (preg_match('~^انتقال جیک\\s+(\\d+)(?:\\s+(?:به\\s+)?(\\d+))?$~u',\$txt,\$m)) {",
$transferRoute."    if (preg_match('~^انتقال جیک\\s+(\\d+)(?:\\s+(?:به\\s+)?(\\d+))?$~u',\$txt,\$m)) {",
'مسیر انتقال میویی');

    // Quick amount callback.
    $quickCallback=<<<'PHP'
    if(preg_match('~^jojo:transfer:quick:(\d+):(\d+)$~',$data,$m)){
        jojo_transfer_prepare($cid,$uid,$db,(int)$m[1],(int)$m[2],[]);
        return true;
    }
PHP;
    $pet=v66_replace_once($pet,"    if(\$data==='jojo:transfer:confirm'){",$quickCallback."    if(\$data==='jojo:transfer:confirm'){",'دکمه‌های انتقال سریع');

    // Casino 3-minute independent cooldown helpers.
    $casinoHelpers=<<<'PHP'
function jojo_casino_cooldown_guard(int $cid,int $uid,array &$db,string $game,string $title): bool
{
    $last=(int)($db['users'][(string)$uid]['casino_cooldowns'][$game]??0);
    $left=max(0,180-(time()-$last));
    if($left<=0) return true;
    $min=(int)ceil($left/60);
    jojo_send($cid,"⏳ <b>{$title}</b> هنوز آماده نیست.\n\nحدود <b>{$min} دقیقه</b> دیگر دوباره امتحان کن.");
    return false;
}
function jojo_casino_cooldown_mark(int $uid,array &$db,string $game): void
{
    $db['users'][(string)$uid]['casino_cooldowns']??=[];
    $db['users'][(string)$uid]['casino_cooldowns'][$game]=time();
}

PHP;
    $pet=v66_replace_once($pet,"function jojo_casino_dice_play(int \$cid,int \$uid,array &\$db,int \$stake,string \$pick): void\n{",$casinoHelpers."function jojo_casino_dice_play(int \$cid,int \$uid,array &\$db,int \$stake,string \$pick): void\n{",'توابع کول‌داون کازینو');

    $pet=v66_replace_once($pet,
"    if(!in_array(\$pick,['even','odd'],true) || !jojo_casino_valid_stake(\$cid,\$uid,\$db,\$stake)) return;\n\n    \$pickFa=",
"    if(!in_array(\$pick,['even','odd'],true) || !jojo_casino_valid_stake(\$cid,\$uid,\$db,\$stake)) return;\n    if(!jojo_casino_cooldown_guard(\$cid,\$uid,\$db,'dice','تاس جیک‌جیکو')) return;\n    jojo_casino_cooldown_mark(\$uid,\$db,'dice');\n\n    \$pickFa=",
'کول‌داون تاس');

    $pet=v66_replace_once($pet,
"    if(!jojo_casino_valid_stake(\$cid,\$uid,\$db,\$stake)) return;\n\n    jojo_casino_wait_panel(\n        \$cid,\n        \"🎰 <b>گردونه کازینو جیک‌جیکو</b>\"",
"    if(!jojo_casino_valid_stake(\$cid,\$uid,\$db,\$stake)) return;\n    if(!jojo_casino_cooldown_guard(\$cid,\$uid,\$db,'wheel','گردونه جیک‌جیکو')) return;\n    jojo_casino_cooldown_mark(\$uid,\$db,'wheel');\n\n    jojo_casino_wait_panel(\n        \$cid,\n        \"🎰 <b>گردونه کازینو جیک‌جیکو</b>\"",
'کول‌داون گردونه');

    $pet=v66_replace_once($pet,
"    if(!jojo_casino_valid_stake(\$cid,\$uid,\$db,\$stake)) return;\n    jojo_add_jp(\$db,\$uid,-\$stake,'کازینو جیک‌جیکو: معدن');",
"    if(!jojo_casino_valid_stake(\$cid,\$uid,\$db,\$stake)) return;\n    if(!jojo_casino_cooldown_guard(\$cid,\$uid,\$db,'mine','معدن جیک‌جیکو')) return;\n    jojo_casino_cooldown_mark(\$uid,\$db,'mine');\n    jojo_add_jp(\$db,\$uid,-\$stake,'کازینو جیک‌جیکو: معدن');",
'کول‌داون معدن');

    // ambot: guarantee module load and route only JoJo-specific group messages.
    if(strpos($ambot,'DERAK_JOJO_V66_GROUP_ROUTER')===false){
        $loadPatch=<<<'PHP'
/* DERAK_JOJO_V66_GROUP_ROUTER */
if (!function_exists('pet_handle') && is_file(__DIR__.'/modules/pet.php')) {
    require_once __DIR__.'/modules/pet.php';
}
PHP;
        $ambot=v66_replace_once($ambot,"/* DERAK_JOJO_V100_PRIVATE_ROUTER_START",$loadPatch."\n/* DERAK_JOJO_V100_PRIVATE_ROUTER_START",'بارگذاری pet.php');

        $groupPatch=<<<'PHP'
/* DERAK_JOJO_V66_GROUP_DIRECT_START */
$__jojoGroupText=(string)$txt;
$__jojoGroupDirect=in_array($__jojoGroupText,[
    'جیک','💰 جیک','جیکو','📊 جیکو','راهنما جیک','راهنمای جیک','❓ راهنمای JoJo',
    'شکار','شکار کرم','🎣 شکار کرم','قلاب','منوی قلاب',
    'انتقال میویی','انتقال میو','انتقال جیک','جوجه من','🐥 جوجه من','کارخانه جیک','کارخونه جیک'
],true)
    || preg_match('~^(?:انتقال میویی|انتقال میو|انتقال جیک)(?:\s|$)~u',$__jojoGroupText)
    || (function_exists('jojo_fast_context_needs_db') && jojo_fast_context_needs_db((int)$uid,(int)$cid,(array)$config,$__jojoGroupText));
if($is_group && $callback && is_string($data) && strpos($data,'jojo:')===0 && function_exists('pet_callback_handle')){
    if(pet_callback_handle($update,$users,$states,$config)) goto SAVE_AND_EXIT;
}
if($is_group && $msg && $__jojoGroupDirect && function_exists('pet_handle')){
    if(pet_handle((int)$cid,(int)$uid,$__jojoGroupText,is_array($msg)?$msg:[],$users,$states,$config)) goto SAVE_AND_EXIT;
}
unset($__jojoGroupText,$__jojoGroupDirect);
/* DERAK_JOJO_V66_GROUP_DIRECT_END */

PHP;
        $ambot=v66_replace_once($ambot,"/* ========= CURRENCY CALLBACK ========= */",$groupPatch."/* ========= CURRENCY CALLBACK ========= */",'مسیر گروه JoJo');
    }

    // Basic syntax sanity checks without executing the bot.
    foreach([
        'function jojo_transfer_quick_menu',
        'JOJO_REPLY_TO_MESSAGE_ID',
        'function jojo_casino_cooldown_guard',
        "'انتقال میویی'"
    ] as $needle){ if(strpos($pet,$needle)===false) v66_fail('بررسی نهایی pet.php ناموفق بود: '.$needle); }
    if(strpos($ambot,'DERAK_JOJO_V66_GROUP_DIRECT_START')===false) v66_fail('بررسی مسیر گروه ناموفق بود.');

    v66_write($petFile,$pet);
    v66_write($ambotFile,$ambot);

    // Clear opcode cache where available.
    if(function_exists('opcache_invalidate')){
        @opcache_invalidate($petFile,true);
        @opcache_invalidate($ambotFile,true);
    }

    $selfDeleted=@unlink(__FILE__);
    $body='<p class="ok"><b>JoJo V6.6 با موفقیت نصب شد.</b></p>'.
        '<p>✅ راهنما جیک، شکار و انتقال میویی همیشه پنل تازه می‌سازند.</p>'.
        '<p>✅ پاسخ مأموریت جیک روی پیام خود کاربر ریپلای می‌شود.</p>'.
        '<p>✅ انتقال میویی روی پیام ریپلای‌شده، منوی 100 / 500 / 1K / 5K می‌سازد.</p>'.
        '<p>✅ تاس، گردونه و معدن هرکدام کول‌داون مستقل ۳ دقیقه‌ای دارند.</p>'.
        '<p>✅ پیام‌های نامرتبط گروه بدون خواندن دیتابیس JoJo عبور می‌کنند.</p>'.
        '<p>✅ آیدی ادمین و config.php تغییر نکرد.</p>'.
        '<p>بکاپ‌ها:<br><code>'.v66_h(str_replace($root.'/','',$petBackup)).'</code><br><code>'.v66_h(str_replace($root.'/','',$ambotBackup)).'</code></p>'.
        ($selfDeleted?'<p>نصب‌کننده خودکار حذف شد.</p>':'<p class="warn">فایل نصب‌کننده را دستی حذف کن.</p>');
    v66_page('✅ نصب موفق JoJo V6.6',$body,true);
} catch(Throwable $e){
    v66_page('❌ نصب ناموفق','<p>'.v66_h($e->getMessage()).'</p><p class="warn">هیچ فایل ناقصی عمداً ذخیره نشد؛ از بکاپ استفاده کن.</p>',false);
}
