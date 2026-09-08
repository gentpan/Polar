<?php
if(!defined('ABSPATH'))exit;
$activity=feng_activity_query();
$weekly=feng_weekly_blog_stats();
$github=feng_weekly_github_stats();
?>
<section id="xf-journal" class="xf-stream xf-container feng-activity" data-xf-reveal data-feng-activity data-pages="<?php echo (int)$activity->max_num_pages; ?>" data-endpoint="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
 <header class="xf-stream__heading"><div><span class="xf-section-kicker">THIS WEEK</span><h2>最近一周动态</h2></div><div class="feng-activity-controls" role="group" aria-label="切换最近一周动态" hidden><button type="button" data-activity-step="-1" aria-label="上一页动态" title="上一页" disabled><?php echo feng_icon('pageprev'); ?></button><button type="button" data-activity-step="1" aria-label="下一页动态" title="下一页" <?php disabled($activity->max_num_pages<=1); ?>><?php echo feng_icon('pagenext'); ?></button></div></header>
 <p class="feng-weekly-period">近 7 天 · <?php echo esc_html(wp_date('n月j日',time()-7*DAY_IN_SECONDS).' — '.wp_date('n月j日')); ?></p>
 <dl class="feng-weekly-stats"><?php foreach(array('posts'=>'发布文章','talks'=>'发布说说','people'=>'来评论的朋友','comments'=>'收到评论','replies'=>'我的回复') as $key=>$label): ?><div><dt><?php echo esc_html($label); ?></dt><dd><?php echo (int)$weekly[$key]; ?><small><?php echo $key==='people'?'人':($key==='posts'?'篇':'条'); ?></small></dd></div><?php endforeach; ?></dl>
 <div class="feng-weekly-connected"><a href="https://github.com/gentpan" target="_blank" rel="noopener noreferrer"><strong>GitHub</strong> <?php if($github!==null): ?><?php echo $github['complete']?'':'至少 '; ?><?php echo (int)$github['pushes']; ?> 次推送 · <?php echo (int)$github['projects']; ?> 个项目<?php else: ?>动态暂时无法同步<?php endif; ?></a><a href="https://x.com/gentpan" target="_blank" rel="noopener noreferrer"><strong>X</strong> @gentpan</a><small>GitHub 仅统计公开项目<?php if($github!==null&&!$github['complete'])echo '，近期动态较多，当前统计不完整'; ?></small></div>
 <div class="feng-activity-list" data-activity-list><?php echo feng_activity_rows($activity); ?></div>
 <?php if(!$activity->posts): ?><p class="feng-empty">近 7 天还没有发布新的文章或说说。</p><?php endif; ?>
 <p class="screen-reader-text" data-activity-status role="status" aria-live="polite"></p>
</section>
