<?php
if(!defined('ABSPATH'))exit;
$xf_cover=feng_post_image_url(null,'full');
$is_post='post'===get_post_type();
$locked=post_password_required();
?>
<article data-feng-pet-post="<?php echo ($is_post&&get_post_status()==='publish'&&!$locked)?get_the_ID():0; ?>" id="post-<?php the_ID(); ?>" <?php post_class('xf-article'); ?>>
<?php if($is_post):
 $feng_author=(int)get_the_author_meta('ID');
 $feng_author_name=get_the_author();
 $feng_stats=$locked?null:feng_article_stats(get_post());
 $feng_previous=get_previous_post();
 $feng_next=get_next_post();
?>
<header class="xf-reading-cover feng-entry-hero <?php echo $xf_cover?'xf-reading-cover--image':''; ?>">
 <?php if($xf_cover): ?><div class="xf-reading-cover__media" aria-hidden="true"><?php echo feng_post_image(null,'full',array('alt'=>'','fetchpriority'=>'high')); ?></div><?php endif; ?>
 <div class="xf-reading-cover__copy feng-entry-hero__copy">
  <h1><?php echo esc_html(get_the_title()?:__('无标题','feng')); ?></h1>
  <div class="feng-entry-tools" aria-label="文章信息与导航">
   <div class="feng-entry-facts">
    <a class="feng-entry-pill" href="<?php echo esc_url(get_author_posts_url($feng_author)); ?>" aria-label="文章作者：<?php echo esc_attr($feng_author_name); ?>"><span class="feng-entry-pill__label">文章作者</span><span class="feng-entry-pill__value"><span class="feng-entry-avatar"><?php $avatar=get_avatar($feng_author,56,'','',array('extra_attr'=>'aria-hidden="true"')); echo $avatar?:esc_html(wp_html_excerpt($feng_author_name,1,'')); ?></span><span class="feng-entry-author"><?php echo esc_html($feng_author_name); ?></span></span></a>
    <span class="feng-entry-pill" tabindex="0"><span class="feng-entry-pill__label">文章发布日期</span><span class="feng-entry-pill__value"><?php echo feng_icon('calendar'); ?><time datetime="<?php echo esc_attr(get_the_date(DATE_W3C)); ?>"><?php echo esc_html(get_the_date('Y年n月j日').' '.get_the_time('H:i')); ?></time></span></span>
    <span class="feng-entry-pill" tabindex="0"><span class="feng-entry-pill__label">热度</span><span class="feng-entry-pill__value"><?php echo feng_icon('heat'); ?><span><?php echo esc_html(number_format_i18n(max(0,(int)get_post_meta(get_the_ID(),'_feng_views',true)))); ?></span></span></span>
    <?php if($feng_stats): ?>
    <span class="feng-entry-pill" tabindex="0"><span class="feng-entry-pill__label">本文共计</span><span class="feng-entry-pill__value"><?php echo feng_icon('words'); ?><span><?php echo esc_html(number_format_i18n($feng_stats['count'])); ?> 字</span></span></span>
    <span class="feng-entry-pill" tabindex="0"><span class="feng-entry-pill__label">预计阅读</span><span class="feng-entry-pill__value"><?php echo feng_icon('clock'); ?><span><?php echo esc_html($feng_stats['minutes']); ?> 分钟</span></span></span>
    <?php endif; ?>
   </div>
   <nav class="feng-entry-actions" aria-label="分享与相邻文章">
    <div class="feng-share" data-feng-share="<?php echo esc_url(get_permalink()); ?>" hidden>
     <button type="button" class="feng-entry-round feng-share-toggle" data-feng-share-toggle aria-label="展开分享" aria-expanded="false" aria-controls="feng-share-options"><span class="feng-share-glyph"><?php echo feng_icon('share'); ?></span><span class="feng-share-cross"><?php echo feng_icon('close'); ?></span></button>
     <div id="feng-share-options" class="feng-share-options" aria-label="分享方式" aria-hidden="true" inert>
      <button type="button" class="feng-share-choice feng-share-choice--wechat" data-feng-wechat aria-label="分享到微信（复制链接）" title="微信"><?php echo feng_icon('wechat'); ?></button>
      <a class="feng-share-choice feng-share-choice--weibo" href="<?php echo esc_url('https://service.weibo.com/share/share.php?'.http_build_query(array('url'=>get_permalink(),'title'=>get_the_title()),'','&',PHP_QUERY_RFC3986)); ?>" target="_blank" rel="noopener noreferrer" aria-label="分享到微博" title="微博"><?php echo feng_icon('weibo'); ?></a>
      <a class="feng-share-choice feng-share-choice--x" href="<?php echo esc_url('https://twitter.com/intent/tweet?'.http_build_query(array('url'=>get_permalink(),'text'=>get_the_title()),'','&',PHP_QUERY_RFC3986)); ?>" target="_blank" rel="noopener noreferrer" aria-label="分享到 X（推特）" title="X / 推特"><?php echo feng_icon('x'); ?></a>
     </div>
     <div class="feng-share-feedback" hidden><p role="status"></p><label hidden>文章链接<input type="text" readonly value="<?php echo esc_url(get_permalink()); ?>"></label></div>
    </div>
    <?php foreach(array('previous'=>array($feng_previous,'上一篇'),'next'=>array($feng_next,'下一篇')) as $direction=>$neighbor): $target=$neighbor[0]; $label=$neighbor[1]; ?>
     <?php if($target): ?><a class="feng-entry-round" href="<?php echo esc_url(get_permalink($target)); ?>" rel="<?php echo $direction==='previous'?'prev':'next'; ?>" aria-label="<?php echo esc_attr($label.'：'.get_the_title($target)); ?>">
     <?php else: ?><span class="feng-entry-round is-unavailable" aria-disabled="true" tabindex="0" aria-label="<?php echo esc_attr('没有'.$label); ?>"><?php endif; ?>
      <span class="feng-entry-round__glyph"><?php echo feng_icon($direction); ?></span>
     <?php echo $target?'</a>':'</span>'; ?>
    <?php endforeach; ?>
    <button type="button" class="feng-entry-round" data-reading-toggle hidden aria-expanded="false" aria-controls="feng-article-toc" aria-label="打开文章目录" title="文章目录"><i class="fa-solid fa-list-ul" aria-hidden="true"></i></button>
   </nav>
  </div>
 </div>
 <?php if($xf_cover): ?><svg class="feng-entry-curve" viewBox="0 0 1000 24" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path d="M0 0 C180 24 820 24 1000 0 V24 H0 Z"/></svg><?php endif; ?>
