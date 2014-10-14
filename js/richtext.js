( function( $ ) {
	fm.richtextarea = {
		add_rte_to_visible_textareas: function() {
			$( 'textarea.fm-richtext:visible' ).each( function() {
				if ( ! $( this ).hasClass( 'fm-tinymce' ) ) {
					var init, ed_id, mce_options, qt_options, proto_id;
					ed_id = $( this ).attr( 'id' );
					proto_id = $( this ).data( 'proto-id' );

					$( this ).addClass( 'fm-tinymce' );

					// Clean up the proto id which appears in some of the wp_editor generated HTML
					$( this ).closest( '.fm-wrapper' ).html( $( this ).closest( '.fm-wrapper' ).html().replace( new RegExp( proto_id, 'g' ), ed_id ) );

					if ( typeof tinymce !== 'undefined' ) {

						if ( typeof tinyMCEPreInit.mceInit[ ed_id ] === 'undefined' ) {
							// This needs to be initialized, so we need to get the options from the proto
							if ( proto_id && typeof tinyMCEPreInit.mceInit[ proto_id ] !== 'undefined' ) {
								mce_options = $.extend( true, {}, tinyMCEPreInit.mceInit[ proto_id ] );
								mce_options.body_class = mce_options.body_class.replace( proto_id, ed_id );
								mce_options.selector = mce_options.selector.replace( proto_id, ed_id );
								mce_options.wp_skip_init = false;
								tinyMCEPreInit.mceInit[ ed_id ] = mce_options;
							} else {
								// TODO: No data to work with, this should throw some sort of error
								return;
							}

							if ( proto_id && typeof tinyMCEPreInit.qtInit[ proto_id ] !== 'undefined' ) {
								qt_options = $.extend( true, {}, tinyMCEPreInit.qtInit[ proto_id ] );
								qt_options.id = qt_options.id.replace( proto_id, ed_id );
								tinyMCEPreInit.qtInit[ ed_id ] = qt_options;
							}

							init = tinyMCEPreInit.mceInit[ ed_id ];

							try {
								result = tinymce.init( init );

								if ( ! window.wpActiveEditor ) {
									window.wpActiveEditor = ed_id;
								}
							} catch(e){}

							try {
								if ( typeof tinyMCEPreInit.qtInit[ ed_id ] !== 'undefined' ) {
									quicktags( tinyMCEPreInit.qtInit[ ed_id ] );

									if ( ! window.wpActiveEditor ) {
										window.wpActiveEditor = ed_id;
									}
								}
							} catch(e){};
						}
					}

					$('.wp-editor-wrap').on( 'click.wp-editor', function() {
						if ( this.id ) {
							window.wpActiveEditor = this.id.slice( 3, -5 );
						}
					});
				}
			} );
		},

		drop_rte_enable_control: function( e, el ) {
			$( el ).find( '.fm-tinymce' ).each( function() {
				var cmd;
				// Disable the editor
				cmd = 'mceRemoveControl';
				if ( parseInt( tinymce.majorVersion ) >= 4 ) {
					cmd = 'mceRemoveEditor';
				}
				tinymce.execCommand( cmd, false, $( this ).attr( 'id' ) );

				// Immediately reenable the editor
				cmd = 'mceAddControl';
				if ( parseInt( tinymce.majorVersion ) >= 4 ) {
					cmd = 'mceAddEditor';
				}
				tinymce.execCommand( cmd, false, $( this ).attr( 'id' ) );
			});
		}
	}
	$( document ).on( 'fm_collapsible_toggle fm_added_element fm_activate_tab fm_displayif_toggle', fm.richtextarea.add_rte_to_visible_textareas );
	$( document ).on( 'fm_sortable_drop', fm.richtextarea.drop_rte_enable_control );
} ) ( jQuery );