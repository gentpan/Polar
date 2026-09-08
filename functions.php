<?php
/**
 * Polar theme bootstrap.
 *
 * Register theme capabilities and load focused feature modules. Front-end markup
 * belongs in the root templates, pages/ or template-parts/.
 *
 * @package Polar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$feng_modules = array(
	'inc-settings.php',   // Settings API and theme administration.
	'inc-ai.php',         // AI providers, protected requests and image import.
	'inc-ai-admin.php',   // Native AI settings and article editing tools.
	'inc-setup.php',      // Theme support, menus and editor integration.
	'inc-assets.php',     // Front-end styles, scripts and fonts.
	'inc-customizer.php', // Live appearance controls.
	'inc-category-badge.php', // Native category badge fields and safe media.
	'inc-media.php',      // Images, video and deferred media.
	'inc-icons.php',      // SVG icons and social links.
	'inc-page.php',       // Page templates, archive helpers and pagination.
	'inc-friend-avatar.php', // Friend identity and commenter avatar matching.
	'inc-feed.php',       // Friend RSS / Atom snapshots and scheduled refresh.
	'inc-feed-ui.php',    // Subscription page, menu badge and native settings.
	'inc-music.php',      // Homepage latest note and persistent music player.
	'inc-activity.php',   // Homepage articles and short-note activity.
	'inc-talk.php',       // Front-end short notes, native storage and permissions.
	'inc-comment.php',    // Comment rendering, emoji and AJAX submission.
	'inc-dashboard.php', // On-demand public activity panel.
	'inc-pet.php',        // Persistent shared pet growth and daily diary.
	'inc-pet-ui.php',     // Pet artwork, settings preview and front-end view.
	'inc-article.php',    // Article statistics and visit counting.
	'inc-reading.php',    // Reading tools, summary provenance and native block styles.
);

foreach ( $feng_modules as $feng_module ) {
	require_once get_template_directory() . '/inc/' . $feng_module;
}
unset( $feng_modules, $feng_module );

require_once get_theme_file_path('/inc/inc-home-profile.php');

require_once get_theme_file_path('/inc/inc-context-menu.php');
require_once get_theme_file_path('/inc/inc-travel.php');

require_once get_theme_file_path('/inc/inc-jieqi.php');

require_once get_theme_file_path('/inc/inc-mail.php');
require_once get_theme_file_path('/inc/inc-optimization.php');

require_once get_theme_file_path('/inc/inc-site-extras.php');

require_once get_theme_file_path('/inc/inc-greeting.php');

require_once get_theme_file_path('/inc/inc-visitor-stats.php');

require_once get_theme_file_path('/inc/inc-activity-calendar.php');

require_once get_theme_file_path('/inc/inc-weather.php');
