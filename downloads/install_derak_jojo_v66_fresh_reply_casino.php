<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');
@set_time_limit(60);

function v66_h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function v66_page(string $title, string $body, bool $ok=false): void {
    $color=$ok?'#166534':'#991b1b';
    echo '<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.v66_h($title).'</title><style>body{font-family:tahoma,Arial;background:#0f172a;color:#e2e8f0;padding:22px;line-height:1.9}.box{max-width:850px;margin:auto;background:#1e293b;border:1px solid #334155;border-radius:18px;padding:22px}h2{color:#fff}code{direction:ltr;display:inline-block;background:#020617;padding:2px 7px;border-radius:7px;color:#86efac}.status{background:'.$color.';padding:10px 14px;border-radius:10px;font-weight:bold}</style><body><div class="box"><h2>'.v66_h($title).'</h2>'.$body.'</div></body></html>';
}
function v66_replace_once(string &$src,string $old,string $new,string $label): void {
    $count=0;
    $src=str_replace($old,$new,$src,$count);
    if($count!==1) throw new RuntimeException($label.' پیدا نشد یا چندبار تکرار شده بود ('.$count.').');
}
function v66_replace_min(string &$src,string $old,string $new,int $min,string $label): void {
    $count=0;
    $src=str_replace($old,$new,$src,$count);
    if($count<$min) throw new RuntimeException($label.' به تعداد لازم پیدا نشد ('.$count.').');
}
function v66_regex_once(string &$src,string $pattern,string $replacement,string $label): void {
    $count=0;
    $next=preg_replace_callback($pattern,static function() use ($replacement){return $replacement;},$src,1,$count);
    if(!is_string($next) || $count!==1) throw new RuntimeException($label.' اعمال نشد ('.$count.').');
    $src=$next;
}
function v66_atomic_write(string $path,string $data): void {
    $tmp=$path.'.v66.'.getmypid().'.tmp';
    if(@file_put_contents($tmp,$data,LOCK_EX)===false) throw new RuntimeException('نوشتن فایل موقت ممکن نشد: '.$path);
    @chmod($tmp,0644);
    if(!@rename($tmp,$path)){
        if(!@copy($tmp,$path)){@unlink($tmp);throw new RuntimeException('جایگزینی فایل ممکن نشد: '.$path);}
        @unlink($tmp);
    }
}

try {
    $root=__DIR__;
    $petPath=$root.'/modules/pet.php';
    $adminPath=$root.'/modules/pet_admin.php';
    if(!is_file($petPath)) throw new RuntimeException('فایل modules/pet.php پیدا نشد. نصب‌کننده باید داخل ریشه ربات، پوشه amir، قرار بگیرد.');

    $pet=(string)@file_get_contents($petPath);
    if($pet==='' || strpos($pet,'function jojo_handle')===false) throw new RuntimeException('فایل pet.php معتبر نیست یا نسخه JoJo داخل آن پیدا نشد.');

    $backupDir=$root.'/backups/jojo_v66_'.date('Ymd_His');
    if(!is_dir($backupDir) && !@mkdir($backupDir,0755,true)) throw new RuntimeException('ساخت پوشه بکاپ ممکن نشد.');
    if(!@copy($petPath,$backupDir.'/pet.php')) throw new RuntimeException('بکاپ pet.php ساخته نشد.');
    if(is_file($adminPath)) @copy($adminPath,$backupDir.'/pet_admin.php');

    // نسخه نمایشی
    $pet=str_replace('JOJO WORLD V6.5 - DIRECT CONFIG ADMIN ACCESS','JOJO WORLD V6.6 - FRESH PANELS, REPLY JIK, FAST IO, 3M CASINO',$pet);

    // تنظیم پیش‌فرض کازینو: هر بازی سه دقیقه کول‌داون مستقل دارد.
    v66_replace_once(
        $pet,
        "'pet_production_base_capacity'=>250,'pet_production_capacity_step'=>225,'pet_refusal_chance'=>12,'jik_mission_timeout'=>180",
        "'pet_production_base_capacity'=>250,'pet_production_capacity_step'=>225,'pet_refusal_chance'=>12,'jik_mission_timeout'=>180,'casino_cooldown'=>180",
        'تنظیم casino_cooldown'
    );
    v66_replace_min(
        $pet,
        "['last_game'=>'','best_multiplier'=>0]",
        "['last_game'=>'','best_multiplier'=>0,'last_play_at'=>['dice'=>0,'wheel'=>0,'mine'=>0]]",
        2,
        'ساختار زمان کازینو کاربران'
    );

    // راهنما، شکار و انتقال باید مثل ورود تازه، پیام جدید بسازند.
    v66_replace_once(
        $pet,
        "    if(jojo_is_pet_name_call(\$txt,\$u)) return true;\n    return in_array(\$txt,[",
        "    if(jojo_is_pet_name_call(\$txt,\$u)) return true;\n    if(preg_match('~^(?:انتقال جیک|انتقال میویی|انتقال میو|انتقال JP)(?:\\s|$)~u',\$txt)) return true;\n    return in_array(\$txt,[",
        'تشخیص ورودی تازه انتقال'
    );
    v66_replace_once(
        $pet,
        "        '💰 جیک','جیک','📊 جیکو','💰 جیکو','جیکو','پروفایل جیک','جیک جیکو','جیک‌جیکو',\n        '🐥 جوجه من'",
        "        '💰 جیک','جیک','📊 جیکو','💰 جیکو','جیکو','پروفایل جیک','جیک جیکو','جیک‌جیکو',\n        'راهنما جیک','راهنمای جیک','❓ راهنمای JoJo','انتقال میویی','انتقال میو','انتقال جیک','انتقال JP',\n        '🐥 جوجه من'",
        'افزودن راهنما و انتقال به پنل تازه'
    );
    v66_replace_once(
        $pet,
        "        'قلاب','منوی قلاب','🏭 کارخانه جیک'",
        "        'قلاب','منوی قلاب','شکار','شکار کرم','🎣 شکار کرم','🏭 کارخانه جیک'",
        'افزودن شکار به پنل تازه'
    );

    // دستورهای بدون مبلغ انتقال میویی نیز به‌عنوان دستور JoJo شناخته شوند.
    v66_replace_once(
        $pet,
        "'درآمد جیک','جدول درآمد جیک','راهنما جیک','راهنمای جیک',",
        "'درآمد جیک','جدول درآمد جیک','راهنما جیک','راهنمای جیک','انتقال میویی','انتقال میو','انتقال جیک','انتقال JP',",
        'ثبت دستور انتقال میویی'
    );
    v66_replace_once(
        $pet,
        "|انتقال JP|انتقال جیک)\\b~u',\$t)===1;",
        "|انتقال JP|انتقال جیک|انتقال میویی|انتقال میو)\\b~u',\$t)===1;",
        'ثبت انتقال میویی همراه مبلغ'
    );

    // امکان ریپلای‌کردن پنل جیک روی همان پیام کاربر.
    $oldFresh=<<<'PHP'
function jojo_ui_begin_fresh_panel(): void
{
    if(empty($GLOBALS['JOJO_UI_CONTEXT']) || !is_array($GLOBALS['JOJO_UI_CONTEXT'])) return;
    $GLOBALS['JOJO_UI_CONTEXT']['message_id']=0;
    $GLOBALS['JOJO_UI_CONTEXT']['message_type']='';
    $GLOBALS['JOJO_UI_CONTEXT']['content_hash']='';
    $GLOBALS['JOJO_UI_CONTEXT']['keyboard_hash']='';
    $GLOBALS['JOJO_UI_CONTEXT']['media_key']='';
}
PHP;
    $newFresh=<<<'PHP'
function jojo_ui_begin_fresh_panel(): void
{
    if(empty($GLOBALS['JOJO_UI_CONTEXT']) || !is_array($GLOBALS['JOJO_UI_CONTEXT'])) return;
    $GLOBALS['JOJO_UI_CONTEXT']['message_id']=0;
    $GLOBALS['JOJO_UI_CONTEXT']['message_type']='';
    $GLOBALS['JOJO_UI_CONTEXT']['content_hash']='';
    $GLOBALS['JOJO_UI_CONTEXT']['keyboard_hash']='';
    $GLOBALS['JOJO_UI_CONTEXT']['media_key']='';
    $GLOBALS['JOJO_UI_CONTEXT']['reply_to_message_id']=0;
}
function jojo_ui_reply_to(int $messageId): void
{
    if($messageId<=0 || empty($GLOBALS['JOJO_UI_CONTEXT']) || !is_array($GLOBALS['JOJO_UI_CONTEXT'])) return;
    $GLOBALS['JOJO_UI_CONTEXT']['reply_to_message_id']=$messageId;
}
function jojo_ui_take_reply_to(int $cid): int
{
    $ctx=$GLOBALS['JOJO_UI_CONTEXT']??null;
    if(!is_array($ctx) || (int)($ctx['cid']??0)!==$cid) return 0;
    $mid=max(0,(int)($ctx['reply_to_message_id']??0));
    $GLOBALS['JOJO_UI_CONTEXT']['reply_to_message_id']=0;
    return $mid;
}
PHP;
    v66_replace_once($pet,$oldFresh,$newFresh,'توابع ریپلای پنل جیک');

    v66_replace_once(
        $pet,
        "    \$p=['chat_id'=>\$cid,'text'=>\$text,'parse_mode'=>\$parse,'disable_web_page_preview'=>true];\n    if (\$keyboard!==null) \$p['reply_markup']=\$keyboardJson;\n    \$res=bot('sendMessage',\$p);",
        "    \$p=['chat_id'=>\$cid,'text'=>\$text,'parse_mode'=>\$parse,'disable_web_page_preview'=>true];\n    if (\$keyboard!==null) \$p['reply_markup']=\$keyboardJson;\n    \$replyTo=jojo_ui_take_reply_to(\$cid);\n    if(\$replyTo>0) \$p['reply_parameters']=json_encode(['message_id'=>\$replyTo,'allow_sending_without_reply'=>true],JSON_UNESCAPED_UNICODE);\n    \$res=bot('sendMessage',\$p);",
        'اتصال reply_parameters به پیام تازه'
    );
    v66_replace_once(
        $pet,
        "    if (in_array(\$txt,['💰 جیک','جیک'],true)) { jojo_do_jik(\$cid,\$uid,\$db); return true; }",
        "    if (in_array(\$txt,['💰 جیک','جیک'],true)) { jojo_ui_reply_to((int)(\$msg['message_id']??0)); jojo_do_jik(\$cid,\$uid,\$db); return true; }",
        'ریپلای فرمان جیک'
    );

    // منوی مستقل انتقال میویی.
    $transferFunction=<<<'PHP'
function jojo_transfer_menu(int $cid,int $uid,array &$db,int $target=0,array $targetFrom=[]): void
{
    $k=(string)$uid;
    $u=$db['users'][$k]??[];
    if($target===$uid){
        unset($db['user_states'][$k]);
        jojo_send($cid,'❌ نمی‌توانی برای خودت جیک انتقال بدهی.');
        return;
    }
    if($target>0 && !isset($db['users'][(string)$target]) && $targetFrom) jojo_touch_user($db,$target,$targetFrom);
    if($target>0 && isset($db['users'][(string)$target])){
        $db['user_states'][$k]=['state'=>'jik_transfer_amount','target'=>$target,'expires_at'=>time()+180];
        $receiver=jojo_user_name($db['users'][(string)$target]);
        $rows=[];$row=[];
        foreach([100,500,1000,5000] as $amount){
            if($amount>(int)($u['jp']??0)) continue;
            $row[]=['text'=>jojo_money($amount).' JP','callback_data'=>'jojo:transfer:quick:'.$amount.':'.$target];
            if(count($row)===2){$rows[]=$row;$row=[];}
        }
        if($row)$rows[]=$row;
        $rows[]=[['text'=>'❌ لغو انتقال','callback_data'=>'jojo:transfer:cancel']];
        $rows[]=[['text'=>'↩️ بازگشت JoJo','callback_data'=>'jojo:cmd:↩️ بازگشت JoJo']];
        jojo_send($cid,
            "╭─ 🔄 <b>انتقال میویی</b> ─╮\n".
            "👤 گیرنده: <b>{$receiver}</b>\n".
            "🆔 آیدی: <code>{$target}</code>\n".
            "💰 موجودی تو: <b>".jojo_money((int)($u['jp']??0))." JP</b>\n\n".
            "مبلغ را به‌صورت عدد بفرست یا یکی از دکمه‌ها را بزن.\n".
            "⏳ فرصت انتخاب: ۳ دقیقه\n".
            "╰─ ୨ৎ ─────── ୨ৎ ─╯",
            jojo_inline($rows)
        );
        return;
    }
    if(($db['user_states'][$k]['state']??'')==='jik_transfer_amount') unset($db['user_states'][$k]);
    jojo_send($cid,
        "╭─ 🔄 <b>انتقال میویی</b> ─╮\n".
        "💰 موجودی: <b>".jojo_money((int)($u['jp']??0))." JP</b>\n\n".
        "در گروه روی پیام بازیکن ریپلای کن و بنویس:\n".
        "<code>انتقال میویی</code>\n\n".
        "یا مستقیم بنویس:\n".
        "<code>انتقال جیک 500 آیدی‌عددی</code>\n".
        "╰─ ୨ৎ ─────── ୨ৎ ─╯",
        jojo_inline([
            [['text'=>'🔄 بروزرسانی','callback_data'=>'jojo:transfer:menu']],
            [['text'=>'↩️ بازگشت JoJo','callback_data'=>'jojo:cmd:↩️ بازگشت JoJo']]
        ])
    );
}

PHP;
    v66_replace_once($pet,"function jojo_transfer_prepare(int \$cid,int \$uid,array &\$db,int \$target,int \$amount,array \$targetFrom=[]): void\n{",$transferFunction."function jojo_transfer_prepare(int \$cid,int \$uid,array &\$db,int \$target,int \$amount,array \$targetFrom=[]): void\n{",'افزودن منوی انتقال میویی');

    v66_replace_once(
        $pet,
        "    if (in_array(\$txt,['راهنما جیک','راهنمای جیک'],true)) { jojo_help(\$cid); return true; }\n    // نام غذای پت",
        "    if (in_array(\$txt,['راهنما جیک','راهنمای جیک'],true)) { jojo_help(\$cid); return true; }\n    if (in_array(\$txt,['انتقال میویی','انتقال میو','انتقال جیک','انتقال JP'],true)) {\n        \$targetFrom=(array)(\$msg['reply_to_message']['from']??[]);\n        \$target=(int)(\$targetFrom['id']??0);\n        jojo_transfer_menu(\$cid,\$uid,\$db,\$target,\$targetFrom);\n        return true;\n    }\n    // نام غذای پت",
        'مسیر منوی انتقال میویی'
    );
    v66_replace_once(
        $pet,
        "    if (preg_match('~^انتقال جیک\\s+(\\d+)(?:\\s+(?:به\\s+)?(\\d+))?$~u',\$txt,\$m)) {",
        "    if (preg_match('~^(?:انتقال جیک|انتقال میویی|انتقال میو)\\s+(\\d+)(?:\\s+(?:به\\s+)?(\\d+))?$~u',\$txt,\$m)) {",
        'انتقال میویی همراه مبلغ'
    );

    // دریافت مبلغ دلخواه برای منوی انتقال.
    v66_replace_once(
        $pet,
        "        case 'jik_transfer_confirm':\n            if(in_array(\$txt,['تایید','تأیید'],true)){",
        "        case 'jik_transfer_amount':\n            if((int)(\$st['expires_at']??0)>0 && time()>(int)\$st['expires_at']){unset(\$db['user_states'][\$k]);jojo_send(\$cid,'⌛ زمان انتخاب مبلغ تمام شد؛ دوباره انتقال میویی را بزن.');return true;}\n            \$amount=jojo_parse_positive_int(\$txt);\n            \$target=(int)(\$st['target']??0);\n            if(\$amount<=0){jojo_send(\$cid,'❌ مبلغ را فقط به‌صورت عدد مثبت بفرست.');return true;}\n            unset(\$db['user_states'][\$k]);\n            jojo_transfer_prepare(\$cid,\$uid,\$db,\$target,\$amount,[]);\n            return true;\n        case 'jik_transfer_confirm':\n            if(in_array(\$txt,['تایید','تأیید'],true)){",
        'وضعیت مبلغ انتقال'
    );

    // callbackهای منوی انتقال.
    v66_replace_once(
        $pet,
        "    if(\$data==='jojo:transfer:confirm'){",
        "    if(\$data==='jojo:transfer:menu'){jojo_transfer_menu(\$cid,\$uid,\$db);return true;}\n    if(preg_match('~^jojo:transfer:quick:(\\d+):(\\d+)$~',\$data,\$m)){unset(\$db['user_states'][(string)\$uid]);jojo_transfer_prepare(\$cid,\$uid,\$db,(int)\$m[2],(int)\$m[1],[]);return true;}\n    if(\$data==='jojo:transfer:confirm'){",
        'callback انتقال میویی'
    );

    // متن راهنما.
    v66_replace_once(
        $pet,
        "        \"• روی پیام بازیکن ریپلای کن: <code>انتقال جیک 500</code>\\n\".",
        "        \"• روی پیام بازیکن ریپلای کن و بنویس: <code>انتقال میویی</code>\\n\".\n        \"• سپس مبلغ را از منو انتخاب کن یا مستقیم بنویس: <code>انتقال جیک 500</code>\\n\".",
        'راهنمای انتقال میویی'
    );

    // کول‌داون مستقل سه‌دقیقه‌ای برای تاس، گردونه و معدن.
    $casinoHelpers=<<<'PHP'
function jojo_casino_cooldown_seconds(array $db): int
{
    return max(180,(int)($db['settings']['casino_cooldown']??180));
}
function jojo_casino_cooldown_remaining(array $db,int $uid,string $game): int
{
    $last=(array)($db['users'][(string)$uid]['casino']['last_play_at']??[]);
    $played=(int)($last[$game]??0);
    return max(0,jojo_casino_cooldown_seconds($db)-(time()-$played));
}
function jojo_casino_cooldown_guard(int $cid,int $uid,array &$db,string $game): bool
{
    $wait=jojo_casino_cooldown_remaining($db,$uid,$game);
    if($wait<=0) return true;
    jojo_send($cid,
        "⏳ <b>".jojo_casino_game_title($game)." هنوز آماده نیست.</b>\n\n".
        "زمان باقی‌مانده: <b>".sprintf('%d:%02d',(int)floor($wait/60),$wait%60)."</b>\n".
        "هر بازی کازینو بعد از اجرا ۳ دقیقه استراحت دارد.",
        jojo_inline([[['text'=>'↩️ کازینو','callback_data'=>'jojo:casino:menu']]])
    );
    return false;
}
function jojo_casino_mark_play(array &$db,int $uid,string $game): void
{
    $k=(string)$uid;
    $db['users'][$k]['casino']['last_play_at']??=['dice'=>0,'wheel'=>0,'mine'=>0];
    $db['users'][$k]['casino']['last_play_at'][$game]=time();
}

PHP;
    v66_replace_once($pet,"function jojo_casino_menu(int \$cid,int \$uid,array &\$db): void\n{",$casinoHelpers."function jojo_casino_menu(int \$cid,int \$uid,array &\$db): void\n{",'توابع کول‌داون کازینو');

    v66_replace_once(
        $pet,
        "    \$amounts=jojo_casino_amounts(\$db,\$uid);",
        "    if(!jojo_casino_cooldown_guard(\$cid,\$uid,\$db,\$game)) return;\n    \$amounts=jojo_casino_amounts(\$db,\$uid);",
        'بررسی زمان در انتخاب مبلغ کازینو'
    );
    v66_replace_once(
        $pet,
        "    if(!in_array(\$pick,['even','odd'],true) || !jojo_casino_valid_stake(\$cid,\$uid,\$db,\$stake)) return;\n\n    \$pickFa=",
        "    if(!in_array(\$pick,['even','odd'],true) || !jojo_casino_valid_stake(\$cid,\$uid,\$db,\$stake)) return;\n    if(!jojo_casino_cooldown_guard(\$cid,\$uid,\$db,'dice')) return;\n    jojo_casino_mark_play(\$db,\$uid,'dice');\n\n    \$pickFa=",
        'کول‌داون تاس'
    );
    v66_replace_once(
        $pet,
        "    if(!jojo_casino_valid_stake(\$cid,\$uid,\$db,\$stake)) return;\n\n    jojo_casino_wait_panel(\n        \$cid,\n        \"🎰 <b>گردونه",
        "    if(!jojo_casino_valid_stake(\$cid,\$uid,\$db,\$stake)) return;\n    if(!jojo_casino_cooldown_guard(\$cid,\$uid,\$db,'wheel')) return;\n    jojo_casino_mark_play(\$db,\$uid,'wheel');\n\n    jojo_casino_wait_panel(\n        \$cid,\n        \"🎰 <b>گردونه",
        'کول‌داون گردونه'
    );
    v66_replace_once(
        $pet,
        "    if(!jojo_casino_valid_stake(\$cid,\$uid,\$db,\$stake)) return;\n    jojo_add_jp(\$db,\$uid,-\$stake,'کازینو جیک‌جیکو: معدن');",
        "    if(!jojo_casino_valid_stake(\$cid,\$uid,\$db,\$stake)) return;\n    if(!jojo_casino_cooldown_guard(\$cid,\$uid,\$db,'mine')) return;\n    jojo_casino_mark_play(\$db,\$uid,'mine');\n    jojo_add_jp(\$db,\$uid,-\$stake,'کازینو جیک‌جیکو: معدن');",
        'کول‌داون معدن'
    );

    // کاهش I/O: fast_index فقط وقتی محتوایش واقعاً تغییر کرده نوشته شود.
    $fastIndex=<<<'PHP'
function jojo_fast_index_write(array $db): void
{
    jojo_ensure_dir();
    $pending=[];
    foreach (array_keys(is_array($db['user_states']??null)?$db['user_states']:[]) as $id) $pending[(string)$id]=1;
    foreach (array_keys(is_array($db['admin_states']??null)?$db['admin_states']:[]) as $id) $pending[(string)$id]=1;
    $petNames=[];
    foreach((array)($db['users']??[]) as $id=>$u){
        if(!is_array($u) || empty($u['has_chick'])) continue;
        $name=jojo_norm((string)($u['chick']['name']??''));
        if($name!=='') $petNames[(string)$id]=$name;
    }
    $idx=[
        'pending'=>$pending,
        'secondary_admin'=>(int)($db['settings']['secondary_admin']??0),
        'support_group_id'=>(int)($db['settings']['support_group_id']??0),
        'pet_names'=>$petNames
    ];
    $GLOBALS['JOJO_FAST_INDEX_CACHE']=$idx;
    $json=json_encode($idx,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    if ($json===false) return;
    $newHash=hash('sha256',$json);
    $file=jojo_fast_index_file();
    $known=(string)($GLOBALS['JOJO_FAST_INDEX_HASH']??'');
    if($known!=='' && hash_equals($known,$newHash)) return;
    if($known===''){
        $old=@file_get_contents($file);
        if(is_string($old) && $old!=='' && hash_equals(hash('sha256',$old),$newHash)){
            $GLOBALS['JOJO_FAST_INDEX_HASH']=$newHash;
            return;
        }
    }
    $tmp=$file.'.'.getmypid().'.tmp';
    if (@file_put_contents($tmp,$json,LOCK_EX)!==false) {
        @chmod($tmp,0644);
        if (!@rename($tmp,$file)) { @copy($tmp,$file); @unlink($tmp); }
        $GLOBALS['JOJO_FAST_INDEX_HASH']=$newHash;
    }
}
function jojo_fast_context_needs_db
PHP;
    v66_regex_once(
        $pet,
        '~function jojo_fast_index_write\(array \$db\): void\s*\{.*?\n\}\nfunction jojo_fast_context_needs_db~s',
        $fastIndex,
        'بهینه‌سازی fast_index'
    );

    // پنل راهنما نیز به‌طور قطعی پنل تازه می‌سازد؛ شکار هم همین رفتار را دارد.
    if(strpos($pet,"'راهنما جیک','راهنمای جیک','❓ راهنمای JoJo'")===false) throw new RuntimeException('بررسی نهایی پنل تازه راهنما ناموفق بود.');
    if(strpos($pet,"jojo_ui_reply_to((int)(\$msg['message_id']??0))")===false) throw new RuntimeException('بررسی نهایی ریپلای جیک ناموفق بود.');
    if(strpos($pet,"'casino_cooldown'=>180")===false) throw new RuntimeException('بررسی نهایی زمان کازینو ناموفق بود.');
    if(strpos($pet,'function jojo_transfer_menu')===false) throw new RuntimeException('بررسی نهایی منوی انتقال ناموفق بود.');

    v66_atomic_write($petPath,$pet);

    if(is_file($adminPath)){
        $admin=(string)@file_get_contents($adminPath);
        if($admin!==''){
            $admin=str_replace('JoJo V6.5 admin module','JoJo V6.6 admin module',$admin);
            $admin=str_replace('نسخه: JoJo V6.5','نسخه: JoJo V6.6',$admin);
            v66_atomic_write($adminPath,$admin);
        }
    }

    $selfDeleted=@unlink(__FILE__);
    $body='<div class="status">✅ JoJo V6.6 نصب شد.</div>';
    $body.='<p>تغییرات:</p><ul>';
    $body.='<li><code>راهنما جیک</code>، <code>شکار</code> و <code>انتقال میویی</code> همیشه پنل تازه پایین چت می‌سازند و منوی قبلی را دست نمی‌زنند.</li>';
    $body.='<li>داخل همان پنل، دکمه‌ها همچنان همان پیام را ویرایش می‌کنند.</li>';
    $body.='<li>وقتی کاربر می‌نویسد <code>جیک</code>، پنل مأموریت به همان پیام ریپلای می‌شود.</li>';
    $body.='<li>برای انتقال: روی پیام بازیکن ریپلای کن و بنویس <code>انتقال میویی</code>.</li>';
    $body.='<li>تاس، گردونه و معدن هرکدام کول‌داون مستقل ۳ دقیقه‌ای دارند.</li>';
    $body.='<li>نوشتن تکراری fast_index حذف شد تا فشار دیسک و زمان پاسخ کمتر شود.</li>';
    $body.='<li>آیدی‌های ادمین و فایل config.php هیچ تغییری نکردند.</li>';
    $body.='</ul><p>بکاپ: <code>'.v66_h(str_replace($root.'/','',$backupDir)).'</code></p>';
    $body.=$selfDeleted?'<p>نصب‌کننده بعد از نصب خودکار حذف شد.</p>':'<p>فایل نصب‌کننده را دستی حذف کن.</p>';
    v66_page('نصب موفق JoJo V6.6',$body,true);
} catch(Throwable $e){
    v66_page('نصب ناموفق','<div class="status">❌ '.v66_h($e->getMessage()).'</div><p>هیچ فایل ناقصی جایگزین نشد؛ بکاپ را بررسی کن.</p>',false);
}
