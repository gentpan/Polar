<?php
/** Template Name: Polar · 友情链接 */
if(!defined('ABSPATH')) exit;
get_header();while(have_posts()):the_post();
if(post_password_required()) { echo '<section class="feng-panel">'.get_the_password_form().'</section>';continue; }
$groups=get_terms(array('taxonomy'=>'link_category','hide_empty'=>false));$visible=0;
?>
<div class="feng-panel feng-special-page"><?php feng_page_heading('A SMALL CONSTELLATION / FRIENDS',feng_setting('friends_intro','在独立的角落，遇见同样认真记录生活的人。')); ?>
<?php if(get_the_content()) { ?><div class="xf-prose feng-page-prose"><?php the_content(); ?></div><?php } ?>
<?php if(!is_wp_error($groups)) foreach($groups as $group): $links=get_bookmarks(array('category'=>$group->term_id,'orderby'=>'name','order'=>'ASC','hide_invisible'=>true)); if(!$links) continue; $visible+=count($links); ?>
<section class="feng-friend-group"><div class="feng-group-title" data-xf-reveal><h2><?php echo esc_html($group->name); ?></h2><span><?php echo esc_html(count($links)); ?> 个邻居</span></div><?php if($group->description) echo '<p class="feng-muted">'.esc_html($group->description).'</p>'; ?><div class="feng-friends">
<?php foreach($links as $friend): ?><a class="feng-friend" href="<?php echo esc_url($friend->link_url); ?>" target="_blank" rel="friend noopener noreferrer" data-xf-reveal><span class="feng-friend__avatar"><?php echo feng_friend_avatar($friend,48); ?></span><span><strong><?php echo esc_html($friend->link_name); ?></strong><small><?php echo esc_html($friend->link_description?:wp_parse_url($friend->link_url,PHP_URL_HOST)); ?></small></span><i aria-hidden="true"><?php echo feng_icon('arrow-up-right'); ?></i></a><?php endforeach; ?></div></section><?php endforeach; if(!$visible) echo '<p class="feng-empty">这里为下一次相遇留了位置。</p>'; ?>
<aside class="feng-friend-note" data-xf-reveal><p class="xf-section-kicker">LET’S CONNECT</p><h2>交换一扇窗</h2><p><?php echo nl2br(esc_html(feng_setting('friends_rules','欢迎通过本页留言交换链接，请留下站点名称、地址和简介。'))); ?></p><dl><div><dt>站点名称</dt><dd><?php bloginfo('name'); ?></dd></div><div><dt>站点地址</dt><dd><a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html(home_url('/')); ?></a></dd></div><div><dt>一句介绍</dt><dd><?php bloginfo('description'); ?></dd></div></dl></aside>
<?php if(comments_open()||get_comments_number()) comments_template(); ?></div>
<?php endwhile; get_footer(); ?>
