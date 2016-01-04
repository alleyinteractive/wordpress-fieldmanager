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

	QUnit.test( "Display-if", function( assert ) {
		$( '.fm-test-displayif-wrapper .fm-element' ).each(function() {
			assert.ok( $( this ).hasClass( 'display-trigger' ), "add '.display-trigger' to trigger" );
		});

		assert.ok( $( '#di-foo' ).not( ':visible' ), "hide display-if value of 'foo'" );
		assert.ok( $( '#di-bar' ).is( ':visible' ), "show display-if value of 'bar'" );
		assert.ok( $( '#di-foobar' ).is( ':visible' ), "show display-if value of 'foo,bar'" );

		assert.ok( $( '#di-blank' ).is( ':visible' ), "show display-if value of 'blank'" );
		assert.ok( $( '#di-notblank' ).not( ':visible' ), "hide display-if value of 'notblank'" );

		$( '.display-always' ).each(function() {
			assert.ok( $( this ).is( ':visible' ), 'non display-if field is visible (parent #' + $( this ).parent().attr( 'id' ) + ')' );
		});

		$( '#displayif-strings .display-trigger' ).attr( 'value', 'foo' ).trigger( 'change' );
		assert.ok( $( '#di-foo' ).is( ':visible' ), "show display-if value of 'foo' after change" );
		assert.ok( $( '#di-bar' ).not( ':visible' ), "hide display-if value of 'bar' after change" );
		assert.ok( $( '#di-foobar' ).is( ':visible' ), "still show display-if value of 'foo,bar'" );

		$( '#displayif-blanks .display-trigger' ).attr( 'value', 'notblank' ).trigger( 'change' );
		assert.ok( $( '#di-blank' ).not( ':visible' ), "hide display-if value of 'blank' after change" );
		assert.ok( $( '#di-notblank' ).is( ':visible' ), "show display-if value of 'notblank' after change" );

		$( '.display-always' ).each(function() {
			assert.ok( $( this ).is( ':visible' ), 'non display-if field is visible after changes (parent #' + $( this ).parent().attr( 'id' ) + ')' );
		});

		// assert.ok( $( '#di-789' ).is( ':visible' ), "show display-if value of '789'" );
		// assert.ok( $( '#di-123' ).not( ':visible' ), "hide display-if value of '123'" );
		// assert.ok( $( '#di-456' ).is( ':visible' ), "hide display-if value of '456'" );
	});

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
