<?php
if(!defined('ABSPATH'))exit;
add_filter('use_widgets_block_editor',function($use){return feng_setting('classic_widgets',true)?false:$use;});
add_filter('wp_revisions_to_keep',function($num){return feng_setting('disable_revisions',false)?0:$num;});
add_action('init',function(){if(!feng_setting('disable_emoji',false))return;remove_action('wp_head','print_emoji_detection_script',7);remove_action('wp_enqueue_scripts','wp_enqueue_emoji_styles');remove_action('wp_print_styles','print_emoji_styles');remove_action('admin_print_scripts','print_emoji_detection_script');remove_action('admin_print_styles','print_emoji_styles');remove_filter('the_content_feed','wp_staticize_emoji');remove_filter('comment_text_rss','wp_staticize_emoji');remove_filter('wp_mail','wp_staticize_emoji_for_email');add_filter('emoji_svg_url','__return_false');add_filter('tiny_mce_plugins',function($plugins){return array_diff($plugins,array('wpemoji'));});});
function feng_category_short_paths(){
 if(!feng_setting('remove_category_base',false)||!get_option('permalink_structure'))return array();
 $terms=get_terms(array('taxonomy'=>'category','hide_empty'=>false));if(is_wp_error($terms))return array();$paths=array();
 foreach($terms as $term){$path=trim(get_category_parents($term->term_id,false,'/',true),'/');if(!$path||get_page_by_path($path))continue;$paths[$term->term_id]=$path;}return $paths;
}
add_filter('category_link',function($url,$id){$paths=feng_category_short_paths();return isset($paths[$id])?home_url(user_trailingslashit($paths[$id],'category')):$url;},10,2);
add_filter('rewrite_rules_array',function($rules){$new=array();foreach(feng_category_short_paths() as $id=>$path){$pattern=preg_quote($path,'#');$new[$pattern.'/?$']='index.php?cat='.$id;$new[$pattern.'/page/([0-9]{1,})/?$']='index.php?cat='.$id.'&paged=$matches[1]';$new[$pattern.'/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$']='index.php?cat='.$id.'&feed=$matches[1]';}return $new+$rules;});
function feng_schedule_category_flush(){add_action('shutdown',function(){flush_rewrite_rules(false);});}
add_action('update_option_feng_settings',function($old,$new){if(!empty($old['remove_category_base'])!==!empty($new['remove_category_base']))feng_schedule_category_flush();},10,2);
foreach(array('created_category','edited_category','delete_category') as $hook)add_action($hook,'feng_schedule_category_flush');
add_action('template_redirect',function(){if(!is_category()||!feng_setting('remove_category_base',false))return;$url=get_category_link(get_queried_object_id());$path=wp_parse_url($url,PHP_URL_PATH);$requested=wp_parse_url(wp_unslash($_SERVER['REQUEST_URI']??''),PHP_URL_PATH);if(!get_query_var('paged')&&!is_feed()&&$requested&&untrailingslashit($path)!==untrailingslashit($requested)){wp_safe_redirect($url,301);exit;}});
function feng_database_cleanup_form(){
 if(!current_user_can('manage_options'))return;
 echo '<details class="feng-settings-section"><summary>数据库维护</summary><p>仅清理已过期的 WordPress 临时缓存，不删除文章、评论、友情链接或修订版本。</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';wp_nonce_field('feng_expired_cache');echo '<input type="hidden" name="action" value="feng_expired_cache">';submit_button('清理过期缓存','secondary');echo '</form></details>';
}
add_action('admin_post_feng_expired_cache',function(){if(!current_user_can('manage_options'))wp_die('没有权限');check_admin_referer('feng_expired_cache');delete_expired_transients(true);wp_safe_redirect(admin_url('themes.php?page=feng-settings&cache_cleaned=1'));exit;});
add_action('admin_notices',function(){if(current_user_can('manage_options')&&($_GET['page']??'')==='feng-settings'&&isset($_GET['cache_cleaned']))echo '<div class="notice notice-success"><p>过期缓存已清理。</p></div>';});
