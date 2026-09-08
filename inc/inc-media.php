<?php
/** Keep card media inert until its viewport intersection, with a no-JS fallback. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
function feng_deferred_image( $image_html ) {
 if ( ! $image_html ) { return ''; }
 // Input comes from attachment rendering or explicitly escaped theme image markup.
 return '<span class="xf-deferred-media" data-xf-lazy-image><template>' . $image_html . '</template><noscript>' . $image_html . '</noscript></span>';
}

/** Route WordPress Gravatar requests through the site's avatar service. */
function feng_avatar_source( $url ) {
 if ( ! is_string( $url ) ) { return $url; }
 // Preserve the hash, size, default and rating; leave uploaded/plugin avatars alone.
 return preg_replace(
  '~^(?:https?:)?//(?:[a-z0-9-]+\.)?gravatar\.com(?=/avatar/)~i',
  'https://gravatar.bluecdn.com',
  $url
 );
}
add_filter( 'get_avatar_url', 'feng_avatar_source', 20 );

/** One deterministic fallback URL per article, shared by cards and the cover. */
function feng_post_image_url( $post = null, $size = 'large' ) {
 $post = get_post( $post );
 if ( ! $post || post_password_required( $post ) ) { return ''; }
 $image = get_the_post_thumbnail_url( $post, $size );
 if ( $image ) { return $image; }
 return 'post' === $post->post_type ? 'https://img.et/1920/1080?type=landscape&s=' . absint( $post->ID ) : '';
}

function feng_post_image( $post = null, $size = 'large', $attributes = array() ) {
 $post = get_post( $post );
 if ( ! $post || post_password_required( $post ) ) { return ''; }
 if ( has_post_thumbnail( $post ) ) { return get_the_post_thumbnail( $post, $size, $attributes ); }
 $url = feng_post_image_url( $post, $size );
 if ( ! $url ) { return ''; }
 $attributes = array_merge( array( 'alt' => '', 'width' => 1920, 'height' => 1080, 'decoding' => 'async' ), $attributes );
 $html = '<img src="' . esc_url( $url ) . '"';
 foreach ( $attributes as $key => $value ) {
  if ( ! preg_match( '/^(?:alt|width|height|class|loading|decoding|fetchpriority|sizes|data-[a-z0-9-]+)$/', $key ) ) { continue; }
  $html .= ' ' . $key . '="' . esc_attr( $value ) . '"';
 }
 return $html . '>';
}