</header>
<?php else: ?>
 <header class="xf-reading-cover <?php echo $xf_cover?'xf-reading-cover--image':''; ?>">
  <?php if($xf_cover): ?><div class="xf-reading-cover__media" aria-hidden="true"><?php the_post_thumbnail('full',array('alt'=>'','fetchpriority'=>'high')); ?></div><?php endif; ?>
  <div class="xf-reading-cover__copy xf-reading"><p class="xf-section-kicker">PERSONAL SPACE</p><h1><?php if(is_page())echo feng_page_title_icon(); echo esc_html(get_the_title()?:__('无标题','feng')); ?></h1></div>
 </header>
<?php endif; ?>
<?php if($is_post):
 $age=feng_article_freshness(get_post());
 if($age): ?>
<aside class="feng-article-age xf-reading" aria-label="文章时效提醒"><strong>这篇文章已有 <?php echo esc_html($age['label']); ?>未更新</strong><p>最后更新于 <?php echo esc_html($age['updated']->format('Y年n月j日')); ?>。文中涉及的信息、操作步骤或观点可能已有变化，请结合最新情况参考。</p></aside>
<?php endif;
 $summary=feng_reading_summary(get_post());
 if($summary['text']): ?>
<details class="feng-reading-summary" open>
 <summary><?php echo feng_icon($summary['ai']?'sparkle':'words'); ?><span><?php echo $summary['ai']?'AI 辅助摘要':'内容提要'; ?></span><?php echo feng_icon('chevron-down'); ?></summary>
 <div><p><?php echo nl2br(esc_html($summary['text'])); ?></p><?php if($summary['ai']): ?><small>由 AI 辅助整理，作者确认后发布。请以正文为准。</small><?php endif; ?></div>
</details>
<?php endif; endif; ?>
 <div id="xf-article-text" class="xf-prose xf-container"<?php if($is_post&&!$locked)echo ' data-feng-reading'; ?>><?php the_content(); ?><?php wp_link_pages(array('before'=>'<nav class="xf-page-links" aria-label="'.esc_attr__('文章分页','feng').'">','after'=>'</nav>')); ?></div>
<?php if($is_post&&!$locked): ?>
 <footer class="xf-article-footer xf-reading"><div class="feng-article-ending"><span class="feng-article-end-label" aria-label="正文结束">END</span></div>
<?php if(feng_setting('article_copyright',true)):
 $post=get_post();$author=get_the_author_meta('display_name',$post->post_author);$published=get_post_datetime($post,'date');$modified=get_post_datetime($post,'modified');if(!$modified||($published&&$modified<$published))$modified=$published;
 $license=feng_setting('article_license','reserved');$licenses=array('by'=>array('CC BY 4.0','https://creativecommons.org/licenses/by/4.0/deed.zh-hans','转载或改编时请注明作者和原文链接，保留版权与许可标识、提供许可链接，并注明修改。'),'by-nc-sa'=>array('CC BY-NC-SA 4.0','https://creativecommons.org/licenses/by-nc-sa/4.0/deed.zh-hans','转载或改编时请署名、保留原文及许可链接并注明修改；仅限非商业用途，改编作品须以相同许可分享。'));
