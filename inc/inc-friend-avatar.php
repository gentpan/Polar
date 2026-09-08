<?php
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
