</main>
<footer class="xf-footer polar-footer">
 <div class="polar-footer__inner"><div class="polar-footer__main"><div class="feng-footer-copyright-group"><span class="feng-footer-copyright">© <?php echo esc_html(wp_date('Y')); ?></span> <a class="xf-footer__name" href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html(get_bloginfo('name')); ?></a><span class="feng-footer-rights">版权所有</span></div><div class="feng-footer-statistics"><?php if(feng_setting('footer_stats',true)): ?><div class="feng-footer-stats" data-footer-stats><span title="从启用统计起记录的全站页面浏览次数"><i class="fa-solid fa-eye" aria-hidden="true"></i>总浏览量 <b data-footer-views>—</b></span><span title="最近 5 分钟活跃的浏览器数量，多标签页合并估算"><i class="feng-online-dot" aria-hidden="true"></i><b data-footer-online>—</b> 人在线</span><span><i class="fa-solid fa-location-dot" aria-hidden="true"></i>最近访客来自 <span data-footer-location>—</span></span></div><?php endif; ?>
 </div></div>

 <div class="polar-footer__legal"><?php
$feng_police_number=trim(feng_setting('police_number',''));
$feng_police_code=preg_replace('/[^0-9]/','',mb_convert_kana($feng_police_number,'n','UTF-8'));
$feng_police_url=$feng_police_code!==''?'http://www.beian.gov.cn/portal/registerSystemInfo?recordcode='.$feng_police_code:'';
$feng_registrations=array(
 array('number'=>trim(feng_setting('icp_number','')),'url'=>'http://beian.miit.gov.cn/','icon'=>'icp'),
 array('number'=>$feng_police_number,'url'=>$feng_police_url,'icon'=>'beian'),
);
if($feng_registrations[0]['number']||$feng_registrations[1]['number']):
?>
<svg width="0" height="0" aria-hidden="true" focusable="false" style="position:absolute;overflow:hidden"><defs><filter id="feng-registration-transparent" color-interpolation-filters="sRGB"><feColorMatrix type="matrix" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  -10 -10 -10 0 30"/><feComposite in2="SourceGraphic" operator="in"/></filter></defs></svg>
<div class="feng-footer-legal"><?php foreach($feng_registrations as $registration): if($registration['number']==='')continue; ?>
 <?php if($registration['url']): ?><a class="feng-registration" href="<?php echo esc_url($registration['url']); ?>" target="_blank" rel="noopener noreferrer"><?php else: ?><span class="feng-registration" tabindex="0"><?php endif; ?>
  <picture class="feng-registration__badge" data-registration-icon="<?php echo esc_attr($registration['icon']); ?>"><source srcset="<?php echo esc_url(get_theme_file_uri('/assets/images/'.$registration['icon'].'.avif')); ?>" type="image/avif"><img src="<?php echo esc_url(get_theme_file_uri('/assets/images/'.$registration['icon'].'.webp')); ?>" width="22" height="22" alt="" decoding="async" loading="lazy"></picture><span><?php echo esc_html($registration['number']); ?></span>
 <?php echo $registration['url']?'</a>':'</span>'; ?>
<?php endforeach; ?></div>
<?php endif; ?></div>
</div>
</footer>
</div>
<button class="xf-top" type="button" data-xf-top data-lordicon-content="top" aria-label="<?php esc_attr_e( '返回顶部', 'feng' ); ?>"><i class="fa-solid fa-square-up" aria-hidden="true"></i></button>
<?php wp_footer(); ?>
</body>
</html>
