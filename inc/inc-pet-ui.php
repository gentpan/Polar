<?php
/** Pet view and native theme-settings integration. Artwork is local, animated SVG. */
if(!defined('ABSPATH')) exit;
function feng_pet_art($kind=null) {
 $kind=$kind?:feng_pet_identity()['kind'];if(!isset(feng_pet_catalog()[$kind]))$kind='bird';
 return file_get_contents(get_theme_file_path('/assets/images/pet-'.$kind.'.svg'));
}
add_action('wp_enqueue_scripts',function(){
 if(!feng_setting('pet_enabled',true))return;
 wp_enqueue_style('feng-pet',get_theme_file_uri('/assets/css/pet.css'),array('feng-pages'),feng_asset_version('/assets/css/pet.css'));
 wp_enqueue_script('feng-pet-motion',get_theme_file_uri('/assets/js/pet-motion.js'),array('xf-app'),feng_asset_version('/assets/js/pet-motion.js'),array('strategy'=>'defer','in_footer'=>true));
 wp_enqueue_script('feng-pet',get_theme_file_uri('/assets/js/pet.js'),array('xf-app','feng-pet-motion'),feng_asset_version('/assets/js/pet.js'),array('strategy'=>'defer','in_footer'=>true));
});
add_action('wp_footer',function(){
 if(!feng_setting('pet_enabled',true))return;$pet=feng_pet_identity();
 ?>
 <aside class="feng-pet" data-feng-pet data-kind="<?php echo esc_attr($pet['kind']); ?>" <?php if(substr($pet['kind'],-2)==='3d')echo 'data-pet-model="'.esc_url(get_theme_file_uri('/assets/models/'.substr($pet['kind'],0,-2).'.glb')).'"'; ?> data-side="<?php echo feng_setting('pet_side','left')==='right'?'right':'left'; ?>" <?php if(!feng_setting('pet_mobile',true))echo 'data-hide-mobile'; ?> aria-label="站点宠物" hidden>
 <div class="feng-pet-bubble" data-pet-bubble hidden aria-hidden="true"></div>
 <button class="feng-pet-launcher" data-pet-toggle type="button" aria-expanded="false" aria-controls="feng-pet-panel" aria-label="打开宠物<?php echo esc_attr($pet['name']); ?>的小窝"><span class="feng-pet-art" aria-hidden="true"><?php echo feng_pet_art(); ?></span><span class="feng-pet-name"><?php echo esc_html($pet['name']); ?></span></button>
 <section id="feng-pet-panel" class="feng-pet-panel" aria-label="宠物小窝" hidden>
 <header><div><p class="feng-pet-kicker">一起，慢慢长大</p><h2><?php echo esc_html($pet['name']); ?>的小窝</h2></div><button class="xf-icon-button" data-pet-close type="button" aria-label="收起宠物小窝"><?php echo feng_icon('close'); ?></button></header>
 <div class="feng-pet-intro"><span class="feng-pet-art" aria-hidden="true"><?php echo feng_pet_art(); ?></span><div><strong data-pet-stage>初遇 · Lv.1</strong><p data-pet-mood>正等你来打个招呼</p><small data-pet-age>全站共同陪伴的伙伴</small></div></div>
 <p class="feng-pet-hint" data-pet-form>幼年 · 体型会随着成长值变化</p>
 <div class="feng-pet-meters"><label>饱食度 <span data-pet-satiety>—</span><meter data-pet-food-meter min="0" max="100" value="0">0</meter></label><label>成长 <span data-pet-xp>—</span><progress data-pet-progress max="100" value="0">0</progress></label></div>
 <div class="feng-pet-actions"><button class="xf-glass-link" type="button" data-pet-action="feed">喂点<?php echo esc_html($pet['food']); ?></button><button class="xf-glass-link" type="button" data-pet-action="pat">摸摸脑袋</button></div>
 <details class="feng-pet-tricks"><summary>看看我的小动作</summary><div class="feng-pet-trick-list"><?php foreach((substr($pet['kind'],-2)==='3d'?array('idle'=>'站一会儿','nod'=>'四处看看','stretch'=>'低头嗅嗅','feed'=>'吃东西','walk'=>'走几步','happy'=>'跳一下'):array('nod'=>'点点头','wave'=>'打招呼','stretch'=>($pet['kind']==='bird'?'展展翅膀':'伸懒腰'),'groom'=>($pet['kind']==='bird'?'梳理羽毛':(in_array($pet['kind'],array('cat','rabbit'),true)?'洗洗脸':'理理尾巴')),'happy'=>'开心一下',...($pet['kind']==='bird'?array('flutter'=>'扑腾试飞','ball'=>'顶小球','kick'=>'踢皮球','butterfly'=>'追蝴蝶','hop'=>'蹦蹦跳','turn'=>'转个身','balance'=>'单脚平衡','bubble'=>'吹泡泡','music'=>'跟着节拍','umbrella'=>'撑小伞','peek'=>'害羞躲躲'):array()),'feed'=>'吃东西')) as $motion=>$label): ?><button type="button" data-pet-preview="<?php echo esc_attr($motion); ?>"><?php echo esc_html($label); ?></button><?php endforeach; ?></div><p class="feng-pet-hint">这里是动作预览，不会消耗投喂次数或增加成长值。</p></details>
 <p class="feng-pet-hint">每天可投喂 3 次，间隔半小时。读文章、发表通过审核的评论，也能陪它成长。</p>
 <details class="feng-pet-diary"><summary>今天与最近的日记</summary><ul data-pet-diary></ul><p class="feng-pet-hint">只记录互动次数，不记录访客姓名和留言内容。</p></details>
 <div class="feng-pet-chat"><div class="feng-pet-chat-heading"><h3>聊一会儿</h3><span data-pet-chat-mode>日常问答</span></div><div class="feng-pet-conversation" data-pet-conversation role="log" aria-label="宠物对话"><p>想知道我今天吃了几次，还是怎么陪我长大？</p></div><div class="feng-pet-prompts"><button type="button" data-pet-question="你今天过得怎么样？">今天过得怎样</button><button type="button" data-pet-question="怎么陪你成长？">怎么成长</button></div><form data-pet-chat-form><label class="screen-reader-text" for="feng-pet-question">问问宠物</label><input id="feng-pet-question" name="question" maxlength="300" autocomplete="off" placeholder="问问我的日常…" required><button type="submit" aria-label="发送给宠物"><?php echo feng_icon('arrow-up-right'); ?></button></form><p class="feng-pet-hint" data-pet-ai-note>日常问答直接根据真实状态回答。</p></div>
 <p class="feng-pet-status" data-pet-status role="status" aria-live="polite"></p>
 <footer><span>不催促，也不会因离开而消失。</span><button type="button" data-pet-hide>本次先休息</button></footer>
 </section></aside>
 <?php
},5);
function feng_pet_settings_preview() {
 $s=feng_pet_snapshot();echo '<div class="feng-pet-admin-gallery" aria-label="宠物外观预览">';
 foreach(feng_pet_catalog() as $kind=>$pet)echo '<figure>'.feng_pet_art($kind).'<figcaption>'.esc_html($pet['label'].' · '.$pet['name']).'</figcaption></figure>';
 echo '</div><p>小鸟保留已有成长档案。修改名字不会清零；关闭功能会保留已有数据。有效阅读 +1、摸摸 +1、投喂 +3、新审核评论 +8。</p><p>当前：<strong>'.esc_html($s['identity']['name'].' · '.$s['stage'].' · '.$s['xp'].' 成长值').'</strong>。已陪伴 '.(int)$s['age'].' 天。</p><p>AI 使用「AI 助手」中保存的文字模型。提问时发送宠物公开统计、最近七天日记和当前访客最近三轮对话；本站不保存对话。未配置或达到额度时，自动使用日常问答。每位访客每天最多 3 次 AI 请求；失败尝试也计入全站额度。昼夜按 WordPress「设置 → 常规」中的站点时区计算。</p>';
}
