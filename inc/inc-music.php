<?php
/** Native playlist settings and a persistent HTML audio player. */
if (!defined('ABSPATH')) exit;
function feng_music_sanitize_tracks($rows) {
 if(is_string($rows)){
  $old=feng_setting('music_tracks',array());$old=is_array($old)?$old:array();$parsed=array();$invalid=false;
  foreach(preg_split('/\r?\n/',$rows) as $line){if(trim($line)==='')continue;$parts=array_map('trim',preg_split('/[|｜]/u',$line));
   if(count($parts)<3||count($parts)>5){$invalid=true;break;}$parts=array_pad($parts,5,'');
   foreach(array(2,3,4) as $i)if(($i===2||$parts[$i]!=='')&&(!filter_var($parts[$i],FILTER_VALIDATE_URL)||!in_array(strtolower(wp_parse_url($parts[$i],PHP_URL_SCHEME)??''),array('http','https'),true)))$invalid=true;
   if($parts[0]==='')$invalid=true;
   $row=array('title'=>$parts[0],'artist'=>$parts[1],'url'=>$parts[2],'lrc_url'=>$parts[3],'cover'=>$parts[4]);
   foreach($old as $previous)if(is_array($previous)&&($previous['url']??'')===$row['url']){$row['lrc']=$previous['lrc']??'';break;}$parsed[]=$row;
  }
  if($invalid||count($parsed)>30){if(function_exists('add_settings_error'))add_settings_error('feng_settings','music_rows','歌单未保存：每行填写「歌名 | 作者 | 音乐地址 | 歌词地址 | 封面地址」，地址须为 HTTP(S)，最多 30 首。原歌单已保留。','error');$rows=$old;}else $rows=$parsed;
 }
 $tracks=array();
 foreach(array_slice(is_array($rows)?$rows:array(),0,30) as $row) {
  if(!is_array($row)) continue;
  $row=array_map(static function($v){return is_scalar($v)?(string)$v:'';},$row);
  $url=esc_url_raw($row['url']??'',array('http','https'));
  if(!$url) continue;
  $tracks[]=array('title'=>mb_substr(sanitize_text_field($row['title']??''),0,120)?:'未命名歌曲','artist'=>mb_substr(sanitize_text_field($row['artist']??''),0,120),'url'=>$url,'lrc_url'=>esc_url_raw($row['lrc_url']??'',array('http','https')),'cover'=>esc_url_raw($row['cover']??'',array('http','https')),'lrc'=>mb_substr(sanitize_textarea_field($row['lrc']??''),0,30000));
 }
 return $tracks;
}
function feng_music_tracks_field($value) {
 $lines=array();foreach(feng_music_sanitize_tracks($value) as $track)$lines[]=implode(' | ',array_map(static function($v){return str_replace('|','%7C',$v);},array($track['title'],$track['artist'],$track['url'],$track['lrc_url'],$track['cover'])));
 echo '<div class="feng-music-links"><p class="description">每行一首，最多 30 首，按行顺序播放。歌名和音乐地址必填；作者、歌词和封面可留空，保留分隔符。</p><p><code>歌名 | 作者 | 音乐地址 | 歌词地址 | 封面地址</code></p><textarea id="feng-music_tracks" class="large-text code" name="feng_settings[music_tracks]" rows="10" spellcheck="false" placeholder="歌名 | 作者 | https://example.com/song.mp3 | https://example.com/song.lrc | https://example.com/cover.webp">'.esc_textarea(implode("\n",$lines)).'</textarea><p class="description">直接填写你自行上传后的文件直链，不是音乐网站的播放页面。歌词支持 UTF-8 LRC 或纯文本，外部歌词服务器需允许跨域读取（CORS）。地址里的竖线请写成 %7C。已有粘贴歌词会保留作备用。</p></div>';
}
add_action('wp_enqueue_scripts',function(){
 wp_enqueue_style('feng-music',get_theme_file_uri('/assets/css/music.css'),array('feng-cards'),feng_asset_version('/assets/css/music.css'));
 if(feng_setting('music_enabled',true)) wp_enqueue_script('feng-music',get_theme_file_uri('/assets/js/music.js'),array('xf-app'),feng_asset_version('/assets/js/music.js'),array('strategy'=>'defer','in_footer'=>true));
});
add_action('wp_footer',function(){
 if(feng_setting('music_enabled',true)) get_template_part('template-parts/music-player');
},5);
