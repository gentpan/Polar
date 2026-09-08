<?php
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
