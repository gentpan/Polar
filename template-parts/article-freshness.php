<?php
if(!defined('ABSPATH'))exit;
$age=feng_article_freshness(get_post());if(!$age)return;
?>
<aside class="feng-article-age xf-reading" aria-label="文章时效提醒"><strong>这篇文章已有 <?php echo esc_html($age['label']); ?>未更新</strong><p>最后更新于 <?php echo esc_html($age['updated']->format('Y年n月j日')); ?>。文中涉及的信息、操作步骤或观点可能已有变化，请结合最新情况参考。</p></aside>
