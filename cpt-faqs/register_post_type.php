<?php
/***
* Special Thanks To Devin Price
* This file is a modified of the original plugin found @https://github.com/devinsays/portfolio-post-type - Special Thanks!
***/


if ( ! class_exists( 'GF_FAQ_Post_Type' ) ) :
class GF_FAQ_Post_Type {

	// Current plugin version
	var $version = 1;

	function __construct() {

		// Runs when the plugin is activated
		register_activation_hook( __FILE__, array( &$this, 'plugin_activation' ) );

		// Add support for translations
		load_plugin_textdomain( 'symple', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

		// Adds the faq post type and taxonomies
		add_action( 'init', array( &$this, 'faq_init' ) );

		// Thumbnail support for faq posts
		add_theme_support( 'post-thumbnails', array( 'faq' ) );

		// Adds columns in the admin view for thumbnail and taxonomies
		add_filter( 'manage_edit-faq_columns', array( &$this, 'faq_edit_columns' ) );
		add_action( 'manage_posts_custom_column', array( &$this, 'faq_column_display' ), 10, 2 );

		// Allows filtering of posts by taxonomy in the admin view
		add_action( 'restrict_manage_posts', array( &$this, 'faq_add_taxonomy_filters' ) );

		// Show faq post counts in the dashboard
		add_action( 'right_now_content_table_end', array( &$this, 'add_faq_counts' ) );
		
		// Add 32px icon
		add_action( 'admin_head', array( &$this, 'faqs_icons' ) );
		
		// Change Enter Title Here
		add_filter('enter_title_here', array( &$this, 'filter_faqs_title_text' ) );
		
		// Customize messaging
		add_filter( 'post_updated_messages', array( &$this, 'faq_updated_messages' ) );
		
	}

	/**
	 * Flushes rewrite rules on plugin activation to ensure faq posts don't 404
	 * http://codex.wordpress.org/Function_Reference/flush_rewrite_rules
	 */

	function plugin_activation() {
		$this->faq_init();
		flush_rewrite_rules();
	}

	function faq_init() {

		/**
		 * Enable the FAQ custom post type
		 * http://codex.wordpress.org/Function_Reference/register_post_type
		 */

		$labels = array(
			'name' => __( 'FAQs', 'symple' ),
			'singular_name' => __( 'FAQ Item', 'symple' ),
			'add_new' => __( 'Add New Item', 'symple' ),
			'add_new_item' => __( 'Add New FAQ Item', 'symple' ),
			'edit_item' => __( 'Edit FAQ Item', 'symple' ),
			'new_item' => __( 'Add New FAQ Item', 'symple' ),
			'view_item' => __( 'View Item', 'symple' ),
			'search_items' => __( 'Search FAQ', 'symple' ),
			'not_found' => __( 'No faq items found', 'symple' ),
			'not_found_in_trash' => __( 'No faq items found in trash', 'symple' )
		);
		
		$args = array(
	    	'labels' => $labels,
	    	'public' => true,
			'supports' => array( 'title', 'editor', 'revisions' ), // You can add 'title', 'editor', 'excerpt', 'thumbnail', 'comments', 'custom-fields', 'revisions'
			'capability_type' => 'post',
			'rewrite' => array("slug" => "faq"), // Permalinks format
			'has_archive' => true,
			'menu_icon' => plugin_dir_url( __FILE__ ) .'images/icon-faqs.png'
			
		); 
		
		$args = apply_filters('symple_faq_args', $args);
		
		register_post_type( 'faq', $args );

		/**
		 * Register a taxonomy for FAQ Categories
		 * http://codex.wordpress.org/Function_Reference/register_taxonomy
		 */

	    $taxonomy_faq_category_labels = array(
			'name' => _x( 'FAQ Categories', 'symple' ),
			'singular_name' => _x( 'FAQ Category', 'symple' ),
			'search_items' => _x( 'Search FAQ Categories', 'symple' ),
			'popular_items' => _x( 'Popular FAQ Categories', 'symple' ),
			'all_items' => _x( 'All FAQ Categories', 'symple' ),
			'parent_item' => _x( 'Parent FAQ Category', 'symple' ),
			'parent_item_colon' => _x( 'Parent FAQ Category:', 'symple' ),
			'edit_item' => _x( 'Edit FAQ Category', 'symple' ),
			'update_item' => _x( 'Update FAQ Category', 'symple' ),
			'add_new_item' => _x( 'Add New FAQ Category', 'symple' ),
			'new_item_name' => _x( 'New FAQ Category Name', 'symple' ),
			'separate_items_with_commas' => _x( 'Separate faq categories with commas', 'symple' ),
			'add_or_remove_items' => _x( 'Add or remove faq categories', 'symple' ),
			'choose_from_most_used' => _x( 'Choose from the most used faq categories', 'symple' ),
			'menu_name' => _x( 'FAQ Categories', 'symple' ),
	    );

	    $taxonomy_faq_category_args = array(
			'labels' => $taxonomy_faq_category_labels,
			'public' => true,
			'show_in_nav_menus' => true,
			'show_ui' => true,
			'show_tagcloud' => true,
			'hierarchical' => true,
			'rewrite' => array( 'slug' => 'faq-category' ),
			'query_var' => true
	    );

		$taxonomy_faq_category_args = apply_filters('symple_taxonomy_faq_category_args', $taxonomy_faq_category_args);
		
	    register_taxonomy( 'faq_category', array( 'faq' ), $taxonomy_faq_category_args );
	    
	    /**
		 * Register a taxonomy for FAQ Tags
		 * http://codex.wordpress.org/Function_Reference/register_taxonomy
		 */

		$taxonomy_faq_tag_labels = array(
			'name' => _x( 'FAQ Tags', 'symple' ),
			'singular_name' => _x( 'FAQ Tag', 'symple' ),
			'search_items' => _x( 'Search FAQ Tags', 'symple' ),
			'popular_items' => _x( 'Popular FAQ Tags', 'symple' ),
			'all_items' => _x( 'All FAQ Tags', 'symple' ),
			'parent_item' => _x( 'Parent FAQ Tag', 'symple' ),
			'parent_item_colon' => _x( 'Parent FAQ Tag:', 'symple' ),
			'edit_item' => _x( 'Edit FAQ Tag', 'symple' ),
			'update_item' => _x( 'Update FAQ Tag', 'symple' ),
			'add_new_item' => _x( 'Add New FAQ Tag', 'symple' ),
			'new_item_name' => _x( 'New FAQ Tag Name', 'symple' ),
			'separate_items_with_commas' => _x( 'Separate faq tags with commas', 'symple' ),
			'add_or_remove_items' => _x( 'Add or remove faq tags', 'symple' ),
			'choose_from_most_used' => _x( 'Choose from the most used faq tags', 'symple' ),
			'menu_name' => _x( 'FAQ Tags', 'symple' )
		);

		$taxonomy_faq_tag_args = array(
			'labels' => $taxonomy_faq_tag_labels,
			'public' => true,
			'show_in_nav_menus' => true,
			'show_ui' => true,
			'show_tagcloud' => true,
			'hierarchical' => false,
			'rewrite' => array( 'slug' => 'faq-tag' ),
			'query_var' => true
		);

		$taxonomy_faq_tag_args = apply_filters('symple_taxonomy_faq_tag_args', $taxonomy_faq_tag_args);
		
		register_taxonomy( 'faq_tag', array( 'faq' ), $taxonomy_faq_tag_args );

	}

	/**
	 * Add Columns to FAQ Edit Screen
	 * http://wptheming.com/2010/07/column-edit-pages/
	 */

	function faq_edit_columns( $faq_columns ) {
		$faq_columns = array(
			"cb" => "<input type=\"checkbox\" />",
			"title" => _x('Question', 'column name'),
			"faq_category" => __('Category', 'symple'),
			"faq_tag" => __('Tags', 'symple'),
			"author" => __('Author', 'symple'),
			"comments" => __('Comments', 'symple'),
			"date" => __('Date', 'symple'),
		);
		$faq_columns['comments'] = '<div class="vers"><img alt="Comments" src="' . esc_url( admin_url( 'images/comment-grey-bubble.png' ) ) . '" /></div>';
		return $faq_columns;
	}

	function faq_column_display( $faq_columns, $post_id ) {

		// Code from: http://wpengineer.com/display-post-thumbnail-post-page-overview

		switch ( $faq_columns ) {


			// Display the faq tags in the column view
			case "faq_category":

			if ( $category_list = get_the_term_list( $post_id, 'faq_category', '', ', ', '' ) ) {
				echo $category_list;
			} else {
				echo __('None', 'symple');
			}
			break;	

			// Display the faq tags in the column view
			case "faq_tag":

			if ( $tag_list = get_the_term_list( $post_id, 'faq_tag', '', ', ', '' ) ) {
				echo $tag_list;
			} else {
				echo __('None', 'symple');
			}
			break;			
		}
	}

	/**
	 * Adds taxonomy filters to the faq admin page
	 * Code artfully lifed from http://pippinsplugins.com
	 */

	function faq_add_taxonomy_filters() {
		global $typenow;

		// An array of all the taxonomyies you want to display. Use the taxonomy name or slug
		$taxonomies = array( 'faq_category', 'faq_tag' );

		// must set this to the post type you want the filter(s) displayed on
		if ( $typenow == 'faq' ) {

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
	 * Add FAQ count to "Right Now" Dashboard Widget
	 */

	function add_faq_counts() {
	        if ( ! post_type_exists( 'faq' ) ) {
	             return;
	        }

	        $num_posts = wp_count_posts( 'faq' );
	        $num = number_format_i18n( $num_posts->publish );
	        $text = _n( 'Frequently Asked Questions', 'Frequently Asked Questions', intval($num_posts->publish) );
	        if ( current_user_can( 'edit_posts' ) ) {
	            $num = "<a href='edit.php?post_type=faq'>$num</a>";
	            $text = "<a href='edit.php?post_type=faq'>$text</a>";
	        }
	        echo '<td class="first b b-faq">' . $num . '</td>';
	        echo '<td class="t faq">' . $text . '</td>';
	        echo '</tr>';

	        if ($num_posts->pending > 0) {
	            $num = number_format_i18n( $num_posts->pending );
	            $text = _n( 'FAQ Item Pending', 'FAQ Items Pending', intval($num_posts->pending) );
	            if ( current_user_can( 'edit_posts' ) ) {
	                $num = "<a href='edit.php?post_status=pending&post_type=faq'>$num</a>";
	                $text = "<a href='edit.php?post_status=pending&post_type=faq'>$text</a>";
	            }
	            echo '<td class="first b b-faq">' . $num . '</td>';
	            echo '<td class="t faq">' . $text . '</td>';

	            echo '</tr>';
	        }
	}

		/**
		 * Add Custom Icon (32px)
		 */
		 function faqs_icons() {
		 ?>
		 <style type="text/css" media="screen">
			 #icon-edit.icon32-posts-faq {background: url(<?php echo plugin_dir_url( __FILE__ ); ?>/images/faqs-32x32.png) no-repeat;}
		</style>
		<?php
		}
		
		/*
		 * Change "Enter Title Here"
		 */
		 function filter_faqs_title_text($title)
		 {
			 $scr = get_current_screen();
			 	if ('faq' == $scr->post_type)
			 	$title = 'Enter Question Here';
			 return ($title);
			 }
			 
		/*
		 * Customize Messeging
		 */

		 function faq_updated_messages( $messages ) {
			 global $post, $post_ID;

			 $messages['faq'] = array(
			   0 => '', // Unused. Messages start at index 1.
			   1 => sprintf( __('FAQ updated. <a href="%s">View FAQ</a>', 'your_text_domain'), esc_url( get_permalink($post_ID) ) ),
			   2 => __('Custom field updated.', 'your_text_domain'),
			   3 => __('Custom field deleted.', 'your_text_domain'),
			   4 => __('FAQ updated.', 'your_text_domain'),
			   /* translators: %s: date and time of the revision */
			   5 => isset($_GET['revision']) ? sprintf( __('FAQ restored to revision from %s', 'your_text_domain'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			   6 => sprintf( __('FAQ published. <a href="%s">View book</a>', 'your_text_domain'), esc_url( get_permalink($post_ID) ) ),
			   7 => __('FAQ saved.', 'your_text_domain'),
			   8 => sprintf( __('FAQ submitted. <a target="_blank" href="%s">Preview FAQ</a>', 'your_text_domain'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			   9 => sprintf( __('FAQ scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview FAQ</a>', 'your_text_domain'),
			     // translators: Publish box date format, see http://php.net/date
			     date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
			   10 => sprintf( __('FAQ draft updated. <a target="_blank" href="%s">Preview FAQ</a>', 'your_text_domain'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			 );
			 
			 return $messages;
			 }

}

new GF_FAQ_Post_Type;

endif;