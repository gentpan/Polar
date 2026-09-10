<?php
if(!defined('ABSPATH'))exit;
function feng_footprint_posts($status='publish'){
 $candidates=get_posts(array('post_type'=>'post','post_status'=>$status,'posts_per_page'=>-1,'orderby'=>'date','order'=>'DESC'));
 return array_values(array_filter($candidates,function($post){$flag=get_post_meta($post->ID,'_feng_footprint',true);if($flag!=='')return $flag==='1';$v=feng_travel_location($post->ID);return has_category('travel',$post)||isset($v['lat'],$v['lng'])||!empty($v['place_id']);}));
}
function feng_travel_location($id){$v=get_post_meta($id,'_feng_travel',true);return is_array($v)?$v:array();}
function feng_travel_clean($v){
 $out=array();foreach(array('date_start','date_end') as $key){$date=$v[$key]??'';$parsed=is_string($date)?DateTimeImmutable::createFromFormat('!Y-m-d',$date):false;$out[$key]=$parsed&&$parsed->format('Y-m-d')===$date?$date:'';}
 if(!$out['date_start']||($out['date_end']&&$out['date_end']<$out['date_start']))$out['date_end']='';
 $out['source']=in_array($v['source']??'',array('mapbox','google','amap'),true)?$v['source']:'manual';$out['place_id']=sanitize_text_field($v['place_id']??'');$out['crs']=($v['crs']??'')==='gcj02'?'gcj02':'wgs84';foreach(array('city','country','note') as $key)$out[$key]=sanitize_text_field($v[$key]??'');
 $code=strtolower(trim($v['code']??''));$out['code']=preg_match('/^[a-z]{2}$/D',$code)?$code:'';
 foreach(array('lat'=>90,'lng'=>180) as $key=>$max){$n=$v[$key]??'';$out[$key]=is_numeric($n)&&is_finite((float)$n)&&abs((float)$n)<=$max?(float)$n:null;}
 if($out['lat']===null||$out['lng']===null){$out['lat']=null;$out['lng']=null;}
 return $out;
}
function feng_travel_badge($id){$v=feng_travel_location($id);if(empty($v['city'])&&empty($v['country']))return '';return '<span class="feng-travel-badge">'.(!empty($v['code'])?'<img src="'.esc_url('https://flagcdn.io/flags/4x3/'.$v['code'].'.svg').'" width="24" height="18" alt="'.esc_attr($v['country']??'').'" loading="lazy">':'').'<span>'.esc_html(implode(' · ',array_filter(array($v['city']??'',$v['country']??'')))).'</span></span>';}
add_action('add_meta_boxes_post',function(){add_meta_box('feng-travel','旅行足迹','feng_travel_box','post','side','default');});
function feng_travel_box($post){
 wp_nonce_field('feng_travel_save','feng_travel_nonce');
 $flag=get_post_meta($post->ID,'_feng_footprint',true);$v=feng_travel_location($post->ID);
 echo '<p><label><input type="checkbox" name="feng_footprint" value="1" '.checked($flag==='1'||($flag===''&&(has_category('travel',$post)||isset($v['lat'],$v['lng'])||!empty($v['place_id']))),true,false).'> 收录到足迹页面</label></p><p class="description">保存文章后，在「文章 → 足迹」配置地点和旅行日期。</p>';
}
function feng_travel_details_box($post){$v=feng_travel_location($post->ID);wp_nonce_field('feng_travel_save','feng_travel_nonce'); ?>
<div data-travel-editor data-post="<?php echo (int)$post->ID; ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('feng_travel_search')); ?>" data-endpoint="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
<p><label><input type="checkbox" name="feng_footprint" value="1" <?php $flag=get_post_meta($post->ID,'_feng_footprint',true);checked($flag==='1'||($flag===''&&(has_category('travel',$post)||isset($v['lat'],$v['lng'])||!empty($v['place_id'])))); ?>> 收录到足迹页面</label></p><p>任何分类的文章都可以收录。手动坐标请指定坐标系；搜索结果会自动标记来源。切换地图服务后，原服务专属搜索结果需要重新选点。</p>
<p><label for="feng-place-query">搜索城市或地址</label> <input id="feng-place-query" type="search" data-place-query placeholder="例如：曼谷，泰国"> <button class="button" type="button" data-place-search>搜索地点</button></p><p data-place-status role="status"></p><div data-place-results></div>
<?php foreach(array('city'=>'城市 / 地点','country'=>'国家 / 地区名称','code'=>'国家代码（两位，例如 cn、th、mv）','lat'=>'纬度（-90～90）','lng'=>'经度（-180～180）','note'=>'足迹备注') as $key=>$label): ?><p><label for="feng-travel-<?php echo $key; ?>"><?php echo esc_html($label); ?></label><br><input class="widefat" id="feng-travel-<?php echo $key; ?>" name="feng_travel[<?php echo $key; ?>]" value="<?php echo esc_attr($v[$key]??''); ?>" <?php if(in_array($key,array('lat','lng'),true))echo 'type="number" step="any" min="-'.($key==='lat'?90:180).'" max="'.($key==='lat'?90:180).'"';else echo 'type="text"'; ?>></p><?php endforeach; ?>
<p><label for="feng-travel-date-start">旅行开始日期</label><br><input type="date" id="feng-travel-date-start" name="feng_travel[date_start]" value="<?php echo esc_attr($v['date_start']??''); ?>"> <label for="feng-travel-date-end">结束日期（可选）</label> <input type="date" id="feng-travel-date-end" name="feng_travel[date_end]" value="<?php echo esc_attr($v['date_end']??''); ?>"></p><p class="description">填写实际旅行日期，与文章发布日期无关。单日旅行只填开始日期；结束日期不能早于开始日期，留空不显示。</p>
<input type="hidden" name="feng_travel[source]" value="<?php echo esc_attr($v['source']??'manual'); ?>"><input type="hidden" name="feng_travel[place_id]" value="<?php echo esc_attr($v['place_id']??''); ?>"><p><label>坐标系 <select name="feng_travel[crs]"><option value="wgs84" <?php selected($v['crs']??'wgs84','wgs84'); ?>>WGS84 / GPS</option><option value="gcj02" <?php selected($v['crs']??'','gcj02'); ?>>GCJ-02 / 高德</option></select></label></p><p>搜索后点击候选地点填入字段，再保存文章。填写地点名称即可显示国旗地点；地图标记需要有效经纬度，或通过 Google 搜索选择的地点 ID。</p></div>
<?php }
function feng_travel_save_post($id){if(wp_is_post_revision($id)||wp_is_post_autosave($id)||!current_user_can('edit_post',$id)||empty($_POST['feng_travel_nonce'])||!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['feng_travel_nonce'])),'feng_travel_save'))return;$v=isset($_POST['feng_travel'])&&is_array($_POST['feng_travel'])?wp_unslash($_POST['feng_travel']):array();foreach($v as $x)if(!is_scalar($x))return;$clean=feng_travel_clean($v);if($clean['source']==='google'&&$clean['place_id']){$clean['lat']=null;$clean['lng']=null;}if(isset($_POST['feng_travel']))update_post_meta($id,'_feng_travel',$clean);update_post_meta($id,'_feng_footprint',isset($_POST['feng_footprint'])?'1':'0');}
add_action('save_post_post','feng_travel_save_post');
add_action('admin_menu',function(){add_submenu_page('edit.php','旅行足迹','足迹','manage_options','feng-travel','feng_travel_admin');});
function feng_map_settings_screen(){
 if(!current_user_can('manage_options'))return;
 if(isset($_POST['feng_travel_config_nonce'])&&check_admin_referer('feng_travel_config','feng_travel_config_nonce')){
  $old=feng_map_config();$input=isset($_POST['feng_map'])&&is_array($_POST['feng_map'])?wp_unslash($_POST['feng_map']):array();$next=$old;
  $next['provider']=in_array($input['provider']??'',array('mapbox','google','amap'),true)?$input['provider']:'mapbox';
  foreach(feng_map_fields() as $key=>$label){$value=is_string($input[$key]??null)?trim(sanitize_text_field($input[$key])):'';if(!empty($_POST['clear_'.$key]))$next[$key]='';elseif($value!=='')$next[$key]=$value;}
  $next['globe_background']=in_array($input['globe_background']??'',array('system','light','dark'),true)?$input['globe_background']:'system';
  $next['amap_proxy']=esc_url_raw(is_string($input['amap_proxy']??null)?$input['amap_proxy']:'',array('https'));
  if($next['mapbox_token']!==''&&!preg_match('/^pk\.[A-Za-z0-9._-]+$/D',$next['mapbox_token']))echo '<div class="notice notice-error"><p>Mapbox 请使用 pk. 开头的公开 Token。</p></div>';
  else {update_option('feng_map_settings',$next,false);echo '<div class="notice notice-success"><p>地图配置已保存。</p></div>';}
 }
 $cfg=feng_map_config();echo '<div class="wrap feng-admin">';feng_settings_header();echo '<div class="feng-admin-shell">';feng_settings_tabs('map');echo '<div class="feng-admin-content"><section class="feng-settings-section"><header class="feng-section-header"><h2>地图服务</h2><p>主题不提供共享 Key。每位站长在服务商申请后填写自己的凭据，只有选中的服务会被加载。</p></header><form class="feng-settings-form" method="post">';wp_nonce_field('feng_travel_config','feng_travel_config_nonce');echo '<table class="form-table"><tr><th>地图服务</th><td><select name="feng_map[provider]">';foreach(array('mapbox'=>'Mapbox（默认，全球地球视图）','google'=>'Google Maps','amap'=>'高德地图（国内）') as $key=>$label)echo '<option value="'.$key.'" '.selected($cfg['provider'],$key,false).'>'.esc_html($label).'</option>';echo '</select></td></tr>';
 echo '<tr><th>地球背景</th><td><select name="feng_map[globe_background]">';foreach(array('system'=>'跟随站点配色','light'=>'浅色','dark'=>'深色星空') as $value=>$label)echo '<option value="'.esc_attr($value).'" '.selected($cfg['globe_background'],$value,false).'>'.esc_html($label).'</option>';echo '</select><p class="description">足迹页地球视图的默认背景，与地图风格独立。</p></td></tr>';
 foreach(feng_map_fields() as $key=>$label)echo '<tr><th><label for="map-'.$key.'">'.esc_html($label).'</label></th><td><input type="password" autocomplete="new-password" class="regular-text" id="map-'.$key.'" name="feng_map['.$key.']" value="" placeholder="'.($cfg[$key]?'已保存，留空保留':'尚未配置').'"><label> <input type="checkbox" name="clear_'.$key.'" value="1">清除</label></td></tr>';
 echo '</table><p>Mapbox：公开 Token 用于地图与 Permanent Geocoding，需开启相应计费权限。</p><p>Google：浏览器 Key 开启 Maps JavaScript API 和 Geocoding API；服务器 Key 开启 Geocoding API。Google 搜索结果只保存 Place ID，坐标在 Google 地图展示时解析，不长期存储。</p><p>高德：分别填写 Web 端 JS Key、安全密钥及 Web 服务 Key。JS 凭据用于浏览器地图，Web 服务 Key 只在服务器搜索时使用。建议按服务商要求限制域名/IP。生产环境也可填写安全代理地址，使用 security.serviceHost 隐藏安全密钥。</p><p><label>高德安全代理地址（可选） <input class="regular-text" type="url" name="feng_map[amap_proxy]" placeholder="https://your-site.example/_AMapService" value="'.esc_attr($cfg['amap_proxy']).'"></label></p>';
 submit_button('保存地图设置');echo '</form>';
 echo '</section></div></div></div>';
}
function feng_travel_admin(){
 if(!current_user_can('manage_options'))return;
 $edit_id=absint($_GET['footprint_post']??0);
 if($edit_id&&get_post_type($edit_id)==='post'&&current_user_can('edit_post',$edit_id)){
  if(isset($_POST['feng_travel_nonce'])){check_admin_referer('feng_travel_save','feng_travel_nonce');feng_travel_save_post($edit_id);echo '<div class="notice notice-success"><p>足迹资料已保存。</p></div>';}
  echo '<div class="wrap"><h1>配置旅行足迹</h1><h2>'.esc_html(get_the_title($edit_id)).'</h2><form method="post">';feng_travel_details_box(get_post($edit_id));submit_button('保存足迹');echo '</form></div>';return;
 }
 $term=get_page_by_path('footprint');echo '<div class="wrap"><h1>旅行足迹</h1>';
 {echo '<p><a class="button" href="'.esc_url($term?get_permalink($term):home_url('/footprint/')).'">查看旅行页面</a> <a class="button" href="'.esc_url(admin_url('edit.php')).'">管理旅行文章</a></p><table class="widefat striped"><thead><tr><th>文章</th><th>地点</th><th>地图坐标</th></tr></thead><tbody>';foreach(feng_footprint_posts(array('publish','draft','pending','private')) as $p){$v=feng_travel_location($p->ID);echo '<tr><td><a href="'.esc_url(admin_url('edit.php?page=feng-travel&footprint_post='.$p->ID)).'">'.esc_html($p->post_title).'</a></td><td>'.esc_html(trim(($v['city']??'').' '.($v['country']??''))).'</td><td>'.esc_html(isset($v['lat'],$v['lng'])?$v['lat'].', '.$v['lng']:'待填写').'</td></tr>';}echo '</tbody></table>'; }echo '</div>';
}
add_action('admin_enqueue_scripts',function($hook){if(in_array($hook,array('post.php','post-new.php','posts_page_feng-travel'),true))wp_enqueue_script('feng-travel-editor',get_theme_file_uri('/assets/js/travel-editor.js'),array(),feng_asset_version('/assets/js/travel-editor.js'),true);});
add_action('wp_ajax_feng_travel_search',function(){
 check_ajax_referer('feng_travel_search','nonce');$id=absint($_POST['post']??0);if(!$id||!current_user_can('edit_post',$id))wp_send_json_error(array('message'=>'没有编辑权限。'),403);
 $cfg=feng_map_config();
 $q=sanitize_text_field(wp_unslash($_POST['q']??''));if(mb_strlen($q)<2||mb_strlen($q)>150)wp_send_json_error(array('message'=>'请输入 2～150 字的地点。'),400);
 $key='feng_geo_limit_'.get_current_user_id();if(get_transient($key))wp_send_json_error(array('message'=>'请稍等几秒再搜索。'),429);set_transient($key,1,3);
 $result=feng_map_search($q,$cfg);if(is_wp_error($result))wp_send_json_error(array('message'=>$result->get_error_message()),400);wp_send_json_success($result);
});
add_action('wp_enqueue_scripts',function(){wp_enqueue_style('feng-travel',get_theme_file_uri('/assets/css/travel.css'),array('feng-cards'),feng_asset_version('/assets/css/travel.css'));wp_enqueue_script('feng-travel',get_theme_file_uri('/assets/js/travel.js'),array('xf-app'),feng_asset_version('/assets/js/travel.js'),true);});
function feng_map_fields(){return array('mapbox_token'=>'Mapbox 公开 Token','google_browser'=>'Google 浏览器 Key','google_server'=>'Google 服务器 Key','amap_browser'=>'高德 Web 端 JS Key','amap_security'=>'高德 JS 安全密钥','amap_server'=>'高德 Web 服务 Key');}
function feng_map_config(){return wp_parse_args(get_option('feng_map_settings',array()),array_merge(array_fill_keys(array_keys(feng_map_fields()),''),array('provider'=>'mapbox','mapbox_token'=>get_option('feng_mapbox_token',''),'amap_proxy'=>'','globe_background'=>'system')));}
function feng_map_public(){ $c=feng_map_config();$p=$c['provider'];return array('globe_background'=>$c['globe_background'],'provider'=>$p,'key'=>$c[$p==='mapbox'?'mapbox_token':$p.'_browser'],'security'=>$p==='amap'&&!$c['amap_proxy']?$c['amap_security']:'','proxy'=>$p==='amap'?$c['amap_proxy']:''); }
function feng_map_search($q,$cfg){
 $provider=$cfg['provider'];$key=$cfg[$provider==='mapbox'?'mapbox_token':$provider.'_server'];
 if(!$key)return new WP_Error('map_key','请先在「外观 → ShanYing 设置 → 地图服务」填写所选地图的搜索 Key。');
 if($provider==='mapbox')$url=add_query_arg(array('q'=>$q,'access_token'=>$key,'permanent'=>'true','autocomplete'=>'false','limit'=>5,'language'=>'zh','types'=>'country,region,place,locality,address'),'https://api.mapbox.com/search/geocode/v6/forward');
 elseif($provider==='google')$url=add_query_arg(array('address'=>$q,'key'=>$key,'language'=>'zh-CN'),'https://maps.googleapis.com/maps/api/geocode/json');
 else $url=add_query_arg(array('address'=>$q,'key'=>$key),'https://restapi.amap.com/v3/geocode/geo');
 $r=wp_safe_remote_get($url,array('timeout'=>15,'redirection'=>0,'limit_response_size'=>200000,'feng_feed_request'=>true));
 if(is_wp_error($r)||wp_remote_retrieve_response_code($r)!==200)return new WP_Error('map_network','地图服务连接失败或拒绝请求，请检查 Key、权限、额度和网络。');
 $data=json_decode(wp_remote_retrieve_body($r),true);return feng_map_results($provider,$data);
}
function feng_map_results($provider,$data){
 if(!is_array($data))return new WP_Error('map_response','地图返回内容无法识别。');$items=array();
 if($provider==='mapbox')foreach($data['features']??array() as $f){$p=$f['properties']??array();$country=$p['context']['country']??array();$xy=$f['geometry']['coordinates']??array();$items[]=feng_travel_clean(array('source'=>'mapbox','city'=>$p['name']??'','country'=>$country['name']??(($p['feature_type']??'')==='country'?($p['name']??''):''),'code'=>$country['country_code']??$p['country_code']??'','lat'=>$xy[1]??null,'lng'=>$xy[0]??null))+array('label'=>sanitize_text_field($p['full_address']??$p['name']??''));}
 elseif($provider==='google'){
  if(!in_array($data['status']??'',array('OK','ZERO_RESULTS'),true))return new WP_Error('map_api','Google 搜索失败，请检查 Geocoding API 权限、服务器 Key 和计费。');
  foreach($data['results']??array() as $r){$v=array('source'=>'google','place_id'=>$r['place_id']??'','lat'=>null,'lng'=>null,'city'=>'','country'=>'','code'=>'');foreach($r['address_components']??array() as $part){if(in_array('country',$part['types'],true)){$v['country']=$part['long_name'];$v['code']=$part['short_name'];}if(in_array('locality',$part['types'],true))$v['city']=$part['long_name'];}$items[]=feng_travel_clean($v)+array('label'=>sanitize_text_field($r['formatted_address']??''));}
 }else{
  if(($data['status']??'')!=='1')return new WP_Error('map_api','高德搜索失败，请检查 Web 服务 Key 和权限。');
  foreach($data['geocodes']??array() as $r){$xy=explode(',',$r['location']??'');$items[]=feng_travel_clean(array('source'=>'amap','crs'=>'gcj02','city'=>is_string($r['city']??null)&&$r['city']!==''?$r['city']:($r['province']??''),'country'=>'中国','code'=>'cn','lng'=>$xy[0]??null,'lat'=>$xy[1]??null))+array('label'=>sanitize_text_field($r['formatted_address']??''));}
 }return array_slice($items,0,5);
}
