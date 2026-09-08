<?php
/** Public site dashboard: published content only. @package Polar */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function feng_dashboard_calendar( $month = '' ) {
 $now = current_datetime();
 $date = preg_match( '/^\d{4}-(0[1-9]|1[0-2])$/', $month ) ? DateTimeImmutable::createFromFormat( '!Y-m', $month, wp_timezone() ) : $now->modify( 'first day of this month' );
 if ( ! $date || (int) $date->format('Y') < 1970 || (int) $date->format('Y') > 2100 ) { $date = $now->modify('first day of this month'); }
 $posts = get_posts(array('post_type'=>'post','post_status'=>'publish','has_password'=>false,'numberposts'=>-1,'year'=>(int)$date->format('Y'),'monthnum'=>(int)$date->format('m'),'fields'=>'ids'));
 $days = array();
 foreach ( $posts as $id ) { $day = (int) get_the_date('j', $id); $days[$day] = isset($days[$day]) ? $days[$day] + 1 : 1; }
 $start = (int) get_option('start_of_week',1);
 $offset = ((int)$date->format('w') - $start + 7) % 7;
 $week = array('日','一','二','三','四','五','六');
 ?>
 <div class="feng-calendar-head"><button type="button" data-feng-month="<?php echo esc_attr($date->modify('-1 month')->format('Y-m')); ?>" aria-label="上个月"><?php echo feng_icon('pageprev'); ?></button><h3><?php echo esc_html($date->format('Y 年 n 月')); ?></h3><button type="button" data-feng-month="<?php echo esc_attr($date->modify('+1 month')->format('Y-m')); ?>" aria-label="下个月"><?php echo feng_icon('pagenext'); ?></button></div>
 <table class="feng-calendar"><caption class="screen-reader-text"><?php echo esc_html($date->format('Y年n月')); ?>文章日历</caption><thead><tr><?php for($i=0;$i<7;$i++) echo '<th scope="col">'.esc_html($week[($start+$i)%7]).'</th>'; ?></tr></thead><tbody><tr>
 <?php
 for($i=0;$i<$offset;$i++) echo '<td></td>';
 $length = (int)$date->format('t');
 for($day=1;$day<=$length;$day++) {
  $today = $date->format('Y-m') === $now->format('Y-m') && $day === (int)$now->format('j');
  echo '<td'.($today?' aria-current="date"':'').'>';
  if(isset($days[$day])) echo '<a href="'.esc_url(get_day_link((int)$date->format('Y'),(int)$date->format('m'),$day)).'" aria-label="'.esc_attr($day.'日，'.$days[$day].'篇文章').'">'.esc_html($day).'</a>';
  else echo '<span>'.esc_html($day).'</span>';
  echo '</td>';
  if(($offset+$day)%7===0 && $day<$length) echo '</tr><tr>';
 }
 for($i=($offset+$length)%7;$i>0 && $i<7;$i++) echo '<td></td>';
 ?></tr></tbody></table><p class="feng-dashboard-note">带圆点的日期有文章，点击即可阅读。</p>
 <?php
}

function feng_dashboard_posts( $posts, $empty ) {
 if(!$posts) { echo '<p class="feng-dashboard-empty">'.esc_html($empty).'</p>'; return; }
 echo '<ul class="feng-dashboard-posts">';
 foreach($posts as $post) echo '<li><a href="'.esc_url(get_permalink($post)).'"><time datetime="'.esc_attr(get_the_date('Y-m-d',$post)).'">'.esc_html(get_the_date('m-d',$post)).'</time><span>'.esc_html(get_the_title($post) ?: __('无标题','feng')).'</span></a></li>';
 echo '</ul>';
}

