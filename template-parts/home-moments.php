<?php
/** A fresh note below the homepage introduction. */
if(!defined('ABSPATH')) exit;
$notes=get_posts(array('post_type'=>'feng_talk','post_status'=>'publish','has_password'=>false,'posts_per_page'=>1,'orderby'=>array('date'=>'DESC','ID'=>'DESC'),'no_found_rows'=>true));
$talk_page=feng_page_url('talks');
?>
<section class="feng-home-moments" aria-label="此刻的分享">
 <a class="feng-latest-talk" href="<?php echo esc_url($notes&&$talk_page?$talk_page.'#talk-'.$notes[0]->ID:($talk_page?:home_url('/'))); ?>">
  <span class="feng-latest-talk__icon" aria-hidden="true"><?php echo feng_icon('comment'); ?></span>
  <span class="feng-latest-talk__copy"><span class="feng-latest-talk__label">最新说说<?php if($notes): ?> <time datetime="<?php echo esc_attr(get_post_time('c',true,$notes[0])); ?>"><?php echo esc_html(human_time_diff(get_post_time('U',true,$notes[0]),time()).'前'); ?></time><?php endif; ?></span><span class="feng-latest-talk__text"><?php echo $notes?esc_html(wp_trim_words(wp_strip_all_tags(strip_shortcodes($notes[0]->post_content)),70,'…')):'还没有留下便签，期待下一个想分享的瞬间。'; ?></span></span>
  <span class="feng-latest-talk__arrow" aria-hidden="true"><?php echo feng_icon('arrow-up-right'); ?></span>
 </a>
 <?php if(feng_setting('pet_enabled',true)): ?><div class="feng-home-perch" data-feng-pet-slot<?php if(!feng_setting('pet_mobile',true)) echo ' data-hide-mobile'; ?>></div><?php endif; ?>
</section>
