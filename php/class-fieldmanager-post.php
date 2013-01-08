<?php
/**
 * @package Fieldmanager
 */

/**
 * Post auto-complete field
 * @package Fieldmanager
 */
class Fieldmanager_Post extends Fieldmanager_Field {

	/**
	 * @var string
	 * Override field class
	 */
	public $field_class = 'post';

	/**
	 * @var callable
	 * What function to call to match posts. Initialized to Null here because it will be 
	 * written in __construct to an internal function that calls get_posts, so only
	 * overwrite it if you do /not/ want to use get_posts.
	 * 
	 * The function signature should be query_callback( $match, $args );
	 */
	public $query_callback = Null;

	/**
	 * @var boolean
	 * Allow editing of the target post?
	 */
	public $editable = false;

	/**
	 * @var boolean
	 * Show post type in typeahead results?
	 */
	public $show_post_type = false;

	/**
	 * @var boolean
	 * Show post date in typeahead results?
	 */
	public $show_post_date = false;

	/**
	 * @var boolean
	 * Override save_empty for this element type
	 */
	public $save_empty = False;

	/**
	 * Add libraries for autocomplete
	 * @param array $options
	 */
	public function __construct( $options = array() ) {
		$this->attributes = array(
			'size' => '50',
		);
		
		// Add the bootstrap library for type-ahead capabilities
		fm_add_script( 'bootstrap', 'js/bootstrap/js/bootstrap.min.js' );
		fm_add_style( 'bootstrap_css', 'js/bootstrap/css/bootstrap.min.css' );

		// Add the post javascript and CSS
		fm_add_script( 'fm_post_js', 'js/fieldmanager-post.js', array(), false, false, 'fm_post', array( 'nonce' => wp_create_nonce( 'fm_post_search_nonce' ) ) );
		fm_add_style( 'fm_post_css', 'css/fieldmanager-post.css' );
		
		// Add the action hook for typeahead handling via AJAX
		add_action('wp_ajax_fm_search_posts', array( $this, 'search_posts' ) );

		if ( empty( $this->query_callback ) ) {
			$this->query_callback = array( $this, 'search_posts_using_get_posts' );
		}

		parent::__construct($options);
	}
	
	/**
	 * Button to clear results
	 */
	public function get_clear_handle() {
		return '<a href="#" class="fmjs-clear" title="Clear">Clear</a>';
	}

	/**
	 * Render form element
	 * @param mixed $value
	 * @return string HTML
	 */
	public function form_element( $value = array() ) {
		if ( empty( $value ) ) {
			// No value or invalid data was present. Use empty values.
			$value = array(
				'id' => '',
				'title' => '',
				'post_type' => '',
				'post_date' => ''
			);
		}
			
		return sprintf(
			'%s<input class="fm-post-element fm-element" type="text" name="%s" id="%s" value="%s" autocomplete="off" data-provide="typeahead" data-editable="%s" data-id="%s" data-post-type="%s" data-post-date="%s" data-show-post-type="%s" data-show-post-date="%s" %s />%s%s',
			( $this->limit == 1 ) ? '<div class="fmjs-clearable-element">' : '',
			$this->get_form_name(),
			$this->get_element_id(),
			htmlspecialchars( $value['title'] ),
			$this->editable,
			htmlspecialchars( $value['id'] ),
			htmlspecialchars( $value['post_type'] ),
			htmlspecialchars( $value['post_date'] ),
			$this->show_post_type,
			$this->show_post_date,
			$this->get_element_attributes(),
			( $this->limit == 1 ) ? '</div>' : '',
			( $this->limit == 1 ) ? $this->get_clear_handle() : ''
		);
	}

	/**
	 * Default callback which uses get_posts
	 * @param string $fragment
	 * @param array $args to get_posts
	 */
	public function search_posts_using_get_posts( $fragment ) {
		return get_posts( array(
			'numberposts' => 10,
			'orderby' => 'post_date',
			'order' => 'DESC',
			'post_status' => 'publish',
			'post_type' => 'any',
			's' => $fragment,
		) );
	}
	
	/**
	 * AJAX callback to find posts
	 */
	public function search_posts() {
		// Check the nonce before we do anything
		check_ajax_referer( 'fm_post_search_nonce', 'fm_post_search_nonce' );
		$posts = call_user_func( $this->query_callback, sanitize_title( $_POST['fm_post_search_term'] ) );
		
		// See if any results were returned and return them as an array
		if ( !empty( $posts ) ) {
		
			$search_data = array();
		
			foreach ( $posts as $result ) {
				// Get the post type display label to show in the dropdown
				$post_type = get_post_type_object( $result->post_type );
				
				// Format the date
				$post_date_formatted = date( 'Y/m/d', strtotime( $result->post_date ) );
			
				// Return an array of display values and an array of corresponding post IDs
				$post_display_title = sprintf( 
					'%s%s%s',
					$result->post_title,
					( $this->show_post_type ) ? " (" . $post_type->labels->singular_name . ")" : "",
					( $this->show_post_date ) ? " " . $post_date_formatted : ""
				);
				$search_data['names'][] = $post_display_title;
				$search_data[$post_display_title]['id'] = $result->ID;
				$search_data[$post_display_title]['post_type'] = $post_type->labels->singular_name;
				$search_data[$post_display_title]['post_date'] = $post_date_formatted;
				$search_data[$post_display_title]['post_title'] = $result->post_title;
				$search_data[$post_display_title]['permalink'] = get_permalink( $result->ID );
			}
			
			echo json_encode( $search_data );
			
		} else {
			echo "0";
		}
		
		die();
	}
	
	/**
	 * Make sure JSON is an associative array, and make sure posts are all clean.
	 * @param array $value
	 * @return array $value
	 */
	public function presave( $value ) {
		// If the value is not empty, convert the JSON data into an associative array so it is handled properly on save
		$value = json_decode( stripslashes( $value ), true );
		$legal_keys = array( 'id', 'title', 'post_type', 'post_date' );
		if ( is_array( $value ) ) {
			foreach ( $value as $k => $v ) {
				if ( !in_array( $k, $legal_keys ) )
					$this->_unauthorized_access( 'Illegal key ' . $k . ' in submission for post field.' );
			}
			// Sanitization
			$value['id'] = intval( $value['id'] );			
			$value['title'] = sanitize_text_field( $value['title'] );
			$value['post_type'] = sanitize_text_field( $value['post_type'] );
			$value['post_date'] = sanitize_text_field( $value['post_date'] );
			// One more validation: For now, you must be able to edit a post in order to reference it.
			if( !current_user_can( 'edit_post', $value['id'] ) ) {
				$this->_unauthorized_access( 'Tried to refer to post ' . $value['id'] . ' which user cannot edit.' );	
			}
		}
		return $value;
	}

}