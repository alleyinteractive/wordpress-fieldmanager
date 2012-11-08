( function( $ ) {

$.fn.fm_grid_serialize = function() {
	console.log('serialize...');
	var rows = [], row_counter = 0, self = this, bottom_row_with_data = 0;
	this.find( 'tbody tr:visible' ).each( function() {
		var row = [];
		$( this ).find( 'td' ).each( function() {
			var to_save = {
				value: $( this ).text()
			};
			self.trigger( 'fm_grid_serialize_cell', [to_save, this] );
			for ( k in to_save) {
				if ( to_save[k].length ) {
					bottom_row_with_data = row_counter;
					break;
				}
			}
			row.push( to_save );
		} );
		row_counter++;
		if ( row.length ) rows.push( row );
	} );
	var json_data = JSON.stringify( rows.slice( 0, bottom_row_with_data + 1 ) );
	console.log( json_data );
	$( '#' + self.attr( 'id' ) + '-input' ).val( json_data );
}

$.fn.fm_grid = function( opts ) {
	var self = this;
	self.data( 'fm_grid_load_complete', false );
	opts.onChange = function() {
		self.trigger( 'fm_grid_change' );
		if ( self.data( 'fm_grid_load_complete' ) ) self.fm_grid_serialize();
	};
	self.trigger( 'fm_grid_options', opts );
	var grid_instance = self.handsontable( opts );
	var input_id = '#' + self.attr( 'id' ) + '-input';
	var rows = $.parseJSON( $( input_id ).val() );
	var row = 0;
	this.find( 'tbody tr:visible' ).each( function() {
		var base_name = self.data( 'fm-grid-name' );
		var col = 0;
		if ( !rows || typeof( rows[row] ) === 'undefined' ) return false;
		$( this ).find( 'td' ).each( function() {
			$( this ).text( rows[row][col]['value'] );
			self.trigger( 'fm_grid_unserialize_cell', [rows[row][col], this] );
			col++;
		} );
		row++;
	} );
	self.data( 'fm_grid_load_complete', true );

	// Event listener to serialize the form properly.
	$( '#publish, #save-post' ).on( 'mousedown', function() {
		if ( self.data( 'handsontable' ).editproxy ) {
			self.data( 'handsontable' ).editproxy.destroy();
			$( self ).fm_grid_serialize();
		}
	} );
}

})( jQuery );