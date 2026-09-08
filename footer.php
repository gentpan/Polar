</main>
<?php
$footer_background=feng_setting('footer_background','');
if(feng_setting('footer_seasonal',true)){
 $month=(int)wp_date('n');
 $season=$month>=3&&$month<=5?'spring':($month>=6&&$month<=8?'summer':($month>=9&&$month<=11?'autumn':'winter'));
 $footer_background=feng_setting('footer_'.$season,'')?:($footer_background?:get_theme_file_uri('/assets/images/seasons/'.$season.'.webp'));
}
?>
<footer class="xf-footer xf-container feng-footer-landscape<?php echo $footer_background?' has-background':''; ?>">
 <?php if($footer_background): ?><img class="feng-footer-landscape__image" src="<?php echo esc_url($footer_background); ?>" alt="" loading="lazy" decoding="async"><?php endif; ?>
 <div class="feng-footer-left"><div class="feng-footer-brand"><div class="feng-footer-copyright-group"><span class="feng-footer-copyright">© <?php echo esc_html(wp_date('Y')); ?></span> <a class="xf-footer__name" href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html(get_bloginfo('name')); ?></a><span class="feng-footer-rights">版权所有</span></div><div class="feng-footer-statistics"><?php if(feng_setting('footer_stats',true)): ?><div class="feng-footer-stats" data-footer-stats><span title="从启用统计起记录的全站页面浏览次数"><i class="fa-solid fa-eye" aria-hidden="true"></i>总浏览量 <b data-footer-views>—</b></span><span title="最近 5 分钟活跃的浏览器数量，多标签页合并估算"><i class="feng-online-dot" aria-hidden="true"></i><b data-footer-online>—</b> 人在线</span><span><i class="fa-solid fa-location-dot" aria-hidden="true"></i>最近访客来自 <span data-footer-location>—</span></span></div><?php endif; ?>
 </div></div>

 <div class="feng-footer-registration-row"><?php get_template_part('template-parts/footer-legal'); ?></div>
</div>
</footer>
</div>
<button class="xf-top" type="button" data-xf-top data-lordicon-content="top" aria-label="<?php esc_attr_e( '返回顶部', 'feng' ); ?>"><i class="fa-solid fa-square-up" aria-hidden="true"></i></button>
<?php wp_footer(); ?>
</body>
</html>
