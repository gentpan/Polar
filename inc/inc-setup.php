<?php
/** Native WordPress capabilities shared by all future designs. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function feng_setup() {
	load_theme_textdomain( 'feng', get_template_directory() . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_editor_style( array( 'assets/css/admin.css' ) );
	add_theme_support( 'custom-logo', array( 'height' => 80, 'width' => 240, 'flex-height' => true, 'flex-width' => true ) );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	register_nav_menus(
		array(
			'primary' => __( '主导航', 'feng' ),
			'collections' => __( '首页分类', 'feng' ),
		)
	);
}
add_action( 'after_setup_theme', 'feng_setup' );

function feng_content_width() {
	$GLOBALS['content_width'] = (int) apply_filters( 'feng_content_width', 760 );
}
add_action( 'after_setup_theme', 'feng_content_width', 0 );
