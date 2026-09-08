<?php
/** Category badges live in native term metadata, independent of theme options. */
if(!defined('ABSPATH'))exit;
function feng_badge_svg($xml){
 if(strlen($xml)>524288 || preg_match('/<!DOCTYPE|<!ENTITY/i',$xml))return false;
 $previous=libxml_use_internal_errors(true);$doc=new DOMDocument();$ok=$doc->loadXML($xml,LIBXML_NONET);libxml_clear_errors();libxml_use_internal_errors($previous);
 if(!$ok||!$doc->documentElement||$doc->documentElement->localName!=='svg')return false;
 $elements=array('svg','g','path','rect','circle','ellipse','line','polyline','polygon','defs','linearGradient','radialGradient','stop','clipPath','mask','title','desc','use');
 $attributes=explode(' ','xmlns xmlns:xlink viewBox width height x y x1 x2 y1 y2 cx cy r rx ry d points fill fill-rule fill-opacity stroke stroke-width stroke-linecap stroke-linejoin stroke-miterlimit stroke-dasharray stroke-dashoffset stroke-opacity opacity transform id offset stop-color stop-opacity gradientUnits gradientTransform spreadMethod fx fy fr clip-path clip-rule mask maskUnits maskContentUnits preserveAspectRatio href xlink:href');
 foreach($doc->getElementsByTagName('*') as $node){
  if(!in_array($node->localName,$elements,true))return false;
  foreach($node->attributes as $attr){
   if(!in_array($attr->nodeName,$attributes,true))return false;
   $v=$attr->nodeValue;
   if(in_array($attr->nodeName,array('href','xlink:href'),true)&&!preg_match('/^#[a-zA-Z_][\w:.-]*$/D',$v))return false;
   if(preg_match('/javascript:|data:|https?:|\/\//i',$v)&&!in_array($attr->nodeName,array('xmlns','xmlns:xlink'),true))return false;
   if(stripos($v,'url(')!==false&&!preg_match('/^url\(#[a-zA-Z_][\w:.-]*\)$/D',$v))return false;
  }
 }
 return $doc->saveXML($doc->documentElement);
}
add_filter('upload_mimes',function($mimes){if(current_user_can('manage_categories')&&current_user_can('upload_files'))$mimes['svg']='image/svg+xml';return $mimes;});
add_filter('wp_handle_upload_prefilter',function($file){
 if(strtolower(pathinfo($file['name'],PATHINFO_EXTENSION))!=='svg')return $file;
 if(!current_user_can('manage_categories')||!current_user_can('upload_files')){$file['error']='没有上传 SVG 徽章的权限。';return $file;}
 $svg=feng_badge_svg(file_get_contents($file['tmp_name']));
 if($svg===false)$file['error']='SVG 包含不支持的元素、样式或外部引用。请导出为纯路径 SVG，或使用 PNG / WebP。';
 elseif(file_put_contents($file['tmp_name'],$svg)===false)$file['error']='SVG 保存失败，请重试。';
 return $file;
});
add_filter('wp_check_filetype_and_ext',function($data,$file,$name){
 if(strtolower(pathinfo($name,PATHINFO_EXTENSION))==='svg'&&current_user_can('manage_categories')&&is_readable($file)&&feng_badge_svg(file_get_contents($file))!==false)return array('ext'=>'svg','type'=>'image/svg+xml','proper_filename'=>false);
 return $data;
},10,3);
function feng_badge_icon_code($value){
 if(!is_string($value)||strlen($value)>20000)return '';
 $value=trim($value);if($value==='')return '';
 if(str_starts_with($value,'<svg'))return feng_badge_svg($value)?:'';
 if(str_contains($value,'<')){if(!preg_match('/^<i\s+[^>]*class\s*=\s*([\"\'])(.*?)\1[^>]*>\s*<\/i>$/is',$value,$match))return '';$value=$match[2];}
 $classes=preg_split('/\s+/',$value);if(count($classes)>12)return '';
 foreach($classes as $class)if(!preg_match('/^(?:fa[srlbtdk]?|fa-[a-z0-9-]+)$/D',$class))return '';
 return implode(' ',array_unique($classes));
}
function feng_badge_icon_markup($code){
 $code=feng_badge_icon_code($code);if(!$code)return '';
 if(str_starts_with($code,'<svg'))return '<span class="feng-category-badge feng-category-badge--svg" aria-hidden="true">'.$code.'</span>';
 return '<i class="feng-category-badge '.esc_attr($code).'" aria-hidden="true"></i>';
}
function feng_category_fontawesome(){
 wp_enqueue_style('feng-fontawesome-pro','https://static.bluecdn.com/libs/fontawesome-pro-plus/7.3.1/css/all.min.css',array(),'7.3.1');
}
add_action('wp_enqueue_scripts','feng_category_fontawesome');
function feng_category_badge($id){
 $term=get_term($id,'category');
 if($term&&!is_wp_error($term)){
  $icons=array('代码'=>'code','旅行'=>'travel','外贸'=>'trade');
  $kind=$icons[$term->name]??'';
  if($kind){$fallback=array('code'=>'fa-code','travel'=>'fa-plane-departure','trade'=>'fa-globe');return feng_badge_icon_markup('fa-solid '.$fallback[$kind]);}
 }

 $code=get_term_meta($id,'feng_category_icon',true);if($code)return feng_badge_icon_markup($code);

 $attachment=(int)get_term_meta($id,'feng_category_badge',true);$url=$attachment?wp_get_attachment_url($attachment):false;
 return $url?'<img class="feng-category-badge" src="'.esc_url($url).'" width="28" height="28" alt="" decoding="async">':'';
}
function feng_category_badge_field($term=null){
 $id=$term instanceof WP_Term?(int)get_term_meta($term->term_id,'feng_category_badge',true):0;
 $code=$term instanceof WP_Term?get_term_meta($term->term_id,'feng_category_icon',true):'';
 wp_nonce_field('feng_category_badge','feng_category_badge_nonce');
 $cover=$term instanceof WP_Term?get_term_meta($term->term_id,'feng_category_cover',true):'';
 echo '<div class="feng-category-cover-field"><p><label for="feng-category-cover">分类固定封面（首页与控制面板）</label></p><div style="display:flex;gap:6px"><input type="url" class="large-text" id="feng-category-cover" name="feng_category_cover" value="'.esc_attr($cover).'" placeholder="图片 URL"><button type="button" class="button" data-category-cover-choose>选择图片</button><button type="button" class="button" data-category-cover-clear>清除</button></div><p class="description">建议使用宽幅图片。留空时使用该分类最新公开文章的封面，无封面时显示渐变背景。</p></div>';

 echo '<p><label for="feng-category-icon">Font Awesome 类名 / SVG 代码</label></p><textarea id="feng-category-icon" name="feng_category_icon" rows="3" class="large-text code" placeholder="fa-sharp fa-solid fa-house">'.esc_textarea($code).'</textarea><div data-icon-preview style="font-size:28px;min-height:40px;margin:8px 0">'.feng_badge_icon_markup($code).'</div><p class="description">支持 Pro 原始类名、完整 &lt;i&gt; 标签或纯路径 SVG 代码。填写代码时优先显示代码图标；清空后使用下方图片。</p>';

 echo '<div data-category-badge><input type="hidden" name="feng_category_badge" value="'.esc_attr($id).'"><div data-badge-preview style="margin:8px 0">'.($id?'<img src="'.esc_url(wp_get_attachment_url($id)).'" width="48" height="48" style="object-fit:contain" alt="分类徽章预览">':'').'</div><button type="button" class="button" data-badge-choose>选择或上传徽章</button> <button type="button" class="button" data-badge-remove>移除</button><p class="description">支持 PNG、WebP、JPG、GIF 和纯路径 SVG。建议使用透明背景的正方形图片；留空时只显示分类名称。</p></div>';
}
add_action('category_add_form_fields',function(){echo '<div class="form-field"><label>分类徽章</label>';feng_category_badge_field();echo '</div>';});
add_action('category_edit_form_fields',function($term){echo '<tr class="form-field"><th scope="row">分类徽章</th><td>';feng_category_badge_field($term);echo '</td></tr>';});
function feng_save_category_badge($id){
 if(!current_user_can('manage_categories')||empty($_POST['feng_category_badge_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['feng_category_badge_nonce'])),'feng_category_badge'))return;
 if(isset($_POST['feng_category_cover'])&&is_string($_POST['feng_category_cover']))update_term_meta($id,'feng_category_cover',esc_url_raw(wp_unslash($_POST['feng_category_cover']),array('http','https')));
 if(isset($_POST['feng_category_icon'])&&is_string($_POST['feng_category_icon'])){$code=feng_badge_icon_code(wp_unslash($_POST['feng_category_icon']));if($code)update_term_meta($id,'feng_category_icon',$code);else delete_term_meta($id,'feng_category_icon');}
 $attachment=isset($_POST['feng_category_badge'])?absint($_POST['feng_category_badge']):0;
 if(!$attachment){delete_term_meta($id,'feng_category_badge');return;}
 if(get_post_type($attachment)!=='attachment'||!in_array(get_post_mime_type($attachment),array('image/png','image/jpeg','image/webp','image/gif','image/svg+xml'),true))return;
 if(get_post_mime_type($attachment)==='image/svg+xml'){$file=get_attached_file($attachment);if(!$file||!is_readable($file)||feng_badge_svg(file_get_contents($file))===false)return;}
 update_term_meta($id,'feng_category_badge',$attachment);
}
add_action('created_category','feng_save_category_badge');add_action('edited_category','feng_save_category_badge');
add_filter('manage_edit-category_columns',function($cols){$cols['feng_badge']='徽章';return $cols;});
add_filter('manage_category_custom_column',function($content,$column,$id){return $column==='feng_badge'?(feng_category_badge($id)?:'—'):$content;},10,3);
add_action('admin_enqueue_scripts',function(){
 $screen=get_current_screen();if(!$screen||$screen->taxonomy!=='category')return;
 feng_category_fontawesome();wp_enqueue_media();wp_enqueue_script('feng-category-badge',get_theme_file_uri('/assets/js/category-badge.js'),array('jquery','media-views'),feng_asset_version('/assets/js/category-badge.js'),true);
});
add_filter('the_category',function($html){
 foreach(get_the_category()?:array() as $term){$badge=feng_category_badge($term->term_id);if($badge)$html=str_replace('>'.esc_html($term->name).'</a>','>'.$badge.esc_html($term->name).'</a>',$html);}
 return $html;
});
add_filter('get_the_archive_title',function($title){return is_category()?feng_category_badge(get_queried_object_id()).$title:$title;});

/** Per-category homepage article layout. */
function feng_collection_layout($id){
 $layout=get_term_meta($id,'feng_collection_layout',true);
 return in_array($layout,array('1','3','4','tiles','list'),true)?$layout:'list';
}
function feng_collection_layout_field($term=null){
 $layout=$term instanceof WP_Term?feng_collection_layout($term->term_id):'list';
 wp_nonce_field('feng_collection_layout','feng_collection_layout_nonce');
 echo '<select id="feng-collection-layout" name="feng_collection_layout">';
 foreach(array('list'=>'文章列表＋右侧封面（默认）','1'=>'单张宽幅卡片','3'=>'三张并排','4'=>'四张并排','tiles'=>'四宫格（两行两列）') as $value=>$label)echo '<option value="'.esc_attr($value).'" '.selected($layout,(string)$value,false).'>'.esc_html($label).'</option>';
 echo '</select>';
 $id=$term instanceof WP_Term?$term->term_id:0;
 echo '<p><label>列表文章数量 <select name="feng_collection_count">';
 foreach(array(3,5) as $n)echo '<option value="'.$n.'" '.selected((int)(get_term_meta($id,'feng_collection_count',true)?:5),$n,false).'>'.$n.' 篇</option>';
 echo '</select></label></p><p><label>卡片底色 <input type="color" name="feng_collection_tint" value="'.esc_attr(get_term_meta($id,'feng_collection_tint',true)?:'#f5f7fa').'"></label></p><p><label><input type="checkbox" name="feng_collection_preview" value="1" '.checked(get_term_meta($id,'feng_collection_preview',true)!=='0',true,false).'> 悬浮文章切换封面</label></p>';
 echo '<p class="description">用于首页此分类的文章展示，每次翻页显示对应数量。请先在“外观 → 菜单”中将此分类加入“首页分类”菜单。手机端自动改为单列。</p>';
}
add_action('category_add_form_fields',function(){echo '<div class="form-field"><label for="feng-collection-layout">首页展示样式</label>';feng_collection_layout_field();echo '</div>';});
add_action('category_edit_form_fields',function($term){echo '<tr class="form-field"><th><label for="feng-collection-layout">首页展示样式</label></th><td>';feng_collection_layout_field($term);echo '</td></tr>';});
function feng_save_collection_layout($id){
 if(!current_user_can('manage_categories')||empty($_POST['feng_collection_layout_nonce'])||!is_string($_POST['feng_collection_layout_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['feng_collection_layout_nonce'])),'feng_collection_layout'))return;
 $layout=isset($_POST['feng_collection_layout'])&&is_string($_POST['feng_collection_layout'])?wp_unslash($_POST['feng_collection_layout']):'3';
 update_term_meta($id,'feng_collection_count',isset($_POST['feng_collection_count'])&&$_POST['feng_collection_count']==='3'?3:5);
 update_term_meta($id,'feng_collection_preview',isset($_POST['feng_collection_preview'])?'1':'0');
 if(isset($_POST['feng_collection_tint'])&&is_string($_POST['feng_collection_tint'])){$tint=sanitize_hex_color(wp_unslash($_POST['feng_collection_tint']));if($tint)update_term_meta($id,'feng_collection_tint',$tint);}
 if(in_array($layout,array('1','3','4','tiles','list'),true))update_term_meta($id,'feng_collection_layout',$layout);
}
add_action('created_category','feng_save_collection_layout');
add_action('edited_category','feng_save_collection_layout');
