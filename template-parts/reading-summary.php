<?php
if(!defined('ABSPATH'))exit;
$summary=feng_reading_summary(get_post());
if(!$summary['text'])return;
?>
<details class="feng-reading-summary" open>
 <summary><?php echo feng_icon($summary['ai']?'sparkle':'words'); ?><span><?php echo $summary['ai']?'AI 辅助摘要':'内容提要'; ?></span><?php echo feng_icon('chevron-down'); ?></summary>
 <div><p><?php echo nl2br(esc_html($summary['text'])); ?></p><?php if($summary['ai']): ?><small>由 AI 辅助整理，作者确认后发布。请以正文为准。</small><?php endif; ?></div>
</details>
