<?php
get_header();
$xf_home=is_home()&&!is_paged();
if($xf_home){
 get_template_part('template-parts/home-hero');
 get_template_part('template-parts/collections');
}elseif(have_posts()){
?>
<div class="xf-post-list<?php if(is_home()) echo ' feng-recent-list'; ?>">
<?php while(have_posts()): the_post();
 if(is_home()):
  $entry=get_post();
  $categories=get_the_category($entry->ID);
  $image=feng_post_image($entry,'medium_large',array('alt'=>'','loading'=>'eager','decoding'=>'async'));
?>
<article id="post-<?php echo absint($entry->ID); ?>" <?php post_class('feng-recent-row'); ?> data-xf-reveal>
 <a class="feng-recent-row__image" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1"><?php if($image) echo feng_deferred_image($image); ?></a>
 <div class="feng-recent-row__copy">
  <div class="feng-recent-row__meta"><?php if($categories): ?><span><?php echo esc_html($categories[0]->name); ?></span><?php endif; ?><time datetime="<?php echo esc_attr(get_the_date(DATE_W3C)); ?>"><?php echo esc_html(get_the_date('Y.m.d')); ?></time></div>
  <h2><a href="<?php the_permalink(); ?>"><?php echo esc_html(get_the_title()?:__('无标题','feng')); ?></a></h2>
  <p><?php echo esc_html(post_password_required()?'这篇文章受密码保护。':wp_trim_words(wp_strip_all_tags(get_the_excerpt()),42,'…')); ?></p>
 </div>
 <span class="feng-recent-row__arrow" aria-hidden="true"><?php echo feng_icon('next'); ?></span>
</article>
<?php else: get_template_part('template-parts/post-card',null,array('post'=>get_post())); endif;
endwhile; ?>
</div>
<?php feng_pagination();
}else{ ?>
<section class="xf-empty xf-reading">
	<h2><?php esc_html_e('暂无内容','feng'); ?></h2>
	<p><?php echo esc_html(is_search()?__('没有找到匹配的结果，可以换个关键词再试。','feng'):__('这里暂时没有已发布的文章。','feng')); ?></p>
	<?php if(!is_search()) get_search_form(); ?>
</section>
<?php }
get_footer();
