<?php
/** Template Name: Polar · 订阅 */
if(!defined('ABSPATH')) exit;
get_header(); while(have_posts()): the_post();
if(post_password_required()) { echo '<section class="feng-panel">'.get_the_password_form().'</section>'; continue; }
$data=feng_feed_data(); $list=feng_feed_list(); $cycle=$data['cycle'];
?>
<div class="feng-panel feng-special-page feng-subscriptions" data-feed-page>
<header class="feng-page-heading feng-feed-hero" data-xf-reveal>
<div class="feng-feed-hero-copy"><p class="xf-section-kicker">FROM OUR NEIGHBORS / SUBSCRIPTIONS</p><h1><?php echo feng_page_title_icon(); the_title(); ?></h1><p>看看朋友们，最近又记录了什么。</p></div>
<div class="feng-feed-overview"><span><?php echo feng_icon('rss'); ?> <strong><?php echo count($data['sources']); ?></strong> 个订阅</span><span><strong data-feed-today-number><?php echo (int)$data['today']; ?></strong> 篇今日更新</span><span class="feng-feed-last-sync"><?php echo feng_icon('clock'); ?> <span><?php echo !empty($cycle['finished'])?esc_html('上次同步 '.wp_date('Y-m-d H:i',$cycle['finished'])):'尚未同步'; ?></span></span></div>
</header>
<div class="feng-feed-toolbar"><div class="feng-feed-tabs" role="group" aria-label="更新范围"><button type="button" data-feed-period="all" aria-pressed="true">全部动态</button><button type="button" data-feed-period="today" aria-pressed="false">今日更新</button></div></div>
<p class="feng-feed-status" data-feed-status role="status"><?php echo $list['total']?'已收录 '.$list['total'].' 篇文章':'还没有同步到动态'; ?></p>
<div class="feng-feed-entries" data-feed-entries><?php echo $list['html']; ?></div>
<div class="feng-feed-empty" data-feed-empty <?php if($list['total']) echo 'hidden'; ?>><?php echo feng_icon('rss'); ?><h2><?php echo $data['sources']?'等待下一封远方来信':'把朋友的更新，放在这里'; ?></h2><p><?php echo $data['sources']?'订阅源同步后，新文章会出现在这里。':'添加友情链接的 RSS 地址后，就能在这里看到大家的文章。'; ?></p><?php if(current_user_can('manage_options')) { ?><a href="<?php echo esc_url(admin_url('themes.php?page=feng-settings#feng-subscriptions')); ?>">设置订阅 <?php echo feng_icon('arrow-up-right'); ?></a><?php } ?></div>
<button type="button" class="feng-feed-more" data-feed-more <?php if(!$list['more']) echo 'hidden'; ?>>再看一些 <?php echo feng_icon('arrow-down'); ?></button>
<footer class="feng-feed-footer"><span>按原文发布时间排序 · 点击标题前往朋友的博客</span></footer>
</div>
<?php endwhile; get_footer(); ?>
