<?php
/**
 * @package Fieldmanager_Datasource
 */
 
/**
 * Data source for WordPress Posts, for autocomplete and option types.
 * @package Fieldmanager_Datasource
 */
class Fieldmanager_Datasource_User extends Fieldmanager_Datasource {
 
    /**
     * Supply a function which returns a list of users; takes one argument,
     * a possible fragement
     */
    public $query_callback = Null;
 
    /**
     * Arguments to get_users(), which uses WP's defaults.
     * @see http://codex.wordpress.org/Template_Tags/get_users
     */
    public $query_args = array();
 
    /**
     * @var boolean
     * Allow AJAX. If set to false, Autocomplete will pre-load get_items() with no fragment,
     * so False could cause performance problems.
     */
    public $use_ajax = True;

    /**
     * @var string
     * Display property. Defaults to display_name, but can also be 'user_login', 'user_email',
     * or 'user_nicename'
     */
    public $display_property = 'display_name';

    /**
     * @var string
     * Capability required to refer to a user via this datasource.
     * @see http://codex.wordpress.org/Roles_and_Capabilities
     */
    public $capability = 'list_users';
 
    /**
     * @var string|Null
     * If not empty, set this post's ID as a value on the user. This is used to
     * establish two-way relationships.
     */
    public $reciprocal = Null;
 
    // constructor not required for this datasource; options are just set to keys,
    // which Fieldmanager_Datasource does.
 
    /**
     * Get a post title by post ID
     * @param int $value post_id
     * @return string post title
     */
    public function get_value( $value ) {
        $id = intval( $value );
        $user = get_userdata( $id );
        return $user ? $user->{$this->display_property} : '';
    }
 
    /**
     * Get users which match this datasource, optionally filtered by 
     * a search fragment, e.g. for Autocomplete.
     * @param string $fragment
     * @return array post_id => post_title for display or AJAX
     */
    public function get_items( $fragment = Null ) {
        if ( is_callable( $this->query_callback ) ) {
            return call_user_func( $this->query_callback, $fragment );
        }
        $default_args = array();
        $user_args = array_merge( $default_args, $this->query_args );
        $ret = array();
        if ( $fragment ) $user_args['search'] = $fragment;
        $users = get_users( $user_args );
        foreach ( $users as $u ) {
            $ret[$u->ID] = $u->{$this->display_property};
        }
        return $ret;
    }
 
    /**
     * Get an action to register by hashing (non cryptographically for speed)
     * the options that make this datasource unique.
     * @return string ajax action
     */
    public function get_ajax_action() {
        if ( !empty( $this->ajax_action ) ) return $this->ajax_action;
        $unique_key = json_encode( $this->query_args );
        $unique_key .= $this->display_property;
        $unique_key .= (string) $this->query_callback;
        return 'fm_datasource_post' . crc32( $unique_key );
    }
 
    /**
     * For post relationships, delete reciprocal post metadata prior to saving (presave will re-add)
     * @param array $values new post values
     * @param array $current_values existing post values
     */
    public function presave_alter_values( Fieldmanager_Field $field, $values, $current_values ) {
        if ( $field->data_type != 'post' || !$this->reciprocal ) return $values;
        foreach ( $current_values as $user_id ) {
            delete_user_meta( $user_id, $this->reciprocal, $field->data_id );
        }
        return $values;
    }
 
    /**
     * Handle reciprocal postmeta
     * @param int $value
     * @return string
     */
    public function presave( Fieldmanager_Field $field, $value, $current_value ) {
        if ( empty( $value ) ) return;
        $return_single = False;
        if ( !is_array( $value ) ) {
            $return_single = True;
            $value = array( $value );
        }
        foreach ( $value as $i => $v ) {
            $value[$i] = intval( $v );
            if( !current_user_can( $this->capability, $v ) ) {
                die( 'Tried to refer to user ' . $v . ' which current user cannot edit.' );  
            }
            if ( $this->reciprocal ) {
                add_user_meta( $v, $this->reciprocal, $field->data_id );
            }
        }
        return $return_single ? $value[0] : $value;
    }
 
    /**
     * Get view link for a user
     * @param int $value
     * @return string
     */
    public function get_view_link( $value ) {
        return '';
    }
 
    /**
     * Get edit link for a user
     * @param int $value
     * @return string
     */
    public function get_edit_link( $value ) {
        return sprintf(
            ' <a target="_new" class="fm-autocomplete-edit-link %s" href="%s">%s</a>',
            empty( $value ) ? 'fm-hidden' : '',
            empty( $value ) ? '#' : get_edit_user_link( $value ),
            __( 'Edit' )
        );
    }
}