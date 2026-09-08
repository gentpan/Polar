<?php
if(!defined('ABSPATH'))exit;
add_action('wp_enqueue_scripts',function(){
 wp_enqueue_style('feng-context',get_theme_file_uri('/assets/css/context-menu.css'),array('feng-pages'),feng_asset_version('/assets/css/context-menu.css'));
 wp_enqueue_script('feng-context',get_theme_file_uri('/assets/js/context-menu.js'),array('xf-app'),feng_asset_version('/assets/js/context-menu.js'),true);
});
add_action('wp_footer',function(){ ?>
<div class="feng-page-menu" data-page-menu role="menu" aria-label="页面快捷操作" hidden>
<div class="feng-page-menu-nav"><?php foreach(array('back'=>array('arrow-left','后退'),'forward'=>array('arrow-right','前进'),'reload'=>array('refresh','重新加载'),'top'=>array('arrow-up','回到顶部')) as $key=>$v): ?><button role="menuitem" type="button" data-page-tool="<?php echo esc_attr($key); ?>" aria-label="<?php echo esc_attr($v[1]); ?>"><?php echo feng_icon($v[0]); ?></button><?php endforeach; ?></div>
<?php foreach(array('link-tab'=>array('arrow-up-right','在新标签页打开'),'link-window'=>array('arrow-up-right','在新窗口打开'),'link-copy'=>array('link','复制链接地址'),'previous'=>array('previous','上一篇'),'next'=>array('next','下一篇'),'comment'=>array('comment','留言'),'random'=>array('dice','随机阅读一篇文章'),'copy'=>array('copy','复制页面地址'),'theme'=>array('moon','切换深浅配色')) as $key=>$v): ?><button role="menuitem" type="button" data-page-tool="<?php echo esc_attr($key); ?>"><?php echo feng_icon($v[0]); ?><span><?php echo esc_html($v[1]); ?></span></button><?php endforeach; ?>
</div><span class="screen-reader-text" data-page-menu-status role="status"></span>
<?php });
