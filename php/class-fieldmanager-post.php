<?php
class Fieldmanager_Post extends Fieldmanager_Field {

	public $field_class = 'post';
	public $post_types = array( 'post' );
	public $search_orderby = 'post_date desc';
	public $search_limit = 5;

	public function __construct( $options = array() ) {
		$this->attributes = array(
			'size' => '50',
		);
		
		// Add the bootstrap library for type-ahead capabilities
		fm_add_script( 'bootstrap', 'js/bootstrap/js/bootstrap.min.js' );
		fm_add_style( 'bootstrap_css', 'js/bootstrap/css/bootstrap.min.css' );
		
		// Add the action hook for typeahead handling via AJAX
		add_action('wp_ajax_fm_search_posts', array( $this, 'search_posts' ) );
		
		parent::__construct($options);
	}

	public function form_element( $value = '' ) {
		return sprintf(
			'<input type="hidden" name="%s[id]" id="%s-id" value="%s" /><input class="fm-post-element fm-element" type="text" name="%s[name]" id="%s-name" value="%s" data-provide="typeahead" %s />',
			$this->get_form_name(),
			$this->get_element_id(),
			htmlspecialchars( $value['id'] ),
			$this->get_form_name(),
			$this->get_element_id(),
			htmlspecialchars( $value['name'] ),
			$this->get_element_attributes()
		);
	}
		
	public function search_posts() {
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
			SELECT ID, post_title
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
				// Return an array of display values and an array of corresponding post IDs
				$search_data['names'][] = $result->post_title;
				$search_data['ids'][$result->post_title] = $result->ID;
			}
			
			echo json_encode( $search_data );
			
		} else {
			echo "0";
		}
		
		die();
	}
	
	public function validate( $value ) {

	}

	public function sanitize( $value ) {

	}

}