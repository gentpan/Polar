<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
function feng_asset_version( $path ) {
 $file = get_theme_file_path( $path );
 return is_file( $file ) ? (string) filemtime( $file ) : wp_get_theme()->get( 'Version' );
}
function feng_enqueue_assets() {
 wp_enqueue_style('feng-space-grotesk','https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap',array(),null);
 wp_enqueue_style('feng-code-font',get_theme_file_uri('/assets/css/code-font.css'),array('feng-article'),feng_asset_version('/assets/css/code-font.css'));
 wp_enqueue_style( 'feng-brand-font', 'https://static.bluecdn.com/fonts/alimama-fangyuanti.css', array(), null );
 if ( is_home() || is_front_page() ) {
  wp_enqueue_style( 'feng-hero-font', 'https://static.bluecdn.com/fonts/zzqxmxht.css', array(), null );
 }
 if ( is_singular( 'post' ) ) {
  wp_enqueue_style( 'feng-title-font', 'https://static.bluecdn.com/fonts/kuaikanshijieti.css', array(), null );
 }
 wp_enqueue_style( 'feng', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );
 wp_enqueue_style( 'xf-tokens', get_theme_file_uri( '/assets/css/tokens.css' ), array( 'feng' ), feng_asset_version( '/assets/css/tokens.css' ) );
 wp_enqueue_style( 'xf-base', get_theme_file_uri( '/assets/css/base.css' ), array( 'xf-tokens' ), feng_asset_version( '/assets/css/base.css' ) );
 wp_enqueue_style( 'xf-layout', get_theme_file_uri( '/assets/css/layout.css' ), array( 'xf-base' ), feng_asset_version( '/assets/css/layout.css' ) );
 wp_enqueue_style('feng-pages',get_theme_file_uri('/assets/css/pages.css'),array('xf-layout'),feng_asset_version('/assets/css/pages.css'));
 wp_enqueue_style( 'feng-typography', get_theme_file_uri('/assets/css/typography.css'), array('feng-pages','feng-brand-font'), feng_asset_version('/assets/css/typography.css') );
 wp_enqueue_style('feng-cards',get_theme_file_uri('/assets/css/cards.css'),array('feng-typography'),feng_asset_version('/assets/css/cards.css'));
 wp_enqueue_style('feng-article',get_theme_file_uri('/assets/css/article.css'),array('feng-typography'),feng_asset_version('/assets/css/article.css'));
 if ( is_page_template( 'pages/archives.php' ) ) {
  wp_enqueue_style( 'feng-archives', get_theme_file_uri('/assets/css/archives.css'), array('feng-article'), feng_asset_version('/assets/css/archives.css') );
 }
 wp_enqueue_style('feng-dashboard',get_theme_file_uri('/assets/css/dashboard.css'),array('feng-article'),feng_asset_version('/assets/css/dashboard.css'));
 wp_enqueue_style('feng-navigation',get_theme_file_uri('/assets/css/navigation.css'),array('feng-pages'),feng_asset_version('/assets/css/navigation.css'));
 wp_enqueue_script('feng-navigation',get_theme_file_uri('/assets/js/navigation.js'),array('xf-app'),feng_asset_version('/assets/js/navigation.js'),array('strategy'=>'defer','in_footer'=>true));
 wp_enqueue_script('feng-dashboard',get_theme_file_uri('/assets/js/dashboard.js'),array('xf-app'),feng_asset_version('/assets/js/dashboard.js'),array('strategy'=>'defer','in_footer'=>true));
 wp_enqueue_script('feng-toast',get_theme_file_uri('/assets/js/toast.js'),array(),feng_asset_version('/assets/js/toast.js'),true);
 wp_enqueue_script( 'xf-app', get_theme_file_uri( '/assets/js/app.js' ), array(), feng_asset_version( '/assets/js/app.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
 // Keep core reply runtime available when navigating into a comment thread.
 if ( get_option( 'thread_comments' ) ) { wp_enqueue_script( 'comment-reply' ); }
}
add_action( 'wp_enqueue_scripts', 'feng_enqueue_assets' );
function feng_runtime_marker() {
 printf('<meta name="feng-scheme" content="%s"><meta name="feng-reveal" content="on">',esc_attr(feng_setting('default_scheme','system')));
 printf( '<meta name="xf-navigation" content="%s">', ! is_customize_preview() ? 'on' : 'off' );
}
add_action( 'wp_head', 'feng_runtime_marker', 1 );

/** Non-executable front-end configuration; no named inline JavaScript sources. */
function feng_frontend_config() {
 $icons=array();
 foreach(array('check','chevron-down','play','pause') as $name) $icons[$name]=feng_icon($name);
 $config=array('icons'=>$icons,'endpoint'=>admin_url('admin-ajax.php'),'commentLocationEndpoint'=>rest_url('feng/v1/comment-location/'));
 $config['greeting']=array('enabled'=>(bool)feng_setting('greeting_enabled',true),'pet'=>(bool)feng_setting('greeting_pet',true),'weather'=>(bool)feng_setting('greeting_weather',false),'service'=>feng_setting('greeting_weather_service','openmeteo'),'site'=>get_bloginfo('name'));
 if(feng_setting('pet_enabled',true)) $config['pet']=array('url'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('feng_pet'));
 echo '<script type="application/json" id="feng-config">'.wp_json_encode($config,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT).'</script>';
}
add_action('wp_head','feng_frontend_config',5);

/** Resolve existing feature registrations into the consolidated front-end assets. */
add_action('wp_enqueue_scripts',function(){
 $manifest=json_decode(file_get_contents(get_theme_file_path('/inc/asset-bundles.json')),true);
 if(!$manifest)return;
 $styles=wp_styles();$scripts=wp_scripts();$enabled=array();
 foreach($manifest['styles'] as $handle=>$source){
  if(isset($styles->registered[$handle]))$styles->registered[$handle]->src=false;
 }
 foreach($manifest['scripts'] as $handle=>$source){
  if(isset($scripts->registered[$handle])){
   if(wp_script_is($handle,'enqueued'))$enabled[]=$handle;
   $scripts->registered[$handle]->src=false;
  }
 }
 wp_enqueue_style('polar-theme',get_theme_file_uri('/assets/css/main.css'),array('feng-fontawesome-pro'),feng_asset_version('/assets/css/main.css'));
 $dependencies=array_values(array_filter($scripts->queue,fn($handle)=>$handle!=='polar-theme'));
 wp_enqueue_script('polar-theme',get_theme_file_uri('/assets/js/main.js'),$dependencies,feng_asset_version('/assets/js/main.js'),true);
 wp_add_inline_script('polar-theme','window.polarEnabledScripts='.wp_json_encode($enabled).';','before');
},PHP_INT_MAX);

/** The dashboard keeps its own small bundle and only runs enqueued modules. */
add_action('admin_enqueue_scripts',function(){
 $scripts=wp_scripts();$enabled=array();$deps=array();
 foreach(array('feng-admin','feng-ai-admin','feng-category-badge','feng-feed-admin','feng-music-admin','feng-travel-editor') as $handle){
  if(isset($scripts->registered[$handle])){
   if(wp_script_is($handle,'enqueued')){$enabled[]=$handle;$deps[]=$handle;}
   $scripts->registered[$handle]->src=false;
  }
 }
 if($enabled){wp_enqueue_script('polar-admin',get_theme_file_uri('/assets/js/admin.js'),$deps,feng_asset_version('/assets/js/admin.js'),true);wp_add_inline_script('polar-admin','window.polarEnabledAdminScripts='.wp_json_encode($enabled).';','before');}
 $styles=wp_styles();
 if(isset($styles->registered['feng-admin-square'])){$styles->registered['feng-admin-square']->src=false;if(!wp_style_is('feng-admin','enqueued'))wp_enqueue_style('feng-admin',get_theme_file_uri('/assets/css/admin.css'),array(),feng_asset_version('/assets/css/admin.css'));}
},PHP_INT_MAX);
