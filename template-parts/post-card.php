<?php
/** Full-image article card with an image-derived caption color. @package ShanYing */
if(!defined('ABSPATH')) exit;
$entry=get_post($args['post'] ?? null);
if(!$entry) return;
$collection=!empty($args['collection']);
$heading=$collection?'h4':'h2';
$image=feng_post_image($entry,'large',array('alt'=>'','loading'=>'eager','decoding'=>'async'));
$categories=get_the_category($entry->ID);
$locked=post_password_required($entry);
$travel=!empty($args['travel']);
$place=$travel&&!$locked?feng_travel_clean(feng_travel_location($entry->ID)):array();
$tone=$locked ? '' : sanitize_hex_color(get_transient(feng_card_color_key($entry->ID)));
$color_url=$locked ? '' : add_query_arg(array('action'=>'feng_card_color','post_id'=>$entry->ID),admin_url('admin-ajax.php'));
?>
<article <?php if(!$collection) echo 'id="post-'.absint($entry->ID).'"'; ?> <?php post_class(array($collection?'xf-story':'xf-note-card','feng-cover-card'),$entry); ?> data-xf-reveal<?php if($tone): ?> style="--feng-card-tone:<?php echo esc_attr($tone); ?>" data-feng-color-ready<?php endif; ?><?php if($color_url): ?> data-feng-color-url="<?php echo esc_url($color_url); ?>"<?php endif; ?>>
 <a class="feng-cover-card__image" href="<?php echo esc_url(get_permalink($entry)); ?>" aria-hidden="true" tabindex="-1"><?php if($image) echo feng_deferred_image($image); ?></a>
 <?php if($travel): ?>
 <?php if(!empty($place['country'])): ?><span class="feng-cover-card__category feng-travel-country"><?php if(!empty($place['code'])): ?><img src="<?php echo esc_url('https://flagcdn.io/flags/4x3/'.$place['code'].'.svg'); ?>" width="24" height="18" alt=""><?php endif; ?><?php echo esc_html($place['country']); ?></span><?php endif; ?>
 <?php elseif(!$collection && $categories): ?><span class="feng-cover-card__category"><?php echo esc_html($categories[0]->name); ?></span><?php endif; ?>
 <?php if($collection): ?><div class="feng-card-corners"><time datetime="<?php echo esc_attr(get_the_date(DATE_W3C,$entry)); ?>"><?php echo feng_icon('calendar'); echo esc_html(get_the_date('Y.m.d',$entry)); ?></time><span aria-label="<?php echo esc_attr(get_comments_number($entry).' 条评论'); ?>"><?php echo feng_icon('comment'); echo esc_html(number_format_i18n(get_comments_number($entry))); ?></span></div><?php endif; ?>
 <div class="feng-cover-card__body">
  <?php if(!$collection&&!$locked&&!$travel) echo feng_travel_badge($entry->ID); ?>
  <?php if(!$collection): ?><div class="feng-cover-card__meta"><?php if($travel): ?><?php if(!empty($place['date_start'])): ?><span class="feng-travel-dates" aria-label="旅行日期"><?php echo feng_icon('calendar'); ?><time datetime="<?php echo esc_attr($place['date_start']); ?>"><?php echo esc_html(str_replace('-','.',$place['date_start'])); ?></time><?php if(!empty($place['date_end'])&&$place['date_end']!==$place['date_start']): ?><span>—</span><time datetime="<?php echo esc_attr($place['date_end']); ?>"><?php echo esc_html(str_replace('-','.',$place['date_end'])); ?></time><?php endif; ?></span><?php endif; ?><?php else: ?><time datetime="<?php echo esc_attr(get_the_date(DATE_W3C,$entry)); ?>"><?php echo feng_icon('calendar'); echo esc_html(get_the_date('Y.m.d',$entry)); ?></time><?php endif; ?><span aria-label="<?php echo esc_attr(get_comments_number($entry).' 条评论'); ?>"><?php echo feng_icon('comment'); echo esc_html(number_format_i18n(get_comments_number($entry))); ?></span><span aria-hidden="true"><?php echo feng_icon('arrow-up-right'); ?></span></div><?php endif; ?>
  <<?php echo $heading; ?>><a href="<?php echo esc_url(get_permalink($entry)); ?>"><?php echo esc_html(get_the_title($entry) ?: __('无标题','feng')); ?></a></<?php echo $heading; ?>>
  <?php if($collection): ?><p class="feng-card-hover-summary" aria-hidden="true"><?php echo esc_html($locked?'这篇文章受密码保护。':(wp_trim_words(wp_strip_all_tags(get_the_excerpt($entry)),60,'…') ?: '打开文章，继续阅读。')); ?></p><?php endif; ?>
  <?php if(!$collection): ?><p><?php echo esc_html($locked?'这篇文章受密码保护。':wp_trim_words(wp_strip_all_tags(get_the_excerpt($entry)),36,'…')); ?></p><?php endif; ?>
 </div>
</article>
