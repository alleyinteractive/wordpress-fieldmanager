<?php

class ContentTypeQuiz {
	public function __construct() {
		add_action( 'init', array( $this, 'create_content_type' ) );
	}

	public function create_content_type() {
		$labels = array(
			'name' => _x( 'Quizzes', 'post type general name' ),
			'singular_name' => _x( 'Quiz', 'post type singular name' ),
			'add_new' => _x( 'Add New', 'quiz' ),
			'add_new_item' => __( 'Add New Quiz' )
		);

		$args = array(
			'labels' => $labels,
			'publicly_queryable' => true,
			'show_ui' => true,
			'query_var' => true,
			'rewrite' => array( 'slug' => 'quiz', 'with_front' => false ),
			'capability_type' => 'page',
			'menu_position' => 5
		);
		register_post_type( 'quiz', $args );

		$this->questions = new Fieldmanager_Group( array(
			'limit' => 0,
			'starting_count' => 2,
			'add_more_label' => 'Add another question',
			'name' => 'question',
			'label' => 'Question',
			'sortable' => True,
			'collapsible' => True,
			'content_types' => array(
				array(
					'content_type' => 'quiz',
					'meta_box_name' => 'quiz_questions',
					'meta_box_title' => 'Quiz Questions'
				),
			),
			'children' => array(
				'question_title' => new Fieldmanager_Textfield( array(
					'label' => 'Question Title',
					'name' => 'question_title',
				) ),
				'answer' => new Fieldmanager_Textfield( array(
					'limit' => 0,
					'starting_count' => 4,
					'add_more_label' => 'Add another answer',
					'name' => 'answer',
					'label' => 'Answers',
					'sortable' => True,
					'one_label_per_item' => False,
				) ),
			)
		) );

		$this->related = new Fieldmanager_Group( array(
			'name' => 'related_posts',
			'content_types' => array(
				array(
					'content_type' => 'quiz',
					'meta_box_name' => 'related_posts',
					'meta_box_title' => 'Related Posts'
				),
			),
			'children' => array(
				'post' => new Fieldmanager_Post( array(
					'limit' => 0,
					'starting_count' => 4,
					'add_more_label' => 'Add another post',
					'name' => 'post',
					'label' => 'Posts',
					'sortable' => True,
					'one_label_per_item' => False,
				) ),
			)
		) );
	}
}

$context = new stdClass;
$context->types = array();
$context->types['quiz'] = new ContentTypeQuiz();