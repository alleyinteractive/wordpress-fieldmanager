/* global wp */

(function( QUnit, $ ) {
	function randStr() {
		return Math.random().toString( 36 ).replace( '0.', '' );
	}

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

		assert.ok( $( '#di-789:visible' ).length, "show display-if value of '789'" );
		assert.notOk( $( '#di-123:visible' ).length, "hide display-if value of '123'" );
		assert.notOk( $( '#di-456:visible' ).length, "hide display-if value of '456'" );

		// The boolean and "wrong"/"right" checkboxs are unchecked by default.
		assert.notOk( $( '#di-when-boolean-checkbox-true:visible' ).length, "hide display-if value of 'true' when unchecked" );
		assert.ok( $( '#di-when-boolean-checkbox-false:visible' ).length, "show display-if value of 'false' when unchecked" );
		assert.notOk( $( '#di-when-string-checkbox-right:visible' ).length, "hide display-if value of 'right' when unchecked" );
		assert.ok( $( '#di-when-string-checkbox-wrong:visible' ).length, "show display-if value of 'wrong' when unchecked" );

		// Now, check the boxes.
		$( '.fm-test-boolean-checkbox-wrapper' ).find( 'input[type=checkbox]' ).attr( 'checked', 'checked' ).trigger( 'change' );
		assert.ok( $( '#di-when-boolean-checkbox-true:visible' ).length, "show display-if 'true' when checked" );
		assert.notOk( $( '#di-when-boolean-checkbox-false:visible' ).length, "hide display-if 'false' when checked" );
		$( '.fm-test-string-checkbox-wrapper' ).find( 'input[type=checkbox]' ).attr( 'checked', 'checked' ).trigger( 'change' );
		assert.ok( $( '#di-when-string-checkbox-right:visible' ).length, "show display-if 'right' when checked" );
		assert.notOk( $( '#di-when-string-checkbox-wrong:visible' ).length, "hide display-if 'wrong' when checked" );

		// No radio is selected by default.
		assert.notOk( $( '#di-when-displayif-radio-b:visible' ).length, "hide 'b' when not selected" );

		// Select the radio button.
		$( '.fm-test-displayif-radio' ).find( 'input[value=b]' ).prop( 'checked', true ).trigger( 'change' );
		assert.ok( $( '#di-when-displayif-radio-b:visible' ).length, "show 'b' when selected" );

		// Select a different radio.
		$( '.fm-test-displayif-radio' ).find( 'input[value=c]' ).prop( 'checked', true ).trigger( 'change' );
		assert.notOk( $( '#di-when-displayif-radio-b:visible' ).length, "hide 'b' when 'c' selected" );
	});

	QUnit.test( 'Autocomplete', function( assert ) {
		assert.ok( $( '#ac-visible' ).hasClass( 'fm-autocomplete-enabled' ) );

		var $invisible = $( '#ac-invisible' );
		assert.notOk( $invisible.hasClass( 'fm-autocomplete-enabled' ) );

		// Simulate activating autocomplete by focusing an element.
		$invisible.show().focus();
		assert.ok( $invisible.hasClass( 'fm-autocomplete-enabled' ) );
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

	QUnit.module( 'Customizer', function ( hooks ) {
		QUnit.module( 'fm.customize API', function ( hooks ) {
			QUnit.test( 'setControl sets Fieldmanager control setting', function ( assert ) {
				var initialValue = randStr();

				var setting = new wp.customize.Setting( randStr(), initialValue, {
					transport: 'noop',
					previewer: wp.customize.previewer,
				});

				var control = new wp.customize.Control( randStr(), {
					setting: setting,
					params: {
						settings: {},
						type: 'fieldmanager',
					},
				});

				assert.equal( fm.customize.setControl( control ), setting, 'Should return setting' );
				assert.notEqual( setting.get(), initialValue, 'Setting value should change' );
			});

			QUnit.test( 'setControl ignores non-Fieldmanager controls', function ( assert ) {
				var value     = randStr();
				var settingId = randStr();

				var setting = new wp.customize.Setting( settingId, value, {
					transport: 'noop',
					previewer: wp.customize.previewer
				});

				var control = new wp.customize.Control( randStr(), {
					setting: setting,
					params: {
						content: $( '<li>' ).append(
							$( '<input />' )
								.addClass( 'fm-element' )
								.attr( 'type', 'text' )
								.attr( 'name', settingId )
						),
						settings: {},
						type: 'text',
					},
				});

				assert.notOk( fm.customize.setControl( control ), 'Should not return setting' );
				assert.equal( setting.get(), value, 'Setting value should not change' );
			});

			QUnit.test( 'setControl serializes only values in targetSelector', function ( assert ) {
				var settingId         = randStr();
				var settingValue      = randStr();
				var fmElementValue    = randStr();
				var notFmElementValue = randStr();

				var markup = $( '<li>' ).append(
					$( '<input />' )
						.addClass( 'fm-element' )
						.attr( 'name', settingId )
						.attr( 'value', fmElementValue ),
					$( '<input />' )
						.addClass( 'not-fm-element' )
						.attr( 'name', 'bar' )
						.attr( 'value', notFmElementValue )
				);

				var setting = new wp.customize.Setting( settingId, settingValue, {
					transport: 'noop',
					previewer: wp.customize.previewer,
				});

				var control = new wp.customize.Control( settingId, {
					setting: setting,
					params: {
						content: markup,
						settings: {},
						type: 'fieldmanager',
					}
				});

				assert.ok( fm.customize.setControl( control ), 'successfully returned setting' );
				assert.ok( -1 !== setting.get().indexOf( fmElementValue ), 'setting now contains "fm-element" value' );
				assert.ok( -1 === setting.get().indexOf( notFmElementValue ), 'setting does not contain "not-fm-element" value' );
			});

			QUnit.test( 'setControl sets value with serializeJSON()', function ( assert ) {
				var settingId = 'option_fields';
				var textValue = randStr();

				var expected = {
					0: {
						'repeatable_group': {
							0: {
								'text': textValue,
							}
						}
					}
				};

				var setting = new wp.customize.Setting( settingId, '', {
					transport: 'noop',
					previewer: wp.customize.previewer,
				});

				var control = new wp.customize.Control( settingId, {
					setting: setting,
					params: {
						content: $( '<li>' ).append(
							$( '<input />' )
								.addClass( 'fm-element' )
								.attr( 'name', 'option_fields[0][repeatable_group][0][text]' )
								.attr( 'value', textValue )
						),
						settings: {},
						type: 'fieldmanager',
					}
				});

				fm.customize.setControl( control );
				assert.deepEqual( setting.get(), expected );
			});

			QUnit.test( 'setControl falls back to serialize()', function ( assert ) {
				var plugin = $.fn.serializeJSON;
				$.fn.serializeJSON = undefined;

				var settingId = 'option_fields';
				var textValue = randStr();

				var expected = 'option_fields%5B0%5D%5Brepeatable_group%5D%5B0%5D%5Btext%5D=' + textValue;

				var setting = new wp.customize.Setting( settingId, '', {
					transport: 'noop',
					previewer: wp.customize.previewer,
				});

				var control = new wp.customize.Control( settingId, {
					setting: setting,
					params: {
						content: $( '<li>' ).append(
							$( '<input />' )
								.addClass( 'fm-element' )
								.attr( 'name', 'option_fields[0][repeatable_group][0][text]' )
								.attr( 'value', textValue )
						),
						settings: {},
						type: 'fieldmanager',
					}
				});

				fm.customize.setControl( control );
				assert.strictEqual( setting.get(), expected );

				$.fn.serializeJSON = plugin;
			});

			QUnit.test( 'setControlsContainingElement() sets only controls containing element', function ( assert ) {
				var initialValue = randStr();
				var newValue = randStr();
				var id1 = randStr();
				var id2 = randStr();

				var setting1 = wp.customize.create( id1, id1, initialValue, {
					transport: 'noop',
					previewer: wp.customize.previewer,
				} );

				var $element1 = $( '<input />' )
					.addClass( 'fm-element' )
					.attr( 'name', id1 )
					.attr( 'value', newValue );

				var control1 = wp.customize.control.create( id1, id1, {
					setting: setting1,
					params: {
						content: $( '<li>' ).append( $element1 ),
						settings: {},
						type: 'fieldmanager',
					},
				});

				var setting2 = wp.customize.create( id2, id2, initialValue, {
					transport: 'noop',
					previewer: wp.customize.previewer,
				});

				var control2 = wp.customize.control.create( id2, id2, {
					setting: setting2,
					params: {
						content: $( '<li>' ).append(
							$( '<input />' )
								.addClass( 'fm-element' )
								.attr( 'name', id2 )
								.attr( 'value', newValue )
						),
						settings: {},
						type: 'fieldmanager',
					}
				});

				fm.customize.setControlsContainingElement( $element1 );
				assert.equal( setting1.get(), newValue, 'First setting value was in the control and should change' );
				assert.equal( setting2.get(), initialValue, 'Second setting value was not in the control and should not change' );
			});

			QUnit.test( 'setEachControl should set each control', function ( assert ) {
				var id1 = randStr();
				var initialValue1 = randStr();
				var newValue1 = randStr();

				var id2 = randStr();
				var initialValue2 = randStr();
				var newValue2 = randStr();

				var setting1 = wp.customize.create( id1, id1, initialValue1, {
					transport: 'noop',
					previewer: wp.customize.previewer,
				} );

				wp.customize.control.create( id1, id1, {
					setting: setting1,
					params: {
						content: $( '<li>' ).append(
							$( '<input />' )
								.addClass( 'fm-element' )
								.attr( 'name', id1 )
								.attr( 'value', newValue1 )
						 ),
						settings: {},
						type: 'fieldmanager',
					},
				});

				var setting2 = wp.customize.create( id2, id2, initialValue2, {
					transport: 'noop',
					previewer: wp.customize.previewer,
				});

				wp.customize.control.create( id2, id2, {
					setting: setting2,
					params: {
						content: $( '<li>' ).append(
							$( '<input />' )
								.addClass( 'fm-element' )
								.attr( 'name', id2 )
								.attr( 'value', newValue2 )
						),
						settings: {},
						type: 'fieldmanager',
					}
				});

				fm.customize.setEachControl();
				assert.equal( setting1.get(), newValue1, 'First setting value changed' );
				assert.equal( setting2.get(), newValue2, 'Second setting value changed' );
			});
		});

		QUnit.module( 'Events', function ( hooks ) {
			var initialValue = 'Wrong';
			var expected = 'First';
			var id = 'customize-text';

			hooks.beforeEach(function( assert ) {
				var setting = wp.customize.create( id, id, initialValue, {
					transport: 'noop',
					previewer: wp.customize.previewer,
				});

				wp.customize.control.create( id, id, {
					setting: setting,
					params: {
						content: $( document ).find( '#customizer-events' ),
						settings: {},
						type: 'fieldmanager',
					}
				});

				wp.customize.trigger( 'ready' );
			});

			hooks.afterEach(function( assert ) {
				wp.customize.remove( id );
				wp.customize.control.remove( id );
			});

			function assertStrictEqualSettingValue( assert ) {
				assert.strictEqual( wp.customize.instance( id ).get(), expected, 'Setting value updated' );
			}

			QUnit.test( '.fm-element keyup', function ( assert ) {
				var done = assert.async();
				$( document ).find( '#customizer-events' ).find( '.fm-element' ).trigger( 'keyup' );
				// Account for the _.debounce() attached to this event.
				setTimeout(function() {
					assertStrictEqualSettingValue( assert );
					done();
				}, 500 );
			});

			QUnit.test( '.fm-autocomplete keyup', function ( assert ) {
				$( document ).find( '#customizer-events' ).find( '.fm-autocomplete' ).trigger( 'keyup' );
				assertStrictEqualSettingValue( assert );
			});

			QUnit.test( '.fm-element change', function ( assert ) {
				$( document ).find( '#customizer-events' ).find( '.fm-element' ).trigger( 'change' );
				assertStrictEqualSettingValue( assert );
			});

			QUnit.test( '.fm-media-remove click ', function ( assert ) {
				$( document ).find( '#customizer-events').find( '.fm-media-remove' ).trigger( 'click' );
				assertStrictEqualSettingValue( assert );
			});

			QUnit.test( '.fmjs-remove click ', function ( assert ) {
				$( document ).find( '#customizer-events').find( '.fmjs-remove' ).trigger( 'click' );
				assertStrictEqualSettingValue( assert );
			});

			QUnit.test( 'fm_sortable_drop', function ( assert ) {
				$( document ).trigger( 'fm_sortable_drop', $( document ).find( '#customizer-events' ).find( '.fm-element' )[0] );
				assertStrictEqualSettingValue( assert );
			});

			QUnit.test( 'fieldmanager_media_preview', function ( assert ) {
				$( document ).find( '#customizer-events' ).find( '.fm-element' ).trigger( 'fieldmanager_media_preview' );
				assertStrictEqualSettingValue( assert );
			});

			// Needs a test with TinyMCE.
			// QUnit.test( 'fm_richtext_init', function ( assert ) {
			// });

			QUnit.test( 'fm_colorpicker_update', function ( assert ) {
				$( document ).trigger( 'fm_colorpicker_update', $( document ).find( '#customizer-events' ).find( '.fm-element' )[0] );
				assertStrictEqualSettingValue( assert );
			});
		});
	});
})( window.QUnit, window.jQuery );
