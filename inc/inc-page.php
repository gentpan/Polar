<?php
if (!defined('ABSPATH')) exit;

/**
 * Move saved page-template assignments when upgrading the development layout.
 * Only known old paths are changed; page content, slugs and IDs are preserved.
 */
function feng_migrate_page_templates() {
	$version = 'pages-v1';
	if ( get_option( 'feng_template_layout_version' ) === $version ) {
		return;
	}

	$complete = true;
	foreach ( array( 'archives', 'friends', 'about', 'guestbook' ) as $name ) {
		$old = 'page-templates/' . $name . '.php';
		$new = 'pages/' . $name . '.php';
		$page_ids = get_posts( array(
			'post_type'      => 'page',
			'post_status'    => array_keys( get_post_stati() ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_wp_page_template',
			'meta_value'     => $old,
		) );
		foreach ( $page_ids as $page_id ) {
			update_post_meta( $page_id, '_wp_page_template', $new, $old );
			if ( get_post_meta( $page_id, '_wp_page_template', true ) === $old ) {
				$complete = false;
			}
		}
	}
	if ( $complete ) {
		update_option( 'feng_template_layout_version', $version, false );
	}
}
add_action( 'init', 'feng_migrate_page_templates', 20 );

function feng_page_url($name) {
 $pages=get_posts(array('post_type'=>'page','post_status'=>'publish','meta_key'=>'_wp_page_template','meta_value'=>'pages/'.$name.'.php','numberposts'=>1));
 return $pages ? get_permalink($pages[0]) : '';
}
function feng_default_menu() {
 $categories=get_categories(array('hide_empty'=>true,'number'=>6));
 echo '<ul class="xf-menu"><li'.($categories?' class="menu-item-has-children"':'').'><a href="'.esc_url(home_url('/')).'"'.(is_front_page()?' aria-current="page"':'').'>首页</a>';
 if($categories) {
  echo '<ul class="sub-menu">';
  foreach($categories as $category) echo '<li><a href="'.esc_url(get_category_link($category->term_id)).'"'.(is_category($category->term_id)?' aria-current="page"':'').'>'.feng_category_badge($category->term_id).esc_html($category->name).'</a></li>';
  echo '</ul>';
 }
 echo '</li>';
 foreach(array('talks'=>'说说','friends'=>'友链','subscriptions'=>'订阅','about'=>'关于','guestbook'=>'留言') as $slug=>$label) {
  $url=feng_page_url($slug);if($url) echo '<li><a href="'.esc_url($url).'"'.(is_page_template('pages/'.$slug.'.php')?' aria-current="page"':'').'>'.esc_html($label).($slug==='subscriptions'?feng_feed_badge():'').'</a></li>';
 }
 echo '</ul>';
}
function feng_query_size($query) {
 if(is_admin() || !$query->is_main_query() || $query->is_singular()) return;
 if($query->is_home()) $query->set('posts_per_page',max(1,min(60,(int)feng_setting('home_posts_per_page',3))));
 elseif($query->is_archive() || $query->is_search()) $query->set('posts_per_page',max(1,min(60,(int)feng_setting('posts_per_page',9))));
}
add_action('pre_get_posts','feng_query_size');
function feng_archive_years() {
 global $wpdb;
 return $wpdb->get_results("SELECT YEAR(post_date) AS year, COUNT(ID) AS total FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish' GROUP BY YEAR(post_date) ORDER BY year DESC");
}
function feng_pagination($total=null,$current=null) {
 global $wp_query;
 $total=$total===null?$wp_query->max_num_pages:$total;
 $current=$current===null?max(1,get_query_var('paged'),get_query_var('page')):$current;
 if($total<2) return;
 $links=paginate_links(array('total'=>$total,'current'=>$current,'mid_size'=>1,'end_size'=>1,'type'=>'array','prev_text'=>feng_icon('pageprev').' 上一页','next_text'=>'下一页 '.feng_icon('pagenext')));
 if($links) echo '<nav class="feng-pagination" aria-label="文章分页"><span class="feng-pagination__count">'.esc_html($current.' / '.$total).'</span><div>'.implode('',$links).'</div></nav>';
}
function feng_page_title_icon() {
 $icons=array('pages/about.php'=>'user','pages/archives.php'=>'box-archive','pages/friends.php'=>'link','pages/guestbook.php'=>'comments','pages/subscriptions.php'=>'rss','pages/talks.php'=>'comment-dots');
 $icon=$icons[get_page_template_slug()]??'file-lines';
 return '<i class="fa-solid fa-'.esc_attr($icon).' feng-page-title-icon" aria-hidden="true"></i>';
}
function feng_page_heading($eyebrow,$intro='') {
 echo '<header class="feng-page-heading" data-xf-reveal><p class="xf-section-kicker">'.esc_html($eyebrow).'</p><h1>'.feng_page_title_icon().esc_html(get_the_title()).'</h1>';
 if($intro) echo '<p>'.nl2br(esc_html($intro)).'</p>';
 echo '</header>';
}

/** Archive filters use native WordPress rewrite rules, not query-string links. */
function feng_archive_filter_url( $filter = '', $page_id = 0 ) {
 $page_id = $page_id ?: get_queried_object_id();
 $base = get_permalink( $page_id );
 if ( ! is_scalar($filter) || ! preg_match('/^(?:[1-9][0-9]{3}|all)$/', (string)$filter) ) { return $base; }
 return user_trailingslashit( untrailingslashit( $base ) . '/' . $filter );
}
function feng_register_archive_routes() {
 $pages = get_posts( array(
  'post_type' => 'page', 'post_status' => 'publish', 'posts_per_page' => -1,
  'meta_key' => '_wp_page_template', 'meta_value' => 'pages/archives.php',
 ) );
 $routes = array();
 foreach ( $pages as $page ) {
  $path = get_page_uri( $page );
  if ( ! $path ) { continue; }
  $routes[$page->ID] = $path;
  add_rewrite_rule( '^' . preg_quote( $path, '#' ) . '/([1-9][0-9]{3}|all)/?$', 'index.php?page_id=' . $page->ID . '&feng_archive_year=$matches[1]', 'top' );
 }
 // Flush only when the route version, archive page path or permalink setting changes.
 $signature = md5( wp_json_encode( array( 'v1', $routes, get_option('permalink_structure') ) ) );
 if ( get_option( 'feng_archive_route_signature' ) !== $signature ) {
  update_option( 'feng_archive_route_signature', $signature, false );
  flush_rewrite_rules( false );
 }
}
add_action( 'init', 'feng_register_archive_routes', 30 );
add_filter( 'query_vars', static function( $vars ) { $vars[] = 'feng_archive_year'; return $vars; } );

function feng_archive_route_canonical() {
 if ( ! is_page_template( 'pages/archives.php' ) || is_preview() ) { return; }
 $filter = get_query_var( 'feng_archive_year', '' );
 $legacy = isset( $_GET['feng_year'] );
 if ( $legacy && '' === $filter ) { $filter = is_string($_GET['feng_year']) ? wp_unslash($_GET['feng_year']) : ''; }
 if ( ! is_string($filter) || ! preg_match('/^(?:[1-9][0-9]{3}|all)$/', $filter) ) { $filter = ''; }
 if ( ! $legacy && '' === $filter ) { return; }
 $url = feng_archive_filter_url( $filter );
 $actual = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
 if ( $legacy || wp_parse_url($actual, PHP_URL_PATH) !== wp_parse_url($url, PHP_URL_PATH) || isset($_GET['feng_archive_year']) ) {
  wp_safe_redirect( $url, 301 );
  exit;
 }
}
add_action( 'template_redirect', 'feng_archive_route_canonical', 9 );
// Core's singular-page canonical must not strip the archive filter segment.
add_filter( 'redirect_canonical', static function( $redirect ) {
 return is_page_template('pages/archives.php') && get_query_var('feng_archive_year') ? false : $redirect;
} );
add_filter( 'get_canonical_url', static function( $url, $post ) {
 $filter = get_query_var('feng_archive_year');
 return is_page_template('pages/archives.php') && $filter && (int)$post->ID === get_queried_object_id() ? feng_archive_filter_url($filter,$post->ID) : $url;
}, 10, 2 );


/** Public category carousel: category-specific page size, newest first. */
function feng_collection_query( $category, $page = 1 ) {
 $layout=feng_collection_layout($category);
 $size=$layout==='list'?((int)get_term_meta($category,'feng_collection_count',true)===3?3:5):($layout==='tiles'?4:(int)$layout);
 return new WP_Query(array('post_type'=>'post','post_status'=>'publish','cat'=>absint($category),'posts_per_page'=>$size,'paged'=>max(1,absint($page)),'orderby'=>array('date'=>'DESC','ID'=>'DESC'),'ignore_sticky_posts'=>true));
}
function feng_collection_ajax() {
 $category=isset($_GET['category']) && is_scalar($_GET['category']) ? absint($_GET['category']) : 0;
 $page=isset($_GET['page']) && is_scalar($_GET['page']) ? absint($_GET['page']) : 1;
 if(!$category || !term_exists($category,'category') || $page<1 || $page>100000) wp_send_json_error(array('message'=>'分类或页码无效。'),400);
 $query=feng_collection_query($category,$page);
 if(!$query->posts) wp_send_json_error(array('message'=>'没有更多文章了。'),404);
 ob_start();
 foreach($query->posts as $entry) get_template_part('template-parts/post-card',null,array('post'=>$entry,'collection'=>true));
 wp_send_json_success(array('html'=>ob_get_clean(),'pages'=>(int)$query->max_num_pages));
}
add_action('wp_ajax_feng_collection','feng_collection_ajax');
add_action('wp_ajax_nopriv_feng_collection','feng_collection_ajax');

/** The archive shortcut lives in the header tools, not twice in primary navigation. */
add_filter('wp_nav_menu_objects',function($items,$args){
 if(($args->theme_location??'')!=='primary') return $items;
 $archive=feng_page_url('archives');if(!$archive)return $items;
 $removed=array();
 foreach($items as $item) {
  $same_url=untrailingslashit($item->url)===untrailingslashit($archive);
  $archive_page=$item->object==='page' && get_page_template_slug((int)$item->object_id)==='pages/archives.php';
  if($same_url||$archive_page)$removed[$item->ID]=$item->menu_item_parent;
 }
 foreach($items as $item)if(isset($removed[$item->menu_item_parent]))$item->menu_item_parent=$removed[$item->menu_item_parent];
 return array_values(array_filter($items,static function($item)use($removed){return !isset($removed[$item->ID]);}));
},20,2);
function feng_random_article_id($exclude=0) {
 global $wpdb;
 $where="post_type='post' AND post_status='publish' AND post_password=''";
 $exclude=absint($exclude);
 $count=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(ID) FROM {$wpdb->posts} WHERE $where AND ID <> %d",$exclude));
 if(!$count && $exclude)return feng_random_article_id();
 if(!$count)return 0;
 return (int)$wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE $where AND ID <> %d ORDER BY ID LIMIT 1 OFFSET %d",$exclude,wp_rand(0,$count-1)));
}
function feng_random_article_ajax(){
 nocache_headers();
 $exclude=isset($_GET['exclude'])&&is_scalar($_GET['exclude'])?absint($_GET['exclude']):0;
 $id=feng_random_article_id($exclude);
 if(!$id)wp_send_json_error(array('message'=>'还没有可以随机阅读的文章。'),404);
 wp_send_json_success(array('url'=>get_permalink($id)));
}
add_action('wp_ajax_feng_random_article','feng_random_article_ajax');
add_action('wp_ajax_nopriv_feng_random_article','feng_random_article_ajax');
function feng_random_article_redirect(){
 nocache_headers();$id=feng_random_article_id();
 wp_safe_redirect($id?get_permalink($id):home_url('/'),302);exit;
}
add_action('admin_post_feng_random_article','feng_random_article_redirect');
add_action('admin_post_nopriv_feng_random_article','feng_random_article_redirect');

/** Public article hub: only published posts, five per page. */
function feng_hub_query($type='latest',$page=1,$category=0){
 $args=array('posts_per_page'=>5,'paged'=>max(1,min(10000,(int)$page)),'post_status'=>'publish','ignore_sticky_posts'=>true,'orderby'=>array('date'=>'DESC','ID'=>'DESC'));
 if($category)$args['cat']=$category;
 if($type==='comments')$args['orderby']=array('comment_count'=>'DESC','date'=>'DESC','ID'=>'DESC');
 if($type==='views'){
  $args['meta_query']=array('relation'=>'OR','view_count'=>array('key'=>'_feng_views','compare'=>'EXISTS','type'=>'NUMERIC'),'no_views'=>array('key'=>'_feng_views','compare'=>'NOT EXISTS'));
  $args['orderby']=array('view_count'=>'DESC','date'=>'DESC','ID'=>'DESC');
 }
 return new WP_Query($args);
}
function feng_hub_ajax(){
 $type=isset($_GET['type'])&&is_string($_GET['type'])?sanitize_key($_GET['type']):'latest';
 if(!in_array($type,array('latest','comments','views'),true))$type='latest';
 $page=max(1,absint($_GET['page']??1));$category=absint($_GET['category']??0);$query=feng_hub_query($type,$page,$category);
 $term=$category?get_term($category,'category'):(object)array('term_id'=>0,'name'=>'最新文章','slug'=>'all');if(!$term||is_wp_error($term))wp_send_json_error(null,404);
 ob_start();get_template_part('template-parts/collection-list',null,array('term'=>$term,'query'=>$query,'all'=>!$category,'type'=>$type,'page'=>$page));$html=ob_get_clean();
 wp_send_json_success(array('html'=>$html));
}
add_action('wp_ajax_feng_hub','feng_hub_ajax');
add_action('wp_ajax_nopriv_feng_hub','feng_hub_ajax');

/** Most-used public article tags within the selected category, including children. */
function feng_hub_keywords($category=0){
 global $wpdb;
 $category_sql='';
 if($category){
  $children=get_term_children($category,'category');
  $ids=array_merge(array((int)$category),is_wp_error($children)?array():array_map('intval',$children));
  $category_sql=" AND EXISTS (SELECT 1 FROM {$wpdb->term_relationships} cr JOIN {$wpdb->term_taxonomy} ct ON ct.term_taxonomy_id=cr.term_taxonomy_id WHERE cr.object_id=p.ID AND ct.taxonomy='category' AND ct.term_id IN (".implode(',',$ids)."))";
 }
 return $wpdb->get_results("SELECT t.term_id,t.name,COUNT(DISTINCT p.ID) AS articles FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} tt ON tt.term_id=t.term_id JOIN {$wpdb->term_relationships} tr ON tr.term_taxonomy_id=tt.term_taxonomy_id JOIN {$wpdb->posts} p ON p.ID=tr.object_id WHERE tt.taxonomy='post_tag' AND p.post_type='post' AND p.post_status='publish' AND p.post_password='' $category_sql GROUP BY t.term_id,t.name ORDER BY articles DESC,t.name ASC LIMIT 8");
}
