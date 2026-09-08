<?php $feng_search_id = wp_unique_id( ! empty( $args['xf_dialog'] ) ? 'xf-dialog-search-' : 'xf-page-search-' ); ?>
<form role="search" method="get" class="xf-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $feng_search_id ); ?>"><?php esc_html_e( '搜索文章', 'feng' ); ?></label>
	<input id="<?php echo esc_attr( $feng_search_id ); ?>" type="search" name="s" value="<?php echo esc_attr( get_search_query( false ) ); ?>" placeholder="<?php esc_attr_e( '搜索文章…', 'feng' ); ?>">
	<button type="submit"><?php esc_html_e( '搜索', 'feng' ); ?></button>
</form>
