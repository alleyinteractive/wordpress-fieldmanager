<?php

class Post_Type_Article {

	/**
	 * Constructor to set necessary action hooks and filters
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'create_content_type' ) );
		add_action( 'admin_init', array( $this, 'manage_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_fields' ) );
	}

	/**
	 * Create the custom content type
	 *
	 * @return void
	 */
	public function create_content_type() {
		$labels = array(
			'name' => _x( 'Articles', 'post type general name' ),
	    	'singular_name' => _x( 'Article', 'post type singular name' ),
		    'plural_name' => _x( 'All Articles', 'post type plural name' ),
		    'add_new' => _x( 'Add New', 'best of' ),
		    'add_new_item' => __( 'Add New' ),
		    'edit_item' => __( 'Edit' ),
		    'new_item' => __( 'New' ),
		    'view_item' => __( 'View' ),
		    'search_items' => __( 'Search' ),
		    'not_found' =>  __( 'No articles found' ),
		    'not_found_in_trash' => __( 'No articles found in Trash' ),
		);

		$args = array(
			'labels' => $labels,
			'label' => __( 'Article' ),
			'description' => __( 'Articles' ),
			'public' => TRUE,
			'publicly_queryable' => TRUE,
			'query_var' => 'article',
			'rewrite' => true,
			'show_ui' => true,
			'capability_type' => 'post',
			'hierarchical' => true,
			'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'comments', 'revisions', 'page-attributes', 'post-formats' ),
			'rewrite' => array("slug" => "article", 'with_front' => FALSE),
			'menu_position' => 5,
			'has_archive' => true
		);
		register_post_type( 'article', $args );
	}

	/**
	 * Manages meta boxes on the post edit screen
	 *
	 * @return void
	 */
	public function manage_meta_boxes() {	
		if ( is_plugin_active( 'wordpress-fieldmanager/fieldmanager.php' ) ) {
			$fields = $this->get_fields();
			add_meta_box( 'headlines_meta_box', __('Headlines'), array( $fields['headlines'], 'render_meta_box' ), 'article', 'normal', 'high' );
			add_meta_box( 'rss_description_meta_box', __('RSS Description'), array( $fields['rss_description'], 'render_meta_box' ), 'article', 'normal', 'high' );
			add_meta_box( 'disable_ads_meta_box', __('Disable ads'), array( $fields['disable_ads'], 'render_meta_box' ), 'article', 'side', 'low' );
			add_meta_box( 'sources', __('Source'), array( $fields['sources'], 'render_meta_box' ), 'article', 'side', 'default' );
			add_meta_box( 'language', __('Language'), array( $fields['language'], 'render_meta_box' ), 'article', 'side', 'default' );
			add_meta_box( 'rights', __('Rights'), array( $fields['rights'], 'render_meta_box' ), 'article', 'side', 'default' );
		}
	}

	/**
	 * Saves data for all fieldmanager custom fields
	 *
	 * @param int $post_id
	 * @return void
	 */
	public function save_fields( $post_id ) {
		foreach ( $this->get_fields() as $field ) {
			$field->save_to_post_meta( $post_id, $_POST[ $field->name ] );
		}
	}

	/**
	 * Configuration information for fieldmanager custom fields on meta boxes
	 *
	 * @return array
	 */
	public function get_fields() {
		return array(
			'headlines' => new Fieldmanager_Group( array(
				'name' => 'headlines',
				'label' => null,
				'children' => array(
					'post_title' => new Fieldmanager_Textfield( array(
						'label' => __('Title'),
						'name' => 'post_title',
					) ),
					'long_headline' => new Fieldmanager_Textfield( array(
						'label' => __('Long'),
						'name' => 'long_headline',
					) ),
					'short_headline' => new Fieldmanager_Textfield( array(
						'label' => __('Short'),
						'name' => 'short_headline',
					) ),
				)
			) ),
			'rss_description' => new Fieldmanager_Group( array(
				'name' => 'rss_description',
				'label' => null,
				'children' => array(
					'rss_description' => new Fieldmanager_TextArea( array(
						'label' => __('The text in the field below will be used as the description of this article in the various site RSS feeds. If left blank, a description will be generated from the body of the article.'),
						'name' => 'rss_description',
						'attributes' => array(
							'cols' => 80,
							'rows' => 5
						)
					) ),
				)
			) ),
			'disable_ads' => new Fieldmanager_Group( array(
				'name' => 'disable_ads',
				'label' => null,
				'children' => array(
					'disable_ads' => new Fieldmanager_Checkbox( array(
						'label' => __('Disable ads for this article'),
						'name' => 'disable_ads',
						'attributes' => array(
							'cols' => 80,
							'rows' => 5
						)
					) ),
				)
			) ),
			'sources' => new Fieldmanager_Group( array(
				'name' => 'sources',
				'label' => null,
				'children' => array(
					'sources' => new Fieldmanager_Select( array(
						'label' => null,
						'name' => 'sources',
						'taxonomy' => 'sources',
						'taxonomy_args' => array(
							'orderby' => 'name',
							'hide_empty' => false
						)
					) ),
				)
			) ),
			'language' => new Fieldmanager_Group( array(
				'name' => 'language',
				'label' => null,
				'children' => array(
					'language' => new Fieldmanager_Select( array(
						'label' => null,
						'name' => 'language',
						'taxonomy' => 'language',
						'taxonomy_args' => array(
							'orderby' => 'name',
							'hide_empty' => false
						)
					) ),
				)
			) ),
			'rights' => new Fieldmanager_Group( array(
				'name' => 'rights',
				'label' => null,
				'children' => array(
					'rights' => new Fieldmanager_Select( array(
						'label' => null,
						'name' => 'rights',
						'taxonomy' => 'rights',
						'taxonomy_args' => array(
							'orderby' => 'name',
							'hide_empty' => false
						)
					) ),
				)
			) ),
		);
	}
}

$context = new stdClass;
$context->types = array();
$context->types['article'] = new Post_Type_Article();