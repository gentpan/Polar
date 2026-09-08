<?php
/** Shared search and taxonomy browsing surface. */
if(!defined('ABSPATH'))exit;
global $wp_query;
$search=is_search();$tag=is_tag();$category=is_category();
$title=$search?(get_search_query(false)?:'搜索文章'):(($tag||$category)?single_term_title('',false):get_the_archive_title());
$icon=$search?'fa-magnifying-glass':($tag?'fa-hashtag':'fa-folder-open');
$description=$search?'从记录里，找回你感兴趣的片刻。':get_the_archive_description();
$count=(int)$wp_query->found_posts;
?>
<section class="feng-browse">
 <header class="feng-browse-head">
  <div><p class="feng-browse-eyebrow"><?php echo $search?'SEARCH / 搜索':($tag?'TOPIC / 关键词':'COLLECTION / 分类浏览'); ?></p><h1><i class="fa-solid <?php echo esc_attr($icon); ?>" aria-hidden="true"></i><?php echo esc_html(wp_strip_all_tags($title)); ?></h1>
  <?php if($description): ?><div class="feng-browse-description"><?php echo wp_kses_post($description); ?></div><?php endif; ?></div>
  <div class="feng-browse-search"><?php get_search_form(); ?></div>
 </header>
 <?php if($category): $terms=get_categories(array('hide_empty'=>true)); if($terms): ?><nav class="feng-browse-topics" aria-label="文章分类"><?php foreach($terms as $term):$url=get_category_link($term);if(is_wp_error($url))continue; ?><a href="<?php echo esc_url($url); ?>"<?php if(get_queried_object_id()===$term->term_id)echo ' aria-current="page"'; ?>><?php echo esc_html($term->name); ?><sup><?php echo (int)$term->count; ?></sup></a><?php endforeach; ?></nav><?php endif; endif; ?>
 <div class="feng-browse-count"><span><?php echo $search?'找到':'共'; ?> <strong><?php echo esc_html(number_format_i18n($count)); ?></strong> 篇内容</span><span><?php echo $search?'按相关度排列':'按发布时间排列'; ?></span></div>
 <?php if(have_posts()): ?><div class="feng-browse-results">
 <?php while(have_posts()):the_post(); ?><article <?php post_class('feng-browse-row'); ?>>
 <div class="feng-browse-copy"><div class="feng-browse-meta"><time datetime="<?php echo esc_attr(get_the_date(DATE_W3C)); ?>"><?php echo esc_html(get_the_date('Y.m.d')); ?></time><?php $cats=get_the_category();if($cats): ?><span><?php echo esc_html($cats[0]->name); ?></span><?php endif; ?></div><h2><a href="<?php the_permalink(); ?>"><?php echo esc_html(get_the_title()?:'无标题'); ?></a></h2><p><?php echo esc_html(post_password_required()?'这篇文章受密码保护。':wp_trim_words(wp_strip_all_tags(get_the_excerpt()),54,'…')); ?></p></div>
 <a class="feng-browse-image" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1"><?php echo feng_post_image(get_post(),'medium_large',array('alt'=>'','loading'=>'lazy','decoding'=>'async')); ?></a>
 </article><?php endwhile; ?></div><?php feng_pagination(); ?>
 <?php else: ?><div class="feng-browse-empty"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><h2><?php echo $search?'没有找到相关内容':'这里还没有文章'; ?></h2><p><?php echo $search?'试试更短的关键词，或换个说法。':'新的记录，正在路上。'; ?></p><a href="<?php echo esc_url(home_url('/')); ?>">返回首页</a></div><?php endif; ?>
</section>