function feng_dashboard_data() {
 global $wpdb;
 $base = array('post_type'=>'post','post_status'=>'publish','has_password'=>false,'numberposts'=>5,'ignore_sticky_posts'=>true);
 $recent = get_posts($base);
 $sticky_ids = array_map('absint',(array)get_option('sticky_posts',array()));
 $sticky = $sticky_ids ? get_posts(array_merge($base,array('post__in'=>$sticky_ids))) : array();
 // Join explicitly to exclude private, draft, password-protected and custom content.
 $comment_ids = $wpdb->get_col("SELECT c.comment_ID FROM {$wpdb->comments} c INNER JOIN {$wpdb->posts} p ON p.ID=c.comment_post_ID WHERE c.comment_approved='1' AND c.comment_type IN ('comment','') AND p.post_status='publish' AND p.post_password='' AND p.post_type IN ('post','page') ORDER BY c.comment_date_gmt DESC LIMIT 100");
 $comment_ids=array_values(array_filter($comment_ids,function($id){$comment=get_comment($id);$user=$comment->user_id?get_userdata((int)$comment->user_id):get_user_by('email',$comment->comment_author_email);return !$user||!user_can($user,'manage_options');}));
 $comment_ids=array_slice($comment_ids,0,6);
 $tags = get_terms(array('taxonomy'=>'post_tag','hide_empty'=>true,'orderby'=>'count','order'=>'DESC','number'=>16));
 $stats = $wpdb->get_row("SELECT COUNT(*) AS posts, MIN(post_date) AS first_date FROM {$wpdb->posts} WHERE post_status='publish' AND post_type='post' AND post_password=''");
 $comment_total = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->comments} c INNER JOIN {$wpdb->posts} p ON p.ID=c.comment_post_ID WHERE c.comment_approved='1' AND c.comment_type IN ('comment','') AND p.post_status='publish' AND p.post_password='' AND p.post_type IN ('post','page')");
 $days = $stats->first_date ? max(1,(int)(current_datetime()->diff(new DateTimeImmutable($stats->first_date,wp_timezone()))->days)+1) : 0;
 $talks=get_posts(array('post_type'=>'feng_talk','post_status'=>'publish','has_password'=>false,'numberposts'=>3));
 $talk_total=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status='publish' AND post_type='feng_talk' AND post_password=''");
 $recommended=get_posts(array_merge($base,array('orderby'=>array('comment_count'=>'DESC','date'=>'DESC'))));
 $categories=get_terms(array('taxonomy'=>'category','hide_empty'=>false,'orderby'=>'name','order'=>'ASC'));
 if(is_wp_error($categories))$categories=array();
 $friends=get_bookmarks(array('hide_invisible'=>true,'limit'=>10,'orderby'=>'rand'));
 $totals=feng_footer_content_stats();
 $since=feng_setting('site_since','');$started=DateTimeImmutable::createFromFormat('!Y-m-d',$since,wp_timezone());
 $age=$started&&$started->format('Y-m-d')===$since&&$started<=current_datetime()?(int)$started->diff(current_datetime())->days:null;
 $summary=array('文章'=>(int)$stats->posts,'说说'=>$talk_total,'评论'=>$comment_total,'分类'=>count($categories),'浏览量'=>(int)$wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name='feng_total_pageviews'"),'总字数'=>(int)$totals['words'],'建站天数'=>$age);
 return compact('summary','friends','recent','sticky','recommended','talks','talk_total','categories','comment_ids','tags','stats','comment_total','days');
}

function feng_dashboard_ajax() {
 $part = isset($_GET['part']) && is_string($_GET['part']) ? sanitize_key($_GET['part']) : '';
 ob_start();
 if($part==='calendar') {
  $month = isset($_GET['month']) && is_string($_GET['month']) ? sanitize_text_field(wp_unslash($_GET['month'])) : '';
  feng_dashboard_calendar($month);
 } else {
  get_template_part('template-parts/dashboard',null,feng_dashboard_data());
 }
 wp_send_json_success(array('html'=>ob_get_clean()));
}
add_action('wp_ajax_feng_dashboard','feng_dashboard_ajax');
add_action('wp_ajax_nopriv_feng_dashboard','feng_dashboard_ajax');
