<?php
/** Subscription page and native WordPress settings UI. */
if (!defined('ABSPATH')) exit;
function feng_feed_rows($items) {
 ob_start();
 foreach($items as $entry) { ?>
 <article class="feng-feed-entry"><span class="feng-feed-entry__mark" aria-hidden="true"><?php echo feng_friend_avatar($entry['source'],40); ?></span><div class="feng-feed-entry__body"><div class="feng-feed-entry__meta"><span><?php echo esc_html($entry['source_name']); ?></span><span>·</span><?php if($entry['published']) { ?><time datetime="<?php echo esc_attr(gmdate('c',$entry['published'])); ?>"><?php echo esc_html(wp_date(wp_date('Y',$entry['published'])===wp_date('Y')?'m 月 d 日 H:i':'Y 年 m 月 d 日',$entry['published'])); ?></time><?php } else { ?><span>发布时间未提供</span><?php } ?><?php if($entry['today']) { ?><span class="feng-feed-today">今日</span><?php } ?></div><h2><a href="<?php echo esc_url($entry['url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($entry['title']); ?></a></h2><?php if($entry['summary']) { ?><p><?php echo esc_html($entry['summary']); ?></p><?php } ?></div></article>
 <?php }
 return ob_get_clean();
}
function feng_feed_list($source=0,$today=false,$page=1) {
 $data=feng_feed_data();
 $items=array_values(array_filter($data['items'],static function($entry) use($source,$today) { return (!$source || $entry['source']===$source) && (!$today || $entry['today']); }));
 $total=count($items); $page=max(1,$page);
 return array('html'=>feng_feed_rows(array_slice($items,($page-1)*30,30)),'total'=>$total,'more'=>$page*30<$total,'page'=>$page);
}
function feng_feed_list_ajax() {
 $source=isset($_GET['source']) && is_scalar($_GET['source'])?absint($_GET['source']):0;
 $page=isset($_GET['page']) && is_scalar($_GET['page'])?min(10000,max(1,absint($_GET['page']))):1;
 wp_send_json_success(feng_feed_list($source,isset($_GET['today']) && $_GET['today']==='1',$page));
}
add_action('wp_ajax_feng_feed_list','feng_feed_list_ajax');
add_action('wp_ajax_nopriv_feng_feed_list','feng_feed_list_ajax');
function feng_feed_assets() {
 wp_enqueue_style('feng-feed',get_theme_file_uri('/assets/css/feed.css'),array('feng-navigation'),feng_asset_version('/assets/css/feed.css'));
 wp_enqueue_script('feng-feed',get_theme_file_uri('/assets/js/feed.js'),array('xf-app'),feng_asset_version('/assets/js/feed.js'),array('strategy'=>'defer','in_footer'=>true));
}
add_action('wp_enqueue_scripts','feng_feed_assets');
function feng_feed_admin_assets($hook) {
 if ($hook!=='appearance_page_feng-settings') return;
 wp_enqueue_script('feng-feed-admin',get_theme_file_uri('/assets/js/feed-admin.js'),array(),feng_asset_version('/assets/js/feed-admin.js'),true);
}
add_action('admin_enqueue_scripts','feng_feed_admin_assets');
function feng_feed_settings_status() {
 $data=feng_feed_data(); $cycle=$data['cycle']; $next=wp_next_scheduled('feng_feed_refresh'); ?>
 <p>在<a href="<?php echo esc_url(admin_url('link-manager.php')); ?>">友情链接</a>中编辑站点，展开「高级」，填写原生「RSS 地址」。支持 RSS / Atom，仅同步公开可见的友链。</p>
 <p>菜单角标按 WordPress 站点时区（<?php echo esc_html(wp_timezone_string()); ?>）统计今日零点后发布、已经同步的文章。首次导入的旧文章不计入今日更新。</p>
 <p>已添加 <?php echo count($data['sources']); ?> 个订阅源 · 今日 <?php echo (int)$data['today']; ?> 篇 · 下次计划：<?php echo $next?esc_html(wp_date('m-d H:i',$next)):'尚未安排'; ?></p>
 <p><button type="button" class="button button-secondary" data-feed-refresh data-endpoint="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('feng_feed_refresh')); ?>">立即同步订阅</button> <span data-feed-admin-status role="status"><?php echo !empty($cycle['pending'])?'正在同步，剩余 '.count($cycle['pending']).' 个订阅源。':(!empty($cycle['finished'])?esc_html('上次同步：'.wp_date('m-d H:i',$cycle['finished']).' · 新收录 '.$cycle['added'].' 篇 · 失败 '.$cycle['errors'].' 个源'):'等待首次同步。'); ?></span></p>
 <p class="description">每个友链保留最近 200 篇已获取记录，不下载全文或附件。源站只提供最近几篇时，首次同步也只能获取这些文章。同步失败保留旧记录。定时任务由 WordPress WP-Cron 执行，低访问量站点可能延后；需要准时执行时，可由服务器定时调用 wp-cron.php。</p>
 <?php if(defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) echo '<p class="description">当前环境关闭了 WP-Cron 自动触发。请确认服务器已配置定时调用；手动同步仍可正常使用。</p>'; ?>
 <?php foreach($data['sources'] as $source) if($source['error']) echo '<p class="description">'.esc_html($source['name'].'：'.$source['error']).'</p>'; ?>
 <p><a href="<?php echo esc_url(feng_page_url('subscriptions')); ?>">查看订阅页面</a> · <a href="<?php echo esc_url(admin_url('nav-menus.php')); ?>">把「订阅」添加到自定义菜单</a></p>
 <?php
}
function feng_feed_admin_refresh() {
 if ($_SERVER['REQUEST_METHOD']!=='POST' || !current_user_can('manage_options')) wp_send_json_error(array('message'=>'没有同步权限。'),403);
 check_ajax_referer('feng_feed_refresh','nonce');
 $start=isset($_POST['start']) && $_POST['start']==='1';
 if ($start && get_transient('feng_feed_manual_cooldown')) wp_send_json_error(array('message'=>'刚刚同步过，请一分钟后再试。'),429);
 if ($start) set_transient('feng_feed_manual_cooldown',1,60);
 $cycle=feng_feed_run($start);
 wp_send_json_success(array('pending'=>count($cycle['pending']??array()),'done'=>$cycle['done']??0,'total'=>$cycle['total']??0,'added'=>$cycle['added']??0,'errors'=>$cycle['errors']??0));
}
add_action('wp_ajax_feng_feed_refresh','feng_feed_admin_refresh');
