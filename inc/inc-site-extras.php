<?php
if(!defined('ABSPATH'))exit;
add_action('wp_head',function(){if(feng_setting('analytics_mode','off')==='custom')echo "\n".feng_setting('analytics_code','')."\n";},90);
function feng_blog_decade_data($now=null){
 $value=feng_setting('decade_start','');
 if(!$value){$first=get_posts(array('post_type'=>'post','post_status'=>'publish','has_password'=>false,'posts_per_page'=>1,'orderby'=>array('date'=>'ASC','ID'=>'ASC'),'ignore_sticky_posts'=>true));if(!$first)return null;$value=substr($first[0]->post_date,0,10);}
 $start=DateTimeImmutable::createFromFormat('!Y-m-d',$value,wp_timezone());if(!$start||$start->format('Y-m-d')!==$value)return null;
 $year=(int)$start->format('Y')+10;$month=(int)$start->format('m');$day=(int)$start->format('d');while(!checkdate($month,$day,$year))$day--;
 $end=$start->setDate($year,$month,$day);$now=$now?:current_datetime();$total=(int)$start->diff($end)->days;$elapsed=max(0,min($total,(int)$start->diff($now)->format('%r%a')));
 return array('start'=>$start,'end'=>$end,'days'=>$elapsed,'total'=>$total,'percent'=>round($elapsed/$total*100,1),'future'=>$now<$start);
}
function feng_blog_decade(){
 if(!feng_setting('decade_enabled',true))return;$d=feng_blog_decade_data();if(!$d)return;
 echo '<section class="feng-decade" aria-labelledby="feng-decade-title"><div class="feng-decade-heading"><div><p class="xf-section-kicker">TEN YEARS OF BLOGGING</p><h2 id="feng-decade-title">博客十年</h2><p>'.($d['future']?'计划尚未开始':($d['days']===$d['total']?'十年记录已完成，故事继续。':'已经记录 '.$d['days'].' 天，继续写下生活。')).'</p></div><strong>'.esc_html($d['percent']).'<small>%</small></strong></div><progress value="'.esc_attr($d['days']).'" max="'.esc_attr($d['total']).'" aria-label="博客十年进度">'.esc_html($d['percent']).'%</progress><div class="feng-decade-dates"><time datetime="'.$d['start']->format('Y-m-d').'">'.$d['start']->format('Y.m.d').'</time><span>十年之约</span><time datetime="'.$d['end']->format('Y-m-d').'">'.$d['end']->format('Y.m.d').'</time></div></section>';
}
function feng_upload_is_apng($file){
 $handle=fopen($file,'rb');if(!$handle)return true;fread($handle,8);
 while(!feof($handle)){$header=fread($handle,8);if(strlen($header)<8)break;$length=unpack('N',substr($header,0,4))[1];$type=substr($header,4,4);if($type==='acTL'){fclose($handle);return true;}if($type==='IDAT'||$type==='IEND')break;if(fseek($handle,$length+4,SEEK_CUR)!==0)break;}
 fclose($handle);return false;
}
function feng_upload_webp($upload){
 if(!feng_setting('images_webp',true)||!empty($upload['error'])||empty($upload['file']))return $upload;
 $file=$upload['file'];$mime=wp_get_image_mime($file);if(!in_array($mime,array('image/jpeg','image/png'),true))return $upload;
 $size=wp_getimagesize($file);if(!$size||$size[0]*$size[1]>40000000||($mime==='image/png'&&feng_upload_is_apng($file)))return $upload;
 $editor=wp_get_image_editor($file);if(is_wp_error($editor)||!$editor->supports_mime_type('image/webp'))return $upload;
 if(is_wp_error($editor->maybe_exif_rotate())||is_wp_error($editor->set_quality(82)))return $upload;
 $dir=dirname($file);$name=wp_unique_filename($dir,pathinfo($file,PATHINFO_FILENAME).'.webp');$result=$editor->save($dir.'/'.$name,'image/webp');if(is_wp_error($result))return $upload;
 $upload['file']=$result['path'];$upload['url']=trailingslashit(dirname($upload['url'])).rawurlencode(basename($result['path']));$upload['type']='image/webp';wp_delete_file($file);return $upload;
}
add_filter('wp_handle_upload','feng_upload_webp');add_filter('wp_handle_sideload','feng_upload_webp');
add_action('wp_enqueue_scripts',function(){wp_enqueue_style('feng-site-extras',get_theme_file_uri('/assets/css/site-extras.css'),array(),feng_asset_version('/assets/css/site-extras.css'));if(feng_setting('images_fade',true))wp_enqueue_script('feng-image-fade',get_theme_file_uri('/assets/js/image-fade.js'),array('xf-app'),feng_asset_version('/assets/js/image-fade.js'),true);});

/** Public article totals. Chinese characters count individually, Latin words as words. */
function feng_footer_content_stats(){
 $stats=get_transient('polar_footer_content_totals_v2');
 if(false!==$stats)return $stats;
 global $wpdb;
 $rows=$wpdb->get_results("SELECT post_content,post_date FROM {$wpdb->posts} WHERE post_type='post' AND post_status='publish' AND post_password='' ORDER BY post_date ASC");
 $stats=array('articles'=>count($rows),'words'=>0,'first'=>$rows?substr($rows[0]->post_date,0,10):'');
 foreach($rows as $row){
  $text=html_entity_decode(wp_strip_all_tags(strip_shortcodes($row->post_content)),ENT_QUOTES,'UTF-8');
  $stats['words']+=preg_match_all('/[\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}]/u',$text);
  $text=preg_replace('/[\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}]/u',' ',$text);
  $stats['words']+=preg_match_all('/[\p{L}\p{N}]+(?:[\x{2019}\x{0027}-][\p{L}\p{N}]+)*/u',$text);
 }
 set_transient('polar_footer_content_totals_v2',$stats,DAY_IN_SECONDS);
 return $stats;
}
add_action('save_post_post',function(){delete_transient('polar_footer_content_totals_v2');});
add_action('deleted_post',function(){delete_transient('polar_footer_content_totals_v2');});

