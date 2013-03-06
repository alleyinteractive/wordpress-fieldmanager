( function( $ ) {

$.fn.fm_grid_serialize = function() {
	var rows = [], row_counter = 0, self = this, bottom_row_with_data = 0;
	if ( this.find( 'tbody:visible' ).length == 0 ) return;
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
	$( this.data( 'input-selector' ) ).val( json_data );
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
	self.data( 'input-selector', 'input:hidden[name="' + self.data( 'fm-grid-name' ) + '"]' );
	var rows = $.parseJSON( $( self.data( 'input-selector' ) ).val() );
	var row = 0;
	var hot = self.data( 'handsontable' );
	var data_to_set = [];
	if ( rows ) {
		for ( var i = 0; i < rows.length; i++ ) {
			for ( var j = 0; j < rows[i].length; j++ ) {
				data_to_set.push( [i, j, rows[i][j]['value']] );
			}
		}
	}
	hot.setDataAtCell( data_to_set );
	this.find( 'tbody tr:visible' ).each( function() {
		var base_name = self.data( 'fm-grid-name' );
		var col = 0;
		if ( !rows || typeof( rows[row] ) === 'undefined' ) return false;
		$( this ).find( 'td' ).each( function() {
			// console.log([row, col, rows[row][col]]);
			if ( rows[row][col] == undefined ) return;
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

$( '.grid-activate' ).live( 'click', function( e ) {
	e.preventDefault();
	var $wrapper = $( $( this ).parents( '.grid-toggle-wrapper' )[0] );
	if ( $wrapper.hasClass( 'with-grid' ) ) {
		$wrapper.find( '.fm-grid' ).hide();
		$wrapper.removeClass( 'with-grid' );
		$( this ).html( $( this ).data( 'original-title' ) );
	}
	else {
		$( this ).data( 'original-title',  $( this).html() );
		$( this ).html( $( this ).data( 'with-grid-title' ) );
		$wrapper.addClass( 'with-grid' );
		$wrapper.find( '.fm-grid' ).show();
	}
} );

})( jQuery );