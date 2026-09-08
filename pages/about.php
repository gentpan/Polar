<?php
/** Template Name: Polar · 关于 */
if(!defined('ABSPATH')) exit;
get_header();while(have_posts()):the_post();
if(post_password_required()) { echo '<section class="feng-panel">'.get_the_password_form().'</section>';continue; }
$name=feng_setting('profile_name','')?:get_bloginfo('name');$avatar=feng_setting('profile_avatar','');
?>
<div class="feng-panel feng-special-page"><?php feng_page_heading('BEHIND THE WORDS / ABOUT'); ?>
<div class="feng-page-grid"><aside class="feng-profile" data-xf-reveal><div class="feng-profile__avatar"><?php if($avatar) echo '<img src="'.esc_url($avatar).'" width="88" height="88" alt="'.esc_attr($name).'">'; else echo esc_html(mb_substr($name,0,1)); ?></div><h2><?php echo esc_html($name); ?></h2><p><?php echo nl2br(esc_html(feng_setting('profile_tagline','保持好奇，记录生活。'))); ?></p><?php if(feng_setting('profile_location','')) echo '<p class="feng-muted">⌖ '.esc_html(feng_setting('profile_location','')).'</p>'; feng_social_links(); ?><div class="feng-profile__stats"><span><strong><?php echo esc_html(wp_count_posts()->publish); ?></strong>篇文章</span><span><strong><?php echo esc_html(wp_count_terms(array('taxonomy'=>'category','hide_empty'=>true))); ?></strong>个主题</span></div></aside>
<div class="xf-prose feng-page-prose" data-xf-reveal><?php the_content(); wp_link_pages(array('before'=>'<nav class="feng-pagination" aria-label="正文分页">','after'=>'</nav>')); if(!trim(get_the_content())) echo '<p>欢迎来到这里。关于我的故事，将在这里慢慢展开。</p>'; ?></div></div>
<?php feng_blog_decade(); ?>
<?php if(comments_open()||get_comments_number()) comments_template(); ?></div>
<?php endwhile;get_footer(); ?>
