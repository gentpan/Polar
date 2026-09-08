<?php
if(!defined('ABSPATH'))exit;
add_action('wp_enqueue_scripts',function(){
 wp_enqueue_script('feng-greeting',get_theme_file_uri('/assets/js/greeting.js'),array('xf-app'),feng_asset_version('/assets/js/greeting.js'),true);
});
function feng_greeting_identity(){
 nocache_headers();
 $user=wp_get_current_user();$commenter=wp_get_current_commenter();
 $name=$user->exists()?$user->display_name:($commenter['comment_author']??'');
 wp_send_json_success(array('name'=>mb_substr(sanitize_text_field($name),0,30)));
}
add_action('wp_ajax_feng_greeting_identity','feng_greeting_identity');
add_action('wp_ajax_nopriv_feng_greeting_identity','feng_greeting_identity');
