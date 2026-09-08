<?php
if(!defined('ABSPATH'))exit;
function feng_hero_music_options(){
 $options=array(''=>'不显示');
 foreach(feng_music_sanitize_tracks(feng_setting('music_tracks',array())) as $track)$options[$track['url']]=$track['title'];
 return $options;
}
add_action('wp_enqueue_scripts',function(){
 wp_enqueue_style('feng-category-font',get_theme_file_uri('/assets/fonts/category/font.css'),array(),feng_asset_version('/assets/fonts/category/font.css'));
 wp_enqueue_style('feng-home-profile',get_theme_file_uri('/assets/css/home-profile.css'),array('feng-pages'),feng_asset_version('/assets/css/home-profile.css'));
 wp_enqueue_script('feng-home-profile',get_theme_file_uri('/assets/js/home-profile.js'),array('xf-app'),feng_asset_version('/assets/js/home-profile.js'),true);
});
