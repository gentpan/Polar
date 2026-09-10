<?php
/** Template Name: ShanYing · 友情链接 */
if(!defined('ABSPATH')) exit;
get_header();while(have_posts()):the_post();
if(post_password_required()) { echo '<section class="feng-panel">'.get_the_password_form().'</section>';continue; }
$groups=get_terms(array('taxonomy'=>'link_category','hide_empty'=>false));$visible=0;$now=time();$today_start=current_datetime()->setTime(0,0)->getTimestamp();
?>
<div class="feng-panel feng-special-page feng-friends-page">
<header class="feng-unified-heading"><h1><?php echo feng_page_title_icon(); the_title(); ?></h1><div class="feng-friend-header-actions"><button type="button" class="feng-submit" data-game-rank-open aria-label="游戏排行榜" title="游戏排行榜" aria-haspopup="dialog" aria-controls="friend-game-ranks"><i class="fa-solid fa-gamepad" aria-hidden="true"></i></button><button type="button" class="feng-submit" data-friend-info-open aria-label="我的站点资料" title="我的站点资料" aria-haspopup="dialog" aria-controls="friend-site-info"><i class="fa-regular fa-address-card" aria-hidden="true"></i></button><button type="button" class="feng-submit" data-friend-open aria-label="申请友链" aria-haspopup="dialog" aria-controls="friend-application"><i class="fa-solid fa-plus" aria-hidden="true"></i><span class="feng-friend-apply-label">申请友链</span></button></div></header>
<p class="feng-page-introduction"><?php echo esc_html(feng_setting('friends_intro','在独立的角落，遇见同样认真记录生活的人。')); ?></p>
<?php if(isset($_GET['friend_submitted']) && $_GET['friend_submitted']==='1') echo '<p class="feng-friend-success" role="status">申请已收到，审核通过后会在这里展示。</p>'; ?>
<?php if(get_the_content()) { ?><div class="xf-prose feng-page-prose"><?php the_content(); ?></div><?php } ?>
<div class="feng-game-player" data-game-player></div><div class="feng-game-type-tabs" role="group" aria-label="游戏类型"><button type="button" data-game-type="memory" aria-pressed="true">博友配对</button><button type="button" data-game-type="emoji" aria-pressed="false">头像消消乐</button></div><section class="feng-emoji-game" data-emoji-game hidden><header><h2>头像消消乐</h2><button type="button" data-emoji-restart>重新开始</button></header><p>点选两个相邻头像进行交换，横向或纵向凑齐三个即可消除。30 步内挑战 2000 分；无效交换不扣步数，连消获得额外分数。</p><div class="feng-game-stats"><span data-emoji-score>0 分</span><span data-emoji-moves>剩余 30 步</span></div><div class="feng-emoji-board" data-emoji-board role="group" aria-label="博友头像棋盘"></div><p data-emoji-status role="status" aria-live="polite"></p><section class="feng-game-ranking"><h3>头像消消乐 · 积分榜</h3><p>休闲积分记录 · 分数优先，同分比较用时</p><ol data-match-ranking></ol><p data-match-rank-status role="status"></p></section></section><section class="feng-friend-play" data-friend-game data-endpoint="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('feng_friend_game')); ?>"><header><div><h2>在这里，遇见朋友</h2><p>翻开头像，找到同一位朋友。</p></div><div><select data-game-size aria-label="棋盘难度"><option value="16">轻松 · 16 格 / 8 对</option><option value="64">挑战 · 64 格 / 32 对</option></select><button type="button" data-game-start>玩一下</button><button type="button" data-game-exit hidden>返回头像墙</button></div></header><p class="feng-game-rules">玩法：每次翻开两张牌，相同则配对成功，不同则自动盖回。找到全部配对即通关；翻牌越少排名越高，同次数按用时排序。</p><div class="feng-friend-wall" data-game-wall>
<?php foreach(get_bookmarks(array('hide_invisible'=>true,'orderby'=>'name')) as $wall_friend): ?><a data-game-friend data-name="<?php echo esc_attr($wall_friend->link_name); ?>" data-description="<?php echo esc_attr($wall_friend->link_description); ?>" href="<?php echo esc_url($wall_friend->link_url); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr($wall_friend->link_name); ?>" title="<?php echo esc_attr($wall_friend->link_name); ?>"><?php echo feng_friend_avatar($wall_friend,112,true); ?></a><?php endforeach; ?>
</div><div class="feng-game-layout"><div data-game-area hidden><div class="feng-game-stats"><span data-game-time>00:00</span><span data-game-moves>翻牌 0 次</span><span data-game-score>配对 0 / 8</span></div><div class="feng-game-board" data-game-board></div><div class="feng-game-friends" data-game-friends aria-label="本局认识的博友"></div><div class="feng-game-result" data-game-result role="status" aria-live="polite">点击任意卡片开始计时。</div></div><section class="feng-game-ranking"><h3>好友配对榜</h3><select data-game-sort aria-label="排行榜排序"><option value="moves">最少翻牌</option><option value="time">最快通关</option></select><p>休闲榜单 · 使用评论昵称或登录昵称，未填写时显示“访客”；16 格与 64 格分别计榜。</p><ol data-game-ranking></ol><p data-game-rank-status role="status"></p></section></div></section>
<?php if(!is_wp_error($groups)) foreach($groups as $group): $links=get_bookmarks(array('category'=>$group->term_id,'orderby'=>'name','order'=>'ASC','hide_invisible'=>true)); if(!$links) continue;
 $updates=array();foreach($links as $link){
  $snapshot=get_option('feng_feed_source_'.(int)$link->link_id,array());$latest=0;
  if(($snapshot['hash']??'')===hash('sha256',trim($link->link_rss))){foreach($snapshot['items']??array() as $item){$published=(int)($item['published']??0);if($published>0&&$published<=$now)$latest=max($latest,$published);}}
  $updates[(int)$link->link_id]=$latest;
 }
 usort($links,static function($a,$b)use($updates){return ($updates[(int)$b->link_id]<=>$updates[(int)$a->link_id])?:((int)$a->link_id<=>(int)$b->link_id);});
 $visible+=count($links); ?>
