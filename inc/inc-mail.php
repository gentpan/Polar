<?php
if(!defined('ABSPATH'))exit;
function feng_mail_config(){return wp_parse_args(get_option('feng_mail_settings',array()),array('enabled'=>false,'host'=>'','port'=>587,'encryption'=>'tls','auth'=>true,'username'=>'','password'=>'','from_email'=>'','from_name'=>''));}
function feng_mail_secret($text,$decrypt=false){
 $key=hash('sha256',wp_salt('auth'),true);
 if($decrypt){$raw=base64_decode($text,true);if(!$raw||strlen($raw)<28)return '';return openssl_decrypt(substr($raw,28),'aes-256-gcm',$key,OPENSSL_RAW_DATA,substr($raw,0,12),substr($raw,12,16))?:'';}
 $iv=random_bytes(12);$tag='';$cipher=openssl_encrypt($text,'aes-256-gcm',$key,OPENSSL_RAW_DATA,$iv,$tag);if($cipher===false)throw new RuntimeException('邮件密码加密失败');return base64_encode($iv.$tag.$cipher);
}
add_action('phpmailer_init',function($mail){
 $c=feng_mail_config();if(!$c['enabled'])return;
 $password=$c['password']?feng_mail_secret($c['password'],true):'';
 if(!$c['host']||!is_email($c['from_email'])||($c['auth']&&(!$c['username']||!$password)))throw new \PHPMailer\PHPMailer\Exception('SMTP 配置不完整，请检查主题邮件设置。');
 $mail->isSMTP();$mail->Host=$c['host'];$mail->Port=(int)$c['port'];$mail->SMTPSecure=$c['encryption'];$mail->SMTPAutoTLS=$c['encryption']!=='';$mail->SMTPAuth=(bool)$c['auth'];$mail->Username=$c['username'];$mail->Password=$password;$mail->Timeout=15;$mail->SMTPDebug=0;$mail->setFrom($c['from_email'],$c['from_name'],false);
});
function feng_mail_settings_screen(){
 if(!current_user_can('manage_options'))return;$notice='';$error=false;
 if($_SERVER['REQUEST_METHOD']==='POST'&&check_admin_referer('feng_mail_settings')){
  if(isset($_POST['mail_test'])){
   $recipient=sanitize_email(wp_unslash($_POST['test_email']??''));
   if(!feng_mail_config()['enabled']||!is_email($recipient)){$notice='请先保存并启用 SMTP，再填写有效的测试收件邮箱。';$error=true;}
   else{$ok=wp_mail($recipient,'SMTP 测试邮件 · '.get_bloginfo('name'),'这是一封由站点管理员主动发送的测试邮件。收到此邮件说明当前邮件配置能够送达。');$notice=$ok?'邮件服务器已接受测试邮件，请检查收件箱和垃圾邮件。':'测试发送失败，请检查服务器地址、端口、加密方式、账号和授权码。';$error=!$ok;}
  }else{
   $old=feng_mail_config();$v=isset($_POST['feng_mail'])&&is_array($_POST['feng_mail'])?wp_unslash($_POST['feng_mail']):array();foreach($v as $k=>$value)if(!is_scalar($value))$v[$k]='';
   $next=array('enabled'=>!empty($v['enabled']),'host'=>strtolower(trim(sanitize_text_field($v['host']??''))),'port'=>absint($v['port']??587),'encryption'=>in_array($v['encryption']??'',array('tls','ssl',''),true)?$v['encryption']:'tls','auth'=>!empty($v['auth']),'username'=>sanitize_text_field($v['username']??''),'password'=>$old['password'],'from_email'=>sanitize_email($v['from_email']??''),'from_name'=>sanitize_text_field($v['from_name']??''));
   if($next['host']!==$old['host']||$next['username']!==$old['username']||!empty($v['clear_password']))$next['password']='';
   if(($v['password']??'')!=='')$next['password']=feng_mail_secret($v['password']);
   if(($next['host']!==''&&!preg_match('/^[a-z0-9.-]+$/D',$next['host']))||$next['port']<1||$next['port']>65535){$notice='服务器只填写主机名或 IPv4 地址，端口范围为 1–65535。';$error=true;}
   elseif($next['enabled']&&(!$next['host']||!$next['from_email']||($next['auth']&&(!$next['username']||!$next['password'])))){$notice='启用前请填写服务器、发件邮箱及认证信息；修改服务器或账号时请重新填写密码。';$error=true;}
   else{update_option('feng_mail_settings',$next,false);$notice='邮件设置已保存。';}
  }
 }
 $c=feng_mail_config();echo '<div class="wrap feng-admin">';feng_settings_header();if($notice)echo '<div class="notice notice-'.($error?'error':'success').'"><p>'.esc_html($notice).'</p></div>';echo '<div class="feng-admin-shell">';feng_settings_tabs('mail');echo '<div class="feng-admin-content"><section class="feng-settings-section"><header class="feng-section-header"><h2>邮件发送 · SMTP</h2><p>用于 WordPress 的通知、找回密码和其他 wp_mail 邮件。请填写邮件服务商提供的配置。</p></header><form method="post" class="feng-settings-form">';wp_nonce_field('feng_mail_settings');
 echo '<table class="form-table"><tr><th>启用 SMTP</th><td><label><input type="checkbox" name="feng_mail[enabled]" value="1" '.checked($c['enabled'],true,false).'>启用</label></td></tr>';
 foreach(array('host'=>'SMTP 服务器','port'=>'端口','username'=>'账号','from_email'=>'发件邮箱','from_name'=>'发件人名称') as $key=>$label){echo '<tr><th><label for="mail-'.$key.'">'.$label.'</label></th><td><input class="regular-text" id="mail-'.$key.'" name="feng_mail['.$key.']" type="'.($key==='port'?'number':($key==='from_email'?'email':'text')).'" value="'.esc_attr($c[$key]).'" '.($key==='port'?'min="1" max="65535"':'').'></td></tr>';}
 echo '<tr><th>连接加密</th><td><select name="feng_mail[encryption]">';foreach(array('tls'=>'STARTTLS（通常 587）','ssl'=>'SSL / TLS（通常 465）',''=>'无加密（仅用于可信内网）') as $key=>$label)echo '<option value="'.esc_attr($key).'" '.selected($c['encryption'],$key,false).'>'.$label.'</option>';echo '</select></td></tr><tr><th>SMTP 认证</th><td><label><input type="checkbox" name="feng_mail[auth]" value="1" '.checked($c['auth'],true,false).'>使用账号密码认证</label></td></tr><tr><th><label for="mail-password">密码 / 授权码</label></th><td><input id="mail-password" class="regular-text" type="password" autocomplete="new-password" name="feng_mail[password]" value="" placeholder="'.($c['password']?'已保存，留空保留':'尚未配置').'"><p><label><input type="checkbox" name="feng_mail[clear_password]" value="1">清除已保存密码</label></p><p class="description">密码加密保存，不在页面回显。邮箱要求授权码时请勿填写登录密码。更换服务器或账号后需重新填写。</p></td></tr></table>';submit_button('保存邮件设置');echo '</form></section><section class="feng-settings-section"><h2>发送测试邮件</h2><p>使用已保存的配置，仅在点击下方按钮后发送。</p><form method="post">';wp_nonce_field('feng_mail_settings');echo '<label for="mail-test-email">收件邮箱</label> <input id="mail-test-email" class="regular-text" name="test_email" type="email" required>';submit_button('发送测试邮件','secondary','mail_test');echo '</form></section></div></div></div>';
}
