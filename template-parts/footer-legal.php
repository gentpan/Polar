<?php
/** Registration details supplied in the native theme settings. */
if(!defined('ABSPATH'))exit;
$feng_police_number=trim(feng_setting('police_number',''));
$feng_police_code=preg_replace('/[^0-9]/','',mb_convert_kana($feng_police_number,'n','UTF-8'));
$feng_police_url=$feng_police_code!==''?'http://www.beian.gov.cn/portal/registerSystemInfo?recordcode='.$feng_police_code:'';
$feng_registrations=array(
 array('number'=>trim(feng_setting('icp_number','')),'url'=>'http://beian.miit.gov.cn/','icon'=>'icp'),
 array('number'=>$feng_police_number,'url'=>$feng_police_url,'icon'=>'beian'),
);
if(!$feng_registrations[0]['number']&&!$feng_registrations[1]['number'])return;
?>
<svg width="0" height="0" aria-hidden="true" focusable="false" style="position:absolute;overflow:hidden"><defs><filter id="feng-registration-transparent" color-interpolation-filters="sRGB"><feColorMatrix type="matrix" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  -10 -10 -10 0 30"/><feComposite in2="SourceGraphic" operator="in"/></filter></defs></svg>
<div class="feng-footer-legal"><?php foreach($feng_registrations as $registration): if($registration['number']==='')continue; ?>
 <?php if($registration['url']): ?><a class="feng-registration" href="<?php echo esc_url($registration['url']); ?>" target="_blank" rel="noopener noreferrer"><?php else: ?><span class="feng-registration" tabindex="0"><?php endif; ?>
  <picture class="feng-registration__badge" data-registration-icon="<?php echo esc_attr($registration['icon']); ?>"><source srcset="<?php echo esc_url(get_theme_file_uri('/assets/images/'.$registration['icon'].'.avif')); ?>" type="image/avif"><img src="<?php echo esc_url(get_theme_file_uri('/assets/images/'.$registration['icon'].'.webp')); ?>" width="22" height="22" alt="" decoding="async" loading="lazy"></picture><span><?php echo esc_html($registration['number']); ?></span>
 <?php echo $registration['url']?'</a>':'</span>'; ?>
<?php endforeach; ?></div>
