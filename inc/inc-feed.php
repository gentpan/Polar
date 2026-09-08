<?php
/** friend-avatar */
/** Match approved commenters to friend domains; retain hashes, never copy email addresses. */
if(!defined('ABSPATH'))exit;
function feng_friend_domain($url){
 $url=trim((string)$url);if(!$url)return '';if(!preg_match('~^https?://~i',$url))$url='https://'.$url;
 $p=wp_parse_url($url);if(!$p||isset($p['user'])||isset($p['pass'])||empty($p['host']))return '';
 $host=strtolower(rtrim($p['host'],'.'));if(function_exists('idn_to_ascii'))$host=idn_to_ascii($host,0,INTL_IDNA_VARIANT_UTS46)?:$host;
 return preg_replace('/^www\./','',$host);
}
function feng_friend_avatar_rebuild(){
 global $wpdb;$old=get_option('feng_friend_avatars',array());$map=array();$domains=array();
 foreach(get_bookmarks(array('hide_invisible'=>false)) as $link){$domain=feng_friend_domain($link->link_url);if(!$domain)continue;$id=(int)$link->link_id;$prior=$old[$id]??array();$map[$id]=array('domain'=>$domain,'manual'=>($prior['domain']??'')===$domain?($prior['manual']??''):'','hash'=>'','comment'=>0);$domains[$domain][]=$id;}
 if($domains){
  $rows=$wpdb->get_results("SELECT comment_ID,comment_author_email,comment_author_url FROM {$wpdb->comments} WHERE comment_approved='1' AND comment_type IN ('','comment') AND comment_author_email<>'' AND comment_author_url<>'' ORDER BY comment_date_gmt DESC,comment_ID DESC");
  foreach($rows as $c){$domain=feng_friend_domain($c->comment_author_url);if(empty($domains[$domain])||!is_email($c->comment_author_email))continue;
   foreach($domains[$domain] as $id)if(!$map[$id]['hash']){$map[$id]['hash']=md5(strtolower(trim($c->comment_author_email)));$map[$id]['comment']=(int)$c->comment_ID;}
  }
 }
 update_option('feng_friend_avatars',$map,false);update_option('feng_friend_avatar_version','1',false);
}
add_action('init',function(){if(get_option('feng_friend_avatar_version')!=='1')feng_friend_avatar_rebuild();},45);
foreach(array('comment_post','transition_comment_status','edit_comment','deleted_comment','add_link','edit_link','deleted_link') as $hook)add_action($hook,'feng_friend_avatar_rebuild',50,0);
function feng_friend_avatar($friend,$size=48){
 if(is_numeric($friend))$friend=get_bookmark((int)$friend);if(!$friend)return '';
 $domain=feng_friend_domain($friend->link_url);$all=get_option('feng_friend_avatars',array());$v=$all[(int)$friend->link_id]??array();
 $hash=($v['domain']??'')===$domain?(($v['manual']??'')?:($v['hash']??'')):'';
 $fallback='https://favicon.la/'.rawurlencode($domain);
 $url=$hash?'https://gravatar.bluecdn.com/avatar/'.$hash.'?s='.($size*2).'&d=404':$fallback;
 return '<img class="feng-friend-avatar-img" src="'.esc_url($url).'" data-friend-fallback="'.esc_url($fallback).'" width="'.absint($size).'" height="'.absint($size).'" alt="" loading="lazy" decoding="async"><span class="feng-friend-avatar-initial" hidden aria-hidden="true">'.esc_html(mb_substr(wp_specialchars_decode($friend->link_name),0,1)).'</span>';
}
add_action('add_meta_boxes_link',function(){add_meta_box('feng-friend-avatar','邮箱头像','feng_friend_avatar_box','link','side');});
function feng_friend_avatar_box($link){
 wp_nonce_field('feng_friend_avatar','feng_friend_avatar_nonce');$all=get_option('feng_friend_avatars',array());$v=$all[(int)($link->link_id??0)]??array();
 echo '<p><label for="feng-friend-email">友链站长邮箱</label></p><input type="email" class="widefat" id="feng-friend-email" name="feng_friend_email" autocomplete="off" value=""><p class="description">填写后仅保存 Gravatar 哈希，不额外保存邮箱原文。留空保留设置；未手动指定时，自动匹配已审核评论的网站域名。</p>';
 if(!empty($v['manual']))echo '<p>已设置手动邮箱头像。</p><label><input type="checkbox" name="feng_friend_email_clear" value="1"> 清除手动设置，恢复自动匹配</label>';
 elseif(!empty($v['hash']))echo '<p>已从审核通过的评论匹配头像。</p>';
 else echo '<p>尚未匹配邮箱，将显示网站徽标。</p>';
}
function feng_friend_avatar_save($id){
 if(!current_user_can('manage_links')||empty($_POST['feng_friend_avatar_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['feng_friend_avatar_nonce'])),'feng_friend_avatar'))return;
 $all=get_option('feng_friend_avatars',array());if(!isset($all[$id]))return;
 if(!empty($_POST['feng_friend_email_clear']))$all[$id]['manual']='';
 $email=isset($_POST['feng_friend_email'])&&is_string($_POST['feng_friend_email'])?trim(wp_unslash($_POST['feng_friend_email'])):'';
 if(is_email($email))$all[$id]['manual']=md5(strtolower($email));update_option('feng_friend_avatars',$all,false);
}
add_action('add_link','feng_friend_avatar_save',60);add_action('edit_link','feng_friend_avatar_save',60);
add_action('wp_enqueue_scripts',function(){wp_enqueue_script('feng-friend-avatar',get_theme_file_uri('/assets/js/friend-avatar.js'),array(),feng_asset_version('/assets/js/friend-avatar.js'),true);});
?>
<?php
/** feed */
/** Friend subscriptions: bounded local snapshots, RSS/Atom ingestion and WP-Cron. */
if (!defined('ABSPATH')) exit;

function feng_feed_sources() {
 return array_values(array_filter(get_bookmarks(array('hide_invisible'=>true,'orderby'=>'name')), static function($link) { return trim((string)$link->link_rss) !== ''; }));
}
function feng_feed_hours() { return (int)feng_setting('feed_hours',4) === 6 ? 6 : 4; }
add_filter('cron_schedules', static function($schedules) {
 foreach(array(4,6) as $h) $schedules['feng_feed_'.$h]=array('interval'=>$h*HOUR_IN_SECONDS,'display'=>'每 '.$h.' 小时同步友链');
 return $schedules;
});
function feng_feed_schedule() {
 $name='feng_feed_'.feng_feed_hours(); $event=wp_get_scheduled_event('feng_feed_refresh');
 if (!$event || $event->schedule!==$name) {
  wp_clear_scheduled_hook('feng_feed_refresh');
  wp_schedule_event(time()+60,$name,'feng_feed_refresh');
 }
}
add_action('init','feng_feed_schedule',40);
add_action('update_option_feng_settings','feng_feed_schedule');
add_action('switch_theme',static function() { foreach(array('feng_feed_refresh','feng_feed_step','feng_feed_changed') as $hook) wp_clear_scheduled_hook($hook); });
function feng_feed_link_changed($id) {
 $link=get_bookmark($id); $saved=get_option('feng_feed_source_'.absint($id),array());
 if (!$link || $link->link_visible!=='Y' || ($saved['hash']??'')!==hash('sha256',trim((string)$link->link_rss))) delete_option('feng_feed_source_'.absint($id));
 if (!wp_next_scheduled('feng_feed_changed')) wp_schedule_single_event(time()+10,'feng_feed_changed');
}
add_action('add_link','feng_feed_link_changed');
add_action('edit_link','feng_feed_link_changed');
add_action('deleted_link',static function($id) { delete_option('feng_feed_source_'.absint($id)); });

/** Do not mistake Atom updated time for original publication time. */
function feng_feed_published($item) {
 foreach(array(array('http://www.w3.org/2005/Atom','published'),array('http://purl.org/atom/ns#','issued'),array('','pubDate'),array('http://purl.org/rss/1.0/','pubDate'),array('http://purl.org/dc/elements/1.1/','date')) as $tag) {
  $values=$item->get_item_tags($tag[0],$tag[1]);
  $raw=trim((string)($values[0]['data']??''));
  // A calendar date is required; relative words such as "today" are not dates.
  if ($raw && preg_match('/\b\d{4}\b/',$raw)) {
   try { return max(0,(new DateTimeImmutable($raw,new DateTimeZone('UTC')))->getTimestamp()); } catch(Exception $e) { return 0; }
  }
 }
 return 0;
}
function feng_feed_text($text,$limit) { return mb_substr(trim(preg_replace('/\s+/u',' ',wp_strip_all_tags(html_entity_decode((string)$text,ENT_QUOTES|ENT_HTML5,'UTF-8')))),0,$limit); }
function feng_feed_parse($xml) {
 if(is_string($xml)&&preg_match('/^\s*(?:<!doctype\s+html|<html\b)/i',$xml))return new WP_Error('feed_html','订阅地址返回了普通网页，可能是站点暂停、地址失效或访问拦截。');
 if (!$xml || strlen($xml)>1048576 || preg_match('/<!\s*(DOCTYPE|ENTITY)/i',$xml) || strpos($xml,"\0")!==false) return new WP_Error('feed_xml','订阅内容为空、过大或包含不支持的 XML 声明。');
 require_once ABSPATH.WPINC.'/class-simplepie.php';
 $feed=new SimplePie\SimplePie();
 $feed->enable_cache(false); $feed->set_raw_data($xml); $feed->set_autodiscovery_level(0);
 if (!$feed->init() || $feed->error() || !$feed->get_type()) return new WP_Error('feed_parse','无法解析 RSS / Atom，请检查 RSS 地址。');
 $entries=array(); $urls=array();
 $raw_items=$feed->get_items();
 usort($raw_items,static function($a,$b) { return feng_feed_published($b)<=>feng_feed_published($a); });
 foreach($raw_items as $item) {
  $url=esc_url_raw((string)$item->get_permalink(),array('http','https'));
  $parts=wp_parse_url($url);
  if (!$url || empty($parts['host']) || !in_array($parts['scheme']??'',array('http','https'),true) || isset($parts['user']) || isset($parts['pass'])) continue;
  $id=hash('sha256',(string)($item->get_id()?:$url));
  if (isset($entries[$id]) || isset($urls[$url])) continue;
  $urls[$url]=true;
  $entries[$id]=array('id'=>$id,'url'=>$url,'title'=>feng_feed_text($item->get_title(),200)?:'无标题文章','summary'=>feng_feed_text($item->get_description(),150),'published'=>feng_feed_published($item));
 }
 usort($entries,static function($a,$b) { return $b['published']<=>$a['published']; });
 return array_slice($entries,0,200);
}
/** Validate each redirect ourselves as well as through the WP safe HTTP transport. */
function feng_feed_safe_url($url) {
 $override=apply_filters('feng_feed_url_validation',null,$url);if(is_bool($override))return $override;
 $parts=wp_parse_url($url);
 if (!$parts || !in_array($parts['scheme']??'',array('http','https'),true) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass']) || !wp_http_validate_url($url)) return false;
 $host=strtolower(trim($parts['host'],'[].'));
 if ($host==='localhost' || str_ends_with($host,'.local')) return false;
 $addresses=filter_var($host,FILTER_VALIDATE_IP)?array($host):gethostbynamel($host);
 if (!$addresses) return false;
 $flags=defined('FILTER_FLAG_GLOBAL_RANGE')?FILTER_FLAG_GLOBAL_RANGE:FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE;
 foreach($addresses as $ip) if (!filter_var($ip,FILTER_VALIDATE_IP,$flags)) return false;
 return true;
}
function feng_feed_fetch($source) {
 $url=trim((string)$source->link_rss); $deadline=microtime(true)+12;
 for($hop=0;$hop<4;$hop++) {
  if (!feng_feed_safe_url($url)) return new WP_Error('feed_url','RSS 地址必须是公开的 HTTP / HTTPS 地址，不能指向本机或私有网络。');
  if (microtime(true)>=$deadline) return new WP_Error('feed_timeout','订阅源响应超时，请稍后重试。');
  $response=wp_safe_remote_get($url,array('feng_feed_request'=>true,'timeout'=>max(1,$deadline-microtime(true)),'redirection'=>0,'limit_response_size'=>1048577,'headers'=>array('Accept'=>'application/rss+xml, application/atom+xml, application/xml, text/xml')));
  if (is_wp_error($response)) {
   $reason=$response->get_error_message();
   if(preg_match('/timed? out|timeout|cURL error 28/i',$reason))return new WP_Error('feed_timeout','订阅源响应超时，请稍后重试。');
   if(preg_match('/SSL|certificate|cURL error 60/i',$reason))return new WP_Error('feed_tls','订阅源 HTTPS 证书验证失败。');
   if(preg_match('/resolve host|DNS/i',$reason))return new WP_Error('feed_dns','订阅源域名暂时无法解析。');
   return new WP_Error('feed_network','暂时无法连接订阅源，请检查地址或稍后重试。');
  }
  $code=wp_remote_retrieve_response_code($response);
  if (in_array($code,array(301,302,303,307,308),true)) {
   $location=wp_remote_retrieve_header($response,'location');
   if (!is_string($location) || !$location) break;
   $url=WP_Http::make_absolute_url($location,$url); continue;
  }
  if ($code!==200) return new WP_Error('feed_http','订阅源返回 HTTP '.absint($code).'。');
  return feng_feed_parse(wp_remote_retrieve_body($response));
 }
 return new WP_Error('feed_redirect','订阅源重定向次数过多或缺少目标地址。');
}
function feng_feed_store($source,$result) {
 $key='feng_feed_source_'.(int)$source->link_id; $hash=hash('sha256',trim($source->link_rss));
 $old=get_option($key,array()); if (($old['hash']??'')!==$hash) $old=array();
 $snapshot=$old; $snapshot['hash']=$hash; $snapshot['attempt']=time();
 if (is_wp_error($result)) {
  $snapshot['error']=$result->get_error_message(); update_option($key,$snapshot,false); return 0;
 }
 $merged=array(); $urls=array();
 foreach($old['items']??array() as $entry) { $merged[$entry['id']]=$entry; $urls[$entry['url']]=$entry['id']; }
 $added=0;
 foreach($result as $entry) {
  $prior=$merged[$entry['id']]??(isset($urls[$entry['url']])?$merged[$urls[$entry['url']]]:null);
  if (!$prior) $added++;
  // An edited or temporarily undated entry keeps its original publication date.
  if ($prior && $prior['published']) $entry['published']=$prior['published'];
  if ($prior) unset($merged[$prior['id']]);
  $merged[$entry['id']]=$entry; $urls[$entry['url']]=$entry['id'];
 }
 usort($merged,static function($a,$b) { return $b['published']<=>$a['published']; });
 $snapshot['items']=array_slice($merged,0,200); $snapshot['success']=time(); $snapshot['error']='';
 update_option($key,$snapshot,false); return $added;
}

/** One source per step bounds worker duration. MySQL advisory lock releases on disconnect. */
function feng_feed_run($start=false) {
 global $wpdb;
 $lock='feng_feed_'.md5(DB_NAME.$wpdb->prefix);
 if ((int)$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s,0)',$lock))!==1) return get_option('feng_feed_cycle',array());
 try {
  $cycle=get_option('feng_feed_cycle',array()); $sources=feng_feed_sources(); $by_id=array();
  foreach($sources as $source) $by_id[(int)$source->link_id]=$source;
  if ($start && empty($cycle['pending'])) $cycle=array('started'=>time(),'pending'=>array_keys($by_id),'total'=>count($sources),'done'=>0,'added'=>0,'errors'=>0);
  if (!empty($cycle['pending'])) {
   $id=array_shift($cycle['pending']);
   if (isset($by_id[$id])) {
    $result=feng_feed_fetch($by_id[$id]);
    // A link may have been edited/deleted while its request was in flight.
    $current=get_bookmark($id);
    if ($current && $current->link_visible==='Y' && $current->link_rss===$by_id[$id]->link_rss) {
     $cycle['added']+=feng_feed_store($current,$result);
     if(is_wp_error($result)) $cycle['errors']++;
    }
   }
   $cycle['done']++;
  }
  if (empty($cycle['pending'])) {
   if (!empty($cycle['started']) && empty($cycle['finished'])) $cycle['finished']=time();
   wp_clear_scheduled_hook('feng_feed_step');
  } elseif (!wp_next_scheduled('feng_feed_step')) wp_schedule_single_event(time()+15,'feng_feed_step');
  update_option('feng_feed_cycle',$cycle,false);
  return $cycle;
 } finally { $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)',$lock)); }
}
add_action('feng_feed_refresh',static function(){ feng_feed_run(true); });
add_action('feng_feed_changed',static function(){ feng_feed_run(true); });
add_action('feng_feed_step',static function(){ feng_feed_run(false); });

/** Cache reads only; the midnight boundary follows Settings > General > Timezone. */
function feng_feed_data($now=null) {
 $now=$now??time(); $start=(new DateTimeImmutable('@'.$now))->setTimezone(wp_timezone())->setTime(0,0);
 $items=array(); $sources=array(); $seen=array(); $today=0;
 foreach(feng_feed_sources() as $source) {
  $snapshot=get_option('feng_feed_source_'.(int)$source->link_id,array());
  if (($snapshot['hash']??'')!==hash('sha256',trim($source->link_rss))) $snapshot=array();
  $sources[]=array('id'=>(int)$source->link_id,'name'=>$source->link_name,'success'=>$snapshot['success']??0,'error'=>$snapshot['error']??'');
  foreach($snapshot['items']??array() as $entry) {
   if ($entry['published']>$now || isset($seen[$entry['url']])) continue;
   $seen[$entry['url']]=true; $entry['source']=(int)$source->link_id; $entry['source_name']=$source->link_name;
   $entry['today']=$entry['published']>=$start->getTimestamp() && $entry['published']>0;
   if ($entry['today']) $today++;
   $items[]=$entry;
  }
 }
 usort($items,static function($a,$b) { return $b['published']<=>$a['published'] ?: strcmp($a['id'],$b['id']); });
 return array('items'=>$items,'sources'=>$sources,'today'=>$today,'day'=>$start->format('Y-m-d'),'midnight'=>$start->modify('+1 day')->getTimestamp(),'cycle'=>get_option('feng_feed_cycle',array()));
}
function feng_feed_badge() {
 $data=feng_feed_data();
 return '<span class="t-badge feng-feed-badge" data-open="'.($data['today']?'true':'false').'" data-feed-count="'.$data['today'].'"><span class="t-badge-dot" aria-hidden="true">'.esc_html($data['today']>99?'99+':$data['today']).'</span><span class="screen-reader-text">'.esc_html('，今日更新 '.$data['today'].' 篇').'</span></span>';
}
add_filter('nav_menu_item_title',static function($title,$item) {
 $url=feng_page_url('subscriptions');
 if ($url && untrailingslashit($item->url)===untrailingslashit($url)) $title.=feng_feed_badge();
 return $title;
},10,2);
add_action('wp_ajax_feng_feed_count','feng_feed_count_ajax');
add_action('wp_ajax_nopriv_feng_feed_count','feng_feed_count_ajax');
function feng_feed_count_ajax() { $data=feng_feed_data(); wp_send_json_success(array('today'=>$data['today'],'day'=>$data['day'],'midnight'=>$data['midnight'])); }
function feng_feed_ensure_page() {
 if (get_option('feng_feed_page_ready')) return;
 $existing=get_posts(array('post_type'=>'page','post_status'=>array('publish','draft','private','pending','trash'),'meta_key'=>'_wp_page_template','meta_value'=>'pages/subscriptions.php','numberposts'=>1));
 if ($existing || get_page_by_path('subscriptions')) { update_option('feng_feed_page_ready',1,false); return; }
 $id=wp_insert_post(array('post_type'=>'page','post_status'=>'publish','post_title'=>'订阅','post_name'=>'subscriptions','comment_status'=>'closed','page_template'=>'pages/subscriptions.php'),true);
 if (!is_wp_error($id) && $id) update_option('feng_feed_page_ready',1,false);
}
add_action('init','feng_feed_ensure_page',35);
?>
<?php
/** feed-ui */
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