<section class="feng-friend-group"><div class="feng-group-title" data-xf-reveal><h2><span class="feng-friend-group-icon"><?php echo feng_friend_category_icon($group->term_id); ?></span><?php echo esc_html($group->name); ?></h2><span><?php echo esc_html(count($links)); ?> 个邻居</span></div><?php if($group->description) echo '<p class="feng-muted">'.esc_html($group->description).'</p>'; ?><div class="feng-friends">
<?php foreach($links as $friend):
 $latest_update=$updates[(int)$friend->link_id];
 $update_level=$latest_update>0&&$latest_update>=$now-3*DAY_IN_SECONDS?'fresh':($latest_update>0&&$latest_update>=$now-7*DAY_IN_SECONDS?'week':'');
 $update_label=$update_level==='fresh'?($latest_update>=$today_start?'今天有新文章':'最近三天有新文章'):($update_level==='week'?'最近一周有新文章':'');

 ?>
<a class="feng-friend-chip<?php echo $update_level?' is-'.esc_attr($update_level):''; ?>" href="<?php echo esc_url($friend->link_url); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo esc_attr($friend->link_description.($update_label?' · '.$update_label:'')); ?>"><span class="feng-friend-chip-avatar"><?php echo feng_friend_avatar($friend,64,true); ?></span><strong><?php echo esc_html($friend->link_name); ?></strong><?php if($update_level): ?><span class="feng-friend-chip-dot" aria-label="<?php echo esc_attr($update_label); ?>"></span><?php endif; ?></a>
<?php endforeach; ?></div></section><?php endforeach; if(!$visible) echo '<p class="feng-empty">这里为下一次相遇留了位置。</p>'; ?>
<?php
// Use the site owner's stored profile, never the current visitor's identity.
$site_owner=get_userdata((int)get_post_field('post_author',get_the_ID()));
if(!$site_owner||!in_array('administrator',(array)$site_owner->roles,true)){
 $site_admins=get_users(array('role'=>'administrator','number'=>1,'orderby'=>'ID','order'=>'ASC'));
 $site_owner=$site_admins[0]??null;
}
$owner_name=$site_owner?(get_user_meta($site_owner->ID,'nickname',true)?:$site_owner->display_name):get_bloginfo('name');
$friend_info=array(
 '博主名称'=>$owner_name,
 '站点名称'=>get_bloginfo('name'),
 '站点描述'=>get_bloginfo('description'),
 '站点网址'=>home_url('/'),
 'RSS 链接'=>get_feed_link(),
 '头像地址'=>feng_setting('profile_avatar','')?:get_site_icon_url(192),
);
$friend_copy=array();foreach($friend_info as $label=>$value)$friend_copy[]=$label.'：'.$value;
?>
<dialog id="friend-game-ranks" class="feng-friend-info" aria-labelledby="friend-ranks-title"><header><h2 id="friend-ranks-title">游戏排行榜</h2><button type="button" data-friend-close aria-label="关闭排行榜"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button></header><div class="feng-rank-controls"><select data-rank-size aria-label="游戏难度"><option value="16">16 格 · 8 对</option><option value="64">64 格 · 32 对</option><option value="match">头像消消乐 · 积分榜</option></select><select data-rank-order aria-label="排名方式"><option value="moves">最少翻牌</option><option value="time">最快通关</option></select></div><ol data-rank-modal-list></ol><p data-rank-modal-status role="status"></p></dialog>
<dialog id="friend-site-info" class="feng-friend-info" aria-labelledby="friend-site-info-title"><header><div><p class="xf-section-kicker">LET’S CONNECT</p><h2 id="friend-site-info-title">我的站点资料</h2></div><button type="button" data-friend-copy="<?php echo esc_attr(implode("\n",$friend_copy)); ?>" aria-label="复制全部站点资料" title="复制全部"><i class="fa-regular fa-copy" aria-hidden="true"></i></button><button type="button" data-friend-close aria-label="关闭站点资料"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button></header>
<div class="feng-site-card-identity">
<?php if($friend_info['头像地址']): ?><img src="<?php echo esc_url($friend_info['头像地址']); ?>" width="72" height="72" alt="<?php echo esc_attr($friend_info['站点名称']); ?>"><?php endif; ?>
<div><strong><?php echo esc_html($friend_info['站点名称']); ?></strong><p><?php echo esc_html($friend_info['站点描述']); ?></p><small><?php echo esc_html($friend_info['博主名称']); ?></small></div></div>
<dl><?php foreach($friend_info as $label=>$value): if($label==='博主名称')continue; ?><div><dt><?php echo esc_html($label); ?></dt><dd<?php if(in_array($label,array('站点网址','RSS 链接','头像地址'),true))echo ' class="feng-site-card-url"'; ?>><?php echo esc_html($value?:'暂未设置'); ?></dd><?php if($value): ?><button type="button" data-friend-copy="<?php echo esc_attr($value); ?>" aria-label="<?php echo esc_attr('复制'.$label); ?>" title="<?php echo esc_attr('复制'.$label); ?>"><i class="fa-regular fa-copy" aria-hidden="true"></i></button><?php endif; ?></div><?php endforeach; ?></dl></dialog>
<dialog class="feng-friend-apply" id="friend-application" aria-labelledby="friend-application-title"><header><h2 id="friend-application-title">让我们成为邻居</h2><div class="feng-friend-mode" role="group" aria-label="申请类型"><button type="button" data-friend-mode="apply" aria-pressed="true">申请友链</button><button type="button" data-friend-mode="edit" aria-pressed="false">修改友链</button></div><button type="button" data-friend-close aria-label="关闭申请表"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button></header><p>填写站点资料，审核通过后会在友链页展示。</p>
<form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" data-no-pjax>
<input type="hidden" name="action" value="feng_friend_apply"><input type="hidden" name="page_id" value="<?php echo absint(get_the_ID()); ?>">
<?php wp_nonce_field('feng_friend_apply','friend_nonce'); ?>
<input type="hidden" name="friend_mode" value="apply">
<label class="feng-friend-apply__wide">常用邮箱<input name="site_email" type="email" required maxlength="254" autocomplete="email" aria-describedby="friend-email-help"><small id="friend-email-help">作为后续修改的匹配依据，仅保存哈希。修改时须填写上次申请的邮箱并验证验证码，审核后生效。</small></label>
<div class="feng-friend-apply__wide feng-friend-verification" data-friend-verification hidden><label>邮箱验证码<input name="email_code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="6 位验证码" disabled></label><button type="button" data-friend-send-code data-endpoint="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">发送验证码</button><input type="hidden" name="email_token"><small data-friend-code-status role="status"></small></div>
<label>站点名称<input name="site_name" required maxlength="100" autocomplete="organization"></label>
<label>站点地址<input name="site_url" type="url" required maxlength="255" placeholder="https://example.com" autocomplete="url"></label>
<label class="feng-friend-apply__wide">一句介绍<textarea name="site_description" required maxlength="255" rows="2" placeholder="你在这里记录些什么？"></textarea></label>
<label>头像地址或邮箱（选填）<input name="site_avatar" type="text" maxlength="255" placeholder="图片网址 / 常用评论邮箱" aria-describedby="friend-avatar-help"><small id="friend-avatar-help">填写邮箱自动获取 Gravatar 全球通用头像，仅保存邮箱哈希；也可直接填写头像图片地址。</small></label>
<label>RSS 地址（选填）<input name="site_rss" type="url" maxlength="255" placeholder="https://…/feed/"></label>
<div hidden aria-hidden="true"><label>请留空<input name="contact_website" tabindex="-1" autocomplete="off"></label></div>
<div class="feng-friend-apply__wide"><button class="feng-submit" type="submit">提交申请</button><small>请填写可公开展示的信息；头像可留空，届时显示默认头像。</small></div>
</form></dialog>
<?php if(comments_open()||get_comments_number()) comments_template(); ?></div>
<?php endwhile; get_footer(); ?>
