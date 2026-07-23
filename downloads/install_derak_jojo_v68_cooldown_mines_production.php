<?php
/*
 * JoJo V6.8 cumulative installer
 * - جوجه‌روگ: شروع هر ۵ دقیقه
 * - تاس و معدن: کول‌داون مستقل ۵ دقیقه
 * - معدن: سه مین و شش خانه امن
 * - جلوگیری از اجرای چندباره با اسپم دکمه
 * - تله‌های سخت‌تر و رویداد حمله مار
 * - تولید و ظرفیت جیک جوجه بیشتر در هر سطح
 * - پیشرفت ارتقا بعد از هر سطح از صفر آغاز می‌شود
 */

declare(strict_types=1);
header('Content-Type: text/html; charset=utf-8');
@ini_set('display_errors','0');
error_reporting(E_ALL);

function v68_h(string $s): string { return htmlspecialchars($s,ENT_QUOTES,'UTF-8'); }
function v68_page(string $title,string $body,bool $ok=false): void
{
    $color=$ok?'#22c55e':'#ef4444';
    echo '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.v68_h($title).'</title><style>body{margin:0;padding:24px;background:#0f172a;color:#e5e7eb;font-family:tahoma,Arial;line-height:2}.box{max-width:860px;margin:auto;background:#1e293b;border:1px solid #334155;border-radius:20px;padding:24px}h1{margin-top:0;color:'.$color.'}code{direction:ltr;display:inline-block;background:#020617;padding:3px 8px;border-radius:7px}.ok{color:#4ade80}.warn{color:#facc15}</style></head><body><div class="box"><h1>'.v68_h($title).'</h1>'.$body.'</div></body></html>';
}
function v68_fail(string $message): void { throw new RuntimeException($message); }
function v68_write(string $path,string $content): void
{
    $tmp=$path.'.v68.'.getmypid().'.tmp';
    if(@file_put_contents($tmp,$content,LOCK_EX)===false) v68_fail('نوشتن فایل موقت ممکن نشد: '.$path);
    @chmod($tmp,0644);
    if(!@rename($tmp,$path)){
        if(!@copy($tmp,$path)){@unlink($tmp);v68_fail('جایگزینی فایل ممکن نشد: '.$path);}
        @unlink($tmp);
    }
}
function v68_replace_once(string $code,string $find,string $replace,string $label): string
{
    if(strpos($code,$replace)!==false) return $code;
    $pos=strpos($code,$find);
    if($pos===false) v68_fail('محل اصلاح «'.$label.'» پیدا نشد.');
    return substr_replace($code,$replace,$pos,strlen($find));
}
function v68_insert_after_once(string $code,string $anchor,string $insert,string $marker,string $label): string
{
    if(strpos($code,$marker)!==false) return $code;
    $pos=strpos($code,$anchor);
    if($pos===false) v68_fail('محل افزودن «'.$label.'» پیدا نشد.');
    $pos+=strlen($anchor);
    return substr_replace($code,$insert,$pos,0);
}
function v68_insert_before_once(string $code,string $anchor,string $insert,string $marker,string $label): string
{
    if(strpos($code,$marker)!==false) return $code;
    $pos=strpos($code,$anchor);
    if($pos===false) v68_fail('محل افزودن «'.$label.'» پیدا نشد.');
    return substr_replace($code,$insert,$pos,0);
}
function v68_replace_function(string $code,string $name,string $replacement): string
{
    $pattern='~^function\\s+'.preg_quote($name,'~').'\\s*\\([^)]*\\)\\s*(?::\\s*[^\\{\\n]+)?\\s*\\{.*?^\\}\\s*~ms';
    if(!preg_match($pattern,$code)) v68_fail('تابع '.$name.' برای اصلاح پیدا نشد.');
    $out=preg_replace_callback($pattern,static fn(): string => rtrim($replacement)."\n\n",$code,1,$count);
    if(!is_string($out) || $count!==1) v68_fail('اصلاح تابع '.$name.' ناموفق بود.');
    return $out;
}
function v68_fetch(string $url): string
{
    if(function_exists('curl_init')){
        $ch=curl_init($url);
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_CONNECTTIMEOUT=>15,CURLOPT_TIMEOUT=>45,CURLOPT_USERAGENT=>'Derak-JoJo-V68-Installer/1.0']);
        $body=curl_exec($ch);$http=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
        if(is_string($body) && $body!=='' && $http>=200 && $http<300) return $body;
    }
    if((bool)ini_get('allow_url_fopen')){
        $ctx=stream_context_create(['http'=>['timeout'=>45,'follow_location'=>1,'user_agent'=>'Derak-JoJo-V68-Installer/1.0']]);
        $body=@file_get_contents($url,false,$ctx);
        if(is_string($body) && $body!=='') return $body;
    }
    return '';
}
function v68_install_rogue_if_missing(string $root,string $petFile): void
{
    $pet=(string)@file_get_contents($petFile);
    if(strpos($pet,'JOJO_V67_CHICK_ROGUE')!==false && strpos($pet,'function jojo_rogue_start')!==false) return;

    $url='https://raw.githubusercontent.com/bahmanbahraminia1366-maker/MTB-Mining-Super-App/jojo-v67-ready/downloads/install_derak_jojo_v67_chick_rogue_ready.php';
    $ready=v68_fetch($url);
    if($ready==='' || strpos($ready,'JoJo V6.7 ready installer')===false) v68_fail('جوجه‌روگ نصب نیست و دریافت نصب‌کننده V6.7 از GitHub ممکن نشد.');
    $tmp=$root.'/.jojo_v67_bootstrap_'.getmypid().'.php';
    if(@file_put_contents($tmp,$ready,LOCK_EX)===false) v68_fail('ساخت فایل موقت V6.7 ممکن نشد.');
    @chmod($tmp,0644);
    ob_start();
    try { require $tmp; } finally { ob_end_clean(); @unlink($tmp); }

    $pet=(string)@file_get_contents($petFile);
    if(strpos($pet,'JOJO_V67_CHICK_ROGUE')===false || strpos($pet,'function jojo_rogue_start')===false) v68_fail('نصب خودکار جوجه‌روگ کامل نشد.');
}

