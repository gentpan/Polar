</main>
<footer class="xf-footer polar-footer">
 <div class="polar-footer__inner"><div class="polar-footer__main"><div class="feng-footer-copyright-group"><span class="feng-footer-copyright">© <?php echo esc_html(wp_date('Y')); ?></span> <a class="xf-footer__name" href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html(get_bloginfo('name')); ?></a><span class="feng-footer-rights">版权所有</span></div><div class="feng-footer-statistics"><?php if(feng_setting('footer_stats',true)): ?><div class="feng-footer-stats" data-footer-stats><span title="从启用统计起记录的全站页面浏览次数"><i class="fa-solid fa-eye" aria-hidden="true"></i>总浏览量 <b data-footer-views>—</b></span><span title="最近 5 分钟活跃的浏览器数量，多标签页合并估算"><i class="feng-online-dot" aria-hidden="true"></i><b data-footer-online>—</b> 人在线</span><span><i class="fa-solid fa-location-dot" aria-hidden="true"></i>最近访客来自 <span data-footer-location>—</span></span></div><?php endif; ?>
 </div></div>

 <div class="polar-footer__legal"><?php get_template_part('template-parts/footer-legal'); ?></div>
</div>
</footer>
</div>
<button class="xf-top" type="button" data-xf-top data-lordicon-content="top" aria-label="<?php esc_attr_e( '返回顶部', 'feng' ); ?>"><i class="fa-solid fa-square-up" aria-hidden="true"></i></button>
<?php wp_footer(); ?>
</body>
</html>
