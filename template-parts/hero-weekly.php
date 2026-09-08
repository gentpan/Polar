<?php
if(!defined('ABSPATH'))exit;
$start=$args['start']??(new DateTimeImmutable('today',wp_timezone()))->modify('-89 days');
$weekly=feng_weekly_blog_stats($start->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'));
?>
<div class="feng-weekly-badges" aria-label="最近 90 天统计">
 <?php foreach(array('posts'=>'文章','talks'=>'说说','comments'=>'评论') as $key=>$label): ?><span title="<?php echo esc_attr('最近 90 天 · '.$label); ?>"><?php echo esc_html($label); ?> <b><?php echo (int)$weekly[$key]; ?></b></span><?php endforeach; ?>
</div>
