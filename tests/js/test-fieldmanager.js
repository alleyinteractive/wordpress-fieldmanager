(function( QUnit, $ ) {

	QUnit.begin(function( details ) {
		// Don't hide the fixture div by default: FM relies on visibility.
		$( '#qunit-fixture' ).css( 'position', 'static' );
		// Initialize the non-FM sortable instance.
		$( '#already-sorted' ).sortable( { 'handle': '.notahandle' } );
	});

	QUnit.done(function( details ) {
		// Hide the fixtures div again: We don't need it to review the results.
		$( '#qunit-fixture' ).css( 'position', 'absolute' );
	});

	module( 'fieldmanager' );

	QUnit.test( "Sortables", function( assert ) {
		var $sortable = $( '#sortable' );
		var $alreadySorted = $( '#already-sorted' );
		var $notSortable = $( '#not-sortable' );

		assert.ok( $sortable.sortable( 'instance' ), "Visible sortable element should have a sortable() instance" );
		assert.equal( $sortable.sortable( 'option', 'handle' ), '.fmjs-drag', "Verify the 'handle' option" );
		assert.equal( $sortable.sortable( 'option', 'items' ), '> .fm-item', "Verify the 'items' option" );
		assert.ok( $sortable.sortable( 'option', 'start' ), "A function should be attached to 'start'" );
		assert.ok( $sortable.sortable( 'option', 'stop' ), "A function should be attached to 'stop'" );
		assert.notOk( $sortable.sortable( 'option', 'over' ), "Only 'start' and 'stop' should have callbacks" );

		assert.equal( $alreadySorted.sortable( 'option', 'handle' ), '.notahandle', "FM should not alter existing sortable() instance" );

		assert.notOk( $notSortable.sortable( 'instance' ), "Invisible sortable element should not have a sortable() instance" );
	});

	QUnit.test( "Display-if comparisons for single field", function( assert ) {
		// start with non-foo value
		assert.notEqual( $( '#fm-display_if_test-0-primary-0' ).val(), 'foo' );
		assert.notOk( $( '.fm-single-test-equals-wrapper' ).is( ':visible' ) );
		assert.ok( $( '.fm-single-test-not-equals-wrapper' ).is( ':visible' ) );
		assert.notOk( $( '.fm-single-test-contains-wrapper' ).is( ':visible' ) );

		// change to foo and expect opposite results
		$( '#fm-display_if_test-0-primary-0' ).val( 'foo' ).change();
		assert.ok( $( '.fm-single-test-equals-wrapper' ).is( ':visible' ) );
		assert.notOk( $( '.fm-single-test-not-equals-wrapper' ).is( ':visible' ) );
		assert.ok( $( '.fm-single-test-contains-wrapper' ).is( ':visible' ) );

	} );

	QUnit.test( "Display-if numeric comparisons", function( assert ) {
		assert.ok( $('#fm-display_if_numeric-0-primary-numeric-0').val() == 12 );
		assert.ok( $( '.fm-numeric-test-equals-wrapper' ).is( ':visible' ) );
		assert.notOk( $( '.fm-numeric-test-not-equals-wrapper' ).is( ':visible' ) );

		$('#fm-display_if_numeric-0-primary-numeric-0').val( 13 ).change();
		assert.notOk( $( '.fm-numeric-test-equals-wrapper' ).is( ':visible' ) );
		assert.ok( $( '.fm-numeric-test-not-equals-wrapper' ).is( ':visible' ) );
	} );

	QUnit.test( 'Renumber', function( assert ) {
		// Reorganize the items in the simple group.
		var $fieldLast = $( '#renumbered .fm-item' ).last();
		$fieldLast.remove();
		$( '#renumbered' ).prepend( $fieldLast );

		// Reorganize the items in the complex group.
		var $groupLastTopLevel = $( '#renumbered-group > .fm-item' ).last();
		$groupLastTopLevel.remove();
		$( '#renumbered-group' ).prepend( $groupLastTopLevel );

		var $groupLastSubLevel = $( '#renumbered-subgroup > .fm-item' ).last();
		$groupLastSubLevel.remove();
		$( '#renumbered-subgroup' ).prepend( $groupLastSubLevel );

		// Simulate a collapse event, which is one way to trigger fm_renumber().
		$( '.test-renumber.fmjs-collapsible-handle' ).each(function() {
			$( this ).trigger( 'click' );
		});

		assert.equal( $( '[name="mytest[0]"]' ).text(), 'Third', "Corresponding name attributes and text values in the reordered simple group" );
		assert.equal( $( '[name="mytest[1]"]' ).text(), 'First', "Corresponding name attributes and text values in the reordered simple group" );
		assert.equal( $( '[name="mytest[2]"]' ).text(), 'Second', "Corresponding name attributes and text values in the reordered simple group" );

		assert.equal( $( '[name="mytest[0][mysubtest][0]"]' ).text(), 'Epsilon', "Corresponding name attributes and text values in the reordered top-level group" );
		assert.equal( $( '[name="mytest[1][mysubtest][0]"]' ).text(), 'Charlie', "Corresponding name attributes and text values in the reordered subgroup" );
		assert.equal( $( '[name="mytest[1][mysubtest][1]"]' ).text(), 'Alpha', "Corresponding name attributes and text values in the reordered subgroup" );
		assert.equal( $( '[name="mytest[1][mysubtest][2]"]' ).text(), 'Bravo', "Corresponding name attributes and text values in the reordered subgroup" );
		assert.equal( $( '[name="mytest[2][mysubtest][0]"]' ).text(), 'Delta', "Corresponding name attributes and text values in the reordered top-level group" );
	});

})( window.QUnit, window.jQuery );
