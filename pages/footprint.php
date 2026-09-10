<?php
/** Template Name: 足迹地图 */
get_header();$travel_title=get_the_title();$posts=feng_footprint_posts();$points=array();
foreach($posts as $p){if(post_password_required($p))continue;$v=feng_travel_location($p->ID);if(isset($v['lat'],$v['lng'])||!empty($v['place_id']))$points[]=array('id'=>$p->ID,'lat'=>$v['lat']??null,'lng'=>$v['lng']??null,'source'=>$v['source']??'manual','crs'=>$v['crs']??'wgs84','place_id'=>$v['place_id']??'','name'=>trim(($v['city']??'').' '.($v['country']??'')),'title'=>$p->post_title,'url'=>get_permalink($p));}
?>
<section class="feng-travel feng-travel-world" data-travel-page data-travel-fullscreen>
<div class="feng-travel-map" data-travel-map aria-label="旅行足迹世界地图"></div>
<header class="feng-travel-world-heading"><div class="feng-travel-perch" data-feng-pet-slot></div><h1>足迹 <sup><span data-travel-place-count>—</span> 处</sup></h1><p>把日子，留在世界的坐标里。</p></header>
<div class="feng-travel-toolbar" role="group" aria-label="足迹地图工具栏"><button type="button" data-travel-all title="旅行文章"><i class="fa-solid fa-list" aria-hidden="true"></i><span>文章</span></button><span data-travel-settings-slot></span><button type="button" data-travel-zoom="in" aria-label="放大地图" title="放大" disabled><i class="fa-solid fa-plus" aria-hidden="true"></i></button><button type="button" data-travel-zoom="out" aria-label="缩小地图" title="缩小" disabled><i class="fa-solid fa-minus" aria-hidden="true"></i></button><button type="button" data-travel-reset title="查看全部足迹" aria-label="查看全部足迹"><i class="fa-solid fa-expand" aria-hidden="true"></i></button></div>
<section class="feng-map-settings" data-map-settings hidden aria-label="地图设置"><h2>地图设置</h2><label>视图模式<select data-travel-view><option value="flat">平面地图</option><option value="terrain">立体地图</option><option value="globe">地球视图</option></select></label><label class="feng-travel-style" hidden>地图风格<select data-travel-style aria-label="地图风格"></select></label><label>地球背景<select data-globe-background><option value="system">跟随站点</option><option value="light">浅色</option><option value="dark">深色星空</option></select></label><p>立体模式可放大查看山川起伏；地球模式可旋转浏览全球。</p></section>
<p data-travel-status role="status">地图加载中…</p>
<script type="application/json" data-travel-data><?php echo wp_json_encode(array('config'=>feng_map_public(),'points'=>$points),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?></script>
<aside class="feng-travel-drawer" data-travel-drawer hidden aria-labelledby="travel-drawer-title">
<header><h2 id="travel-drawer-title" tabindex="-1">全部旅行文章</h2><button type="button" data-travel-close aria-label="关闭文章面板"><?php echo feng_icon('close'); ?></button></header>
<form data-travel-filter role="search"><label class="screen-reader-text" for="travel-query">筛选旅行文章</label><input id="travel-query" type="search" placeholder="搜索城市、国家或文章"><button type="submit" aria-label="搜索旅行文章" title="搜索"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></button></form>
<div class="feng-travel-grid"><?php foreach($posts as $entry): $place=post_password_required($entry)?array():feng_travel_location($entry->ID); ?><div data-travel-card="<?php echo (int)$entry->ID; ?>" data-travel-text="<?php echo esc_attr($entry->post_title.' '.($place['city']??'').' '.($place['country']??'')); ?>"><?php get_template_part('template-parts/post-card',null,array('post'=>$entry,'travel'=>true)); ?></div><?php endforeach; ?></div><p data-travel-empty hidden>没有找到对应记录，试试其他城市或国家。</p>
</aside>
</section>
<?php get_footer(); ?>