/** Cache colors by image source so replacing the featured image invalidates them. */
function feng_card_color_key( $post_id ) {
 $attachment = get_post_thumbnail_id($post_id);
 return 'feng_tone_' . md5(feng_post_image_url($post_id,'large').'|'.($attachment ? get_post_modified_time('U',true,$attachment) : 'seed'));
}
function feng_card_dominant_color( $bytes ) {
 if(!function_exists('imagecreatefromstring')) return '';
 $size=@getimagesizefromstring($bytes);
 if(!$size || $size[0]*$size[1]>20000000) return '';
 $source=@imagecreatefromstring($bytes);
 if(!$source) return '';
 $sample=imagecreatetruecolor(32,32);
 imagecopyresampled($sample,$source,0,0,0,0,32,32,imagesx($source),imagesy($source));
 $buckets=array();
 for($y=0;$y<32;$y++) for($x=0;$x<32;$x++) {
  $pixel=imagecolorat($sample,$x,$y); $r=($pixel>>16)&255; $g=($pixel>>8)&255; $b=$pixel&255;
  $max=max($r,$g,$b); $min=min($r,$g,$b);
  if($max<16 || $min>238) continue;
  $saturation=$max ? ($max-$min)/$max : 0;
  $weight=1+$saturation*.6;
  $key=(($r>>5)<<6)|(($g>>5)<<3)|($b>>5);
  if(!isset($buckets[$key])) $buckets[$key]=array(0,0,0,0);
  $buckets[$key][0]+=$weight; $buckets[$key][1]+=$r*$weight; $buckets[$key][2]+=$g*$weight; $buckets[$key][3]+=$b*$weight;
 }
 imagedestroy($sample); imagedestroy($source);
 if(!$buckets) return '#1a2428';
 usort($buckets,static function($a,$b){return $b[0]<=>$a[0];});
 $dominant=$buckets[0];
 // Keep hue, cap brightness: white text remains legible even on a pale source.
 $rgb=array_map(static function($channel)use($dominant){return $channel/$dominant[0];},array_slice($dominant,1));
 $factor=min(.42,72/max(1,max($rgb)));
 return sprintf('#%02x%02x%02x',max(12,(int)round($rgb[0]*$factor)),max(12,(int)round($rgb[1]*$factor)),max(12,(int)round($rgb[2]*$factor)));
}
function feng_card_color_ajax() {
 $id=isset($_GET['post_id']) && is_scalar($_GET['post_id']) ? absint($_GET['post_id']) : 0;
 $post=get_post($id);
 if(!$post || $post->post_status!=='publish' || $post->post_type!=='post' || $post->post_password!=='') wp_send_json_error(null,404);
 $key=feng_card_color_key($id); $color=get_transient($key);
 if(!$color) {
  $bytes=''; $attachment=get_post_thumbnail_id($id);
  if($attachment) {
   $file=get_attached_file($attachment);
   if($file && is_file($file) && filesize($file)<=5000000) $bytes=file_get_contents($file);
  } else {
   // No user-supplied fetch URL: only the fixed, seeded image service is allowed.
   $response=wp_remote_get('https://img.et/1920/1080?type=landscape&s='.$id,array('timeout'=>7,'redirection'=>0,'limit_response_size'=>3000000));
   if(!is_wp_error($response) && wp_remote_retrieve_response_code($response)===200) $bytes=wp_remote_retrieve_body($response);
  }
  $color=$bytes ? feng_card_dominant_color($bytes) : '';
  set_transient($key,$color ?: '#1a2428',$color ? 12*HOUR_IN_SECONDS : 5*MINUTE_IN_SECONDS);
 }
 wp_send_json_success(array('color'=>$color ?: '#1a2428'));
}
add_action('wp_ajax_feng_card_color','feng_card_color_ajax');
add_action('wp_ajax_nopriv_feng_card_color','feng_card_color_ajax');

/** Keep article media URLs inert until they approach the viewport. */
add_filter('the_content',function($html){
 if(is_admin()||is_feed()||!is_singular())return $html;
 $html=preg_replace_callback('/<source\b[^>]*>/i',function($m){$t=new WP_HTML_Tag_Processor($m[0]);$t->next_tag();$value=$t->get_attribute('srcset');if($value){$t->set_attribute('data-article-srcset',$value);$t->remove_attribute('srcset');}return $t->get_updated_html();},$html);
 return preg_replace_callback('/<img\b[^>]*>/i',function($match){
  $tag=new WP_HTML_Tag_Processor($match[0]);if(!$tag->next_tag('IMG'))return $match[0];
  $src=$tag->get_attribute('src');if(!$src||str_starts_with($src,'data:'))return $match[0];
  foreach(array('src','srcset','sizes') as $attr){$value=$tag->get_attribute($attr);if($value){$tag->set_attribute('data-article-'.$attr,$value);$tag->remove_attribute($attr);}}
  $tag->set_attribute('data-lz-src',$src);
  $tag->set_attribute('data-article-lazy','');$tag->set_attribute('decoding','async');$tag->remove_attribute('fetchpriority');
  return $tag->get_updated_html().'<noscript>'.$match[0].'</noscript>';
 },$html);
},99);
add_action('wp_enqueue_scripts',function(){
 wp_enqueue_script('feng-litezoom','https://litezoom.dev/litezoom.min.js',array(),null,array('strategy'=>'defer','in_footer'=>true));
 wp_enqueue_script('feng-highlight',get_theme_file_uri('/assets/vendor/highlight/highlight.min.js'),array(), '11.11.1',true);
 wp_enqueue_script('feng-article-media',get_theme_file_uri('/assets/js/article-media.js'),array('xf-app','feng-litezoom','feng-highlight'),feng_asset_version('/assets/js/article-media.js'),true);
});