?>
<section class="feng-article-copyright" aria-label="文章署名与版权">
 <span class="feng-copyright-watermark" aria-hidden="true"><i class="fa-brands fa-creative-commons"></i></span><h2>文章信息与版权</h2><dl>
 <div><dt>本文作者</dt><dd><a href="<?php echo esc_url(get_author_posts_url($post->post_author)); ?>"><?php echo esc_html($author); ?></a></dd></div>
 <div><dt>文章分类</dt><dd><?php the_category(' · '); ?></dd></div>
 <?php foreach(array('首次发布'=>$published,'最后更新'=>$modified) as $label=>$date):if(!$date)continue; ?><div><dt><?php echo esc_html($label); ?></dt><dd><time datetime="<?php echo esc_attr($date->format(DATE_W3C)); ?>"><?php echo esc_html($date->format('Y-m-d H:i')); ?></time></dd></div><?php endforeach; ?>
 </dl><div class="feng-copyright-source"><span>原文</span><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a><span class="feng-copyright-url"><?php echo esc_html(get_permalink()); ?></span></div><?php $keywords=get_the_tags(); if($keywords && !is_wp_error($keywords)): ?>
 <nav class="feng-copyright-keywords" aria-label="文章关键词"><span>关键词</span><?php foreach($keywords as $keyword): $keyword_url=get_tag_link($keyword); if(is_wp_error($keyword_url))continue; ?><a href="<?php echo esc_url($keyword_url); ?>">#<?php echo esc_html($keyword->name); ?></a><?php endforeach; ?></nav>
 <?php endif; ?><p class="feng-copyright-rule">© <?php echo esc_html(wp_date('Y').' '.$author); ?> · <?php if(isset($licenses[$license])): $rule=$licenses[$license]; ?><a rel="license noopener" href="<?php echo esc_url($rule[1]); ?>" target="_blank"><?php echo esc_html($rule[0]); ?></a> · <?php echo esc_html($rule[2]); ?><?php elseif($license==='custom'&&feng_setting('article_license_note','')):echo nl2br(esc_html(feng_setting('article_license_note','')));else: ?>保留所有权利。除法律允许外，转载须获授权，并保留署名、标题、原文链接及版权声明。<?php endif; ?> <span>仅适用于作者享有权利的内容，另有声明及第三方素材依原授权。</span></p>
</section>
<?php endif; ?></footer>
<?php endif; ?>
</article>
<?php if(!is_page()&&!$locked): ?>
<aside class="feng-reading-tools" id="feng-article-toc" data-reading-tools hidden aria-label="阅读工具">
 <div class="feng-reading-tools__heading"><span>文章目录</span><button type="button" data-reading-close aria-label="关闭文章目录"><?php echo feng_icon('close'); ?></button></div>
 <details class="feng-reading-toc" open><summary>目录</summary><nav aria-label="文章目录"><p>本文目录</p><ol></ol></nav></details>
 <button type="button" data-reading-focus aria-pressed="false" aria-label="开启专注阅读" title="专注阅读"><?php echo feng_icon('reading'); ?></button>
 <button type="button" data-reading-print aria-label="打印文章" title="打印文章"><?php echo feng_icon('print'); ?></button>
 <span class="screen-reader-text" role="status" data-reading-status></span>
</aside>
<?php
$items=array();$seen=array();
foreach(array('related','category','random') as $mode){
 foreach(feng_reading_related(get_the_ID(),$mode)->posts as $entry){
  if(isset($seen[$entry->ID]))continue;$seen[$entry->ID]=true;$items[]=$entry;
 }
}
?>
<section class="feng-reading-related" aria-label="继续阅读" data-related>
 <header class="feng-related-heading"><div><p class="xf-section-kicker">KEEP READING</p><h2>再读一篇</h2></div><button type="button" class="feng-related-refresh" data-related-refresh aria-label="换一组文章"><i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i></button></header>
 <div class="feng-related-panel" data-related-panel>
 <?php if(!$items): ?><p class="feng-related-empty">暂时没有其他文章。</p><?php endif; ?>
 <?php foreach($items as $index=>$entry): ?><a class="feng-related-item" href="<?php echo esc_url(get_permalink($entry)); ?>" <?php if($index>=3)echo 'hidden'; ?>>
 <div class="feng-related-image"><?php $image=feng_post_image($entry,'medium_large',array('alt'=>'','loading'=>'lazy','decoding'=>'async'));echo $image?:'<span aria-hidden="true">'.feng_icon('words').'</span>'; ?></div>
 <div class="feng-related-caption"><span class="feng-related-title"><?php echo esc_html(get_the_title($entry)?:'无标题文章'); ?></span><div class="feng-related-meta"><time datetime="<?php echo esc_attr(get_the_date(DATE_W3C,$entry)); ?>"><?php echo esc_html(get_the_date('Y.m.d',$entry)); ?></time><span><?php echo esc_html(get_comments_number($entry->ID)); ?> 评论</span><span><?php echo esc_html(number_format_i18n(max(0,(int)get_post_meta($entry->ID,'_feng_views',true)))); ?> 阅读</span></div></div></a><?php endforeach; ?>
 </div>
 <p class="feng-related-status screen-reader-text" role="status" data-related-status></p>
</section>
<?php endif; ?>
