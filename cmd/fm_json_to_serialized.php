<?php

// Run the script longer than the max execution time (may need to adjust for final file based on measured execution time)
if( !ini_get('safe_mode') ) ini_set( 'max_execution_time', 100000 );

// Extend the memory limit for the script to be on the safe side
define( 'WP_MAX_MEMORY_LIMIT', '4096M' );

// Load the WordPress environment
require_once( '../wp-load.php' );

// If no meta keys are provided, display a usage notice and exit
if ( !isset( $argv[1] ) ) {
	echo "Usage: /path/to/php fm_json_to_serialized.php meta_key_1,meta_key_2,meta_key_etc [limit]\n" ;
	exit(1);
}

// Allow the limit to be configurable, though this probably isn't very useful
$limit = 10000000;
if ( isset( $argv[2] ) ) $limit = intval( $argv[2] );

exit( main( $argv[1], $limit ) );

function main( $meta_keys_param, $limit ) {
	
	// Note the start time and keep track of how many fields have been converted for script output
	$timestamp_start = microtime( true );
	$converted_fields = 0;
	
	// Get the list of meta keys that need to be converted from json to serialized format
	// Convert them into a format compatible with the WHERE clause of the SQL query
	$meta_keys = explode( ",", $meta_keys_param );
	$meta_keys = array_map( 'fm_convert_trim_and_quote', $meta_keys );
	$meta_keys = implode( ",", $meta_keys );
	
	// It will be fastest to use a simple SQL query to find all the matching rows in the postmeta table
	global $wpdb;
	
	// Query for all postmeta rows matching the provided keys
	// This script is internal only, so prepare is not necessary
	$sql = sprintf( 
		"SELECT post_id, meta_key, meta_value FROM $wpdb->postmeta WHERE meta_key IN (%s) LIMIT %s",
		$meta_keys,
		$limit
	);
	$meta_rows = $wpdb->get_results( $sql );
		
	// Verify any results were returned before proceeding
	if( $meta_rows == null ) {
		echo "No matching Fieldmanager meta data fields were found.\n";
		return;
	}
	
	// Iterate through the rows and update data as needed
	echo "Starting conversion of Fieldmanager JSON data to serialized format.\n";
	foreach( $meta_rows as $meta_row ) {
		// Attempt to decode the JSON data. If this fails, the data is either invalid or already updated.
		// In both cases, ignore it and move to the next row.
		$json_data = json_decode( $meta_row->meta_value );
		if( $json_data != null ) {
			// The decoded JSON data will be a PHP object. It must be an associative array, so convert it.
			$array_data = fm_object_to_array( $json_data );

			// Update the value in the database
			// WordPress will automatically convert this to its serialized format
			$result = update_post_meta( $meta_row->post_id, $meta_row->meta_key, $array_data, $meta_row->meta_value );
			if( $result ) {
				echo sprintf(
					"Converted %s for post ID %s from JSON to serialized data\n",
					$meta_row->meta_key,
					$meta_row->post_id
				);
				$converted_fields++;
			} else {
				echo sprintf(
					"ERROR: Failed saving serialized data for %s for post ID %s\n",
					$meta_row->meta_key,
					$meta_row->post_id
				);
			}
		} else {
			// JSON decoding failed. Skip this field.
			echo sprintf(
				"WARNING: Skipping %s for post ID %s. Not in valid JSON format.\n",
				$meta_row->meta_key,
				$meta_row->post_id
			);
		}
	}
	
	echo "Finished converting " . $converted_fields . " Fieldmanager fields from JSON to serialized data in " . number_format( (microtime( true ) - $timestamp_start), 2 ) . " seconds\n\n"; 
	
}

// Handles removing extraneous quotes and spaces from the meta keys specified on the command line to ensure we have a proper SQL query format
function fm_convert_trim_and_quote( $meta_key ) {
	$meta_key = str_replace( '"', "", $meta_key );
	$meta_key = str_replace( "'", "", $meta_key );
	return sprintf( 
		"'%s'",
		trim( $meta_key )
	);
}

// Handles recursive conversion of the returned JSON object into the associative array now expected by Fieldmanager
function fm_object_to_array( $obj ) {
	$arr = is_object( $obj ) ? get_object_vars( $obj ) : $obj;
	$converted_arr = array();
	foreach ( $arr as $key => $val ) {
		$val = ( is_array( $val ) || is_object( $val ) ) ? fm_object_to_array( $val ) : $val;
		$converted_arr[$key] = $val;
	}
	return $converted_arr;
}