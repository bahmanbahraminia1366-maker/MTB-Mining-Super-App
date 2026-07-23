<?php
/*
 * JoJo V6.7 installer
 * - واحد پول و فرمان‌ها فقط «جیک / JP»
 * - پاسخ پنل‌های تازه به پیام خود بازیکن
 * - متن‌های خلوت‌تر و خواناتر
 * - بازی تک‌نفره «جوجه‌روگ؛ زیرزمین بی‌انتها» در یک پنل
 *
 * فایل را کنار ambot.php داخل پوشه amir قرار بده و یک‌بار در مرورگر اجرا کن.
 */

declare(strict_types=1);
header('Content-Type: text/html; charset=utf-8');
@ini_set('display_errors','0');
error_reporting(E_ALL);

function v67_h(string $s): string { return htmlspecialchars($s,ENT_QUOTES,'UTF-8'); }
function v67_page(string $title,string $body,bool $ok=false): void
{
    $color=$ok?'#22c55e':'#ef4444';
    echo '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.v67_h($title).'</title><style>body{margin:0;padding:24px;background:#0f172a;color:#e5e7eb;font-family:tahoma,Arial;line-height:2}.box{max-width:860px;margin:auto;background:#1e293b;border:1px solid #334155;border-radius:20px;padding:24px}h1{margin-top:0;color:'.$color.'}code{direction:ltr;display:inline-block;background:#020617;padding:3px 8px;border-radius:7px}.ok{color:#4ade80}.warn{color:#facc15}</style></head><body><div class="box"><h1>'.v67_h($title).'</h1>'.$body.'</div></body></html>';
}
function v67_fail(string $message): void { throw new RuntimeException($message); }
function v67_write(string $path,string $content): void
{
    $tmp=$path.'.v67.'.getmypid().'.tmp';
    if(@file_put_contents($tmp,$content,LOCK_EX)===false) v67_fail('نوشتن فایل موقت ممکن نشد: '.$path);
    @chmod($tmp,0644);
    if(!@rename($tmp,$path)){
        if(!@copy($tmp,$path)){@unlink($tmp);v67_fail('جایگزینی فایل ممکن نشد: '.$path);}
        @unlink($tmp);
    }
}
function v67_replace_once(string $code,string $find,string $replace,string $label): string
{
    if(strpos($code,$replace)!==false) return $code;
    $pos=strpos($code,$find);
    if($pos===false) v67_fail('محل اصلاح «'.$label.'» پیدا نشد؛ نسخه pet.php با نسخه مورد انتظار فرق دارد.');
    return substr_replace($code,$replace,$pos,strlen($find));
}
function v67_insert_after_once(string $code,string $anchor,string $insert,string $marker,string $label): string
{
    if(strpos($code,$marker)!==false) return $code;
    $pos=strpos($code,$anchor);
    if($pos===false) v67_fail('محل افزودن «'.$label.'» پیدا نشد.');
    $pos+=strlen($anchor);
    return substr_replace($code,$insert,$pos,0);
}
function v67_insert_before_once(string $code,string $anchor,string $insert,string $marker,string $label): string
{
    if(strpos($code,$marker)!==false) return $code;
    $pos=strpos($code,$anchor);
    if($pos===false) v67_fail('محل افزودن «'.$label.'» پیدا نشد.');
    return substr_replace($code,$insert,$pos,0);
}

try{
    $root=__DIR__;
    $petFile=$root.'/modules/pet.php';
    if(!is_file($petFile)) v67_fail('فایل modules/pet.php پیدا نشد. نصب‌کننده را مستقیم داخل پوشه اصلی ربات (amir) بگذار.');

    $pet=(string)@file_get_contents($petFile);
    if($pet==='') v67_fail('خواندن modules/pet.php ممکن نشد.');

    if(strpos($pet,'JOJO_V67_CHICK_ROGUE')!==false){
        v67_page('✅ قبلاً نصب شده','<p>آپدیت JoJo V6.7 و بازی جوجه‌روگ از قبل نصب است.</p>',true);
        exit;
    }

    $backupDir=$root.'/backup';
    if(!is_dir($backupDir) && !@mkdir($backupDir,0755,true)) v67_fail('ساخت پوشه backup ممکن نشد.');
    $stamp=date('Ymd_His');
    $backup=$backupDir.'/pet_before_v67_'.$stamp.'.php';
    if(@file_put_contents($backup,$pet,LOCK_EX)===false) v67_fail('ساخت بکاپ pet.php ممکن نشد.');

    $pet=preg_replace('/^<\?php\s*/',"<?php\n/* JOJO_V67_CHICK_ROGUE */\n",$pet,1,$headerCount);
    if($headerCount!==1) v67_fail('هدر PHP فایل pet.php معتبر نیست.');

    // هیچ واحد یا فرمان «میویی» در JoJo باقی نماند.
    $pet=str_replace(['انتقال میویی','انتقال میو'],'انتقال جیک',$pet);
    $pet=str_replace('میویی','جیک',$pet);

    // تمام فرمان‌های متنی JoJo روی پیام همان بازیکن ریپلای شوند.
    $replyAnchor="    jojo_ui_bind(\$db, \$uid, \$cid, 0);";
    $replyInsert="\n    /* JOJO_ALWAYS_REPLY_TO_USER */\n    if(!empty(\$msg['message_id'])){\n        \$GLOBALS['JOJO_REPLY_TO_MESSAGE_ID']=(int)\$msg['message_id'];\n    }";
    $pet=v67_insert_after_once($pet,$replyAnchor,$replyInsert,'JOJO_ALWAYS_REPLY_TO_USER','ریپلای همیشگی به پیام بازیکن');

    if(strpos($pet,'JOJO_REPLY_MESSAGE_ON_SEND')===false && strpos($pet,"\$replyId=(int)(\$GLOBALS['JOJO_REPLY_TO_MESSAGE_ID']??0)")===false){
        $old="    \$p=['chat_id'=>\$cid,'text'=>\$text,'parse_mode'=>\$parse,'disable_web_page_preview'=>true];\n    if (\$keyboard!==null) \$p['reply_markup']=\$keyboardJson;\n    \$res=bot('sendMessage',\$p);";
        $new="    \$p=['chat_id'=>\$cid,'text'=>\$text,'parse_mode'=>\$parse,'disable_web_page_preview'=>true];\n    if (\$keyboard!==null) \$p['reply_markup']=\$keyboardJson;\n    /* JOJO_REPLY_MESSAGE_ON_SEND */\n    \$replyId=(int)(\$GLOBALS['JOJO_REPLY_TO_MESSAGE_ID']??0);\n    if(\$replyId>0){\n        \$p['reply_to_message_id']=\$replyId;\n        \$p['allow_sending_without_reply']=true;\n        unset(\$GLOBALS['JOJO_REPLY_TO_MESSAGE_ID']);\n    }\n    \$res=bot('sendMessage',\$p);";
        $pet=v67_replace_once($pet,$old,$new,'ریپلای پنل متنی');
    }

    if(strpos($pet,'JOJO_REPLY_PHOTO_ON_SEND')===false){
        $old="    \$payload=['chat_id'=>\$cid,'photo'=>\$makePhoto('photo'),'caption'=>\$caption,'parse_mode'=>'HTML'];\n    if(\$keyboard!==null) \$payload['reply_markup']=\$keyboardJson;\n    \$res=@bot('sendPhoto',\$payload);";
        $new="    \$payload=['chat_id'=>\$cid,'photo'=>\$makePhoto('photo'),'caption'=>\$caption,'parse_mode'=>'HTML'];\n    if(\$keyboard!==null) \$payload['reply_markup']=\$keyboardJson;\n    /* JOJO_REPLY_PHOTO_ON_SEND */\n    \$replyId=(int)(\$GLOBALS['JOJO_REPLY_TO_MESSAGE_ID']??0);\n    if(\$replyId>0){\n        \$payload['reply_to_message_id']=\$replyId;\n        \$payload['allow_sending_without_reply']=true;\n        unset(\$GLOBALS['JOJO_REPLY_TO_MESSAGE_ID']);\n    }\n    \$res=@bot('sendPhoto',\$payload);";
        $pet=v67_replace_once($pet,$old,$new,'ریپلای پنل تصویری');
    }

    // فرمان‌های جیک روگ در نگهبان سریع و پنل تازه.
    $pet=v67_replace_once(
        $pet,
        "        '💰 جیک','📊 جیکو'",
        "        '🐥 جیک روگ','جیک روگ','روگ جیک','زیرزمین جیک','جیک بی‌انتها',\n        '💰 جیک','📊 جیکو'",
        'شناخت فرمان جیک روگ'
    );
    $pet=v67_replace_once(
        $pet,
        "        '🐥 جوجه من','جوجه','جوجه من','پروفایل جوجه',",
        "        '🐥 جیک روگ','جیک روگ','روگ جیک','زیرزمین جیک','جیک بی‌انتها',\n        '🐥 جوجه من','جوجه','جوجه من','پروفایل جوجه',",
        'پنل تازه جیک روگ'
    );

    // مسیر فرمان متنی.
    $routeAnchor="    if (in_array(\$txt,['جهان پت','پنل جهان پت','منوی پت','JoJo','جوجو','↩️ بازگشت JoJo','🔙 بازگشت JoJo'],true)) { jojo_send_main(\$cid,\$uid,\$db,\$config); return true; }";
    $routeInsert="\n    /* JOJO_V67_ROGUE_ROUTE */\n    if(in_array(\$txt,['🐥 جیک روگ','جیک روگ','روگ جیک','زیرزمین جیک','جیک بی‌انتها'],true)){ jojo_rogue_menu(\$cid,\$uid,\$db); return true; }";
    $pet=v67_insert_after_once($pet,$routeAnchor,$routeInsert,'JOJO_V67_ROGUE_ROUTE','مسیر جیک روگ');

    // معرفی کوتاه و خلوت در صفحه اصلی و راهنما.
    if(strpos($pet,'برای بازی تک‌نفره بنویس')===false){
        $pet=v67_replace_once(
            $pet,
            '        "برای مأموریت بنویس: <code>جیک</code>\\n".',
            '        "برای مأموریت بنویس: <code>جیک</code>\\n".\n        "برای بازی تک‌نفره بنویس: <code>جیک روگ</code>\\n".',
            'معرفی جیک روگ در پنل اصلی'
        );
    }
    if(strpos($pet,'جیک روگ</code> — زیرزمین')===false){
        $pet=v67_replace_once(
            $pet,
            '        "• <code>جیک</code> — مأموریت مرحله‌ای؛ برداشت یا ادامه\\n".',
            '        "• <code>جیک</code> — مأموریت مرحله‌ای؛ برداشت یا ادامه\\n".\n        "• <code>جیک روگ</code> — زیرزمین تک‌نفره و بی‌انتها\\n".',
            'معرفی جیک روگ در راهنما'
        );
    }

    $rogueFunctions=<<<'V67ROGUE'
/* ===== JOJO_V67_CHICK_ROGUE_GAME ===== */
function jojo_rogue_defaults(array &$db,int $uid): void
{
    if(!isset($db['rogue']) || !is_array($db['rogue'])) $db['rogue']=[];
    if(!isset($db['rogue']['sessions']) || !is_array($db['rogue']['sessions'])) $db['rogue']['sessions']=[];
    if(!isset($db['rogue']['stats']) || !is_array($db['rogue']['stats'])) $db['rogue']['stats']=[];
    $k=(string)$uid;
    $base=['runs'=>0,'best_floor'=>0,'best_score'=>0,'total_jp'=>0,'deaths'=>0];
    $db['rogue']['stats'][$k]=array_replace($base,is_array($db['rogue']['stats'][$k]??null)?$db['rogue']['stats'][$k]:[]);
}
function jojo_rogue_item_label(string $id): string
{
    return [
        'shield'=>'🛡 سپر پوسته‌ای',
        'magnet'=>'🧲 آهنربای جیک',
        'medkit'=>'🩹 دانه درمانی',
        'glasses'=>'👓 عینک تله‌بین',
        'bomb'=>'💣 تخم انفجاری'
    ][$id]??$id;
}
function jojo_rogue_inventory_slots(array $s): int
{
    $sum=0;
    foreach(($s['inventory']??[]) as $n) $sum+=max(0,(int)$n);
    return $sum;
}
function jojo_rogue_has_item(array $s,string $id): bool
{
    return (int)($s['inventory'][$id]??0)>0;
}
function jojo_rogue_consume_item(array &$s,string $id,int $count=1): bool
{
    $have=(int)($s['inventory'][$id]??0);
    if($have<$count) return false;
    $have-=$count;
    if($have<=0) unset($s['inventory'][$id]); else $s['inventory'][$id]=$have;
    return true;
}
function jojo_rogue_add_item(array &$s,string $id): string
{
    if(jojo_rogue_inventory_slots($s)>=3){
        $gain=50+((int)($s['floor']??1)*5);
        $s['loot']=(int)($s['loot']??0)+$gain;
        $s['score']=(int)($s['score']??0)+$gain;
        return '🎒 کوله پر بود؛ وسیله به <b>'.jojo_money($gain).' JP</b> تبدیل شد.';
    }
    $s['inventory'][$id]=(int)($s['inventory'][$id]??0)+1;
    return '🎒 وسیله پیدا شد: <b>'.jojo_rogue_item_label($id).'</b>';
}
function jojo_rogue_chick_name(array $db,int $uid): string
{
    $name=trim((string)($db['users'][(string)$uid]['chick']['name']??'جوجه'));
    if($name==='') $name='جوجه';
    return htmlspecialchars($name,ENT_QUOTES,'UTF-8');
}
function jojo_rogue_update_best(array &$db,int $uid,array $s): void
{
    jojo_rogue_defaults($db,$uid);
    $st=&$db['rogue']['stats'][(string)$uid];
    $st['best_floor']=max((int)$st['best_floor'],(int)($s['floor']??0));
    $st['best_score']=max((int)$st['best_score'],(int)($s['score']??0));
}
function jojo_rogue_new_floor(array &$s): void
{
    $size=5;
    $floor=max(1,(int)($s['floor']??1));
    $s['x']=0;$s['y']=0;
    $s['exit_x']=$size-1;$s['exit_y']=$size-1;
    $s['visited']=['0,0'=>1];
    $s['resolved']=[];
    $s['revealed_traps']=[];
    $s['events']=[];
    $s['at_exit']=false;
    $s['exit_rewarded']=false;
    $s['floor_started_at']=time();
    $s['note']='🐥 جوجه وارد طبقه <b>'.$floor.'</b> شد. راه خروج را پیدا کن.';

    $coords=[];
    for($y=0;$y<$size;$y++){
        for($x=0;$x<$size;$x++){
            if(($x===0&&$y===0)||($x===$size-1&&$y===$size-1)) continue;
            $coords[]=$x.','.$y;
        }
    }
    shuffle($coords);
    $trapCount=min(6,2+intdiv(max(0,$floor-1),2));
    $types=array_merge(
        array_fill(0,$trapCount,'trap'),
        array_fill(0,5,'coin'),
        array_fill(0,2,'chest'),
        array_fill(0,2,'item'),
        ['heal','mimic']
    );
    shuffle($types);
    foreach($types as $i=>$type){
        if(!isset($coords[$i])) break;
        $s['events'][$coords[$i]]=$type;
    }
}
function jojo_rogue_start(int $cid,int $uid,array &$db): void
{
    jojo_rogue_defaults($db,$uid);
    $db['rogue']['stats'][(string)$uid]['runs']=(int)$db['rogue']['stats'][(string)$uid]['runs']+1;
    $s=[
        'active'=>true,'floor'=>1,'hp'=>3,'max_hp'=>3,'loot'=>0,'score'=>0,
        'moves'=>0,'inventory'=>[],'started_at'=>time()
    ];
    jojo_rogue_new_floor($s);
    $db['rogue']['sessions'][(string)$uid]=$s;
    jojo_rogue_render($cid,$uid,$db);
}
function jojo_rogue_menu(int $cid,int $uid,array &$db): void
{
    jojo_rogue_defaults($db,$uid);
    $k=(string)$uid;
    $active=!empty($db['rogue']['sessions'][$k]['active']);
    $st=$db['rogue']['stats'][$k];
    $text="🐥 <b>جوجه‌روگ؛ زیرزمین بی‌انتها</b>\n\n".
        "هر طبقه یک نقشه تازه دارد. جیک و وسیله جمع کن، راه خروج را پیدا کن و تصمیم بگیر برداشت کنی یا پایین‌تر بروی.\n\n".
        "❤️ سه جان داری\n".
        "🎒 فقط سه وسیله نگه می‌داری\n".
        "💀 با مرگ، جیک همان دور از دست می‌رود\n\n".
        "🏆 رکورد طبقه: <b>".(int)$st['best_floor']."</b>\n".
        "⭐ رکورد امتیاز: <b>".jojo_money((int)$st['best_score'])."</b>";
    $rows=[];
    if($active){
        $s=$db['rogue']['sessions'][$k];
        $rows[]=[['text'=>'▶️ ادامه طبقه '.(int)$s['floor'],'callback_data'=>'jojo:rogue:resume']];
        $rows[]=[['text'=>'🔄 شروع از اول','callback_data'=>'jojo:rogue:restart']];
    }else{
        $rows[]=[['text'=>'🐥 شروع بازی','callback_data'=>'jojo:rogue:start']];
    }
    $rows[]=[['text'=>'🏆 رکورد من','callback_data'=>'jojo:rogue:records']];
    jojo_send($cid,$text,jojo_inline($rows));
}
function jojo_rogue_map(array $s): string
{
    $size=5;$rows=[];
    $px=(int)($s['x']??0);$py=(int)($s['y']??0);
    $ex=(int)($s['exit_x']??4);$ey=(int)($s['exit_y']??4);
    foreach(range(0,$size-1) as $y){
        $row='';
        foreach(range(0,$size-1) as $x){
            $key=$x.','.$y;
            if($x===$px&&$y===$py) $cell='🐥';
            elseif($x===$ex&&$y===$ey) $cell='🚪';
            elseif(!empty($s['revealed_traps'][$key]) && empty($s['resolved'][$key])) $cell='🪤';
            elseif(!empty($s['visited'][$key])) $cell='▫️';
            else $cell='⬛';
            $row.=$cell;
        }
        $rows[]=$row;
    }
    return implode("\n",$rows);
}
function jojo_rogue_inventory_text(array $s): string
{
    $parts=[];
    foreach(($s['inventory']??[]) as $id=>$n){
        if((int)$n>0) $parts[]=jojo_rogue_item_label((string)$id).' ×'.(int)$n;
    }
    return $parts?implode(' • ',$parts):'خالی';
}
function jojo_rogue_keyboard(array $s): array
{
    if(!empty($s['at_exit'])){
        return jojo_inline([
            [
                ['text'=>'💰 برداشت '.jojo_money((int)($s['loot']??0)).' JP','callback_data'=>'jojo:rogue:cashout'],
                ['text'=>'⬇️ طبقه بعد','callback_data'=>'jojo:rogue:next']
            ],
            [['text'=>'🎒 کوله','callback_data'=>'jojo:rogue:bag']]
        ]);
    }
    $x=(int)($s['x']??0);$y=(int)($s['y']??0);
    $cash=(int)($s['loot']??0)>0
        ?['text'=>'💰 برداشت','callback_data'=>'jojo:rogue:cashout']
        :['text'=>'🏳 پایان','callback_data'=>'jojo:rogue:abandon'];
    return jojo_inline([
        [[ 'text'=>'⬆️','callback_data'=>$y>0?'jojo:rogue:move:u':'jojo:noop' ]],
        [
            ['text'=>'⬅️','callback_data'=>$x>0?'jojo:rogue:move:l':'jojo:noop'],
            ['text'=>'🎒','callback_data'=>'jojo:rogue:bag'],
            ['text'=>'➡️','callback_data'=>$x<4?'jojo:rogue:move:r':'jojo:noop']
        ],
        [
            ['text'=>'⬇️','callback_data'=>$y<4?'jojo:rogue:move:d':'jojo:noop'],
            $cash
        ]
    ]);
}
function jojo_rogue_render(int $cid,int $uid,array &$db,string $extra=''): void
{
    jojo_rogue_defaults($db,$uid);
    $k=(string)$uid;
    if(empty($db['rogue']['sessions'][$k]['active'])){jojo_rogue_menu($cid,$uid,$db);return;}
    $s=$db['rogue']['sessions'][$k];
    $name=jojo_rogue_chick_name($db,$uid);
    $note=$extra!==''?$extra:(string)($s['note']??'');
    $text="🐥 <b>جوجه‌روگ؛ زیرزمین بی‌انتها</b>\n\n".
        "🏚 طبقه: <b>".(int)$s['floor']."</b>   ❤️ جان: <b>".(int)$s['hp']."/".(int)$s['max_hp']."</b>\n".
        "💰 جیک این دور: <b>".jojo_money((int)$s['loot'])." JP</b>\n".
        "⭐ امتیاز: <b>".jojo_money((int)$s['score'])."</b>\n\n".
        jojo_rogue_map($s)."\n\n".
        "🎒 ".jojo_rogue_inventory_text($s)."\n\n".
        "────────────\n".
        $note."\n\n".
        "<i>{$name} منتظر حرکت توست.</i>";
    jojo_send($cid,$text,jojo_rogue_keyboard($s));
}
function jojo_rogue_gain(array &$s,int $amount): int
{
    if(jojo_rogue_has_item($s,'magnet')) $amount=(int)ceil($amount*1.25);
    $amount=max(0,$amount);
    $s['loot']=(int)($s['loot']??0)+$amount;
    $s['score']=(int)($s['score']??0)+$amount;
    return $amount;
}
function jojo_rogue_damage(array &$s,int $amount): array
{
    $amount=max(1,$amount);
    if(jojo_rogue_has_item($s,'shield')){
        jojo_rogue_consume_item($s,'shield');
        return ['dead'=>false,'text'=>'🛡 سپر پوسته‌ای شکست و ضربه را کامل گرفت.'];
    }
    $s['hp']=max(0,(int)($s['hp']??0)-$amount);
    return ['dead'=>$s['hp']<=0,'text'=>'💥 جوجه <b>'.$amount.'</b> جان از دست داد.'];
}
function jojo_rogue_resolve_cell(array &$s,string $key): array
{
    if(!empty($s['resolved'][$key])) return ['dead'=>false,'text'=>'ردّ قبلی جوجه روی زمین مانده است.'];
    $s['resolved'][$key]=1;
    $floor=max(1,(int)($s['floor']??1));
    $type=(string)($s['events'][$key]??'empty');

    if($type==='coin'){
        $gain=jojo_rogue_gain($s,random_int(35,90)+$floor*12);
        return ['dead'=>false,'text'=>'🪙 زیر خاک <b>'.jojo_money($gain).' JP</b> پیدا شد.'];
    }
    if($type==='trap'){
        $hit=($floor>=6 && random_int(1,100)<=25)?2:1;
        $d=jojo_rogue_damage($s,$hit);
        $d['text']='🪤 تله از زیر پای جوجه باز شد!\n'.$d['text'];
        return $d;
    }
    if($type==='heal'){
        if((int)$s['hp']<(int)$s['max_hp']){
            $s['hp']++;
            return ['dead'=>false,'text'=>'🌱 چشمه دانه‌ای پیدا شد؛ <b>یک جان</b> برگشت.'];
        }
        $gain=jojo_rogue_gain($s,60+$floor*5);
        return ['dead'=>false,'text'=>'🌱 جان جوجه کامل بود؛ چشمه به <b>'.jojo_money($gain).' JP</b> تبدیل شد.'];
    }
    if($type==='item'){
        $items=['shield','magnet','medkit','glasses','bomb'];
        $id=$items[array_rand($items)];
        return ['dead'=>false,'text'=>jojo_rogue_add_item($s,$id)];
    }
    if($type==='chest'){
        $roll=random_int(1,100);
        if($roll<=55){
            $gain=jojo_rogue_gain($s,random_int(100,210)+$floor*20);
            return ['dead'=>false,'text'=>'🧰 صندوق باز شد: <b>'.jojo_money($gain).' JP</b>'];
        }
        if($roll<=80){
            $items=['shield','medkit','glasses','bomb'];
            return ['dead'=>false,'text'=>'🧰 '.jojo_rogue_add_item($s,$items[array_rand($items)])];
        }
        $d=jojo_rogue_damage($s,1);
        $d['text']='👹 صندوق، جوجه‌خوار بود!\n'.$d['text'];
        return $d;
    }
    if($type==='mimic'){
        if(random_int(1,100)<=50){
            $gain=jojo_rogue_gain($s,random_int(140,260)+$floor*22);
            return ['dead'=>false,'text'=>'👹 جوجه موجود نگهبان را فریب داد و <b>'.jojo_money($gain).' JP</b> برداشت.'];
        }
        $d=jojo_rogue_damage($s,1);
        $d['text']='👹 موجود سایه‌ای از تاریکی پرید!\n'.$d['text'];
        return $d;
    }
    $empty=[
        'ردّ پاها به دیوار ختم شد؛ این خانه خالی بود.',
        'جوجه یک پر قدیمی پیدا کرد، اما ارزشی نداشت.',
        'صدایی آمد و خاموش شد؛ چیزی اینجا نیست.',
        'فقط چند سنگ و خاک زیر پای جوجه بود.'
    ];
    return ['dead'=>false,'text'=>'▫️ '.$empty[array_rand($empty)]];
}
function jojo_rogue_finish_loss(int $cid,int $uid,array &$db,array $s): void
{
    jojo_rogue_defaults($db,$uid);
    jojo_rogue_update_best($db,$uid,$s);
    $db['rogue']['stats'][(string)$uid]['deaths']=(int)$db['rogue']['stats'][(string)$uid]['deaths']+1;
    unset($db['rogue']['sessions'][(string)$uid]);
    jojo_send($cid,
        "💀 <b>جوجه در زیرزمین افتاد</b>\n\n".
        "🏚 آخرین طبقه: <b>".(int)$s['floor']."</b>\n".
        "⭐ امتیاز: <b>".jojo_money((int)$s['score'])."</b>\n".
        "💸 جیک از دست‌رفته: <b>".jojo_money((int)$s['loot'])." JP</b>\n\n".
        "نقشه بعدی کاملاً تازه است.",
        jojo_inline([
            [['text'=>'🔄 دوباره بازی کن','callback_data'=>'jojo:rogue:start']],
            [['text'=>'↩️ منوی جیک روگ','callback_data'=>'jojo:rogue:menu']]
        ])
    );
}
function jojo_rogue_move(int $cid,int $uid,array &$db,string $dir): void
{
    jojo_rogue_defaults($db,$uid);
    $k=(string)$uid;
    if(empty($db['rogue']['sessions'][$k]['active'])){jojo_rogue_menu($cid,$uid,$db);return;}
    $s=&$db['rogue']['sessions'][$k];
    if(!empty($s['at_exit'])){jojo_rogue_render($cid,$uid,$db,'🚪 به خروج رسیدی؛ برداشت کن یا به طبقه بعد برو.');return;}
    $dx=0;$dy=0;
    if($dir==='u')$dy=-1; elseif($dir==='d')$dy=1; elseif($dir==='l')$dx=-1; elseif($dir==='r')$dx=1; else return;
    $nx=(int)$s['x']+$dx;$ny=(int)$s['y']+$dy;
    if($nx<0||$nx>4||$ny<0||$ny>4){jojo_rogue_render($cid,$uid,$db,'🧱 جوجه به دیوار خورد؛ مسیر دیگری را امتحان کن.');return;}
    $s['x']=$nx;$s['y']=$ny;$s['moves']=(int)$s['moves']+1;
    $key=$nx.','.$ny;$s['visited'][$key]=1;
    $result=['dead'=>false,'text'=>'جوجه آرام جلو رفت.'];
    if($nx===(int)$s['exit_x'] && $ny===(int)$s['exit_y']){
        if(empty($s['exit_rewarded'])){
            $bonus=jojo_rogue_gain($s,100+(int)$s['floor']*40);
            $s['exit_rewarded']=true;
            $result['text']='🚪 خروج پیدا شد؛ جایزه طبقه <b>'.jojo_money($bonus).' JP</b> بود.';
        }
        $s['at_exit']=true;
    }else{
        $result=jojo_rogue_resolve_cell($s,$key);
    }
    if(!empty($result['dead'])){
        $copy=$s;
        jojo_rogue_finish_loss($cid,$uid,$db,$copy);
        return;
    }
    $s['note']=(string)$result['text'];
    jojo_rogue_render($cid,$uid,$db);
}
function jojo_rogue_cashout(int $cid,int $uid,array &$db): void
{
    jojo_rogue_defaults($db,$uid);
    $k=(string)$uid;
    if(empty($db['rogue']['sessions'][$k]['active'])){jojo_rogue_menu($cid,$uid,$db);return;}
    $s=$db['rogue']['sessions'][$k];
    $loot=max(0,(int)($s['loot']??0));
    if($loot>0) jojo_add_jp($db,$uid,$loot,'جوجه‌روگ: برداشت زیرزمین');
    jojo_rogue_update_best($db,$uid,$s);
    $db['rogue']['stats'][$k]['total_jp']=(int)$db['rogue']['stats'][$k]['total_jp']+$loot;
    unset($db['rogue']['sessions'][$k]);
    jojo_send($cid,
        "💰 <b>جوجه سالم برگشت</b>\n\n".
        "🏚 طبقه: <b>".(int)$s['floor']."</b>\n".
        "⭐ امتیاز: <b>".jojo_money((int)$s['score'])."</b>\n".
        "✅ واریز به موجودی: <b>".jojo_money($loot)." JP</b>",
        jojo_inline([
            [['text'=>'🐥 دور تازه','callback_data'=>'jojo:rogue:start']],
            [['text'=>'↩️ منوی جیک روگ','callback_data'=>'jojo:rogue:menu']]
        ])
    );
}
function jojo_rogue_next_floor(int $cid,int $uid,array &$db): void
{
    jojo_rogue_defaults($db,$uid);
    $k=(string)$uid;
    if(empty($db['rogue']['sessions'][$k]['active'])){jojo_rogue_menu($cid,$uid,$db);return;}
    $s=&$db['rogue']['sessions'][$k];
    if(empty($s['at_exit'])){jojo_rogue_render($cid,$uid,$db,'🚪 هنوز خروج این طبقه را پیدا نکردی.');return;}
    $s['floor']=(int)$s['floor']+1;
    $s['hp']=min((int)$s['max_hp'],(int)$s['hp']+1);
    $s['score']=(int)$s['score']+(int)$s['floor']*75;
    jojo_rogue_new_floor($s);
    jojo_rogue_render($cid,$uid,$db);
}
function jojo_rogue_bag(int $cid,int $uid,array &$db): void
{
    jojo_rogue_defaults($db,$uid);
    $k=(string)$uid;
    if(empty($db['rogue']['sessions'][$k]['active'])){jojo_rogue_menu($cid,$uid,$db);return;}
    $s=$db['rogue']['sessions'][$k];
    $rows=[];
    foreach(['medkit'=>'🩹 استفاده از دانه درمانی','glasses'=>'👓 استفاده از عینک','bomb'=>'💣 استفاده از تخم انفجاری'] as $id=>$label){
        if(jojo_rogue_has_item($s,$id)) $rows[]=[['text'=>$label,'callback_data'=>'jojo:rogue:use:'.$id]];
    }
    $rows[]=[['text'=>'↩️ برگشت به نقشه','callback_data'=>'jojo:rogue:resume']];
    jojo_send($cid,
        "🎒 <b>کوله جوجه</b>\n\n".
        jojo_rogue_inventory_text($s)."\n\n".
        "🛡 سپر: اولین ضربه را می‌گیرد.\n".
        "🧲 آهنربا: جیک‌های پیدا‌شده را ۲۵٪ بیشتر می‌کند.\n".
        "🩹 دانه درمانی: یک جان برمی‌گرداند.\n".
        "👓 عینک: تله‌های همان طبقه را نشان می‌دهد.\n".
        "💣 تخم انفجاری: یک تله را نابود می‌کند.",
        jojo_inline($rows)
    );
}
function jojo_rogue_use_item(int $cid,int $uid,array &$db,string $id): void
{
    jojo_rogue_defaults($db,$uid);
    $k=(string)$uid;
    if(empty($db['rogue']['sessions'][$k]['active'])){jojo_rogue_menu($cid,$uid,$db);return;}
    $s=&$db['rogue']['sessions'][$k];
    if(!jojo_rogue_has_item($s,$id)){jojo_rogue_bag($cid,$uid,$db);return;}
    if($id==='medkit'){
        if((int)$s['hp']>=(int)$s['max_hp']){jojo_rogue_render($cid,$uid,$db,'❤️ جان جوجه کامل است؛ دانه درمانی نگه داشته شد.');return;}
        jojo_rogue_consume_item($s,$id);$s['hp']++;
        jojo_rogue_render($cid,$uid,$db,'🩹 جوجه دانه درمانی خورد و یک جان برگشت.');return;
    }
    if($id==='glasses'){
        jojo_rogue_consume_item($s,$id);
        $count=0;
        foreach(($s['events']??[]) as $key=>$type){
            if($type==='trap' && empty($s['resolved'][$key])){$s['revealed_traps'][$key]=1;$count++;}
        }
        jojo_rogue_render($cid,$uid,$db,$count>0?'👓 عینک روشن شد؛ <b>'.$count.'</b> تله روی نقشه پیدا شد.':'👓 عینک چیزی پیدا نکرد؛ تله فعالی نمانده است.');return;
    }
    if($id==='bomb'){
        $traps=[];
        foreach(($s['events']??[]) as $key=>$type) if($type==='trap' && empty($s['resolved'][$key])) $traps[]=$key;
        if(!$traps){jojo_rogue_render($cid,$uid,$db,'💣 تله فعالی وجود ندارد؛ تخم انفجاری نگه داشته شد.');return;}
        jojo_rogue_consume_item($s,$id);
        $key=$traps[array_rand($traps)];
        $s['resolved'][$key]=1;$s['revealed_traps'][$key]=1;
        jojo_rogue_render($cid,$uid,$db,'💣 صدای انفجار آمد؛ یکی از تله‌های طبقه نابود شد.');return;
    }
    jojo_rogue_bag($cid,$uid,$db);
}
function jojo_rogue_records(int $cid,int $uid,array &$db): void
{
    jojo_rogue_defaults($db,$uid);
    $st=$db['rogue']['stats'][(string)$uid];
    jojo_send($cid,
        "🏆 <b>رکورد جوجه‌روگ</b>\n\n".
        "🎮 دورهای شروع‌شده: <b>".(int)$st['runs']."</b>\n".
        "🏚 بهترین طبقه: <b>".(int)$st['best_floor']."</b>\n".
        "⭐ بهترین امتیاز: <b>".jojo_money((int)$st['best_score'])."</b>\n".
        "💰 کل جیک برداشت‌شده: <b>".jojo_money((int)$st['total_jp'])." JP</b>\n".
        "💀 تعداد باخت: <b>".(int)$st['deaths']."</b>",
        jojo_inline([[['text'=>'↩️ منوی جیک روگ','callback_data'=>'jojo:rogue:menu']]])
    );
}
function jojo_rogue_callback(int $cid,int $uid,string $data,array &$db): bool
{
    if(strpos($data,'jojo:rogue:')!==0) return false;
    $action=substr($data,11);
    if($action==='menu'){jojo_rogue_menu($cid,$uid,$db);return true;}
    if($action==='start'){jojo_rogue_start($cid,$uid,$db);return true;}
    if($action==='resume'){jojo_rogue_render($cid,$uid,$db);return true;}
    if($action==='records'){jojo_rogue_records($cid,$uid,$db);return true;}
    if($action==='bag'){jojo_rogue_bag($cid,$uid,$db);return true;}
    if($action==='cashout'){jojo_rogue_cashout($cid,$uid,$db);return true;}
    if($action==='next'){jojo_rogue_next_floor($cid,$uid,$db);return true;}
    if($action==='abandon'){
        unset($db['rogue']['sessions'][(string)$uid]);
        jojo_rogue_menu($cid,$uid,$db);return true;
    }
    if($action==='restart'){
        jojo_send($cid,"🔄 <b>شروع از اول؟</b>\n\nپیشرفت و جیک جمع‌شده این دور حذف می‌شود.",jojo_inline([
            [['text'=>'✅ بله، شروع تازه','callback_data'=>'jojo:rogue:restart_yes']],
            [['text'=>'↩️ ادامه بازی','callback_data'=>'jojo:rogue:resume']]
        ]));
        return true;
    }
    if($action==='restart_yes'){jojo_rogue_start($cid,$uid,$db);return true;}
    if(strpos($action,'move:')===0){jojo_rogue_move($cid,$uid,$db,substr($action,5));return true;}
    if(strpos($action,'use:')===0){jojo_rogue_use_item($cid,$uid,$db,substr($action,4));return true;}
    return true;
}

V67ROGUE;

    $pet=v67_insert_before_once(
        $pet,
        '/* -------------------------- Callbacks -------------------------- */',
        $rogueFunctions,
        'JOJO_V67_CHICK_ROGUE_GAME',
        'توابع جوجه‌روگ'
    );

    $callbackAnchor="    if(strpos(\$data,'jojo:')!==0) return false;";
    $callbackInsert="\n    /* JOJO_V67_ROGUE_CALLBACK */\n    if(strpos(\$data,'jojo:rogue:')===0) return jojo_rogue_callback(\$cid,\$uid,\$data,\$db);";
    $pet=v67_insert_after_once($pet,$callbackAnchor,$callbackInsert,'JOJO_V67_ROGUE_CALLBACK','مسیر دکمه‌های جوجه‌روگ');

    v67_write($petFile,$pet);

    $self=basename(__FILE__);
    v67_page('✅ JoJo V6.7 نصب شد',
        '<p class="ok">بازی <b>جوجه‌روگ؛ زیرزمین بی‌انتها</b> فعال شد.</p>'.
        '<p>فرمان بازی: <code>جیک روگ</code></p>'.
        '<p>همه پنل‌های تازه روی پیام همان بازیکن ریپلای می‌شوند و واحد پول فقط <b>جیک / JP</b> است.</p>'.
        '<p>بکاپ: <code>'.v67_h($backup).'</code></p>'.
        '<p class="warn">برای امنیت، فایل نصب‌کننده <code>'.v67_h($self).'</code> را از پوشه amir حذف کن.</p>',true);
}catch(Throwable $e){
    v67_page('❌ نصب ناموفق','<p>'.v67_h($e->getMessage()).'</p><p class="warn">هیچ فایل ناقصی عمداً جایگزین نشده است؛ از بکاپ استفاده نکن مگر pet.php تغییر کرده باشد.</p>');
}
