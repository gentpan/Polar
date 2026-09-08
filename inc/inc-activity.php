<?php
if(!defined('ABSPATH'))exit;
function feng_activity_query($page=1){
 return new WP_Query(array('post_type'=>array('post','feng_talk'),'post_status'=>'publish','has_password'=>false,'posts_per_page'=>max(1,min(30,(int)feng_setting('home_posts_per_page',3))),'paged'=>max(1,(int)$page),'orderby'=>array('date'=>'DESC','ID'=>'DESC'),'ignore_sticky_posts'=>true,'date_query'=>array(array('column'=>'post_date_gmt','after'=>gmdate('Y-m-d H:i:s',time()-7*DAY_IN_SECONDS),'inclusive'=>true))));
}
function feng_activity_rows($query){
 ob_start();foreach($query->posts as $entry)get_template_part('template-parts/activity-item',null,array('post'=>$entry));return ob_get_clean();
}
add_action('wp_enqueue_scripts',function(){
 wp_enqueue_style('feng-activity',get_theme_file_uri('/assets/css/activity.css'),array('feng-pages'),feng_asset_version('/assets/css/activity.css'));
 wp_enqueue_script('feng-activity',get_theme_file_uri('/assets/js/activity.js'),array('xf-app'),feng_asset_version('/assets/js/activity.js'),true);
});
function feng_activity_ajax(){
 $page=isset($_GET['page'])&&is_scalar($_GET['page'])?max(1,absint($_GET['page'])):1;
 if($page>10000)wp_send_json_error(array('message'=>'页码超出范围。'),400);
 $query=feng_activity_query($page);
 wp_send_json_success(array('html'=>feng_activity_rows($query),'page'=>$page,'pages'=>(int)$query->max_num_pages));
}
add_action('wp_ajax_feng_activity','feng_activity_ajax');
add_action('wp_ajax_nopriv_feng_activity','feng_activity_ajax');

/** Public activity over a rolling seven-day window; pending comments stay private. */
function feng_weekly_blog_stats($after=null){
 $after=$after?:gmdate('Y-m-d H:i:s',time()-7*DAY_IN_SECONDS);
 $stats=array('posts'=>0,'talks'=>0,'comments'=>0,'people'=>0,'replies'=>0);
 foreach(array('post'=>'posts','feng_talk'=>'talks') as $type=>$key){
  $query=new WP_Query(array('post_type'=>$type,'post_status'=>'publish','has_password'=>false,'posts_per_page'=>1,'fields'=>'ids','date_query'=>array(array('column'=>'post_date_gmt','after'=>$after,'inclusive'=>true))));
  $stats[$key]=(int)$query->found_posts;
 }
 $people=array();$owners=array();
 foreach(get_comments(array('status'=>'approve','type'=>'comment','number'=>0,'post_status'=>'publish','post_password'=>'','date_query'=>array(array('column'=>'comment_date_gmt','after'=>$after,'inclusive'=>true)))) as $comment){
  $email=strtolower(trim($comment->comment_author_email));
  $identity=$comment->user_id?'user:'.$comment->user_id:'email:'.$email;
  if(!array_key_exists($identity,$owners)){
   $user=$comment->user_id?get_userdata((int)$comment->user_id):($email?get_user_by('email',$email):false);
   $owners[$identity]=$user&&user_can($user,'manage_options');
  }
  if($owners[$identity]){if((int)$comment->comment_parent>0)$stats['replies']++;continue;}
  $stats['comments']++;
  $people[$email?:$identity.':'.$comment->comment_author]=true;
 }
 $stats['people']=count($people);
 return $stats;
}

/** Cache public events, then apply the rolling window at render time. */
function feng_weekly_github_stats(){
 $cache=get_transient('polar_github_gentpan_events_v1');
 if(false===$cache){
  $cache=array('events'=>array(),'complete'=>false,'error'=>false);
  for($page=1;$page<=3;$page++){
   $response=wp_remote_get('https://api.github.com/users/gentpan/events/public?per_page=100&page='.$page,array('timeout'=>8,'headers'=>array('Accept'=>'application/vnd.github+json','User-Agent'=>'Polar-xifeng.net')));
   if(is_wp_error($response)||200!==wp_remote_retrieve_response_code($response)){$cache['error']=true;break;}
   $events=json_decode(wp_remote_retrieve_body($response),true);
   if(!is_array($events)){$cache['error']=true;break;}
   $cache['events']=array_merge($cache['events'],$events);
   $last=end($events);
   if(count($events)<100||($last&&strtotime($last['created_at'])<time()-7*DAY_IN_SECONDS)){$cache['complete']=true;break;}
  }
  set_transient('polar_github_gentpan_events_v1',$cache,$cache['error']?5*MINUTE_IN_SECONDS:15*MINUTE_IN_SECONDS);
 }
 if($cache['error'])return null;
 $pushes=0;$repos=array();$seen=array();$daily=array();
 for($i=7;$i>=0;$i--)$daily[wp_date('Y-m-d',time()-$i*DAY_IN_SECONDS)]=0;
 foreach($cache['events'] as $event){
  if(strtotime($event['created_at'])<time()-7*DAY_IN_SECONDS||$event['type']!=='PushEvent'||isset($seen[$event['id']]))continue;
  $day=wp_date('Y-m-d',strtotime($event['created_at']));if(isset($daily[$day]))$daily[$day]++;
  $seen[$event['id']]=true;$pushes++;$repos[$event['repo']['name']]=true;
 }
 return array('daily'=>$daily,'pushes'=>$pushes,'projects'=>count($repos),'complete'=>$cache['complete']);
}
