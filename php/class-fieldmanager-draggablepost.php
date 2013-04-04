<?php
/**
 * Class file for draggable post selector field
 * @package Fieldmanager
 */
class Fieldmanager_DraggablePost extends Fieldmanager_Field {

	/**
	 * @var array
	 * Keyed array defining repository boxes and what their content should be. Keys are the machine names of the repositories. Each can include:
	 *		- 'label' (string) the publicly displayed label of the box (eg 'News Artickes')
	 *		- 'post_type' (string or array of strings) post type(s) to select for this box
	 *		- 'length' (int) number of items to show in the box
	 *	 	- 'orderby' (string) field to order the query by, as allowed by WP_Query
	 *		- 'order' (string) ASC or DESC
	 *		- 'taxonomy_args' (array) arguments to pass to WP_Query to filter by category (see https://codex.wordpress.
	 *			org/Class_Reference/WP_Query#Taxonomy_Parameters). If omitted, no taxonomy filtering will be performed.
	 *		- 'callback' (callable) a custom function to call in lieu of WP_Query to retrieve posts for this repository. The function 
	 *			must have the signature callback($key, $data) and must return an array of post ids. If callback is set, all the above 
	 *			options (except label) will be overridden.
	 */
	public $repositories = array();

	/**
	 * @var array
	 * Keyed array defining the bins (destination droppable locations) for the draggable items. Keys are machine names, values are labels.
	 */
	public $bins = array();

	/**
	 * @var boolean
	 * Provide "Use image?" checkbox for draggable divs? 
	 */
	public $use_image_checkbox = False;


	/**
	 * Add scripts and styles and other setup tasks.
	 * @param string $label
	 * @param array $options
	 */
	public function __construct( $label = '', $options = array() ) {
		parent::__construct( $label, $options );
		// Refuse to allow more than one instance of this field type.
		$this->limit = 1;

		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'jquery-ui-droppable' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		fm_add_script( 'fm_draggablepost_js', 'js/fieldmanager-draggablepost.js' );
		fm_add_style( 'fm_draggablepost_css', 'css/fieldmanager-draggablepost.css' );
	} 


	/**
	 * Massage form data into proper storage format..
	 * @param array $value
	 * @return array $value
	 */
	public function presave( $value, $current_values = array() ) {

		foreach ( $this->bins as $bin => $name ) {
			if ( isset( $value[$bin] ) ) {
				$value[$bin] = explode(',', $value[$bin]);
			}
		}

		$image_flags = array();
		if ( isset( $value['_image_flags'] ) ) {
			foreach ( $value['_image_flags'] as $id => $val ) {
				$image_flags[] = $id;
			}
			$value['_image_flags'] = $image_flags;
		}

		return $value;
	}

	/**
	 * Render form element
	 * @param mixed $value
	 * @return string HTML
	 */
	public function form_element( $value ) {
		// Avoid null array errors later.
		$value['_image_flags'] = isset( $value['_image_flags'] ) ? $value['_image_flags'] : array();

		$all = $this->flatten_arrays( $value );
		$out = '<div id="fm-draggablepost"><div class="post-repository-wrapper">';
		foreach ( $this->repositories as $name => $repo ) {
			$out .= sprintf( '<h2>%s</h2><ul class="post-repository sortables">', $repo['label'] );
			if ( isset( $repo['callback'] ) ) {
				$ids = call_user_func( $repo['callback'], $name, $repo );
				if ( !empty( $ids ) ) {
					// Return value here should be all numeric post ids, but we'll tolerate non-numerics if they show up.
					foreach ( $ids as $id ) {
						if ( !is_numeric( $id ) || in_array( $id, $all ) ) {
							continue;
						}
						$out .= $this->draggable_item_html( $id );
					}
				}
			}
			else {
				$query_args = array(
					'post_type' => $repo['post_type'],
					'post_status' => 'publish',
					'posts_per_page' => $repo['length'],
					'orderby' => $repo['orderby'],
					'order' => $repo['order'],
					'tax_query' => array($repo['taxonomy_args']),
				);
				$q = new WP_Query( $query_args );
				while ( $q->have_posts() ) {
					$q->the_post();
					if ( in_array( get_the_ID(), $all ) ) {
						continue;
					}
					$out .= $this->draggable_item_html( get_the_ID() );
				}
			}
			$out .= "</ul>";
		}
		$out .= "</div>";
		$out .= '<div class="post-bin-wrapper">';
		foreach ( $this->bins as $name => $bin ) {
			$out .= sprintf( '<h2>%s</h2>', $bin );
			$out .= sprintf( '<ul class="post-bin sortables" id="%s-bin"><em class="empty-message">drop posts here</em>', $name );
			if ( isset( $value[$name] ) ) {
				foreach ( $value[$name] as $id ) {
					if ( !$id ) {
						continue;
					}
					$out .= $this->draggable_item_html( $id, in_array( $id, $value['_image_flags'] ) );
				}
			}
		    $out .= "</ul>";
		}

		foreach ( $this->bins as $bin => $label ) {
			$out .= sprintf( '<input type="hidden" value="%s" name="%s" id="%s" />', 
						empty( $value[$bin] ) ?  '' : implode( ',', $value[$bin] ),
						$this->get_form_name() . '[' . $bin . ']',
						$bin
					);
		}
		$out .= '</div></div>';
		return $out;
	}