try{
    $root=__DIR__;
    $petFile=$root.'/modules/pet.php';
    if(!is_file($petFile)) v68_fail('فایل modules/pet.php پیدا نشد. نصب‌کننده را مستقیم داخل پوشه amir بگذار.');

    v68_install_rogue_if_missing($root,$petFile);
    $pet=(string)@file_get_contents($petFile);
    if($pet==='') v68_fail('خواندن modules/pet.php ممکن نشد.');
    if(strpos($pet,'JOJO_V68_COOLDOWN_MINES_PRODUCTION')!==false){
        v68_page('✅ قبلاً نصب شده','<p>آپدیت JoJo V6.8 از قبل نصب است.</p>',true);exit;
    }

    $backupDir=$root.'/backup';
    if(!is_dir($backupDir) && !@mkdir($backupDir,0755,true)) v68_fail('ساخت پوشه backup ممکن نشد.');
    $stamp=date('Ymd_His');
    $backup=$backupDir.'/pet_before_v68_'.$stamp.'.php';
    if(@file_put_contents($backup,$pet,LOCK_EX)===false) v68_fail('ساخت بکاپ pet.php ممکن نشد.');

    $pet=preg_replace('/^<\\?php\\s*/',"<?php\n/* JOJO_V68_COOLDOWN_MINES_PRODUCTION */\n",$pet,1,$headerCount);
    if($headerCount!==1) v68_fail('هدر PHP فایل pet.php معتبر نیست.');

    /* ---------- قفل ضد اسپم ---------- */
    $lockHelpers=<<<'PHP'
/* JOJO_V68_ANTISPAM_LOCK */
function jojo_v68_action_lock(int $uid,string $key,float $gap=0.85): bool
{
    $dir=jojo_base_dir().'/action_locks';
    if(!is_dir($dir)) @mkdir($dir,0755,true);
    $path=$dir.'/'.sha1($uid.'|'.$key).'.lock';
    $fp=@fopen($path,'c+');
    if(!$fp) return true;
    if(!@flock($fp,LOCK_EX|LOCK_NB)){@fclose($fp);return false;}
    @rewind($fp);
    $last=(float)trim((string)stream_get_contents($fp));
    $now=microtime(true);
    if($last>0 && ($now-$last)<$gap){@flock($fp,LOCK_UN);@fclose($fp);return false;}
    @ftruncate($fp,0);@rewind($fp);@fwrite($fp,sprintf('%.6F',$now));@fflush($fp);
    $GLOBALS['JOJO_V68_HELD_LOCKS'][]=$fp;
    return true;
}
function jojo_v68_spam_notice(array $cb=[]): void
{
    if(!empty($cb['id'])) @bot('answerCallbackQuery',['callback_query_id'=>$cb['id'],'text'=>'⏳ یک حرکت در حال اجراست؛ دوباره نزن.','show_alert'=>false]);
}
PHP;
    $pet=v68_insert_before_once($pet,'/* -------------------------- Callbacks -------------------------- */',$lockHelpers."\n\n",'JOJO_V68_ANTISPAM_LOCK','قفل ضد اسپم');

    $callbackAnchor="    if(strpos(\$data,'jojo:')!==0) return false;";
    $callbackInsert=<<<'PHP'

    /* JOJO_V68_CALLBACK_ANTISPAM */
    $mutatingRogue=preg_match('~^jojo:rogue:(?:move:|cashout$|next$|abandon$|use:|restart_yes$)~',$data)===1;
    $mutatingCasino=preg_match('~^jojo:casino:mine:(?:pick:|cashout$)~',$data)===1;
    if(($mutatingRogue || $mutatingCasino) && !jojo_v68_action_lock($uid,$mutatingRogue?'rogue-action':'mine-action',0.75)){
        jojo_v68_spam_notice($cb);
        return true;
    }
PHP;
    $pet=v68_insert_after_once($pet,$callbackAnchor,$callbackInsert,'JOJO_V68_CALLBACK_ANTISPAM','ضد اسپم دکمه‌های بازی');

    /* ---------- کول‌داون کازینو ---------- */
    $casinoHelpers=<<<'PHP'
function jojo_casino_cooldown_seconds(string $game): int
{
    return in_array($game,['dice','mine'],true)?300:180;
}
function jojo_casino_cooldown_guard(int $cid,int $uid,array &$db,string $game,string $title): bool
{
    $last=(int)($db['users'][(string)$uid]['casino_cooldowns'][$game]??0);
    $left=max(0,jojo_casino_cooldown_seconds($game)-(time()-$last));
    if($left<=0) return true;
    $clock=sprintf('%d:%02d',intdiv($left,60),$left%60);
    jojo_send($cid,"⏳ <b>{$title}</b> هنوز آماده نیست.\n\nزمان باقی‌مانده: <b>{$clock}</b>");
    return false;
}
function jojo_casino_cooldown_mark(int $uid,array &$db,string $game): void
{
    $db['users'][(string)$uid]['casino_cooldowns']??=[];
    $db['users'][(string)$uid]['casino_cooldowns'][$game]=time();
}
PHP;
    if(strpos($pet,'function jojo_casino_cooldown_guard')!==false){
        $pattern='~^function jojo_casino_cooldown_guard\\s*\\([^)]*\\)\\s*(?::\\s*[^\\{\\n]+)?\\s*\\{.*?^\\}\\s*^function jojo_casino_cooldown_mark\\s*\\([^)]*\\)\\s*(?::\\s*[^\\{\\n]+)?\\s*\\{.*?^\\}\\s*~ms';
        if(!preg_match($pattern,$pet)) v68_fail('ساختار کول‌داون کازینو قابل اصلاح نبود.');
        $pet=preg_replace_callback($pattern,static fn(): string => $casinoHelpers."\n\n",$pet,1,$casinoCount);
        if(!is_string($pet) || $casinoCount!==1) v68_fail('اصلاح کول‌داون کازینو ناموفق بود.');
    }else{
        $pet=v68_insert_before_once($pet,'function jojo_casino_dice_play',$casinoHelpers."\n\n",'function jojo_casino_cooldown_seconds','توابع کول‌داون کازینو');
    }

    if(strpos($pet,"jojo_casino_cooldown_guard(\$cid,\$uid,\$db,'dice'")===false){
        $find="    if(!in_array(\$pick,['even','odd'],true) || !jojo_casino_valid_stake(\$cid,\$uid,\$db,\$stake)) return;";
        $replace=$find."\n    if(!jojo_v68_action_lock(\$uid,'dice-play',1.20)){jojo_send(\$cid,'⏳ تاس قبلی هنوز در حال اجراست.');return;}\n    if(!jojo_casino_cooldown_guard(\$cid,\$uid,\$db,'dice','تاس جیک‌جیکو')) return;\n    jojo_casino_cooldown_mark(\$uid,\$db,'dice');";
        $pet=v68_replace_once($pet,$find,$replace,'کول‌داون و ضد اسپم تاس');
    }else if(strpos($pet,"'dice-play'")===false){
        $find="    if(!in_array(\$pick,['even','odd'],true) || !jojo_casino_valid_stake(\$cid,\$uid,\$db,\$stake)) return;\n    if(!jojo_casino_cooldown_guard";
        $replace="    if(!in_array(\$pick,['even','odd'],true) || !jojo_casino_valid_stake(\$cid,\$uid,\$db,\$stake)) return;\n    if(!jojo_v68_action_lock(\$uid,'dice-play',1.20)){jojo_send(\$cid,'⏳ تاس قبلی هنوز در حال اجراست.');return;}\n    if(!jojo_casino_cooldown_guard";
        $pet=v68_replace_once($pet,$find,$replace,'ضد اسپم تاس');
    }

    if(strpos($pet,"jojo_casino_cooldown_guard(\$cid,\$uid,\$db,'mine'")===false){
        $find="    if(!jojo_casino_valid_stake(\$cid,\$uid,\$db,\$stake)) return;\n    jojo_add_jp(\$db,\$uid,-\$stake,'کازینو جیک‌جیکو: معدن');";
        $replace="    if(!jojo_casino_valid_stake(\$cid,\$uid,\$db,\$stake)) return;\n    if(!jojo_v68_action_lock(\$uid,'mine-start',1.20)){jojo_send(\$cid,'⏳ معدن قبلی هنوز در حال اجراست.');return;}\n    if(!jojo_casino_cooldown_guard(\$cid,\$uid,\$db,'mine','معدن جیک‌جیکو')) return;\n    jojo_casino_cooldown_mark(\$uid,\$db,'mine');\n    jojo_add_jp(\$db,\$uid,-\$stake,'کازینو جیک‌جیکو: معدن');";
        $pet=v68_replace_once($pet,$find,$replace,'کول‌داون و ضد اسپم معدن');
    }else if(strpos($pet,"'mine-start'")===false){
        $find="    if(!jojo_casino_valid_stake(\$cid,\$uid,\$db,\$stake)) return;\n    if(!jojo_casino_cooldown_guard(\$cid,\$uid,\$db,'mine'";
        $replace="    if(!jojo_casino_valid_stake(\$cid,\$uid,\$db,\$stake)) return;\n    if(!jojo_v68_action_lock(\$uid,'mine-start',1.20)){jojo_send(\$cid,'⏳ معدن قبلی هنوز در حال اجراست.');return;}\n    if(!jojo_casino_cooldown_guard(\$cid,\$uid,\$db,'mine'";
        $pet=v68_replace_once($pet,$find,$replace,'ضد اسپم شروع معدن');
    }

    /* ---------- معدن سه‌مین ---------- */
    $pet=str_replace('$bombs=array_slice($cells,0,2);','$bombs=array_slice($cells,0,3);',$pet,$bombSliceCount);
    if($bombSliceCount<1 && strpos($pet,'$bombs=array_slice($cells,0,3);')===false) v68_fail('محل افزایش مین‌ها پیدا نشد.');
    $pet=str_replace('"💥 بمب باقی‌مانده: <b>2</b>\\n".','"💥 تعداد مین‌ها: <b>".count((array)$session[\'bombs\'])."</b>\\n".',$pet,$bombTextCount);
    if($bombTextCount<1 && strpos($pet,'تعداد مین‌ها')===false) v68_fail('متن تعداد مین‌ها پیدا نشد.');
    $pet=str_replace('if(count($opened)>=7){','if(count($opened)>=6){',$pet,$safeCount);
    if($safeCount<1 && strpos($pet,'if(count($opened)>=6){')===false) v68_fail('شرط پایان معدن پیدا نشد.');

    $mineMultiplier=<<<'PHP'
function jojo_casino_mine_multiplier(int $safe): float
{
    $table=[0=>1.0,1=>1.25,2=>1.65,3=>2.25,4=>3.20,5=>4.80,6=>8.00];
    return $table[max(0,min(6,$safe))];
}
PHP;
    $pet=v68_replace_function($pet,'jojo_casino_mine_multiplier',$mineMultiplier);

    /* ---------- کول‌داون جوجه‌روگ ---------- */
    $rogueHelpers=<<<'PHP'
/* JOJO_V68_ROGUE_COOLDOWN */
function jojo_rogue_cooldown_left(array $db,int $uid): int
{
    $last=(int)($db['rogue']['stats'][(string)$uid]['last_start']??0);
    return max(0,300-(time()-$last));
}
function jojo_rogue_cooldown_clock(int $seconds): string
{
    return sprintf('%d:%02d',intdiv(max(0,$seconds),60),max(0,$seconds)%60);
}
PHP;
    $pet=v68_insert_before_once($pet,'function jojo_rogue_start',$rogueHelpers."\n\n",'JOJO_V68_ROGUE_COOLDOWN','کول‌داون جوجه‌روگ');

    $rogueStartOld="    jojo_rogue_defaults(\$db,\$uid);\n    \$db['rogue']['stats'][(string)\$uid]['runs']=(int)\$db['rogue']['stats'][(string)\$uid]['runs']+1;";
    $rogueStartNew="    jojo_rogue_defaults(\$db,\$uid);\n    if(!jojo_v68_action_lock(\$uid,'rogue-start',1.20)){jojo_send(\$cid,'⏳ شروع بازی قبلی هنوز در حال پردازش است.');return;}\n    \$left=jojo_rogue_cooldown_left(\$db,\$uid);\n    if(\$left>0){\n        jojo_send(\$cid,\"⏳ <b>جوجه هنوز خسته است</b>\\n\\nشروع دور تازه بعد از: <b>\".jojo_rogue_cooldown_clock(\$left).\"</b>\",jojo_inline([[['text'=>'↩️ منوی جیک روگ','callback_data'=>'jojo:rogue:menu']]]));\n        return;\n    }\n    \$db['rogue']['stats'][(string)\$uid]['last_start']=time();\n    \$db['rogue']['stats'][(string)\$uid]['runs']=(int)\$db['rogue']['stats'][(string)\$uid]['runs']+1;";
    $pet=v68_replace_once($pet,$rogueStartOld,$rogueStartNew,'شروع پنج‌دقیقه‌ای جوجه‌روگ');

    /* ---------- سخت‌ترشدن زیرزمین و مار ---------- */
    $oldTypes=<<<'PHP'
    $trapCount=min(6,2+intdiv(max(0,$floor-1),2));
    $types=array_merge(
        array_fill(0,$trapCount,'trap'),
        array_fill(0,5,'coin'),
        array_fill(0,2,'chest'),
        array_fill(0,2,'item'),
        ['heal','mimic']
    );
PHP;
    $newTypes=<<<'PHP'
    $trapCount=min(8,3+intdiv(max(0,$floor-1),2));
    $snakeCount=$floor>=4?2:1;
    $types=array_merge(
        array_fill(0,$trapCount,'trap'),
        array_fill(0,$snakeCount,'snake'),
        array_fill(0,5,'coin'),
        array_fill(0,2,'chest'),
        array_fill(0,2,'item'),
        ['heal','mimic']
    );
PHP;
    $pet=v68_replace_once($pet,$oldTypes,$newTypes,'تله‌های بیشتر و افزودن مار');
    $pet=str_replace("\$hit=(\$floor>=6 && random_int(1,100)<=25)?2:1;","\$hit=(\$floor>=5 && random_int(1,100)<=35)?2:1;",$pet,$hardTrapCount);
    if($hardTrapCount<1 && strpos($pet,'$floor>=5 && random_int(1,100)<=35')===false) v68_fail('سخت‌ترکردن تله پیدا نشد.');

    $snakeBlock=<<<'PHP'
    if($type==='snake'){
        $hit=($floor>=7 && random_int(1,100)<=30)?2:1;
        $d=jojo_rogue_damage($s,$hit);
        $d['text']="🐍 <b>یک مار از لای سنگ‌ها بیرون پرید!</b>\nمار به جوجه حمله کرد.\n".$d['text'];
        return $d;
    }
PHP;
    $pet=v68_insert_before_once($pet,"    if(\$type==='mimic'){",$snakeBlock,'یک مار از لای سنگ‌ها','رویداد حمله مار');

    /* ---------- تولید جیک جوجه ---------- */
    $productionRate=<<<'PHP'
function jojo_pet_production_rate_milli(array $u,array $db): int
{
    $level=max(1,min(jojo_pet_production_max_level($db),(int)($u['pet_production']['level']??1)));
    $table=[1=>120,2=>200,3=>300,4=>430,5=>590,6=>780,7=>1000,8=>1250,9=>1530,10=>1850,11=>2210,12=>2610,13=>3050,14=>3530,15=>4050];
    $rate=(int)($table[$level]??4050);
    if(jojo_user_has_chick($u)){
        $def=$db['chicks'][(string)($u['chick']['type']??'basic')]??[];
        $rate+=(int)round($rate*max(0,(int)($def['reward_percent']??0))/100);
    }
    return max(1,$rate);
}
PHP;
    $productionCapacity=<<<'PHP'
function jojo_pet_production_capacity(array $u,array $db): int
{
    $level=max(1,min(jojo_pet_production_max_level($db),(int)($u['pet_production']['level']??1)));
    $table=[1=>1500,2=>2800,3=>4600,4=>7000,5=>10000,6=>13800,7=>18400,8=>24000,9=>30600,10=>38200,11=>47000,12=>57000,13=>68400,14=>81200,15=>95500];
    return (int)($table[$level]??95500);
}
PHP;
    $pet=v68_replace_function($pet,'jojo_pet_production_rate_milli',$productionRate);
    $pet=v68_replace_function($pet,'jojo_pet_production_capacity',$productionCapacity);
    $pet=str_replace("    \$p['xp']=max(0,(int)\$p['xp']-\$need);","    \$p['xp']=0; // با ورود به سطح تازه، پیشرفت ارتقای همان سطح از صفر آغاز می‌شود.",$pet,$xpResetCount);
    if($xpResetCount<1 && strpos($pet,'پیشرفت ارتقای همان سطح از صفر')===false) v68_fail('ریست پیشرفت بعد از ارتقا پیدا نشد.');

    $productionMenu=<<<'PHP'
function jojo_pet_production_menu(int $cid,int $uid,array &$db,string $notice=''): void
{
    $u=&$db['users'][(string)$uid];
    if(!jojo_user_has_chick($u)){
        jojo_send($cid,"🐥 برای تولید جیک اول باید یک جوجه داشته باشی.",jojo_chick_purchase_keyboard($db,$u,true));
        return;
    }
    jojo_pet_production_tick($u,$db);
    $p=&$u['pet_production'];
    $level=max(1,(int)$p['level'];
    $max=jojo_pet_production_max_level($db);
    $need=$level>=$max?0:jojo_pet_production_required_xp($level);
    $xp=$level>=$max?0:(int)($p['xp']??0);
    $capacity=jojo_pet_production_capacity($u,$db);
    $rateMilli=jojo_pet_production_rate_milli($u,$db);
    $per5=(int)floor($rateMilli*300/1000);
    $perHour=(int)floor($rateMilli*3600/1000);
    $petName=htmlspecialchars((string)($u['chick']['name']??'جوجو'),ENT_QUOTES,'UTF-8');
    $owner=htmlspecialchars(jojo_user_name($u),ENT_QUOTES,'UTF-8');
    $hunger=max(0,min(100,(int)($u['chick']['hunger']??0)));
    $belly=max(0,min(8,(int)ceil($hunger/12.5)));
    $bellyText=$hunger>=75?'سیر':($hunger>=40?'نیمه‌سیر':'گرسنه');
    $upgrade=$level>=$max?'حداکثر':jojo_money(jojo_pet_production_upgrade_cost($level)).' JP';
    $progress=$level>=$max?'👑 حداکثر':'<b>'.$xp.' / '.$need.'</b>';
    $next='';
    if($level<$max){
        $preview=$u;$preview['pet_production']['level']=$level+1;
        $nextRate=(int)floor(jojo_pet_production_rate_milli($preview,$db)*300/1000);
        $nextCap=jojo_pet_production_capacity($preview,$db);
        $next="\n⬆️ سطح بعد: <b>".jojo_money($nextRate)." JP / ۵ دقیقه</b> • ظرفیت <b>".jojo_money($nextCap)." JP</b>";
    }
    $notice=$notice!==''?$notice."\n\n":'';
    jojo_send($cid,
        $notice.
        "🐥 <b>{$petName}ِ {$owner}</b>\n\n".
        "🟢 در حال تولید جیک\n".
        "🍗 شکم: <b>{$bellyText} ({$belly}/8)</b>\n\n".
        "⭐ سطح تولید: <b>{$level} / {$max}</b>\n".
        "📈 پیشرفت این سطح: {$progress}\n\n".
        "💰 آماده برداشت: <b>".jojo_money((int)$p['stored'])." JP</b>\n".
        "⏱ تولید ۵ دقیقه: <b>".jojo_money($per5)." JP</b>\n".
        "🕐 تولید یک ساعت: <b>".jojo_money($perHour)." JP</b>\n".
        "📦 ظرفیت: <b>".jojo_money((int)$p['stored'])." / ".jojo_money($capacity)." JP</b>\n".
        $next."\n\n".
        "💳 هزینه ارتقا: <b>{$upgrade}</b>",
        jojo_pet_production_keyboard()
    );
}
PHP;
    $pet=v68_replace_function($pet,'jojo_pet_production_menu',$productionMenu);

    /* تصحیح یک پرانتز برای نسخه تولیدشده بالا، پیش از ذخیره نهایی. */
    $pet=str_replace("\$level=max(1,(int)\$p['level'];","\$level=max(1,(int)\$p['level']);",$pet);

    foreach([
        'JOJO_V68_ANTISPAM_LOCK','JOJO_V68_ROGUE_COOLDOWN','function jojo_casino_cooldown_seconds',
        '$bombs=array_slice($cells,0,3);','یک مار از لای سنگ‌ها','پیشرفت ارتقای همان سطح از صفر',
        "'dice-play'","'mine-start'"
    ] as $needle){
        if(strpos($pet,$needle)===false) v68_fail('بررسی نهایی ناموفق بود: '.$needle);
    }

    v68_write($petFile,$pet);
    if(function_exists('opcache_invalidate')) @opcache_invalidate($petFile,true);

    v68_page('✅ JoJo V6.8 نصب شد',
        '<p class="ok"><b>آپدیت جدید با موفقیت فعال شد.</b></p>'.
        '<p>✅ جوجه‌روگ، تاس و معدن هرکدام شروع پنج‌دقیقه‌ای دارند.</p>'.
        '<p>✅ معدن سه مین دارد و ضریب‌های آن متناسب با خطر بیشتر شده‌اند.</p>'.
        '<p>✅ اسپم دکمه باعث اجرای چندباره، برداشت تکراری یا شروع چند بازی نمی‌شود.</p>'.
        '<p>✅ تله‌ها سخت‌تر شده‌اند و مار نیز ممکن است به جوجه حمله کند.</p>'.
        '<p>✅ تولید و ظرفیت جیک جوجه در هر سطح بیشتر می‌شود و پیشرفت سطح بعد از صفر آغاز می‌شود.</p>'.
        '<p>بکاپ: <code>'.v68_h($backup).'</code></p>'.
        '<p class="warn">پس از اطمینان از عملکرد ربات، فایل نصب‌کننده را از پوشه amir حذف کن.</p>',true);
}catch(Throwable $e){
    v68_page('❌ نصب ناموفق','<p>'.v68_h($e->getMessage()).'</p><p class="warn">نسخه اصلی pet.php عمداً جایگزین نشد؛ بکاپ داخل پوشه backup قرار دارد.</p>');
}
