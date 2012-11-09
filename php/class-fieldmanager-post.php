<?php
class Fieldmanager_Post extends Fieldmanager_Field {

	public $field_class = 'post';
	public $post_types = array( 'post' );
	public $search_orderby = 'post_date desc';
	public $search_limit = 5;
	public $editable = false;
	public $show_post_type = false;
	public $show_post_date = false;

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

		parent::__construct($options);
	}
	
	public function get_clear_handle() {
		return '<a href="#" class="fmjs-clear" title="Clear">Clear</a>';
	}

	public function form_element( $value = '' ) {
		if ( !is_array($value) ) {
			// No value or invalid data was present. Use empty values.
			$value = array(
				"id" => "",
				"title" => "",
				"post_type" => "",
				"post_date" => ""
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
		
	public function search_posts() {
		// Check the nonce before we do anything
		check_ajax_referer( 'fm_post_search_nonce', 'fm_post_search_nonce' );
	
		global $wpdb; 
		
		// Confirm all the data is in place for the query. If not, die.
		if ( !is_array( $this->post_types ) 
			|| empty( $this->post_types ) 
			|| !array_key_exists( 'fm_post_search_term', $_POST ) 
			|| empty( $_POST['fm_post_search_term'] )
			|| !isset( $this->search_limit )
			|| !is_numeric( $this->search_limit )
			|| !isset( $this->search_orderby )
			|| empty( $this->search_orderby ) ) {
			
			echo "-1";
			die();
		}
		
		// Containers for query data
		$query_params = array();
		$post_type_placeholders = array();
		
		// Prepare the list of post types
		foreach ( $this->post_types as $post_type ) {
			$post_type_placeholders[] = '%s';
			$query_params[] = $post_type;
		}
		
		$query_params[] = $_POST['fm_post_search_term'];
		$query_params[] = $this->search_orderby;
		$query_params[] = $this->search_limit;
		
		$post_search_results = $wpdb->get_results( $wpdb->prepare( 
			"
			SELECT ID, post_title, post_type, post_date
			FROM $wpdb->posts
			WHERE
			post_type IN (" . implode( ',', $post_type_placeholders ) . ")
			AND post_title like '%%%s%%'
			ORDER BY %s
			LIMIT %d
			", 
			$query_params
		) );
		
		// See if any results were returned and return them as an array
		if ( $post_search_results ) {
		
			$search_data = array();
		
			foreach ( $post_search_results as $result ) {
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
			}
			
			echo json_encode( $search_data );
			
		} else {
			echo "0";
		}
		
		die();
	}
	
	public function presave( $value ) {
			
		// If the value is not empty, convert the JSON data into an associative array so it is handled properly on save
		return json_decode( stripslashes( $value ), true );
		
	}
	
	public function validate( $value ) {

	}

	public function sanitize( $value ) {

	}

}