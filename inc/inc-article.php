<?php
/** Article metadata. Counts Chinese characters and other words, excluding markup. */
if (!defined('ABSPATH')) exit;
function feng_article_stats($post) {
 $text=html_entity_decode(wp_strip_all_tags(strip_shortcodes($post->post_content)),ENT_QUOTES,get_bloginfo('charset'));
 $han=preg_match_all('/\p{Han}/u',$text);
 $other=preg_replace('/\p{Han}/u',' ',$text);
 $words=preg_match_all("/[\p{L}\p{N}]+(?:[’'’-][\p{L}\p{N}]+)*/u",$other);
 return array('count'=>$han+$words,'minutes'=>max(1,(int)ceil($han/350+$words/200)));
}
/** Basic visit heat: one request per article per browser every 30 minutes.
 * Full-page caches must bypass this hook or supply their own analytics counter.
 */
function feng_record_article_visit() {
 if (!is_singular('post') || is_preview() || is_feed() || ($_SERVER['REQUEST_METHOD']??'')!=='GET') return;
 $post=get_queried_object();
 if (!$post || $post->post_status!=='publish' || $post->post_password || current_user_can('edit_post',$post->ID)) return;
 $cookie='feng_read_'.$post->ID;
 if (isset($_COOKIE[$cookie])) return;
 $views=max(0,(int)get_post_meta($post->ID,'_feng_views',true));
 update_post_meta($post->ID,'_feng_views',$views+1);
 setcookie($cookie,'1',array('expires'=>time()+30*MINUTE_IN_SECONDS,'path'=>COOKIEPATH?:'/','secure'=>is_ssl(),'httponly'=>true,'samesite'=>'Lax'));
}
add_action('template_redirect','feng_record_article_visit');
