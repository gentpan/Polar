<section class="xf-empty xf-reading">
	<h2><?php esc_html_e( '暂无内容', 'feng' ); ?></h2>
	<p><?php echo esc_html( is_search() ? __( '没有找到匹配的结果，可以换个关键词再试。', 'feng' ) : __( '这里暂时没有已发布的文章。', 'feng' ) ); ?></p>
	<?php if ( ! is_search() ) { get_search_form(); } ?>
</section>
