<?php
/** Native WordPress settings and editor tools for the AI module. */
if(!defined('ABSPATH')) exit;
add_action('admin_init',function(){
 add_option('feng_ai_settings',array(),'',false);
 register_setting('feng_ai_group','feng_ai_settings',array('type'=>'array','sanitize_callback'=>'feng_ai_sanitize_settings','default'=>array(),'show_in_rest'=>false));
});
function feng_settings_tabs($ai=false) {
 $icons=array('analytics'=>'chart-area','appearance'=>'admin-appearance','home'=>'admin-home','music'=>'format-audio','subscriptions'=>'rss','pet'=>'pets','profile'=>'admin-users','footer'=>'admin-links');
 echo '<nav class="nav-tab-wrapper feng-admin-tabs" aria-label="设置分区"><span class="feng-nav-label">配置与管理</span>';
 foreach(feng_settings_schema() as $id=>$group) {
  $url=($ai?admin_url('themes.php?page=feng-settings'):'').'#feng-'.$id;
  echo '<a class="nav-tab" href="'.esc_url($url).'"><span class="dashicons dashicons-'.esc_attr($icons[$id]??'admin-generic').'" aria-hidden="true"></span>'.esc_html($group['title']).'</a>';
 }
 echo '<a class="nav-tab'.($ai===true?' nav-tab-active':'').'" '.($ai===true?'aria-current="page"':'').' href="'.esc_url(admin_url('themes.php?page=feng-settings&tab=ai')).'"><span class="dashicons dashicons-superhero-alt" aria-hidden="true"></span>AI 助手</a>';
 echo '<a class="nav-tab'.($ai==='map'?' nav-tab-active':'').'" '.($ai==='map'?'aria-current="page"':'').' href="'.esc_url(admin_url('themes.php?page=feng-settings&tab=map')).'"><span class="dashicons dashicons-location-alt" aria-hidden="true"></span>地图服务</a>';
 echo '<a class="nav-tab'.($ai==='mail'?' nav-tab-active':'').'" '.($ai==='mail'?'aria-current="page"':'').' href="'.esc_url(admin_url('themes.php?page=feng-settings&tab=mail')).'"><span class="dashicons dashicons-email-alt" aria-hidden="true"></span>邮件发送</a></nav>';
}
function feng_ai_settings_screen() {
 ?>
 <div class="wrap feng-admin"><?php feng_settings_header(); settings_errors(); ?><div class="feng-admin-shell"><?php feng_settings_tabs(true); ?><div class="feng-admin-content">
 <header class="feng-section-header feng-ai-heading"><h2>AI 助手</h2><p>分别配置文字与封面服务。支持自定义模型和接口地址。</p></header>
 <form class="feng-settings-form" method="post" action="options.php" id="feng-ai-settings-form"><?php settings_fields('feng_ai_group'); ?>
 <?php foreach(array('text'=>'文字生成','image'=>'封面生成') as $kind=>$heading): $config=feng_ai_config($kind); ?>
 <section class="feng-ai-config feng-settings-section" data-kind="<?php echo esc_attr($kind); ?>"><header class="feng-section-header"><h2><?php echo esc_html($heading); ?></h2><p><?php echo $kind==='text'?'用于文章摘要、关键词提取和宠物问答。':'用于生成文章封面，可选择不同于文字服务的供应商。'; ?></p></header><table class="form-table" role="presentation"><colgroup><col class="feng-label-column"><col></colgroup>
 <tr><th><label for="feng-ai-<?php echo esc_attr($kind); ?>-provider">服务预设</label></th><td><select id="feng-ai-<?php echo esc_attr($kind); ?>-provider" name="feng_ai_settings[<?php echo esc_attr($kind); ?>][provider]" data-ai-field="provider">
 <?php foreach(feng_ai_presets()[$kind] as $id=>$preset) { ?><option value="<?php echo esc_attr($id); ?>" <?php selected($config['provider'],$id); ?>><?php echo esc_html($preset['label']); ?></option><?php } ?></select><p class="description">选择预设会填写模型、地址和协议；仍可手动修改。切换服务或地址后需重新填写密钥。</p></td></tr>
 <tr><th><label for="feng-ai-<?php echo esc_attr($kind); ?>-protocol">接口协议</label></th><td><select id="feng-ai-<?php echo esc_attr($kind); ?>-protocol" name="feng_ai_settings[<?php echo esc_attr($kind); ?>][protocol]" data-ai-field="protocol">
 <?php foreach(($kind==='text'?array('chat'=>'OpenAI 兼容 · Chat Completions','responses'=>'OpenAI · Responses'):array('images'=>'OpenAI 兼容 · Images','dashscope'=>'百炼 · 同步图像生成')) as $id=>$label) { ?><option value="<?php echo esc_attr($id); ?>" <?php selected($config['protocol'],$id); ?>><?php echo esc_html($label); ?></option><?php } ?></select></td></tr>
 <?php foreach(array('endpoint'=>'Endpoint / API 地址','model'=>'模型名称','key'=>'API Key') as $field=>$label): $id='feng-ai-'.$kind.'-'.$field; ?>
 <tr><th><label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($label); ?></label></th><td><input class="regular-text" id="<?php echo esc_attr($id); ?>" name="feng_ai_settings[<?php echo esc_attr($kind); ?>][<?php echo esc_attr($field); ?>]" type="<?php echo $field==='key'?'password':($field==='endpoint'?'url':'text'); ?>" data-ai-field="<?php echo esc_attr($field); ?>" value="<?php echo $field==='key'?'':esc_attr($config[$field]); ?>" <?php if($field==='key') { ?>autocomplete="new-password" placeholder="<?php echo $config['key']!==''?'已保存；留空保持不变':'填写服务商 API Key'; ?>"<?php } elseif($field==='model') { ?>list="feng-ai-<?php echo esc_attr($kind); ?>-models"<?php } ?>>
 <?php if($field==='key') { ?><p class="description"><?php echo $config['key']!==''?'密钥已保存，不在页面回显。':'尚未配置密钥。'; ?></p><label><input type="checkbox" name="feng_ai_settings[<?php echo esc_attr($kind); ?>][clear_key]" value="1">删除已保存的密钥</label><?php }
 elseif($field==='endpoint') { ?><p class="description">支持 HTTPS 基础地址或完整接口地址。千问预设为北京地域；也可填写自己的百炼业务空间域名或其他地域地址。</p><?php }
 else { ?><datalist id="feng-ai-<?php echo esc_attr($kind); ?>-models"><?php foreach(feng_ai_presets()[$kind] as $preset) foreach($preset['models'] as $model) echo '<option value="'.esc_attr($model).'"></option>'; ?></datalist><p class="description">可直接输入模型 ID，是否可用取决于当前账号及地域。</p><?php } ?></td></tr>
 <?php endforeach; ?></table>
 <p><button type="button" class="button" data-ai-test="<?php echo esc_attr($kind); ?>"><?php echo $kind==='text'?'测试文字连接':'测试封面生成'; ?></button> <span class="spinner"></span></p>
 <p class="description"><?php echo $kind==='text'?'测试已保存的配置，发送一条简短请求。':'测试已保存的配置，实际生成 1 张图片并保存到媒体库，会产生服务商用量。'; ?></p><div class="feng-ai-status" role="status" aria-live="polite"></div>
 </section><?php endforeach; feng_settings_save_bar(true); ?></form>
 <p>保存后，在「文章 → 编辑文章」的 <strong>Polar AI 助手</strong> 中使用。点击生成时，标题和正文会发送给你配置的服务商；文章不会自动发布。</p>
 <p class="description">模型预设核对于 2026-09-06。官方文档：<a href="https://api-docs.deepseek.com/" target="_blank" rel="noopener noreferrer">DeepSeek</a> · <a href="https://developers.openai.com/api/docs/models" target="_blank" rel="noopener noreferrer">OpenAI</a> · <a href="https://help.aliyun.com/zh/model-studio/getting-started/models" target="_blank" rel="noopener noreferrer">千问</a>。DeepSeek 用于文字生成，封面请另外配置图片服务。</p></div></div></div>
 <?php
}
add_action('add_meta_boxes_post',function($post){
 if(current_user_can('edit_post',$post->ID)) add_meta_box('feng-ai-editor','Polar AI 助手','feng_ai_editor_screen','post','side','default',array('__block_editor_compatible_meta_box'=>true));
});
function feng_ai_editor_screen($post) {
 ?>
 <div id="feng-ai-editor-tools" data-post-id="<?php echo (int)$post->ID; ?>">
 <?php wp_nonce_field('feng_summary','feng_summary_nonce'); ?>
 <input type="hidden" id="feng-ai-summary-saved" name="feng_ai_summary_saved" value="<?php echo esc_attr(get_post_meta($post->ID,'_feng_ai_summary',true)); ?>">
 <p>根据当前标题和正文生成。摘要和关键词预览后应用；封面生成后自动设为特色图片。<?php if(current_user_can('manage_options')) { ?><a href="<?php echo esc_url(admin_url('themes.php?page=feng-settings&tab=ai')); ?>" target="_blank" rel="noopener">配置 AI 服务 <span class="dashicons dashicons-external" aria-hidden="true"></span></a><?php } ?></p>
 <div class="feng-ai-actions"><button type="button" class="button" data-ai-generate="summary">一键生成摘要</button> <button type="button" class="button" data-ai-generate="keywords">一键提取关键词</button></div>
 <p class="description">文章摘要会显示在正文开头。AI 生成并应用的摘要会标注来源；手写摘要显示为「内容提要」。</p>
 <div class="feng-ai-result" data-ai-result="summary" hidden><p><label for="feng-ai-summary"><strong>摘要预览</strong></label></p><textarea id="feng-ai-summary" class="widefat" rows="4"></textarea><p><button type="button" class="button button-primary" data-ai-apply="summary">应用到文章摘要</button></p></div>
 <div class="feng-ai-result" data-ai-result="keywords" hidden><p><label for="feng-ai-keywords"><strong>关键词预览</strong></label></p><input id="feng-ai-keywords" class="widefat" type="text"><p class="description">用逗号分隔，可编辑后添加为标签，已有标签保留。</p><p><button type="button" class="button button-primary" data-ai-apply="keywords">添加到文章标签</button></p></div>
 <?php if(current_user_can('upload_files')) { ?><hr><p><label for="feng-ai-style"><strong>封面风格</strong></label> <select id="feng-ai-style"><?php foreach(feng_ai_styles() as $id=>$style) echo '<option value="'.esc_attr($id).'">'.esc_html($style[0]).'</option>'; ?></select></p>
 <p><label for="feng-ai-prompt">补充画面要求（可选）</label></p><textarea id="feng-ai-prompt" class="widefat" rows="2" placeholder="例如：清晨的山谷，偏绿色，留出标题空间"></textarea>
 <p><button type="button" class="button" data-ai-generate="cover">一键生成封面</button></p><p class="description">生成 1 张横版封面，自动压缩为 WebP（最长边 1920px，质量 82），保存到媒体库并设为特色图片。</p>
 <div class="feng-ai-result" data-ai-result="cover" hidden><img alt="生成的封面预览"><p><a data-ai-media-link target="_blank" rel="noopener">在媒体库查看</a></p></div><?php } ?>
 <div class="feng-ai-status" role="status" aria-live="polite"></div><span class="spinner"></span>
 </div>
 <?php
}
add_action('admin_enqueue_scripts',function($hook){
 $settings=$hook==='appearance_page_feng-settings'; $screen=get_current_screen();
 $editor=in_array($hook,array('post.php','post-new.php'),true) && $screen && $screen->post_type==='post';
 if(!$settings && !$editor) return;
 wp_enqueue_style('feng-admin',get_theme_file_uri('/assets/css/admin.css'),array('dashicons'),feng_asset_version('/assets/css/admin.css'));
 $deps=array();
 if($editor) { wp_enqueue_media(); $deps=array('wp-api-fetch','wp-data','wp-core-data'); }
 wp_enqueue_script('feng-ai-admin',get_theme_file_uri('/assets/js/ai-admin.js'),$deps,feng_asset_version('/assets/js/ai-admin.js'),true);
 wp_localize_script('feng-ai-admin','fengAI',array('url'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('feng_ai'),'presets'=>feng_ai_presets()));
});

// Respect existing layout for other boxes, while keeping these tools in the sidebar.
add_filter('get_user_option_meta-box-order_post',function($order){
 if(!is_array($order))return $order;
 $ids=array('feng-ai-editor','feng-travel');
 foreach($order as $context=>$list)$order[$context]=implode(',',array_diff(explode(',',(string)$list),$ids));
 $order['side']=implode(',',array_filter(array_merge(explode(',',$order['side']??''),$ids)));
 return $order;
});