	/**
	 * Generate the html for a single draggable item
	 * @param int $post_id
	 * @param boolean $use_image_checked if true, render this item with the "use image" checkbox checked (if enabled)
	 * @return string containing the li element.
	 */
	protected function draggable_item_html( $post_id, $use_image_checked = false ) {
		$post = get_post( $post_id );
		$bylines = array();
		if ( is_plugin_active( 'co-authors-plus/co-authors-plus.php' ) ) {
			$authors = get_coauthors( $post_id );
			foreach ($authors as $author) {
				$bylines[] = $author->display_name;
			}
			if ( empty( $bylines ) ) {
				$authorstr = '(no authors)';
			}
			else {
				$authorstr = implode(', ', $bylines);
			}
		}
		else {
			$author = get_userdata( $post->post_author );
			$authorstr = $author->display_name;
		}

		$image_meta = get_post_meta( $post_id, '_thumbnail_id' );

		$permalink = get_permalink( $post_id );

		if ( isset( $image_meta[0] ) ) {
			$image = wp_get_attachment_image( $image_meta[0], array(32,32) );
		}
		else{
			$image = '';
		}

		$li = sprintf( '<li class="draggable-post" id="draggable-post-%d" post_id="%d">', $post_id, $post_id );
		$li_inner = sprintf('<strong><a href="%s" target="_new">%s</a></strong><br />
							<small>%s &mdash; %s</small><br />
							<small><em>%s %s</em></small>',
			$permalink,
			$post->post_title,
			$post->post_date,
			$authorstr,
			$image,
			$post->post_excerpt
		);

		if ( $this->use_image_checkbox && $image ) {
			$checked = $use_image_checked ? 'checked' : '';
			$li_inner .= sprintf( '<small><input type="checkbox" value="1" name="%s[_image_flags][%d]" %s /> Use image?</small>', $this->get_form_name(), $post_id, $checked );
		}

		$li = $li . apply_filters( 'fieldmanager_draggablepost_li_content', array('li_inner' => $li_inner, 'post_id' => $post_id) ) . '</li>';
		return $li;
	}

	/**
	 * Helper to convert the value passed to form_element into a non-hierarchical list of post_ids so we know which posts are already assigned to a bin and thus should be skipped in rendering.
	 * @param array $value as passed to form_element()
	 * @return array containing post_ids in any subarray, except the one attached to key '_image_flags'.
	 */
	protected function flatten_arrays( $value ) {
		if ( empty ( $value ) ) {
			return array();
		}
		$result = array();
		foreach ( $value as $key => $array ) {
			if ( $key == '_image_flags' ) {
				continue;
			}
			$result = array_merge( $result, $value[$key] );
		}
		$return = array();
		foreach ( $result as $id ) {
			if ( $id ) {
				$return[] = $id;
			}
		}
		return $return;
	}
}