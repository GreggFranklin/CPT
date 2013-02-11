<?php
/***
* Special Thanks To Devin Price
* This file is a modified of the original plugin found @https://github.com/devinsays/portfolio-post-type - Special Thanks!
***/

if ( ! class_exists( 'GF_Slide_Post_Type' ) ) :
class GF_Slide_Post_Type {

	// Current plugin version
	var $version = 1;

	function __construct() {

		// Runs when the plugin is activated
		register_activation_hook( __FILE__, array( &$this, 'plugin_activation' ) );

		// Add support for translations
		load_plugin_textdomain( 'symple', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

		// Adds the slide post type and taxonomies
		add_action( 'init', array( &$this, 'slide_init' ) );

		// Thumbnail support for slide posts
		add_theme_support( 'post-thumbnails', array( 'slide' ) );

		// Adds columns in the admin view for thumbnail and taxonomies
		add_filter( 'manage_edit-slide_columns', array( &$this, 'slide_edit_columns' ) );
		add_action( 'manage_posts_custom_column', array( &$this, 'slide_column_display' ), 10, 2 );

		// Show slide post counts in the dashboard
		add_action( 'right_now_content_table_end', array( &$this, 'add_slide_counts' ) );
		
		// Add 32px icon
		add_action( 'admin_head', array( &$this, 'slide_icons' ) );
		
		// Customize messaging
		add_filter( 'post_updated_messages', array( &$this, 'slide_updated_messages' ) );
		
		// Move Featured Image meta box under title
		add_action('do_meta_boxes', array( &$this, 'customposttype_image_box' ) );

	}

	/**
	 * Flushes rewrite rules on plugin activation to ensure slide posts don't 404
	 * http://codex.wordpress.org/Function_Reference/flush_rewrite_rules
	 */

	function plugin_activation() {
		$this->slide_init();
		flush_rewrite_rules();
	}

	function slide_init() {

		/**
		 * Enable the Slide custom post type
		 * http://codex.wordpress.org/Function_Reference/register_post_type
		 */

		$labels = array(
			'name' => __( 'Slide', 'symple' ),
			'singular_name' => __( 'Slide Item', 'symple' ),
			'add_new' => __( 'Add New Item', 'symple' ),
			'add_new_item' => __( 'Add New Slide Item', 'symple' ),
			'edit_item' => __( 'Edit Slide Item', 'symple' ),
			'new_item' => __( 'Add New Slide Item', 'symple' ),
			'view_item' => __( 'View Item', 'symple' ),
			'search_items' => __( 'Search Slide', 'symple' ),
			'not_found' => __( 'No Slide items found', 'symple' ),
			'not_found_in_trash' => __( 'No Slide items found in trash', 'symple' )
		);
		
		$args = array(
	    	'labels' => $labels,
	    	'public' => true,
			'supports' => array( 'title', 'thumbnail' ), // You can add 'title', 'editor', 'excerpt', 'thumbnail', 'comments', 'custom-fields', 'revisions'
			'capability_type' => 'post',
			'rewrite' => array("slug" => "slide"), // Permalinks format
			'has_archive' => true,
			'menu_icon' => plugin_dir_url( __FILE__ ) .'images/icon-slides.png'
		); 
		
		$args = apply_filters('symple_slide_args', $args);
		
		register_post_type( 'slide', $args );

	}

	/**
	 * Add Columns to Slide Edit Screen
	 * http://wptheming.com/2010/07/column-edit-pages/
	 */

	function slide_edit_columns( $slide_columns ) {
		$slide_columns = array(
			"cb" => "<input type=\"checkbox\" />",
			"title" => _x('Title', 'column name'),
			"slide_thumbnail" => __('Slide', 'symple'),
			"author" => __('Author', 'symple'),
			"comments" => __('Comments', 'symple'),
			"date" => __('Date', 'symple'),
		);
		$slide_columns['comments'] = '<div class="vers"><img alt="Comments" src="' . esc_url( admin_url( 'images/comment-grey-bubble.png' ) ) . '" /></div>';
		return $slide_columns;
	}

	function slide_column_display( $slide_columns, $post_id ) {

		// Code from: http://wpengineer.com/display-post-thumbnail-post-page-overview

		switch ( $slide_columns ) {

			// Display the thumbnail in the column view
			case "slide_thumbnail":
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
			
		}
	}

	/**
	 * Add slide count to "Right Now" Dashboard Widget
	 */

	function add_slide_counts() {
	        if ( ! post_type_exists( 'slide' ) ) {
	             return;
	        }

	        $num_posts = wp_count_posts( 'slide' );
	        $num = number_format_i18n( $num_posts->publish );
	        $text = _n( 'Slides', 'Slides', intval($num_posts->publish) );
	        if ( current_user_can( 'edit_posts' ) ) {
	            $num = "<a href='edit.php?post_type=slide'>$num</a>";
	            $text = "<a href='edit.php?post_type=slide'>$text</a>";
	        }
	        echo '<td class="first b b-slide">' . $num . '</td>';
	        echo '<td class="t slide">' . $text . '</td>';
	        echo '</tr>';

	        if ($num_posts->pending > 0) {
	            $num = number_format_i18n( $num_posts->pending );
	            $text = _n( 'Slide Item Pending', 'Slide Items Pending', intval($num_posts->pending) );
	            if ( current_user_can( 'edit_posts' ) ) {
	                $num = "<a href='edit.php?post_status=pending&post_type=slide'>$num</a>";
	                $text = "<a href='edit.php?post_status=pending&post_type=slide'>$text</a>";
	            }
	            echo '<td class="first b b-slide">' . $num . '</td>';
	            echo '<td class="t slide">' . $text . '</td>';

	            echo '</tr>';
	        }
	}

		/**
		 * Add Custom Icon (32px)
		 */
		 function slide_icons() {
		 ?>
		 <style type="text/css" media="screen">
			 #icon-edit.icon32-posts-slide {background: url(<?php echo plugin_dir_url( __FILE__ ); ?>images/slides-32x32.png) no-repeat;}
		</style>
		<?php
		}
				 
		/*
		 * Customize Messeging
		 */

		 function slide_updated_messages( $messages ) {
			 global $post, $post_ID;

			 $messages['slide'] = array(
			   0 => '', // Unused. Messages start at index 1.
			   1 => sprintf( __('Slide updated. <a href="%s">View Slide</a>', 'your_text_domain'), esc_url( get_permalink($post_ID) ) ),
			   2 => __('Custom field updated.', 'your_text_domain'),
			   3 => __('Custom field deleted.', 'your_text_domain'),
			   4 => __('Slide updated.', 'your_text_domain'),
			   /* translators: %s: date and time of the revision */
			   5 => isset($_GET['revision']) ? sprintf( __('Slide restored to revision from %s', 'your_text_domain'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			   6 => sprintf( __('Slide published. <a href="%s">View Slide</a>', 'your_text_domain'), esc_url( get_permalink($post_ID) ) ),
			   7 => __('Slide saved.', 'your_text_domain'),
			   8 => sprintf( __('Slide submitted. <a target="_blank" href="%s">Preview Slide</a>', 'your_text_domain'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			   9 => sprintf( __('Slide scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Slide</a>', 'your_text_domain'),
			     // translators: Publish box date format, see http://php.net/date
			     date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
			   10 => sprintf( __('Slide draft updated. <a target="_blank" href="%s">Preview Slide</a>', 'your_text_domain'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			 );
			 
			 return $messages;
			 }
			 
		
		/*
		 * Move Featured Image under Title
		 */
		 function customposttype_image_box() {

			 remove_meta_box( 'postimagediv', 'slide', 'side' );
			 add_meta_box('postimagediv', __('Slide Image'), 'post_thumbnail_meta_box', 'slide', 'normal', 'high');
			 }
			 

}

new GF_Slide_Post_Type;

endif;