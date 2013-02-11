<?php
/***
* Special Thanks To Devin Price
* This file is a modified of the original plugin found @https://github.com/devinsays/portfolio-post-type - Special Thanks!
***/

if ( ! class_exists( 'GF_Portfolio_Post_Type' ) ) :
class GF_Portfolio_Post_Type {

	// Current plugin version
	var $version = 1;

	function __construct() {

		// Runs when the plugin is activated
		register_activation_hook( __FILE__, array( &$this, 'plugin_activation' ) );

		// Add support for translations
		load_plugin_textdomain( 'symple', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

		// Adds the portfolio post type and taxonomies
		add_action( 'init', array( &$this, 'portfolio_init' ) );

		// Thumbnail support for portfolio posts
		add_theme_support( 'post-thumbnails', array( 'portfolio' ) );

		// Adds columns in the admin view for thumbnail and taxonomies
		add_filter( 'manage_edit-portfolio_columns', array( &$this, 'portfolio_edit_columns' ) );
		add_action( 'manage_posts_custom_column', array( &$this, 'portfolio_column_display' ), 10, 2 );

		// Allows filtering of posts by taxonomy in the admin view
		add_action( 'restrict_manage_posts', array( &$this, 'portfolio_add_taxonomy_filters' ) );

		// Show portfolio post counts in the dashboard
		add_action( 'right_now_content_table_end', array( &$this, 'add_portfolio_counts' ) );
		
		// Add 32px icon
		add_action( 'admin_head', array( &$this, 'portfolio_icons' ) );
		
		// Customize messaging
		add_filter( 'post_updated_messages', array( &$this, 'portfolio_updated_messages' ) );

	}

	/**
	 * Flushes rewrite rules on plugin activation to ensure portfolio posts don't 404
	 * http://codex.wordpress.org/Function_Reference/flush_rewrite_rules
	 */

	function plugin_activation() {
		$this->portfolio_init();
		flush_rewrite_rules();
	}

	function portfolio_init() {

		/**
		 * Enable the Portfolio custom post type
		 * http://codex.wordpress.org/Function_Reference/register_post_type
		 */

		$labels = array(
			'name' => __( 'Portfolio', 'symple' ),
			'singular_name' => __( 'Portfolio Item', 'symple' ),
			'add_new' => __( 'Add New Item', 'symple' ),
			'add_new_item' => __( 'Add New Portfolio Item', 'symple' ),
			'edit_item' => __( 'Edit Portfolio Item', 'symple' ),
			'new_item' => __( 'Add New Portfolio Item', 'symple' ),
			'view_item' => __( 'View Item', 'symple' ),
			'search_items' => __( 'Search Portfolio', 'symple' ),
			'not_found' => __( 'No portfolio items found', 'symple' ),
			'not_found_in_trash' => __( 'No portfolio items found in trash', 'symple' )
		);
		
		$args = array(
	    	'labels' => $labels,
	    	'public' => true,
			'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'comments', 'custom-fields', 'revisions' ),
			'capability_type' => 'post',
			'rewrite' => array("slug" => "portfolio"), // Permalinks format
			'has_archive' => true,
			'menu_icon' => plugin_dir_url( __FILE__ ) .'images/icon-portfolio.png'
		); 
		
		$args = apply_filters('symple_portfolio_args', $args);
		
		register_post_type( 'portfolio', $args );
		
		/**
		 * Register a taxonomy for Portfolio Categories
		 * http://codex.wordpress.org/Function_Reference/register_taxonomy
		 */

	    $taxonomy_portfolio_category_labels = array(
			'name' => _x( 'Portfolio Categories', 'symple' ),
			'singular_name' => _x( 'Portfolio Category', 'symple' ),
			'search_items' => _x( 'Search Portfolio Categories', 'symple' ),
			'popular_items' => _x( 'Popular Portfolio Categories', 'symple' ),
			'all_items' => _x( 'All Portfolio Categories', 'symple' ),
			'parent_item' => _x( 'Parent Portfolio Category', 'symple' ),
			'parent_item_colon' => _x( 'Parent Portfolio Category:', 'symple' ),
			'edit_item' => _x( 'Edit Portfolio Category', 'symple' ),
			'update_item' => _x( 'Update Portfolio Category', 'symple' ),
			'add_new_item' => _x( 'Add New Portfolio Category', 'symple' ),
			'new_item_name' => _x( 'New Portfolio Category Name', 'symple' ),
			'separate_items_with_commas' => _x( 'Separate portfolio categories with commas', 'symple' ),
			'add_or_remove_items' => _x( 'Add or remove portfolio categories', 'symple' ),
			'choose_from_most_used' => _x( 'Choose from the most used portfolio categories', 'symple' ),
			'menu_name' => _x( 'Portfolio Categories', 'symple' ),
	    );

	    $taxonomy_portfolio_category_args = array(
			'labels' => $taxonomy_portfolio_category_labels,
			'public' => true,
			'show_in_nav_menus' => true,
			'show_ui' => true,
			'show_tagcloud' => true,
			'hierarchical' => true,
			'rewrite' => array( 'slug' => 'portfolio-category' ),
			'query_var' => true
	    );

		$taxonomy_portfolio_category_args = apply_filters('symple_taxonomy_portfolio_category_args', $taxonomy_portfolio_category_args);
		
	    register_taxonomy( 'portfolio_category', array( 'portfolio' ), $taxonomy_portfolio_category_args );

		/**
		 * Register a taxonomy for Portfolio Tags
		 * http://codex.wordpress.org/Function_Reference/register_taxonomy
		 */

		$taxonomy_portfolio_tag_labels = array(
			'name' => _x( 'Portfolio Tags', 'symple' ),
			'singular_name' => _x( 'Portfolio Tag', 'symple' ),
			'search_items' => _x( 'Search Portfolio Tags', 'symple' ),
			'popular_items' => _x( 'Popular Portfolio Tags', 'symple' ),
			'all_items' => _x( 'All Portfolio Tags', 'symple' ),
			'parent_item' => _x( 'Parent Portfolio Tag', 'symple' ),
			'parent_item_colon' => _x( 'Parent Portfolio Tag:', 'symple' ),
			'edit_item' => _x( 'Edit Portfolio Tag', 'symple' ),
			'update_item' => _x( 'Update Portfolio Tag', 'symple' ),
			'add_new_item' => _x( 'Add New Portfolio Tag', 'symple' ),
			'new_item_name' => _x( 'New Portfolio Tag Name', 'symple' ),
			'separate_items_with_commas' => _x( 'Separate portfolio tags with commas', 'symple' ),
			'add_or_remove_items' => _x( 'Add or remove portfolio tags', 'symple' ),
			'choose_from_most_used' => _x( 'Choose from the most used portfolio tags', 'symple' ),
			'menu_name' => _x( 'Portfolio Tags', 'symple' )
		);

		$taxonomy_portfolio_tag_args = array(
			'labels' => $taxonomy_portfolio_tag_labels,
			'public' => true,
			'show_in_nav_menus' => true,
			'show_ui' => true,
			'show_tagcloud' => true,
			'hierarchical' => false,
			'rewrite' => array( 'slug' => 'portfolio-tag' ),
			'query_var' => true
		);

		$taxonomy_portfolio_tag_args = apply_filters('symple_taxonomy_portfolio_tag_args', $taxonomy_portfolio_tag_args);
		
		register_taxonomy( 'portfolio_tag', array( 'portfolio' ), $taxonomy_portfolio_tag_args );

	}

	/**
	 * Add Columns to Portfolio Edit Screen
	 * http://wptheming.com/2010/07/column-edit-pages/
	 */

	function portfolio_edit_columns( $portfolio_columns ) {
		$portfolio_columns = array(
			"cb" => "<input type=\"checkbox\" />",
			"title" => _x('Title', 'column name'),
			"portfolio_thumbnail" => __('Thumbnail', 'symple'),
			"portfolio_category" => __('Category', 'symple'),
			"portfolio_tag" => __('Tags', 'symple'),
			"author" => __('Author', 'symple'),
			"comments" => __('Comments', 'symple'),
			"date" => __('Date', 'symple'),
		);
		$portfolio_columns['comments'] = '<div class="vers"><img alt="Comments" src="' . esc_url( admin_url( 'images/comment-grey-bubble.png' ) ) . '" /></div>';
		return $portfolio_columns;
	}

	function portfolio_column_display( $portfolio_columns, $post_id ) {

		// Code from: http://wpengineer.com/display-post-thumbnail-post-page-overview

		switch ( $portfolio_columns ) {

			// Display the thumbnail in the column view
			case "portfolio_thumbnail":
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

			// Display the portfolio tags in the column view
			case "portfolio_category":

			if ( $category_list = get_the_term_list( $post_id, 'portfolio_category', '', ', ', '' ) ) {
				echo $category_list;
			} else {
				echo __('None', 'symple');
			}
			break;	

			// Display the portfolio tags in the column view
			case "portfolio_tag":

			if ( $tag_list = get_the_term_list( $post_id, 'portfolio_tag', '', ', ', '' ) ) {
				echo $tag_list;
			} else {
				echo __('None', 'symple');
			}
			break;			
		}
	}

	/**
	 * Adds taxonomy filters to the portfolio admin page
	 * Code artfully lifed from http://pippinsplugins.com
	 */

	function portfolio_add_taxonomy_filters() {
		global $typenow;

		// An array of all the taxonomyies you want to display. Use the taxonomy name or slug
		$taxonomies = array( 'portfolio_category', 'portfolio_tag' );

		// must set this to the post type you want the filter(s) displayed on
		if ( $typenow == 'portfolio' ) {

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
	 * Add Portfolio count to "Right Now" Dashboard Widget
	 */

	function add_portfolio_counts() {
	        if ( ! post_type_exists( 'portfolio' ) ) {
	             return;
	        }

	        $num_posts = wp_count_posts( 'portfolio' );
	        $num = number_format_i18n( $num_posts->publish );
	        $text = _n( 'Portfolio Item', 'Portfolio Items', intval($num_posts->publish) );
	        if ( current_user_can( 'edit_posts' ) ) {
	            $num = "<a href='edit.php?post_type=portfolio'>$num</a>";
	            $text = "<a href='edit.php?post_type=portfolio'>$text</a>";
	        }
	        echo '<td class="first b b-portfolio">' . $num . '</td>';
	        echo '<td class="t portfolio">' . $text . '</td>';
	        echo '</tr>';

	        if ($num_posts->pending > 0) {
	            $num = number_format_i18n( $num_posts->pending );
	            $text = _n( 'Portfolio Item Pending', 'Portfolio Items Pending', intval($num_posts->pending) );
	            if ( current_user_can( 'edit_posts' ) ) {
	                $num = "<a href='edit.php?post_status=pending&post_type=portfolio'>$num</a>";
	                $text = "<a href='edit.php?post_status=pending&post_type=portfolio'>$text</a>";
	            }
	            echo '<td class="first b b-portfolio">' . $num . '</td>';
	            echo '<td class="t portfolio">' . $text . '</td>';

	            echo '</tr>';
	        }
	}

		/**
		 * Add Custom Icon (32px)
		 */
		 function portfolio_icons() {
		 ?>
		 <style type="text/css" media="screen">
			 #icon-edit.icon32-posts-portfolio {background: url(<?php echo plugin_dir_url( __FILE__ ); ?>images/portfolio-32x32.png) no-repeat;}
		</style>
		<?php
		}
				 
		/*
		 * Customize Messeging
		 */

		 function portfolio_updated_messages( $messages ) {
			 global $post, $post_ID;

			 $messages['portfolio'] = array(
			   0 => '', // Unused. Messages start at index 1.
			   1 => sprintf( __('Portfolio updated. <a href="%s">View Portfolio</a>', 'your_text_domain'), esc_url( get_permalink($post_ID) ) ),
			   2 => __('Custom field updated.', 'your_text_domain'),
			   3 => __('Custom field deleted.', 'your_text_domain'),
			   4 => __('Portfolio updated.', 'your_text_domain'),
			   /* translators: %s: date and time of the revision */
			   5 => isset($_GET['revision']) ? sprintf( __('Portfolio restored to revision from %s', 'your_text_domain'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			   6 => sprintf( __('Portfolio published. <a href="%s">View Portfolio</a>', 'your_text_domain'), esc_url( get_permalink($post_ID) ) ),
			   7 => __('Portfolio saved.', 'your_text_domain'),
			   8 => sprintf( __('Portfolio submitted. <a target="_blank" href="%s">Preview Portfolio</a>', 'your_text_domain'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			   9 => sprintf( __('Portfolio scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Portfolio</a>', 'your_text_domain'),
			     // translators: Publish box date format, see http://php.net/date
			     date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
			   10 => sprintf( __('Portfolio draft updated. <a target="_blank" href="%s">Preview Portfolio</a>', 'your_text_domain'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			 );
			 
			 return $messages;
			 }

}

new GF_Portfolio_Post_Type;

endif;