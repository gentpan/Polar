<?php
/** Template Name: Polar · 留言 */
if(!defined('ABSPATH')) exit;
get_header();while(have_posts()):the_post(); ?>
<div class="feng-panel feng-special-page"><?php feng_page_heading('LEAVE A LITTLE NOTE / GUESTBOOK','路过也好，常来也好。留下一句话，让我知道你来过。'); ?><div class="xf-prose feng-page-prose"><?php the_content(); ?></div><?php comments_template(); ?></div>
<?php endwhile;get_footer(); ?>
