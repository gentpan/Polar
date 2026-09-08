<?php
if(!defined('ABSPATH')||post_password_required()||!feng_setting('article_copyright',true))return;
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
