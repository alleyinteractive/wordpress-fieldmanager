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

	/**
	 * test basic string comparisons for equals, not-equals, contains
	 */
	QUnit.test( "Display-if comparisons for single field", function( assert ) {
		$trigger = $( '#fm-display_if_test-0-primary-0' );
		// start with non-foo value
		assert.notEqual( $trigger.val(), 'foo' );
		assert.notOk( $( '.fm-single-test-equals-wrapper' ).is( ':visible' ) );
		assert.ok( $( '.fm-single-test-not-equals-wrapper' ).is( ':visible' ) );
		assert.notOk( $( '.fm-single-test-contains-wrapper' ).is( ':visible' ) );

		// change to foo and expect opposite results
		$trigger.val( 'foo' ).change();
		assert.ok( $( '.fm-single-test-equals-wrapper' ).is( ':visible' ) );
		assert.notOk( $( '.fm-single-test-not-equals-wrapper' ).is( ':visible' ) );
		assert.ok( $( '.fm-single-test-contains-wrapper' ).is( ':visible' ) );

	} );

	/**
	 * test numeric equals and not-equals
	 */
	QUnit.test( "Display-if numeric comparisons", function( assert ) {
		$trigger = $('#fm-display_if_numeric-0-primary-numeric-0');
		assert.equal( $trigger.val(), 12 );
		assert.ok( $( '.fm-numeric-test-equals-wrapper' ).is( ':visible' ) );
		assert.notOk( $( '.fm-numeric-test-not-equals-wrapper' ).is( ':visible' ) );

		$trigger.val( 13 ).change();
		assert.notOk( $( '.fm-numeric-test-equals-wrapper' ).is( ':visible' ) );
		assert.ok( $( '.fm-numeric-test-not-equals-wrapper' ).is( ':visible' ) );
	} );

	/**
	 * test equals, not-equals, contains with string and numeric CSV values
	 */
	QUnit.test( "Display-if CSV comparisons", function( assert ) {
		$trigger = $('#fm-display_if_csv-0-primary-csv-0');

		assert.equal( $trigger.val(), 'bar' );
		assert.ok( $( '.fm-csv-strings-wrapper' ).is(':visible' ) );
		assert.notOk( $( '.fm-csv-numbers-wrapper' ).is(':visible' ) );
		assert.notOk( $( '.fm-csv-not-equals-wrapper' ).is(':visible' ) );
		assert.notOk( $( '.fm-csv-contains-wrapper' ).is(':visible' ) );

		$trigger.val( 12 ).change();
		assert.notOk( $( '.fm-csv-strings-wrapper' ).is(':visible' ) );
		assert.ok( $( '.fm-csv-numbers-wrapper' ).is(':visible' ) );
		assert.ok( $( '.fm-csv-not-equals-wrapper' ).is(':visible' ) );
		assert.notOk( $( '.fm-csv-contains-wrapper' ).is(':visible' ) );

		$trigger.val( 'foo' ).change();
		assert.ok( $( '.fm-csv-strings-wrapper' ).is(':visible' ) );
		assert.notOk( $( '.fm-csv-numbers-wrapper' ).is(':visible' ) );
		assert.ok( $( '.fm-csv-not-equals-wrapper' ).is(':visible' ) );
		assert.ok( $( '.fm-csv-contains-wrapper' ).is(':visible' ) );
	} );

	/**
	 * test showing/hiding repeatable fields
	 */
	QUnit.test( "Display-if for repeatable field", function( assert ) {
		$trigger = $( '#fm-display_if_repeatable-0-primary-repeatable-0' );
		assert.equal( $trigger.val(), 'bar' );
		// start with no visible fields
		assert.equal( $( '.fm-test-repeatable:visible:not(.fmjs-proto)' ).length, 0 );

		// change to display-if value
		$trigger.val( 'foo' ).change();
		assert.equal( $( '.fm-test-repeatable:visible:not(.fmjs-proto)' ).length, 1 );

		// add a field, should be visible
		$('.fm-test-repeatable-add-another:first').click();
		assert.equal( $( '.fm-test-repeatable:visible:not(.fmjs-proto)' ).length, 2 );

		// hide fields
		$trigger.val( 'bar' ).change();
		assert.equal( $( '.fm-test-repeatable:visible:not(.fmjs-proto)' ).length, 0 );

		// show again
		$trigger.val( 'foo' ).change();
		assert.equal( $( '.fm-test-repeatable:visible:not(.fmjs-proto)' ).length, 2 );
	} );

	/**
	 * test using custom JS events to show/hide fields
	 */
	QUnit.test( "Display-if custom events", function( assert ) {
		// start with all visible
		assert.equal( $( '.fm-multi-repeatable:visible:not(.fmjs-proto)' ).length, 1 );
		assert.equal( $( '.fm-multi-single:visible' ).length, 1 );
		assert.equal( $( '.fm-multi-other:visible' ).length, 1 );

		// hide two of them
		$( document ).trigger( 'my-custom-event', [ false ] );
		assert.equal( $( '.fm-multi-repeatable:visible:not(.fmjs-proto)' ).length, 0 );
		assert.equal( $( '.fm-multi-single:visible' ).length, 0 );
		assert.equal( $( '.fm-multi-other:visible' ).length, 1 );

		// hide again, should stay hidden
		$( document ).trigger( 'my-custom-event', [ false ] );
		assert.equal( $( '.fm-multi-repeatable:visible:not(.fmjs-proto)' ).length, 0 );
		assert.equal( $( '.fm-multi-single:visible' ).length, 0 );
		assert.equal( $( '.fm-multi-other:visible' ).length, 1 );

		// show them
		$( document ).trigger( 'my-custom-event', [ true ] );
		assert.equal( $( '.fm-multi-repeatable:visible:not(.fmjs-proto)' ).length, 1 );
		assert.equal( $( '.fm-multi-single:visible' ).length, 1 );
		assert.equal( $( '.fm-multi-other:visible' ).length, 1 );

		// show again, should stay visible
		$( document ).trigger( 'my-custom-event', [ true ] );
		assert.equal( $( '.fm-multi-repeatable:visible:not(.fmjs-proto)' ).length, 1 );
		assert.equal( $( '.fm-multi-single:visible' ).length, 1 );
		assert.equal( $( '.fm-multi-other:visible' ).length, 1 );

		// add a repeated field, should be visible
		$('.fm-multi-repeatable-add-another:first').click();
		assert.equal( $( '.fm-multi-repeatable:visible:not(.fmjs-proto)' ).length, 2 );

		// hide again
		$( document ).trigger( 'my-custom-event', [ false ] );
		assert.equal( $( '.fm-multi-repeatable:visible:not(.fmjs-proto)' ).length, 0 );
		assert.equal( $( '.fm-multi-single:visible' ).length, 0 );
		assert.equal( $( '.fm-multi-other:visible' ).length, 1 );

		// hide the other one
		$( document ).trigger( 'my-other-event', [ false ] );
		assert.equal( $( '.fm-multi-other:visible' ).length, 0 );

		// show originals again
		$( document ).trigger( 'my-custom-event', [ true ] );
		assert.equal( $( '.fm-multi-repeatable:visible:not(.fmjs-proto)' ).length, 2 );
		assert.equal( $( '.fm-multi-single:visible' ).length, 1 );
		assert.equal( $( '.fm-multi-other:visible' ).length, 0 );

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
