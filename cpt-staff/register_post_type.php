<?php
/***
* Special Thanks To Devin Price
* This file is a modified of the original plugin found @https://github.com/devinsays/portfolio-post-type - Special Thanks!
***/

if ( ! class_exists( 'GF_Staff_Post_Type' ) ) :
class GF_Staff_Post_Type {

	// Current plugin version
	var $version = 1;

	function __construct() {

		// Runs when the plugin is activated
		register_activation_hook( __FILE__, array( &$this, 'plugin_activation' ) );

		// Add support for translations
		load_plugin_textdomain( 'symple', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

		// Adds the staff post type and taxonomies
		add_action( 'init', array( &$this, 'staff_init' ) );

		// Thumbnail support for staff posts
		add_theme_support( 'post-thumbnails', array( 'staff' ) );

		// Adds columns in the admin view for thumbnail and taxonomies
		add_filter( 'manage_edit-staff_columns', array( &$this, 'staff_edit_columns' ) );
		add_action( 'manage_posts_custom_column', array( &$this, 'staff_column_display' ), 10, 2 );

		// Allows filtering of posts by taxonomy in the admin view
		add_action( 'restrict_manage_posts', array( &$this, 'staff_add_taxonomy_filters' ) );

		// Show staff post counts in the dashboard
		add_action( 'right_now_content_table_end', array( &$this, 'add_staff_counts' ) );

		// Add 32px icon
		add_action( 'admin_head', array( &$this, 'staff_icons' ) );
		
		// Change Enter Title Here
		add_filter('enter_title_here', array( &$this, 'filter_staff_title_text' ) );
		
		// Customize messaging
		add_filter( 'post_updated_messages', array( &$this, 'staff_updated_messages' ) );
	}

	/**
	 * Flushes rewrite rules on plugin activation to ensure staff posts don't 404
	 * http://codex.wordpress.org/Function_Reference/flush_rewrite_rules
	 */

	function plugin_activation() {
		$this->staff_init();
		flush_rewrite_rules();
	}

	function staff_init() {

		/**
		 * Enable the Staff custom post type
		 * http://codex.wordpress.org/Function_Reference/register_post_type
		 */

		$labels = array(
			'name' => __( 'Staff', 'symple' ),
			'singular_name' => __( 'Staff Member', 'symple' ),
			'add_new' => __( 'Add New Member', 'symple' ),
			'add_new_item' => __( 'Add New Staff Member', 'symple' ),
			'edit_item' => __( 'Edit Staff Member', 'symple' ),
			'new_item' => __( 'Add New Staff Member', 'symple' ),
			'view_item' => __( 'View Member', 'symple' ),
			'search_items' => __( 'Search Staff', 'symple' ),
			'not_found' => __( 'No staff Members found', 'symple' ),
			'not_found_in_trash' => __( 'No staff Members found in trash', 'symple' )
		);
		
		$args = array(
	    	'labels' => $labels,
	    	'public' => true,
			'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'comments', 'custom-fields', 'revisions' ),
			'capability_type' => 'post',
			'rewrite' => array("slug" => "staff"), // Permalinks format
			'has_archive' => true,
			'menu_icon' => plugin_dir_url( __FILE__ ) .'images/icon-staff.png'
		); 
		
		$args = apply_filters('symple_staff_args', $args);
		
		register_post_type( 'staff', $args );

		/**
		 * Register a taxonomy for Staff Categories
		 * http://codex.wordpress.org/Function_Reference/register_taxonomy
		 */

	    $taxonomy_staff_category_labels = array(
			'name' => _x( 'Departments', 'symple' ),
			'singular_name' => _x( 'Department', 'symple' ),
			'search_items' => _x( 'Search Departments', 'symple' ),
			'popular_items' => _x( 'Popular Departments', 'symple' ),
			'all_items' => _x( 'All Departments', 'symple' ),
			'parent_item' => _x( 'Parent Department', 'symple' ),
			'parent_item_colon' => _x( 'Department:', 'symple' ),
			'edit_item' => _x( 'Edit Department', 'symple' ),
			'update_item' => _x( 'Update Department', 'symple' ),
			'add_new_item' => _x( 'Add New Department', 'symple' ),
			'new_item_name' => _x( 'New Department', 'symple' ),
			'separate_items_with_commas' => _x( 'Separate Departments with commas', 'symple' ),
			'add_or_remove_items' => _x( 'Add or remove Departments', 'symple' ),
			'choose_from_most_used' => _x( 'Choose from the most used Departments', 'symple' ),
			'menu_name' => _x( 'Departments', 'symple' ),
	    );

	    $taxonomy_staff_category_args = array(
			'labels' => $taxonomy_staff_category_labels,
			'public' => true,
			'show_in_nav_menus' => true,
			'show_ui' => true,
			'show_tagcloud' => true,
			'hierarchical' => true,
			'rewrite' => array( 'slug' => 'department' ),
			'query_var' => true
	    );

		$taxonomy_staff_category_args = apply_filters('symple_taxonomy_staff_category_args', $taxonomy_staff_category_args);
		
	    register_taxonomy( 'department', array( 'staff' ), $taxonomy_staff_category_args );

	}

	/**
	 * Add Columns to Staff Edit Screen
	 * http://wptheming.com/2010/07/column-edit-pages/
	 */

	function staff_edit_columns( $staff_columns ) {
		$staff_columns = array(
			"cb" => "<input type=\"checkbox\" />",
			"title" => _x('Name', 'column name'),
			"staff_thumbnail" => __('Photo', 'symple'),
			"department" => __('Department', 'symple'),
			"author" => __('Author', 'symple'),
			"comments" => __('Comments', 'symple'),
			"date" => __('Date', 'symple'),
		);
		$staff_columns['comments'] = '<div class="vers"><img alt="Comments" src="' . esc_url( admin_url( 'images/comment-grey-bubble.png' ) ) . '" /></div>';
		return $staff_columns;
	}

	function staff_column_display( $staff_columns, $post_id ) {

		// Code from: http://wpengineer.com/display-post-thumbnail-post-page-overview

		switch ( $staff_columns ) {

			// Display the thumbnail in the column view
			case "staff_thumbnail":
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

			// Display the staff tags in the column view
			case "department":

			if ( $category_list = get_the_term_list( $post_id, 'department', '', ', ', '' ) ) {
				echo $category_list;
			} else {
				echo __('None', 'symple');
			}
			break;	
		
		}
	}

	/**
	 * Adds taxonomy filters to the staff admin page
	 * Code artfully lifed from http://pippinsplugins.com
	 */

	function staff_add_taxonomy_filters() {
		global $typenow;

		// An array of all the taxonomyies you want to display. Use the taxonomy name or slug
		$taxonomies = array( 'department' );

		// must set this to the post type you want the filter(s) displayed on
		if ( $typenow == 'staff' ) {

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
	 * Add Staff count to "Right Now" Dashboard Widget
	 */

	function add_staff_counts() {
	        if ( ! post_type_exists( 'staff' ) ) {
	             return;
	        }

	        $num_posts = wp_count_posts( 'staff' );
	        $num = number_format_i18n( $num_posts->publish );
	        $text = _n( 'Staff Members', 'Staff Members', intval($num_posts->publish) );
	        if ( current_user_can( 'edit_posts' ) ) {
	            $num = "<a href='edit.php?post_type=staff'>$num</a>";
	            $text = "<a href='edit.php?post_type=staff'>$text</a>";
	        }
	        echo '<td class="first b b-staff">' . $num . '</td>';
	        echo '<td class="t staff">' . $text . '</td>';
	        echo '</tr>';

	        if ($num_posts->pending > 0) {
	            $num = number_format_i18n( $num_posts->pending );
	            $text = _n( 'Staff Item Pending', 'Staff Items Pending', intval($num_posts->pending) );
	            if ( current_user_can( 'edit_posts' ) ) {
	                $num = "<a href='edit.php?post_status=pending&post_type=staff'>$num</a>";
	                $text = "<a href='edit.php?post_status=pending&post_type=staff'>$text</a>";
	            }
	            echo '<td class="first b b-staff">' . $num . '</td>';
	            echo '<td class="t staff">' . $text . '</td>';

	            echo '</tr>';
	        }
	}

		/**
		 * Add Custom Icon (32px)
		 */
		 function staff_icons() {
		 ?>
		 <style type="text/css" media="screen">
			 #icon-edit.icon32-posts-staff {background: url(<?php echo plugin_dir_url( __FILE__ ); ?>/images/staff-32x32.png) no-repeat;}
		</style>
		<?php
		}
		
		/*
		 * Change "Enter Title Here"
		 */
		 function filter_staff_title_text($title)
		 {
			 $scr = get_current_screen();
			 	if ('staff' == $scr->post_type)
			 	$title = 'Enter Staff Name';
			 return ($title);
			 }
			 
		/*
		 * Customize Messeging
		 */

		 function staff_updated_messages( $messages ) {
			 global $post, $post_ID;

			 $messages['staff'] = array(
			   0 => '', // Unused. Messages start at index 1.
			   1 => sprintf( __('Staff updated. <a href="%s">View Staff</a>', 'your_text_domain'), esc_url( get_permalink($post_ID) ) ),
			   2 => __('Custom field updated.', 'your_text_domain'),
			   3 => __('Custom field deleted.', 'your_text_domain'),
			   4 => __('Staff updated.', 'your_text_domain'),
			   /* translators: %s: date and time of the revision */
			   5 => isset($_GET['revision']) ? sprintf( __('Staff restored to revision from %s', 'your_text_domain'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			   6 => sprintf( __('Staff published. <a href="%s">View book</a>', 'your_text_domain'), esc_url( get_permalink($post_ID) ) ),
			   7 => __('Staff saved.', 'your_text_domain'),
			   8 => sprintf( __('Staff submitted. <a target="_blank" href="%s">Preview Staff</a>', 'your_text_domain'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			   9 => sprintf( __('Staff scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Staff</a>', 'your_text_domain'),
			     // translators: Publish box date format, see http://php.net/date
			     date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
			   10 => sprintf( __('Staff draft updated. <a target="_blank" href="%s">Preview Staff</a>', 'your_text_domain'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			 );
			 
			 return $messages;
			 }

}

new GF_Staff_Post_Type;

endif;