<?php
if(!defined('ABSPATH'))exit;
function feng_weather_json($url,$ttl=1200){
 $key='feng_weather_'.md5($url);$cached=get_transient($key);if($cached!==false)return $cached?:null;
 // Brief negative cache also prevents repeated calls during outages.
 set_transient($key,array(),60);
 $response=wp_remote_get($url,array('timeout'=>6,'redirection'=>0));
 if(is_wp_error($response)||wp_remote_retrieve_response_code($response)!==200)return null;
 $data=json_decode(wp_remote_retrieve_body($response),true);if(!is_array($data))return null;
 set_transient($key,$data,$ttl);return $data;
}
function feng_weather_city($name){
 if(!$name)return null;
 $data=feng_weather_json(add_query_arg(array('name'=>$name,'count'=>1,'language'=>'zh','format'=>'json'),'https://geocoding-api.open-meteo.com/v1/search'),DAY_IN_SECONDS);
 return $data['results'][0]??null;
}
function feng_weather_current($city){
 if(!$city||!is_numeric($city['latitude']??null)||!is_numeric($city['longitude']??null))return null;
 $lat=(float)$city['latitude'];$lon=(float)$city['longitude'];if(abs($lat)>90||abs($lon)>180)return null;
 $data=feng_weather_json(add_query_arg(array('latitude'=>round($lat,2),'longitude'=>round($lon,2),'current'=>'temperature_2m,weather_code,is_day','timezone'=>'auto'),'https://api.open-meteo.com/v1/forecast'));
 $c=$data['current']??null;if(!is_numeric($c['temperature_2m']??null)||!isset($c['weather_code']))return null;
 return array('city'=>sanitize_text_field($city['name']??''),'temperature'=>round($c['temperature_2m']),'code'=>(int)$c['weather_code'],'day'=>(bool)($c['is_day']??true),'time'=>sanitize_text_field($c['time']??''));
}
function feng_weather_ajax(){
 nocache_headers();if(!feng_setting('greeting_weather',true))wp_send_json_error(null,404);
 $kind=is_string($_GET['kind']??null)?$_GET['kind']:'host';$city=null;
 if($kind==='visitor'){
  if(!feng_setting('weather_visitor',true))wp_send_json_success(null);
  // Trust the server address, not arbitrary client-supplied forwarding headers.
  $ip=$_SERVER['REMOTE_ADDR']??'';
  if(filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE)){
   $geo=feng_weather_json('https://ipwho.is/'.rawurlencode($ip).'?fields=success,city,latitude,longitude',HOUR_IN_SECONDS);
   if(!empty($geo['success']))$city=array('name'=>$geo['city']??'','latitude'=>$geo['latitude']??null,'longitude'=>$geo['longitude']??null);
  }
 }else $city=feng_weather_city(feng_setting('weather_city',''));
 wp_send_json_success(feng_weather_current($city));
}
add_action('wp_ajax_feng_weather','feng_weather_ajax');add_action('wp_ajax_nopriv_feng_weather','feng_weather_ajax');
