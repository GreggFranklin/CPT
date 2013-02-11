<?php
/***
* Special Thanks To Devin Price
* This file is a modified of the original plugin found @https://github.com/devinsays/portfolio-post-type - Special Thanks!
***/

if ( ! class_exists( 'GF_Services_Post_Type' ) ) :
class GF_Services_Post_Type {

	// Current plugin version
	var $version = 1;

	function __construct() {

		// Runs when the plugin is activated
		register_activation_hook( __FILE__, array( &$this, 'plugin_activation' ) );

		// Add support for translations
		load_plugin_textdomain( 'symple', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

		// Adds the services post type and taxonomies
		add_action( 'init', array( &$this, 'services_init' ) );

		// Thumbnail support for services posts
		add_theme_support( 'post-thumbnails', array( 'services' ) );

		// Adds columns in the admin view for thumbnail and taxonomies
		add_filter( 'manage_edit-services_columns', array( &$this, 'services_edit_columns' ) );
		add_action( 'manage_posts_custom_column', array( &$this, 'services_column_display' ), 10, 2 );

		// Allows filtering of posts by taxonomy in the admin view
		add_action( 'restrict_manage_posts', array( &$this, 'services_add_taxonomy_filters' ) );

		// Show services post counts in the dashboard
		add_action( 'right_now_content_table_end', array( &$this, 'add_services_counts' ) );

		// Add 32px icon
		add_action( 'admin_head', array( &$this, 'service_icons' ) );
		
		// Change Enter Title Here
		add_filter('enter_title_here', array( &$this, 'filter_service_title_text' ) );
		
		// Customize messaging
		add_filter( 'post_updated_messages', array( &$this, 'service_updated_messages' ) );
	}

	/**
	 * Flushes rewrite rules on plugin activation to ensure services posts don't 404
	 * http://codex.wordpress.org/Function_Reference/flush_rewrite_rules
	 */

	function plugin_activation() {
		$this->services_init();
		flush_rewrite_rules();
	}

	function services_init() {

		/**
		 * Enable the Services custom post type
		 * http://codex.wordpress.org/Function_Reference/register_post_type
		 */

		$labels = array(
			'name' => __( 'Services', 'symple' ),
			'singular_name' => __( 'Services Item', 'symple' ),
			'add_new' => __( 'Add New Item', 'symple' ),
			'add_new_item' => __( 'Add New Services Item', 'symple' ),
			'edit_item' => __( 'Edit Services Item', 'symple' ),
			'new_item' => __( 'Add New Services Item', 'symple' ),
			'view_item' => __( 'View Item', 'symple' ),
			'search_items' => __( 'Search Services', 'symple' ),
			'not_found' => __( 'No services items found', 'symple' ),
			'not_found_in_trash' => __( 'No services items found in trash', 'symple' )
		);
		
		$args = array(
	    	'labels' => $labels,
	    	'public' => true,
			'supports' => array( 'title', 'editor', 'thumbnail', 'comments', 'revisions' ), // You can add 'title', 'editor', 'excerpt', 'thumbnail', 'comments', 'custom-fields', 'revisions'
			'capability_type' => 'post',
			'rewrite' => array("slug" => "services"), // Permalinks format
			'has_archive' => true,
			'menu_icon' => plugin_dir_url( __FILE__ ) .'images/icon-services.png'
		); 
		
		$args = apply_filters('symple_services_args', $args);
		
		register_post_type( 'services', $args );
		
		/**
		 * Register a taxonomy for Services Categories
		 * http://codex.wordpress.org/Function_Reference/register_taxonomy
		 */

	    $taxonomy_services_category_labels = array(
			'name' => _x( 'Services Categories', 'symple' ),
			'singular_name' => _x( 'Services Category', 'symple' ),
			'search_items' => _x( 'Search Services Categories', 'symple' ),
			'popular_items' => _x( 'Popular Services Categories', 'symple' ),
			'all_items' => _x( 'All Services Categories', 'symple' ),
			'parent_item' => _x( 'Parent Services Category', 'symple' ),
			'parent_item_colon' => _x( 'Parent Services Category:', 'symple' ),
			'edit_item' => _x( 'Edit Services Category', 'symple' ),
			'update_item' => _x( 'Update Services Category', 'symple' ),
			'add_new_item' => _x( 'Add New Services Category', 'symple' ),
			'new_item_name' => _x( 'New Services Category Name', 'symple' ),
			'separate_items_with_commas' => _x( 'Separate services categories with commas', 'symple' ),
			'add_or_remove_items' => _x( 'Add or remove services categories', 'symple' ),
			'choose_from_most_used' => _x( 'Choose from the most used services categories', 'symple' ),
			'menu_name' => _x( 'Services Categories', 'symple' ),
	    );

	    $taxonomy_services_category_args = array(
			'labels' => $taxonomy_services_category_labels,
			'public' => true,
			'show_in_nav_menus' => true,
			'show_ui' => true,
			'show_tagcloud' => true,
			'hierarchical' => true,
			'rewrite' => array( 'slug' => 'services-category' ),
			'query_var' => true
	    );

		$taxonomy_services_category_args = apply_filters('symple_taxonomy_services_category_args', $taxonomy_services_category_args);
		
	    register_taxonomy( 'services_category', array( 'services' ), $taxonomy_services_category_args );

		/**
		 * Register a taxonomy for Services Tags
		 * http://codex.wordpress.org/Function_Reference/register_taxonomy
		 */

		$taxonomy_services_tag_labels = array(
			'name' => _x( 'Services Tags', 'symple' ),
			'singular_name' => _x( 'Services Tag', 'symple' ),
			'search_items' => _x( 'Search Services Tags', 'symple' ),
			'popular_items' => _x( 'Popular Services Tags', 'symple' ),
			'all_items' => _x( 'All Services Tags', 'symple' ),
			'parent_item' => _x( 'Parent Services Tag', 'symple' ),
			'parent_item_colon' => _x( 'Parent Services Tag:', 'symple' ),
			'edit_item' => _x( 'Edit Services Tag', 'symple' ),
			'update_item' => _x( 'Update Services Tag', 'symple' ),
			'add_new_item' => _x( 'Add New Services Tag', 'symple' ),
			'new_item_name' => _x( 'New Services Tag Name', 'symple' ),
			'separate_items_with_commas' => _x( 'Separate services tags with commas', 'symple' ),
			'add_or_remove_items' => _x( 'Add or remove services tags', 'symple' ),
			'choose_from_most_used' => _x( 'Choose from the most used services tags', 'symple' ),
			'menu_name' => _x( 'Services Tags', 'symple' )
		);

		$taxonomy_services_tag_args = array(
			'labels' => $taxonomy_services_tag_labels,
			'public' => true,
			'show_in_nav_menus' => true,
			'show_ui' => true,
			'show_tagcloud' => true,
			'hierarchical' => false,
			'rewrite' => array( 'slug' => 'services-tag' ),
			'query_var' => true
		);

		$taxonomy_services_tag_args = apply_filters('symple_taxonomy_services_tag_args', $taxonomy_services_tag_args);
		
		register_taxonomy( 'services_tag', array( 'services' ), $taxonomy_services_tag_args );

	}

	/**
	 * Add Columns to Services Edit Screen
	 * http://wptheming.com/2010/07/column-edit-pages/
	 */

	function services_edit_columns( $services_columns ) {
		$services_columns = array(
			"cb" => "<input type=\"checkbox\" />",
			"title" => _x('Title', 'column name'),
			"services_thumbnail" => __('Thumbnail', 'symple'),
			"services_category" => __('Category', 'symple'),
			"services_tag" => __('Tags', 'symple'),
			"author" => __('Author', 'symple'),
			"comments" => __('Comments', 'symple'),
			"date" => __('Date', 'symple'),
		);
		$services_columns['comments'] = '<div class="vers"><img alt="Comments" src="' . esc_url( admin_url( 'images/comment-grey-bubble.png' ) ) . '" /></div>';
		return $services_columns;
	}

	function services_column_display( $services_columns, $post_id ) {

		// Code from: http://wpengineer.com/display-post-thumbnail-post-page-overview

		switch ( $services_columns ) {

			// Display the thumbnail in the column view
			case "services_thumbnail":
				$width = (int) 80;
				$height = (int) 80;
				$thumbnail_id = get_post_meta( $post_id, '_thumbnail_id', true );

				// Display the featured image in the column view if possible
				if ( $thumbnail_id ) {
					$thumb = wp_get_attachment_image( $thumbnail_id, array($width, $height), true );
				}
				if ( isset( $thumb ) ) {
					echo $thumb;
				} else {
					echo __('None', 'symple');
				}
				break;	

			// Display the services tags in the column view
			case "services_category":

			if ( $category_list = get_the_term_list( $post_id, 'services_category', '', ', ', '' ) ) {
				echo $category_list;
			} else {
				echo __('None', 'symple');
			}
			break;	

			// Display the services tags in the column view
			case "services_tag":

			if ( $tag_list = get_the_term_list( $post_id, 'services_tag', '', ', ', '' ) ) {
				echo $tag_list;
			} else {
				echo __('None', 'symple');
			}
			break;			
		}
	}

	/**
	 * Adds taxonomy filters to the services admin page
	 * Code artfully lifed from http://pippinsplugins.com
	 */

	function services_add_taxonomy_filters() {
		global $typenow;

		// An array of all the taxonomyies you want to display. Use the taxonomy name or slug
		$taxonomies = array( 'services_category', 'services_tag' );

		// must set this to the post type you want the filter(s) displayed on
		if ( $typenow == 'services' ) {

			foreach ( $taxonomies as $tax_slug ) {
				$current_tax_slug = isset( $_GET[$tax_slug] ) ? $_GET[$tax_slug] : false;
				$tax_obj = get_taxonomy( $tax_slug );
				$tax_name = $tax_obj->labels->name;
				$terms = get_terms($tax_slug);
				if ( count( $terms ) > 0) {
					echo "<select name='$tax_slug' id='$tax_slug' class='postform'>";
					echo "<option value=''>$tax_name</option>";
					foreach ( $terms as $term ) {
						echo '<option value=' . $term->slug, $current_tax_slug == $term->slug ? ' selected="selected"' : '','>' . $term->name .' (' . $term->count .')</option>';
					}
					echo "</select>";
				}
			}
		}
	}

	/**
	 * Add Services count to "Right Now" Dashboard Widget
	 */

	function add_services_counts() {
	        if ( ! post_type_exists( 'services' ) ) {
	             return;
	        }

	        $num_posts = wp_count_posts( 'services' );
	        $num = number_format_i18n( $num_posts->publish );
	        $text = _n( 'Services', 'Services', intval($num_posts->publish) );
	        if ( current_user_can( 'edit_posts' ) ) {
	            $num = "<a href='edit.php?post_type=services'>$num</a>";
	            $text = "<a href='edit.php?post_type=services'>$text</a>";
	        }
	        echo '<td class="first b b-services">' . $num . '</td>';
	        echo '<td class="t services">' . $text . '</td>';
	        echo '</tr>';

	        if ($num_posts->pending > 0) {
	            $num = number_format_i18n( $num_posts->pending );
	            $text = _n( 'Services Item Pending', 'Services Items Pending', intval($num_posts->pending) );
	            if ( current_user_can( 'edit_posts' ) ) {
	                $num = "<a href='edit.php?post_status=pending&post_type=services'>$num</a>";
	                $text = "<a href='edit.php?post_status=pending&post_type=services'>$text</a>";
	            }
	            echo '<td class="first b b-services">' . $num . '</td>';
	            echo '<td class="t services">' . $text . '</td>';

	            echo '</tr>';
	        }
	}

		/**
		 * Add Custom Icon (32px)
		 */
		 function service_icons() {
		 ?>
		 <style type="text/css" media="screen">
			 #icon-edit.icon32-posts-services {background: url(<?php echo plugin_dir_url( __FILE__ ); ?>/images/services-32x32.png) no-repeat;}
		</style>
		<?php
		}
		
		/*
		 * Change "Enter Title Here"
		 */
		 function filter_service_title_text($title)
		 {
			 $scr = get_current_screen();
			 	if ('services' == $scr->post_type)
			 	$title = 'Enter Service Name';
			 return ($title);
			 }
			 
		/*
		 * Customize Messeging
		 */

		 function service_updated_messages( $messages ) {
			 global $post, $post_ID;

			 $messages['services'] = array(
			   0 => '', // Unused. Messages start at index 1.
			   1 => sprintf( __('Service updated. <a href="%s">View Service</a>', 'your_text_domain'), esc_url( get_permalink($post_ID) ) ),
			   2 => __('Custom field updated.', 'your_text_domain'),
			   3 => __('Custom field deleted.', 'your_text_domain'),
			   4 => __('Service updated.', 'your_text_domain'),
			   /* translators: %s: date and time of the revision */
			   5 => isset($_GET['revision']) ? sprintf( __('Service restored to revision from %s', 'your_text_domain'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			   6 => sprintf( __('Service published. <a href="%s">View book</a>', 'your_text_domain'), esc_url( get_permalink($post_ID) ) ),
			   7 => __('Service saved.', 'your_text_domain'),
			   8 => sprintf( __('Service submitted. <a target="_blank" href="%s">Preview Service</a>', 'your_text_domain'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			   9 => sprintf( __('Service scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Service</a>', 'your_text_domain'),
			     // translators: Publish box date format, see http://php.net/date
			     date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
			   10 => sprintf( __('Service draft updated. <a target="_blank" href="%s">Preview Service</a>', 'your_text_domain'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			 );
			 
			 return $messages;
			 }

}

new GF_Services_Post_Type;

endif;