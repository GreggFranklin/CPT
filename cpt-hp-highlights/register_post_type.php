<?php
/***
* Special Thanks To Devin Price
* This file is a modified of the original plugin found @https://github.com/devinsays/portfolio-post-type - Special Thanks!
***/

if ( ! class_exists( 'GF_HP_Highlights_Post_Type' ) ) :
class GF_HP_Highlights_Post_Type {

	// Current plugin version
	var $version = 1;

	function __construct() {

		// Runs when the plugin is activated
		register_activation_hook( __FILE__, array( &$this, 'plugin_activation' ) );

		// Add support for translations
		load_plugin_textdomain( 'symple', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

		// Adds the slide post type and taxonomies
		add_action( 'init', array( &$this, 'hp_highlights_init' ) );

		// Thumbnail support for hp_highlights posts
		add_theme_support( 'post-thumbnails', array( 'hp_highlights' ) );

		// Adds columns in the admin view for thumbnail and taxonomies
		add_filter( 'manage_edit-slide_columns', array( &$this, 'hp_highlights_edit_columns' ) );
		add_action( 'manage_posts_custom_column', array( &$this, 'hp_highlights_column_display' ), 10, 2 );

		// Show HP Highlights post counts in the dashboard
		add_action( 'right_now_content_table_end', array( &$this, 'add_hp_highlights_counts' ) );
		
		// Add 32px icon
		add_action( 'admin_head', array( &$this, 'hp_highlights_icons' ) );
		
		// Customize messaging
		add_filter( 'post_updated_messages', array( &$this, 'hp_highlights_updated_messages' ) );


	}

	/**
	 * Flushes rewrite rules on plugin activation to ensure hp_highlights posts don't 404
	 * http://codex.wordpress.org/Function_Reference/flush_rewrite_rules
	 */

	function plugin_activation() {
		$this->hp_highlights_init();
		flush_rewrite_rules();
	}

	function hp_highlights_init() {

		/**
		 * Enable the hp_highlights custom post type
		 * http://codex.wordpress.org/Function_Reference/register_post_type
		 */

		$labels = array(
			'name' => __( 'HP Highlights', 'symple' ),
			'singular_name' => __( 'Highlight Item', 'symple' ),
			'add_new' => __( 'Add New Item', 'symple' ),
			'add_new_item' => __( 'Add New Highlight Item', 'symple' ),
			'edit_item' => __( 'Edit Highlight Item', 'symple' ),
			'new_item' => __( 'Add New Highlight Item', 'symple' ),
			'view_item' => __( 'View Item', 'symple' ),
			'search_items' => __( 'Search Highlight', 'symple' ),
			'not_found' => __( 'No Highlight items found', 'symple' ),
			'not_found_in_trash' => __( 'No Highlight items found in trash', 'symple' )
		);
		
		$args = array(
	    	'labels' => $labels,
	    	'public' => true,
			'supports' => array( 'title', 'thumbnail' ), // You can add 'title', 'editor', 'excerpt', 'thumbnail', 'comments', 'custom-fields', 'revisions'
			'capability_type' => 'post',
			'rewrite' => array("slug" => "hp-highlight"), // Permalinks format
			'has_archive' => true,
			'menu_icon' => plugin_dir_url( __FILE__ ) .'images/icon-hp-highlights.png'
		); 
		
		$args = apply_filters('symple_slide_args', $args);
		
		register_post_type( 'hp_highlights', $args );

	}

	/**
	 * Add Columns to Slide Edit Screen
	 * http://wptheming.com/2010/07/column-edit-pages/
	 */

	function hp_highlights_edit_columns( $hp_highlights_columns ) {
		$hp_highlights_columns = array(
			"cb" => "<input type=\"checkbox\" />",
			"title" => _x('Title', 'column name'),
			"hp_highlights_thumbnail" => __('Slide', 'symple'),
			"author" => __('Author', 'symple'),
			"comments" => __('Comments', 'symple'),
			"date" => __('Date', 'symple'),
		);
		$hp_highlights_columns['comments'] = '<div class="vers"><img alt="Comments" src="' . esc_url( admin_url( 'images/comment-grey-bubble.png' ) ) . '" /></div>';
		return $hp_highlights_columns;
	}

	function hp_highlights_column_display( $hp_highlights_columns, $post_id ) {

		// Code from: http://wpengineer.com/display-post-thumbnail-post-page-overview

		switch ( $hp_highlights_columns ) {

			// Display the thumbnail in the column view
			case "hp_highlights_thumbnail":
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
	 * Add HP Highlights count to "Right Now" Dashboard Widget
	 */

	function add_hp_highlights_counts() {
	        if ( ! post_type_exists( 'hp_highlights' ) ) {
	             return;
	        }

	        $num_posts = wp_count_posts( 'hp_highlights' );
	        $num = number_format_i18n( $num_posts->publish );
	        $text = _n( 'HP Highlights', 'HP Highlights', intval($num_posts->publish) );
	        if ( current_user_can( 'edit_posts' ) ) {
	            $num = "<a href='edit.php?post_type=hp_highlights'>$num</a>";
	            $text = "<a href='edit.php?post_type=hp_highlights'>$text</a>";
	        }
	        echo '<td class="first b b-hp_highlights">' . $num . '</td>';
	        echo '<td class="t hp_highlights">' . $text . '</td>';
	        echo '</tr>';

	        if ($num_posts->pending > 0) {
	            $num = number_format_i18n( $num_posts->pending );
	            $text = _n( 'HP Highlight Item Pending', 'HP Highlight Items Pending', intval($num_posts->pending) );
	            if ( current_user_can( 'edit_posts' ) ) {
	                $num = "<a href='edit.php?post_status=pending&post_type=hp_highlights'>$num</a>";
	                $text = "<a href='edit.php?post_status=pending&post_type=hp_highlights'>$text</a>";
	            }
	            echo '<td class="first b b-hp_highlights">' . $num . '</td>';
	            echo '<td class="t hp_highlights">' . $text . '</td>';

	            echo '</tr>';
	        }
	}

		/**
		 * Add Custom Icon (32px)
		 */
		 function hp_highlights_icons() {
		 ?>
		 <style type="text/css" media="screen">
			 #icon-edit.icon32-posts-hp_highlights {background: url(<?php echo plugin_dir_url( __FILE__ ); ?>images/highlights-32x32.png) no-repeat;}
		</style>
		<?php
		}
				 
		/*
		 * Customize Messeging
		 */

		 function hp_highlights_updated_messages( $messages ) {
			 global $post, $post_ID;

			 $messages['hp_highlights'] = array(
			   0 => '', // Unused. Messages start at index 1.
			   1 => sprintf( __('HP Highlights updated. <a href="%s">View HP Highlights</a>', 'your_text_domain'), esc_url( get_permalink($post_ID) ) ),
			   2 => __('Custom field updated.', 'your_text_domain'),
			   3 => __('Custom field deleted.', 'your_text_domain'),
			   4 => __('HP Highlights updated.', 'your_text_domain'),
			   /* translators: %s: date and time of the revision */
			   5 => isset($_GET['revision']) ? sprintf( __('HP Highlights restored to revision from %s', 'your_text_domain'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			   6 => sprintf( __('HP Highlights published. <a href="%s">View HP Highlights</a>', 'your_text_domain'), esc_url( get_permalink($post_ID) ) ),
			   7 => __('Slide saved.', 'your_text_domain'),
			   8 => sprintf( __('HP Highlights submitted. <a target="_blank" href="%s">Preview HP Highlights</a>', 'your_text_domain'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			   9 => sprintf( __('HP Highlights scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview HP Highlights</a>', 'your_text_domain'),
			     // translators: Publish box date format, see http://php.net/date
			     date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
			   10 => sprintf( __('HP Highlights draft updated. <a target="_blank" href="%s">Preview HP Highlights</a>', 'your_text_domain'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			 );
			 
			 return $messages;
			 }
			 
			
}

new GF_HP_Highlights_Post_Type;

endif;