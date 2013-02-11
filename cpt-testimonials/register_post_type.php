<?php
/***
* Special Thanks To Devin Price
* This file is a modified of the original plugin found @https://github.com/devinsays/portfolio-post-type - Special Thanks!
***/

if ( ! class_exists( 'GF_Testimonial_Post_Type' ) ) :
class GF_Testimonial_Post_Type {

	// Current plugin version
	var $version = 1;

	function __construct() {

		// Runs when the plugin is activated
		register_activation_hook( __FILE__, array( &$this, 'plugin_activation' ) );

		// Add support for translations
		load_plugin_textdomain( 'symple', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

		// Adds the testimonials post type and taxonomies
		add_action( 'init', array( &$this, 'testimonial_init' ) );

		// Thumbnail support for testimonials posts
		add_theme_support( 'post-thumbnails', array( 'testimonial' ) );

		// Adds columns in the admin view for thumbnail and taxonomies
		add_filter( 'manage_edit-testimonial_columns', array( &$this, 'testimonial_edit_columns' ) );
		add_action( 'manage_posts_custom_column', array( &$this, 'testimonial_column_display' ), 10, 2 );

		// Allows filtering of posts by taxonomy in the admin view
		add_action( 'restrict_manage_posts', array( &$this, 'testimonial_add_taxonomy_filters' ) );

		// Show Testimnials post counts in the dashboard
		add_action( 'right_now_content_table_end', array( &$this, 'add_testimonial_counts' ) );

		// Add 32px icon
		add_action( 'admin_head', array( &$this, 'testimonial_icons' ) );
		
		// Change Enter Title Here
		add_filter('enter_title_here', array( &$this, 'filter_testimonial_title_text' ) );
		
		// Customize messaging
		add_filter( 'post_updated_messages', array( &$this, 'testimonial_updated_messages' ) );
	}

	/**
	 * Flushes rewrite rules on plugin activation to ensure testimonials posts don't 404
	 * http://codex.wordpress.org/Function_Reference/flush_rewrite_rules
	 */

	function plugin_activation() {
		$this->testimonial_init();
		flush_rewrite_rules();
	}

	function testimonial_init() {

		/**
		 * Enable the Testimonial custom post type
		 * http://codex.wordpress.org/Function_Reference/register_post_type
		 */

		$labels = array(
			'name' => __( 'Testimonial', 'symple' ),
			'singular_name' => __( 'Testimonial Item', 'symple' ),
			'add_new' => __( 'Add New Item', 'symple' ),
			'add_new_item' => __( 'Add New Testimonial Item', 'symple' ),
			'edit_item' => __( 'Edit Testimonial Item', 'symple' ),
			'new_item' => __( 'Add New Testimonial Item', 'symple' ),
			'view_item' => __( 'View Item', 'symple' ),
			'search_items' => __( 'Search Testimonial', 'symple' ),
			'not_found' => __( 'No Testimonial items found', 'symple' ),
			'not_found_in_trash' => __( 'No Testimonial items found in trash', 'symple' )
		);
		
		$args = array(
	    	'labels' => $labels,
	    	'public' => true,
			'supports' => array( 'title', 'editor', 'thumbnail', 'comments', 'revisions' ), // You can add 'title', 'editor', 'excerpt', 'thumbnail', 'comments', 'custom-fields', 'revisions'
			'capability_type' => 'post',
			'rewrite' => array("slug" => "testimonial"), // Permalinks format
			'has_archive' => true,
			'menu_icon' => plugin_dir_url( __FILE__ ) .'images/icon-testimonials.png'
		); 
		
		$args = apply_filters('symple_testimnials_args', $args);
		
		register_post_type( 'testimonial', $args );
		
		/**
		 * Register a taxonomy for Testimonials Categories
		 * http://codex.wordpress.org/Function_Reference/register_taxonomy
		 */

	    $taxonomy_testimonial_category_labels = array(
			'name' => _x( 'Testimonial Categories', 'symple' ),
			'singular_name' => _x( 'Testimonial Category', 'symple' ),
			'search_items' => _x( 'Search Testimonial Categories', 'symple' ),
			'popular_items' => _x( 'Popular Testimonial Categories', 'symple' ),
			'all_items' => _x( 'All Testimonial Categories', 'symple' ),
			'parent_item' => _x( 'Parent Testimonial Category', 'symple' ),
			'parent_item_colon' => _x( 'Parent Testimonial Category:', 'symple' ),
			'edit_item' => _x( 'Edit Testimonial Category', 'symple' ),
			'update_item' => _x( 'Update Testimonial Category', 'symple' ),
			'add_new_item' => _x( 'Add New Testimonial Category', 'symple' ),
			'new_item_name' => _x( 'New Testimonial Category Name', 'symple' ),
			'separate_items_with_commas' => _x( 'Separate Testimonial categories with commas', 'symple' ),
			'add_or_remove_items' => _x( 'Add or remove Testimonial categories', 'symple' ),
			'choose_from_most_used' => _x( 'Choose from the most used Testimonial categories', 'symple' ),
			'menu_name' => _x( 'Testimonial Categories', 'symple' ),
	    );

	    $taxonomy_testimonial_category_args = array(
			'labels' => $taxonomy_testimonial_category_labels,
			'public' => true,
			'show_in_nav_menus' => true,
			'show_ui' => true,
			'show_tagcloud' => true,
			'hierarchical' => true,
			'rewrite' => array( 'slug' => 'testimonials-category' ),
			'query_var' => true
	    );

		$taxonomy_testimonial_category_args = apply_filters('symple_taxonomy_testimonial_category_args', $taxonomy_testimonial_category_args);
		
	    register_taxonomy( 'testimonial_category', array( 'testimonial' ), $taxonomy_testimonial_category_args );

		/**
		 * Register a taxonomy for Testimonials Tags
		 * http://codex.wordpress.org/Function_Reference/register_taxonomy
		 */

		$taxonomy_testimonial_tag_labels = array(
			'name' => _x( 'Testimonial Tags', 'symple' ),
			'singular_name' => _x( 'Testimonial Tag', 'symple' ),
			'search_items' => _x( 'Search Testimonial Tags', 'symple' ),
			'popular_items' => _x( 'Popular Testimonial Tags', 'symple' ),
			'all_items' => _x( 'All Testimonial Tags', 'symple' ),
			'parent_item' => _x( 'Parent Testimonial Tag', 'symple' ),
			'parent_item_colon' => _x( 'Parent Testimonial Tag:', 'symple' ),
			'edit_item' => _x( 'Edit Testimonial Tag', 'symple' ),
			'update_item' => _x( 'Update Testimonial Tag', 'symple' ),
			'add_new_item' => _x( 'Add New Testimonial Tag', 'symple' ),
			'new_item_name' => _x( 'New Testimonial Tag Name', 'symple' ),
			'separate_items_with_commas' => _x( 'Separate Testimonial tags with commas', 'symple' ),
			'add_or_remove_items' => _x( 'Add or remove Testimonial tags', 'symple' ),
			'choose_from_most_used' => _x( 'Choose from the most used Testimonial tags', 'symple' ),
			'menu_name' => _x( 'Testimonial Tags', 'symple' )
		);

		$taxonomy_testimonial_tag_args = array(
			'labels' => $taxonomy_testimonial_tag_labels,
			'public' => true,
			'show_in_nav_menus' => true,
			'show_ui' => true,
			'show_tagcloud' => true,
			'hierarchical' => false,
			'rewrite' => array( 'slug' => 'testimonial-tag' ),
			'query_var' => true
		);

		$taxonomy_testimonial_tag_args = apply_filters('symple_taxonomy_testimonial_tag_args', $taxonomy_testimonial_tag_args);
		
		register_taxonomy( 'testimonial_tag', array( 'testimonial' ), $taxonomy_testimonial_tag_args );

	}

	/**
	 * Add Columns to Testimonial Edit Screen
	 * http://wptheming.com/2010/07/column-edit-pages/
	 */

	function testimonial_edit_columns( $testimonial_columns ) {
		$testimonial_columns = array(
			"cb" => "<input type=\"checkbox\" />",
			"title" => _x('Name', 'column name'),
			"testimonials_thumbnail" => __('Thumbnail', 'symple'),
			"testimonials_category" => __('Category', 'symple'),
			"testimonials_tag" => __('Tags', 'symple'),
			"author" => __('Author', 'symple'),
			"comments" => __('Comments', 'symple'),
			"date" => __('Date', 'symple'),
		);
		$testimonial_columns['comments'] = '<div class="vers"><img alt="Comments" src="' . esc_url( admin_url( 'images/comment-grey-bubble.png' ) ) . '" /></div>';
		return $testimonial_columns;
	}

	function testimonial_column_display( $testimonial_columns, $post_id ) {

		// Code from: http://wpengineer.com/display-post-thumbnail-post-page-overview

		switch ( $testimonial_columns ) {

			// Display the thumbnail in the column view
			case "testimonials_thumbnail":
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

			// Display the testimonials tags in the column view
			case "testimonial_category":

			if ( $category_list = get_the_term_list( $post_id, 'testimonial_category', '', ', ', '' ) ) {
				echo $category_list;
			} else {
				echo __('None', 'symple');
			}
			break;	

			// Display the testimonials tags in the column view
			case "testimonial_tag":

			if ( $tag_list = get_the_term_list( $post_id, 'testimonial_tag', '', ', ', '' ) ) {
				echo $tag_list;
			} else {
				echo __('None', 'symple');
			}
			break;			
		}
	}

	/**
	 * Adds taxonomy filters to the testimonials admin page
	 * Code artfully lifed from http://pippinsplugins.com
	 */

	function testimonial_add_taxonomy_filters() {
		global $typenow;

		// An array of all the taxonomyies you want to display. Use the taxonomy name or slug
		$taxonomies = array( 'testimonial_category', 'testimonial_tag' );

		// must set this to the post type you want the filter(s) displayed on
		if ( $typenow == 'testimonial' ) {

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
	 * Add Testimonials count to "Right Now" Dashboard Widget
	 */

	function add_testimonial_counts() {
	        if ( ! post_type_exists( 'testimonial' ) ) {
	             return;
	        }

	        $num_posts = wp_count_posts( 'testimonial' );
	        $num = number_format_i18n( $num_posts->publish );
	        $text = _n( 'Testimonial', 'Testimonial', intval($num_posts->publish) );
	        if ( current_user_can( 'edit_posts' ) ) {
	            $num = "<a href='edit.php?post_type=testimonial'>$num</a>";
	            $text = "<a href='edit.php?post_type=testimonial'>$text</a>";
	        }
	        echo '<td class="first b b-testimonials">' . $num . '</td>';
	        echo '<td class="t testimonials">' . $text . '</td>';
	        echo '</tr>';

	        if ($num_posts->pending > 0) {
	            $num = number_format_i18n( $num_posts->pending );
	            $text = _n( 'Testimonial Item Pending', 'Testimonial Items Pending', intval($num_posts->pending) );
	            if ( current_user_can( 'edit_posts' ) ) {
	                $num = "<a href='edit.php?post_status=pending&post_type=testimonial'>$num</a>";
	                $text = "<a href='edit.php?post_status=pending&post_type=testimonial'>$text</a>";
	            }
	            echo '<td class="first b b-testimonials">' . $num . '</td>';
	            echo '<td class="t testimonials">' . $text . '</td>';

	            echo '</tr>';
	        }
	}

		/**
		 * Add Custom Icon (32px)
		 */
		 function testimonial_icons() {
		 ?>
		 <style type="text/css" media="screen">
			 #icon-edit.icon32-posts-testimonial {background: url(<?php echo plugin_dir_url( __FILE__ ); ?>/images/testimonials-32x32.png) no-repeat;}
		</style>
		<?php
		}
		
		/*
		 * Change "Enter Title Here"
		 */
		 function filter_testimonial_title_text($title)
		 {
			 $scr = get_current_screen();
			 	if ('testimonial' == $scr->post_type)
			 	$title = 'Enter Name Here';
			 return ($title);
			 }
			 
		/*
		 * Customize Messeging
		 */

		 function testimonial_updated_messages( $messages ) {
			 global $post, $post_ID;

			 $messages['testimonial'] = array(
			   0 => '', // Unused. Messages start at index 1.
			   1 => sprintf( __('Testimonial updated. <a href="%s">View Testimonial</a>', 'your_text_domain'), esc_url( get_permalink($post_ID) ) ),
			   2 => __('Custom field updated.', 'your_text_domain'),
			   3 => __('Custom field deleted.', 'your_text_domain'),
			   4 => __('Testimonial updated.', 'your_text_domain'),
			   /* translators: %s: date and time of the revision */
			   5 => isset($_GET['revision']) ? sprintf( __('Testimonial restored to revision from %s', 'your_text_domain'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			   6 => sprintf( __('Testimonial published. <a href="%s">View Testimonial</a>', 'your_text_domain'), esc_url( get_permalink($post_ID) ) ),
			   7 => __('Testimonial saved.', 'your_text_domain'),
			   8 => sprintf( __('Testimonial submitted. <a target="_blank" href="%s">Preview Testimonial</a>', 'your_text_domain'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			   9 => sprintf( __('Testimonial scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Testimonial</a>', 'your_text_domain'),
			     // translators: Publish box date format, see http://php.net/date
			     date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
			   10 => sprintf( __('Testimonial draft updated. <a target="_blank" href="%s">Preview Testimonial</a>', 'your_text_domain'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			 );
			 
			 return $messages;
			 }

}

new GF_Testimonial_Post_Type;

endif;