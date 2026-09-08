<?php
/** Template Name: ShanYing · 文章存档 */
if(!defined('ABSPATH')) exit;
get_header();
while(have_posts()): the_post();
if(post_password_required()){echo '<section class="feng-panel">'.get_the_password_form().'</section>';continue;}
$entries=get_posts(array('post_type'=>'post','post_status'=>'publish','posts_per_page'=>-1,'orderby'=>'date','order'=>'DESC','has_password'=>false));
$categories=get_terms(array('taxonomy'=>'category','hide_empty'=>true));if(is_wp_error($categories))$categories=array();
$tags=get_terms(array('taxonomy'=>'post_tag','hide_empty'=>true,'orderby'=>'count','order'=>'DESC'));if(is_wp_error($tags))$tags=array();
$years=array();$latest=array();$post_categories=array();
foreach($entries as $entry){$years[get_the_date('Y',$entry)][get_the_date('n',$entry)][]=$entry;$terms=get_the_category($entry->ID);$post_categories[$entry->ID]=$terms;foreach($terms as $term)if(!isset($latest[$term->term_id]))$latest[$term->term_id]=$entry;}
$totals=feng_footer_content_stats();
?>
<div class="feng-panel feng-special-page feng-archive-index">
<header class="feng-archive-index-heading feng-unified-heading"><h1><span class="feng-archive-title-icon"><i class="fa-solid fa-box-archive" aria-hidden="true"></i></span><?php the_title(); ?></h1><div class="feng-archive-index-stats"><span><b><?php echo count($entries); ?></b> 篇文章</span><span><b><?php echo count($categories); ?></b> 个分类</span><span><b><?php echo esc_html(number_format_i18n($totals['words'])); ?></b> 字</span><span><b><?php echo esc_html(number_format_i18n((int)get_option('feng_total_pageviews',0))); ?></b> 次浏览</span></div></header>
<div class="feng-archive-index-content">
<div class="feng-archive-index-heatmap"><?php feng_activity_calendar('post',0); ?></div>
<section class="feng-archive-category-section"><header class="feng-archive-section-heading"><h2><i class="fa-solid fa-folder-tree" aria-hidden="true"></i> 分类</h2><span><?php echo count($categories); ?> 个</span></header><div class="feng-archive-category-grid">
<?php foreach($categories as $category): $recent=$latest[$category->term_id]??null; $badge=feng_category_badge($category->term_id)?:'<i class="fa-regular fa-folder" aria-hidden="true"></i>'; ?>
<article class="feng-archive-category"><header><a href="<?php echo esc_url(get_category_link($category)); ?>"><span class="feng-archive-category-icon"><?php echo $badge; ?></span><?php echo esc_html($category->name); ?></a><span><?php echo (int)$category->count; ?> 篇</span></header><span class="feng-archive-category-watermark" aria-hidden="true"><?php echo $badge; ?></span><p><?php echo esc_html(wp_strip_all_tags($category->description)?:'关于'.$category->name.'的记录与分享。'); ?></p><?php if($recent): ?><a class="feng-archive-category-latest" href="<?php echo esc_url(get_permalink($recent)); ?>"><span><?php echo esc_html(get_the_title($recent)?:'无标题'); ?></span><time datetime="<?php echo esc_attr(get_the_date('c',$recent)); ?>"><?php echo esc_html(get_the_date('Y.m.d',$recent)); ?></time></a><?php endif; ?></article>
<?php endforeach; ?></div></section>
<section class="feng-archive-tag-section"><header class="feng-archive-section-heading"><h2><i class="fa-solid fa-tags" aria-hidden="true"></i> 标签</h2><span><?php echo count($tags); ?> 个</span></header><div class="feng-archive-tag-list"><?php foreach($tags as $tag): ?><a href="<?php echo esc_url(get_tag_link($tag)); ?>"><?php echo esc_html($tag->name); ?> <sup><?php echo (int)$tag->count; ?></sup></a><?php endforeach; ?></div></section>
<?php foreach($years as $year=>$months): $year_count=array_sum(array_map('count',$months)); ?>
<section class="feng-archive-year" id="archive-year-<?php echo (int)$year; ?>"><header><h2><?php echo (int)$year; ?></h2><span><?php echo $year_count; ?> 篇</span></header>
<?php foreach($months as $month=>$posts): ?><section class="feng-archive-month"><header><h3><?php echo (int)$month; ?>月</h3><span><?php echo count($posts); ?> 篇</span></header><ol>
<?php foreach($posts as $entry): $terms=$post_categories[$entry->ID];$badge=$terms?feng_category_badge($terms[0]->term_id):''; ?>
<li><time datetime="<?php echo esc_attr(get_the_date('c',$entry)); ?>"><?php echo esc_html(get_the_date('m/d',$entry)); ?></time><a class="feng-archive-post" href="<?php echo esc_url(get_permalink($entry)); ?>"><span class="feng-archive-category-icon" aria-hidden="true"><?php echo $badge?:'<i class="fa-regular fa-file-lines"></i>'; ?></span><span><?php echo esc_html(get_the_title($entry)?:'无标题'); ?></span></a><span class="feng-archive-post-stats"><span aria-label="<?php echo (int)$entry->comment_count; ?> 条评论"><i class="fa-regular fa-comment" aria-hidden="true"></i> <?php echo (int)$entry->comment_count; ?></span><span aria-label="<?php echo (int)get_post_meta($entry->ID,'_feng_views',true); ?> 次浏览"><i class="fa-regular fa-eye" aria-hidden="true"></i> <?php echo number_format_i18n((int)get_post_meta($entry->ID,'_feng_views',true)); ?></span></span></li>
<?php endforeach; ?></ol></section><?php endforeach; ?></section><?php endforeach; ?>
<?php if(!$entries): ?><p class="feng-empty">还没有公开的文章。</p><?php endif; ?>
</div></div>
<?php endwhile;get_footer(); ?>
