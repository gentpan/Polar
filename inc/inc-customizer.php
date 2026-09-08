<?php
if (!defined('ABSPATH')) exit;
function feng_customize($manager) {
 $manager->add_section('feng_appearance',array('title'=>'Polar · 快速外观','priority'=>30));
 foreach(array('xf_eyebrow','xf_hero_intro') as $key) {
  foreach(feng_settings_schema() as $group) if(isset($group['fields'][$key])) $field=$group['fields'][$key];
  $id='feng_settings['.$key.']';
  $callback=$field[1]==='textarea'?'sanitize_textarea_field':'sanitize_text_field';
  $manager->add_setting($id,array('type'=>'option','default'=>$field[2],'sanitize_callback'=>$callback));
  $manager->add_control($id,array('section'=>'feng_appearance','label'=>$field[0],'type'=>$field[1],'choices'=>$field[3]??array()));
 }
}
add_action('customize_register','feng_customize');
