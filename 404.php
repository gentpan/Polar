<?php get_header(); ?>
<section class="xf-reading">
	<h1><?php esc_html_e( '没有找到这个页面', 'feng' ); ?></h1>
	<p><?php esc_html_e( '可以搜索文章，或返回首页继续浏览。', 'feng' ); ?></p>
	<?php get_search_form(); ?>
	<p><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( '返回首页', 'feng' ); ?></a></p>
</section>
<?php get_footer(); ?>
