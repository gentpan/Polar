<?php /** Public dashboard, loaded on demand. */
if(!defined('ABSPATH'))exit;
?>
<div class="feng-dashboard-summary" data-dashboard-summary aria-label="站点统计">
<?php foreach($args['summary'] as $label=>$value): ?><div title="<?php echo esc_attr($label==='总字数'?'公开文章正文：中文按字、英文按词计数':($label==='建站天数'?'按后台设置的建站日期计算':$label)); ?>"><strong<?php if($value!==null): ?> data-stat-roll<?php endif; ?>><?php echo $value===null?'未设置':esc_html(number_format_i18n($value)); ?></strong><span><?php echo esc_html($label); ?></span></div><?php endforeach; ?>
</div>
<section class="feng-dash-section"><header><h2><i class="fa-solid fa-folder-open" aria-hidden="true"></i> 分类浏览</h2><a href="<?php echo esc_url(feng_page_url('archives')?:home_url('/')); ?>">全部存档</a></header><div class="feng-dash-categories">
<?php foreach($args['categories'] as $term): ?><a href="<?php echo esc_url(get_category_link($term)); ?>"><span class="feng-dash-category-icon" aria-hidden="true"><?php echo feng_category_badge($term->term_id)?:feng_icon('folder'); ?></span><span><strong><?php echo esc_html($term->name); ?></strong><small><?php echo esc_html($term->description?wp_html_excerpt(wp_strip_all_tags($term->description),32,'…'):'查看这个分类的文章'); ?></small></span><b><?php echo (int)$term->count; ?></b></a><?php endforeach; ?>
<?php if(!$args['categories']): ?><p class="feng-dashboard-empty">还没有公开的文章分类。</p><?php endif; ?></div></section>
<div class="feng-dash-workspace"><div class="feng-dash-main">
<div class="feng-dash-reading">
<section class="feng-dash-section"><header><h2><i class="fa-solid fa-pen-nib" aria-hidden="true"></i> 最新文章</h2></header><?php feng_dashboard_posts($args['recent'],'新的记录正在路上。'); ?></section>
<section class="feng-dash-section"><header><h2><i class="fa-solid fa-bookmark" aria-hidden="true"></i> 热门阅读</h2></header><?php feng_dashboard_posts($args['recommended'],'还没有公开文章。'); ?></section>
</div>
<section class="feng-dash-section"><header><h2><i class="fa-solid fa-comment-dots" aria-hidden="true"></i> 最近说说</h2><a href="<?php echo esc_url(feng_page_url('talks')?:home_url('/memos/')); ?>">全部说说</a></header><div class="feng-dash-talks">
<?php foreach($args['talks'] as $talk): ?><a href="<?php echo esc_url(feng_page_url('talks')?:home_url('/memos/')); ?>"><time datetime="<?php echo esc_attr(get_the_date('c',$talk)); ?>"><?php echo esc_html(get_the_date('m.d',$talk)); ?></time><span><?php echo esc_html(wp_html_excerpt(strip_shortcodes(wp_strip_all_tags($talk->post_content)),100,'…')); ?></span></a><?php endforeach; ?>
<?php if(!$args['talks']): ?><p class="feng-dashboard-empty">还没有说说。</p><?php endif; ?></div></section>
<section class="feng-dash-section"><section class="feng-dashboard-conversations" aria-labelledby="feng-conversations-title">
 <div class="feng-dashboard-section-head"><h2 id="feng-conversations-title"><?php echo feng_icon('comment'); ?>最近评论</h2><?php if(feng_page_url('guestbook')): ?><a href="<?php echo esc_url(feng_page_url('guestbook')); ?>">去留言 <?php echo feng_icon('arrow-up-right'); ?></a><?php endif; ?></div>
 <div class="feng-dashboard-comments">
 <?php foreach($args['comment_ids'] as $id): $comment=get_comment($id); $name=get_comment_author($comment); ?>
 <a class="feng-dashboard-comment" href="<?php echo esc_url(get_comment_link($comment)); ?>">
  <span class="feng-dashboard-avatar" aria-hidden="true"><?php if(get_option('show_avatars')) echo get_avatar($comment,36,'','',array('loading'=>'lazy')); else echo esc_html(mb_substr($name,0,1)); ?></span>
  <span class="feng-dashboard-comment-text"><span class="feng-dashboard-byline"><strong><?php echo esc_html($name); ?></strong><time datetime="<?php echo esc_attr(get_comment_date('c',$comment)); ?>"><?php echo esc_html(human_time_diff(strtotime($comment->comment_date_gmt.' UTC'),time())); ?>前</time></span><span class="feng-dashboard-excerpt"><?php echo esc_html(wp_html_excerpt(strip_shortcodes(wp_strip_all_tags($comment->comment_content)),90,'…')); ?></span></span>
 </a>
 <?php endforeach; if(!$args['comment_ids']): ?><p class="feng-dashboard-empty">还没有留言，来留下第一句话吧。</p><?php endif; ?>
 </div>
</section>
</section></div><aside class="feng-dash-sidebar">
<section class="feng-dash-section"><header><h2><i class="fa-solid fa-calendar-days" aria-hidden="true"></i> 文章日历</h2></header><div data-feng-calendar><?php feng_dashboard_calendar(); ?></div></section>
<section class="feng-dash-section"><header><h2><i class="fa-solid fa-hashtag" aria-hidden="true"></i> 热门标签</h2></header><div class="feng-dashboard-tags"><?php if(!is_wp_error($args['tags']))foreach($args['tags'] as $tag){$url=get_term_link($tag);if(!is_wp_error($url))echo '<a href="'.esc_url($url).'">'.esc_html($tag->name).'<small>'.(int)$tag->count.'</small></a>';}if(is_wp_error($args['tags'])||!$args['tags'])echo '<p class="feng-dashboard-empty">还没有标签。</p>'; ?></div></section>
<section class="feng-dash-section"><header><h2><?php echo feng_icon('link'); ?> 友情链接</h2><a href="<?php echo esc_url(feng_page_url('friends')?:home_url('/links/')); ?>">全部友链</a></header><div class="feng-dash-friends">
<?php foreach($args['friends'] as $friend): ?><a href="<?php echo esc_url($friend->link_url); ?>" target="_blank" rel="noopener noreferrer"><span class="feng-dash-friend-avatar" aria-hidden="true"><?php echo feng_friend_avatar($friend,28,true); ?></span><span><?php echo esc_html($friend->link_name); ?></span></a><?php endforeach; ?>
<?php if(!$args['friends']): ?><p class="feng-dashboard-empty">还没有公开的友情链接。</p><?php endif; ?>
</div></section>
</aside></div>
