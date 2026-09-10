<?php
/** Template Name: ShanYing · 关于 */
if(!defined('ABSPATH')) exit;
get_header();while(have_posts()):the_post();
if(post_password_required()) { echo '<section class="feng-panel">'.get_the_password_form().'</section>';continue; }
$name=feng_setting('profile_name','')?:get_bloginfo('name');$owner=get_userdata((int)get_post_field('post_author',get_the_ID()));
if(!$owner||!in_array('administrator',(array)$owner->roles,true)){$admins=get_users(array('role'=>'administrator','number'=>1,'orderby'=>'ID','order'=>'ASC'));$owner=$admins[0]??null;}
$intro=trim((string)feng_setting('profile_tagline',''));
$location=trim((string)feng_setting('profile_location',''));
$avatar=get_avatar_url($owner?$owner->user_email:get_option('admin_email'),array('size'=>176));
?>
<div class="feng-panel feng-special-page feng-about-page"><header class="feng-page-heading feng-unified-heading"><h1><?php echo feng_page_title_icon().esc_html(get_the_title()); ?></h1><?php feng_social_links(); ?></header>
<div class="feng-page-grid"><aside class="feng-profile" data-xf-reveal><div class="feng-profile__avatar"><?php if($avatar) echo '<img src="'.esc_url($avatar).'" width="88" height="88" alt="'.esc_attr($name).'">'; else echo esc_html(mb_substr($name,0,1)); ?></div><h2><?php echo esc_html($name); ?></h2><?php if($intro!=='') echo '<p>'.nl2br(esc_html($intro)).'</p>'; if($location!=='') echo '<p class="feng-muted">⌖ '.esc_html($location).'</p>'; ?></aside>
<div class="xf-prose feng-page-prose" data-xf-reveal><?php the_content(); wp_link_pages(array('before'=>'<nav class="feng-pagination" aria-label="正文分页">','after'=>'</nav>')); ?></div></div>
<?php if(comments_open()||get_comments_number()) comments_template(); ?>
<?php feng_blog_decade(); ?></div>
<?php endwhile;get_footer(); ?>
