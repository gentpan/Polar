<?php
if(!defined('ABSPATH') || post_password_required())return;
$items=array();$seen=array();
foreach(array('related','category','random') as $mode){
 foreach(feng_reading_related(get_the_ID(),$mode)->posts as $entry){
  if(isset($seen[$entry->ID]))continue;$seen[$entry->ID]=true;$items[]=$entry;
 }
}
?>
<section class="feng-reading-related" aria-label="继续阅读" data-related>
 <header class="feng-related-heading"><div><p class="xf-section-kicker">KEEP READING</p><h2>再读一篇</h2></div><button type="button" class="feng-related-refresh" data-related-refresh data-lordicon-content="relatedrefresh" aria-label="换一组文章"><?php echo feng_icon('refresh'); ?></button></header>
 <div class="feng-related-panel" data-related-panel>
 <?php if(!$items): ?><p class="feng-related-empty">暂时没有其他文章。</p><?php endif; ?>
 <?php foreach($items as $index=>$entry): ?><a class="feng-related-item" href="<?php echo esc_url(get_permalink($entry)); ?>" <?php if($index>=3)echo 'hidden'; ?>>
 <div class="feng-related-image"><?php $image=feng_post_image($entry,'medium_large',array('alt'=>'','loading'=>'lazy','decoding'=>'async'));echo $image?:'<span aria-hidden="true">'.feng_icon('words').'</span>'; ?></div>
 <div class="feng-related-caption"><span class="feng-related-title"><?php echo esc_html(get_the_title($entry)?:'无标题文章'); ?></span><div class="feng-related-meta"><time datetime="<?php echo esc_attr(get_the_date(DATE_W3C,$entry)); ?>"><?php echo esc_html(get_the_date('Y.m.d',$entry)); ?></time><span><?php echo esc_html(get_comments_number($entry->ID)); ?> 评论</span><span><?php echo esc_html(number_format_i18n(max(0,(int)get_post_meta($entry->ID,'_feng_views',true)))); ?> 阅读</span></div></div></a><?php endforeach; ?>
 </div>
 <p class="feng-related-status screen-reader-text" role="status" data-related-status></p>
</section>
