<?php
if(!defined('ABSPATH'))exit;
add_action('wp_enqueue_scripts',function(){
 if(!feng_setting('jieqi_enabled',true))return;
 wp_enqueue_script('feng-jieqi',get_theme_file_uri('/assets/js/jieqi.js'),array(),feng_asset_version('/assets/js/jieqi.js'),true);
 wp_enqueue_style('feng-jieqi',get_theme_file_uri('/assets/css/jieqi.css'),array(),feng_asset_version('/assets/css/jieqi.css'));
});
function feng_jieqi_toggle(){
 if(!feng_setting('jieqi_enabled',true))return;
 echo '<button type="button" class="feng-jieqi-toggle" data-jieqi-toggle aria-pressed="true" title="关闭节气与节假日提醒">节气提醒<span data-jieqi-state>已开启</span></button>';
}
