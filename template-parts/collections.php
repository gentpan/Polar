<?php
$xf_groups=get_categories(array('hide_empty'=>false,'orderby'=>'name','order'=>'ASC'));
?>
<section id="xf-collections" class="xf-collections feng-article-hub" aria-labelledby="xf-collections-title">
 <header class="feng-article-hub__heading"><div><span class="xf-section-kicker">COLLECTIONS</span><h2 id="xf-collections-title">在不同的片刻里，发现我</h2></div><div class="feng-hub-mood"><img data-hub-watermark alt="" hidden><p data-hub-description>把走过的路、想过的事，慢慢写成自己的生活。</p></div></header>
 <nav class="feng-article-hub__tabs" aria-label="文章分类">
 <button type="button" data-collection-tab="all" data-description="把走过的路、想过的事，慢慢写成自己的生活。" aria-pressed="true" aria-controls="collection-panel-all"><?php echo feng_icon('words'); ?>全部</button>
 <?php $first=false;foreach($xf_groups as $group): ?><button type="button" data-collection-tab="<?php echo (int)$group->term_id; ?>" data-description="<?php echo esc_attr(wp_strip_all_tags($group->description)?:'关于'.$group->name.'，一些实践与记录。'); ?>" data-watermark="<?php echo esc_url(get_term_meta($group->term_id,'feng_category_cover',true)); ?>" aria-pressed="<?php echo $first?'true':'false'; ?>" aria-controls="collection-panel-<?php echo (int)$group->term_id; ?>"><?php echo feng_category_badge($group->term_id)?:feng_icon('folder'); ?><?php echo esc_html($group->name); ?></button><?php $first=false;endforeach; ?>
 </nav>
 <div id="collection-panel-all" data-collection-panel="all" data-hub-endpoint="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
 <?php $latest=feng_hub_query();$all=(object)array('term_id'=>0,'name'=>'最新文章','slug'=>'all');if($latest->posts)get_template_part('template-parts/collection-list',null,array('term'=>$all,'query'=>$latest,'all'=>true));else echo '<p>还没有发布文章。</p>'; ?>
 </div>
 <?php $first=false;foreach($xf_groups as $group):
 $query=feng_hub_query('latest',1,$group->term_id); ?>
 <div id="collection-panel-<?php echo (int)$group->term_id; ?>" data-collection-panel="<?php echo (int)$group->term_id; ?>" data-hub-endpoint="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" data-hub-category="<?php echo (int)$group->term_id; ?>" <?php if(!$first)echo 'hidden'; ?>>
 <?php get_template_part('template-parts/collection-list',null,array('term'=>$group,'query'=>$query)); ?>
 </div>
 <?php $first=false;endforeach; ?>
</section>
