<?php
if (!defined('ABSPATH')) exit;
$xf_cover = isset( $args['cover'] ) ? $args['cover'] : '';
$feng_post=get_post();
$feng_locked=post_password_required();
$feng_author=(int)get_the_author_meta('ID');
$feng_author_name=get_the_author();
$feng_stats=$feng_locked?null:feng_article_stats($feng_post);
$feng_previous=get_previous_post();
$feng_next=get_next_post();
?>
<header class="xf-reading-cover feng-entry-hero <?php echo $xf_cover?'xf-reading-cover--image':''; ?>">
 <?php if ($xf_cover): ?><div class="xf-reading-cover__media" aria-hidden="true"><?php echo feng_post_image(null,'full',array('alt'=>'','fetchpriority'=>'high')); ?></div><?php endif; ?>
 <div class="xf-reading-cover__copy feng-entry-hero__copy">
  <h1><?php echo esc_html(get_the_title()?:__('无标题','feng')); ?></h1>
  <div class="feng-entry-tools" aria-label="文章信息与导航">
   <div class="feng-entry-facts">
    <a class="feng-entry-pill" href="<?php echo esc_url(get_author_posts_url($feng_author)); ?>" aria-label="文章作者：<?php echo esc_attr($feng_author_name); ?>"><span class="feng-entry-pill__label">文章作者</span><span class="feng-entry-pill__value"><span class="feng-entry-avatar"><?php $avatar=get_avatar($feng_author,56,'','',array('extra_attr'=>'aria-hidden="true"')); echo $avatar?:esc_html(wp_html_excerpt($feng_author_name,1,'')); ?></span><span class="feng-entry-author"><?php echo esc_html($feng_author_name); ?></span></span></a>
    <span class="feng-entry-pill" tabindex="0"><span class="feng-entry-pill__label">文章发布日期</span><span class="feng-entry-pill__value"><?php echo feng_icon('calendar'); ?><time datetime="<?php echo esc_attr(get_the_date(DATE_W3C)); ?>"><?php echo esc_html(get_the_date('Y年n月j日').' '.get_the_time('H:i')); ?></time></span></span>
    <span class="feng-entry-pill" tabindex="0"><span class="feng-entry-pill__label">热度</span><span class="feng-entry-pill__value"><?php echo feng_icon('heat'); ?><span><?php echo esc_html(number_format_i18n(max(0,(int)get_post_meta(get_the_ID(),'_feng_views',true)))); ?></span></span></span>
    <?php if ($feng_stats): ?>
    <span class="feng-entry-pill" tabindex="0"><span class="feng-entry-pill__label">本文共计</span><span class="feng-entry-pill__value"><?php echo feng_icon('words'); ?><span><?php echo esc_html(number_format_i18n($feng_stats['count'])); ?> 字</span></span></span>
    <span class="feng-entry-pill" tabindex="0"><span class="feng-entry-pill__label">预计阅读</span><span class="feng-entry-pill__value"><?php echo feng_icon('clock'); ?><span><?php echo esc_html($feng_stats['minutes']); ?> 分钟</span></span></span>
    <?php endif; ?>
   </div>
   <nav class="feng-entry-actions" aria-label="分享与相邻文章">
    <div class="feng-share" data-feng-share="<?php echo esc_url(get_permalink()); ?>" hidden>
     <button type="button" class="feng-entry-round feng-share-toggle" data-feng-share-toggle aria-label="展开分享" aria-expanded="false" aria-controls="feng-share-options"><span class="feng-share-glyph"><?php echo feng_icon('share'); ?></span><span class="feng-share-cross"><?php echo feng_icon('close'); ?></span></button>
     <div id="feng-share-options" class="feng-share-options" aria-label="分享方式" aria-hidden="true" inert>
      <button type="button" class="feng-share-choice feng-share-choice--wechat" data-feng-wechat aria-label="分享到微信（复制链接）" title="微信"><?php echo feng_icon('wechat'); ?></button>
      <a class="feng-share-choice feng-share-choice--weibo" href="<?php echo esc_url('https://service.weibo.com/share/share.php?'.http_build_query(array('url'=>get_permalink(),'title'=>get_the_title()),'', '&', PHP_QUERY_RFC3986)); ?>" target="_blank" rel="noopener noreferrer" aria-label="分享到微博" title="微博"><?php echo feng_icon('weibo'); ?></a>
      <a class="feng-share-choice feng-share-choice--x" href="<?php echo esc_url('https://twitter.com/intent/tweet?'.http_build_query(array('url'=>get_permalink(),'text'=>get_the_title()),'', '&', PHP_QUERY_RFC3986)); ?>" target="_blank" rel="noopener noreferrer" aria-label="分享到 X（推特）" title="X / 推特"><?php echo feng_icon('x'); ?></a>
     </div>
     <div class="feng-share-feedback" hidden><p role="status"></p><label hidden>文章链接<input type="text" readonly value="<?php echo esc_url(get_permalink()); ?>"></label></div>
    </div>
    <?php foreach(array('previous'=>array($feng_previous,'上一篇'),'next'=>array($feng_next,'下一篇')) as $direction=>$neighbor): $target=$neighbor[0]; $label=$neighbor[1]; ?>
     <?php if ($target): ?><a class="feng-entry-round" href="<?php echo esc_url(get_permalink($target)); ?>" rel="<?php echo $direction==='previous'?'prev':'next'; ?>" aria-label="<?php echo esc_attr($label.'：'.get_the_title($target)); ?>">
     <?php else: ?><span class="feng-entry-round is-unavailable" aria-disabled="true" tabindex="0" aria-label="<?php echo esc_attr('没有'.$label); ?>"><?php endif; ?>
      <span class="feng-entry-round__glyph"><?php echo feng_icon($direction); ?></span>
     <?php echo $target?'</a>':'</span>'; ?>
    <?php endforeach; ?>
   </nav>
  </div>
 </div>
 <?php if ($xf_cover): ?><svg class="feng-entry-curve" viewBox="0 0 1000 24" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path d="M0 0 C180 24 820 24 1000 0 V24 H0 Z"/></svg><?php endif; ?>
</header>
