<?php
if(!defined('ABSPATH'))exit;
function feng_visitor_stats_install(){
 if(get_option('feng_visitor_stats_version')==='1')return;
 global $wpdb;require_once ABSPATH.'wp-admin/includes/upgrade.php';$collate=$wpdb->get_charset_collate();
 dbDelta("CREATE TABLE {$wpdb->prefix}feng_visitors (
 visitor char(64) NOT NULL,
 seen bigint unsigned NOT NULL,
 event char(36) NOT NULL DEFAULT '',
 PRIMARY KEY (visitor),
 KEY seen (seen)
 ) ENGINE=InnoDB $collate;");
 add_option('feng_total_pageviews',0,'',false);
 update_option('feng_visitor_stats_version','1',false);
}
add_action('init','feng_visitor_stats_install');
function feng_visitor_stats_ping(){
 if(!feng_setting('footer_stats',true))wp_send_json_error(null,404);
 nocache_headers();
 $visitor=is_string($_POST['visitor']??null)?$_POST['visitor']:'';$event=is_string($_POST['event']??null)?$_POST['event']:'';
 if(!preg_match('/^[a-f0-9-]{36}$/D',$visitor)||($event!==''&&!preg_match('/^[a-f0-9-]{36}$/D',$event)))wp_send_json_error(null,400);
 $origin=$_SERVER['HTTP_ORIGIN']??'';if($origin&&wp_parse_url($origin,PHP_URL_HOST)!==wp_parse_url(home_url(),PHP_URL_HOST))wp_send_json_error(null,403);
 global $wpdb;$table=$wpdb->prefix.'feng_visitors';$now=time();$hash=hash_hmac('sha256',$visitor,wp_salt('auth'));
 $wpdb->query('START TRANSACTION');
 $ok=$wpdb->query($wpdb->prepare("INSERT IGNORE INTO $table (visitor,seen,event) VALUES (%s,%d,'')",$hash,$now));
 $old=$wpdb->get_var($wpdb->prepare("SELECT event FROM $table WHERE visitor=%s FOR UPDATE",$hash));
 if($event!==''&&$old!==$event){
  $ok=$ok!==false&&$wpdb->query("UPDATE {$wpdb->options} SET option_value=CAST(option_value AS UNSIGNED)+1 WHERE option_name='feng_total_pageviews'")!==false;
 }
 $ok=$ok!==false&&$wpdb->query($wpdb->prepare("UPDATE $table SET seen=%d,event=%s WHERE visitor=%s",$now,$event?:$old,$hash))!==false;
 $wpdb->query($ok?'COMMIT':'ROLLBACK');if(!$ok)wp_send_json_error(null,503);
 $wpdb->query($wpdb->prepare("DELETE FROM $table WHERE seen<%d",$now-DAY_IN_SECONDS));
 $online=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE seen>=%d",$now-300));
 $total=(int)$wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name='feng_total_pageviews'");
 $location=get_transient('polar_latest_visitor_location');
 if($event!==''&&$old!==$event){
  $ip=$_SERVER['REMOTE_ADDR']??'';
  $geo=feng_comment_geo_lookup($ip);
  if($geo){$location=$geo;set_transient('polar_latest_visitor_location',$geo,DAY_IN_SECONDS);}
 }
 wp_send_json_success(array('online'=>$online,'views'=>$total,'location'=>$location?:null));
}
add_action('wp_ajax_feng_visitor_stats','feng_visitor_stats_ping');add_action('wp_ajax_nopriv_feng_visitor_stats','feng_visitor_stats_ping');
add_action('wp_enqueue_scripts',function(){if(feng_setting('footer_stats',true))wp_enqueue_script('feng-visitor-stats',get_theme_file_uri('/assets/js/visitor-stats.js'),array('xf-app'),feng_asset_version('/assets/js/visitor-stats.js'),true);});
