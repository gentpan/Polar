<?php
/** Progressive reading tools and author-approved article summaries. */
if (!defined('ABSPATH')) exit;
add_action('init',static function() {
 add_post_type_support('post','custom-fields');
 register_post_meta('post','_feng_ai_summary',array('type'=>'string','single'=>true,'default'=>'','show_in_rest'=>true,'sanitize_callback'=>'feng_sanitize_reading_summary','auth_callback'=>static function($allowed,$key,$post_id){return current_user_can('edit_post',$post_id);}));
 foreach(array('feng-note'=>'说明','feng-tip'=>'小提示','feng-warning'=>'注意事项') as $name=>$label) {
  foreach(array('core/group','core/paragraph') as $block) register_block_style($block,array('name'=>$name,'label'=>$label));
 }
 register_block_pattern_category('feng-reading',array('label'=>'Polar · 阅读组件'));
 register_block_pattern('feng/reading-note',array('title'=>'阅读提示卡','categories'=>array('feng-reading'),'content'=>'<!-- wp:group {"className":"is-style-feng-note","layout":{"type":"constrained"}} --><div class="wp-block-group is-style-feng-note"><!-- wp:paragraph --><p><strong>写在前面</strong></p><!-- /wp:paragraph --><!-- wp:paragraph --><p>在这里补充背景、适用范围或阅读建议。</p><!-- /wp:paragraph --></div><!-- /wp:group -->'));
 register_block_pattern('feng/reading-questions',array('title'=>'折叠问答','categories'=>array('feng-reading'),'content'=>'<!-- wp:details --><details class="wp-block-details"><summary>这里写一个读者可能关心的问题</summary><!-- wp:paragraph --><p>在这里给出解释，读者点击问题就能展开。</p><!-- /wp:paragraph --></details><!-- /wp:details -->'));
});
function feng_sanitize_reading_summary($text) { return is_scalar($text)?mb_substr(sanitize_textarea_field((string)$text),0,1800):''; }
add_filter('rest_prepare_post',static function($response,$post) {
 // Provenance is editor data; never expose it through public REST, including
 // on password-protected posts where core redacts only the normal excerpt.
 if(!current_user_can('edit_post',$post->ID)) {
  $data=$response->get_data();unset($data['meta']['_feng_ai_summary']);$response->set_data($data);
 }
 return $response;
},10,2);
function feng_reading_summary($post) {
 $post=get_post($post);
 if(!$post || $post->post_type!=='post' || post_password_required($post)) return array('text'=>'','ai'=>false);
 $text=trim(wp_strip_all_tags($post->post_excerpt));
 $ai=trim((string)get_post_meta($post->ID,'_feng_ai_summary',true));
 return array('text'=>$text,'ai'=>$text!=='' && $ai!=='' && $text===$ai);
}
add_action('save_post_post',static function($id) {
 if(wp_is_post_revision($id) || wp_is_post_autosave($id) || !current_user_can('edit_post',$id))return;
 if(!isset($_POST['feng_summary_nonce']) || !is_string($_POST['feng_summary_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['feng_summary_nonce'])),'feng_summary'))return;
 if(isset($_POST['feng_ai_summary_saved']) && is_string($_POST['feng_ai_summary_saved'])) update_post_meta($id,'_feng_ai_summary',feng_sanitize_reading_summary(wp_unslash($_POST['feng_ai_summary_saved'])));
});
add_action('wp_enqueue_scripts',static function() {
 // Global dependencies keep PJAX transitions into articles compatible.
 wp_enqueue_style('feng-reading',get_theme_file_uri('/assets/css/reading.css'),array('feng-article'),feng_asset_version('/assets/css/reading.css'));
 wp_enqueue_script('feng-reading',get_theme_file_uri('/assets/js/reading.js'),array('xf-app'),feng_asset_version('/assets/js/reading.js'),array('strategy'=>'defer','in_footer'=>true));
});

function feng_reading_related($post_id,$mode='category') {
 $query=array('post_type'=>'post','post_status'=>'publish','has_password'=>false,'post__not_in'=>array($post_id),'posts_per_page'=>30,'no_found_rows'=>true,'ignore_sticky_posts'=>true,'orderby'=>array('date'=>'DESC','ID'=>'DESC'));
 if($mode==='related'){$tags=wp_get_post_tags($post_id,array('fields'=>'ids'));if(!$tags)return new WP_Query(array('post__in'=>array(0)));$query['tag__in']=$tags;}
 elseif($mode==='category'){$cats=wp_get_post_categories($post_id);if(!$cats)return new WP_Query(array('post__in'=>array(0)));$query['category__in']=$cats;$query['orderby']='rand';}
 else $query['orderby']='rand';
 return new WP_Query($query);
}

add_action('wp_enqueue_scripts',function(){wp_enqueue_script('feng-related',get_theme_file_uri('/assets/js/related.js'),array('xf-app'),feng_asset_version('/assets/js/related.js'),true);});

function feng_article_freshness($post,$now=null){
 $post=get_post($post);$threshold=(int)feng_setting('article_stale_days','365');
 if(!$post||!$threshold||post_password_required($post))return null;
 $published=get_post_datetime($post,'date');$modified=get_post_datetime($post,'modified');
 if(!$published)return null;$updated=$modified&&$modified>$published?$modified:$published;
 $now=$now?:current_datetime();$days=max(0,(int)$updated->diff($now)->format('%r%a'));
 if($days<$threshold)return null;
 $age=$updated->diff($now);$label=$age->y?$age->y.'年'.($age->m?'零'.$age->m.'个月':''):($age->m?$age->m.'个月':$days.'天');
 return array('days'=>$days,'label'=>$label,'updated'=>$updated);
}

// Legacy editor examples may contain literal HTML inside pre/code. Keep those
// examples as text before wpautop and the browser can interpret resource tags.
function feng_reading_escape_code_examples($content){
 return preg_replace_callback('~(<pre\b[^>]*>\s*<code\b[^>]*>)(.*?)(</code>\s*</pre>)~is',static function($match){
  if(!preg_match('~<(?:script|link|style|iframe|object|embed)\b~i',$match[2]))return $match[0];
  return $match[1].esc_html($match[2]).$match[3];
 },$content);
}
add_filter('the_content','feng_reading_escape_code_examples',8);
