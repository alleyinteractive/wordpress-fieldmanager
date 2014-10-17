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
								if ( 'html' !== fm.richtextarea.mode_enabled( this ) ) {
									tinymce.init( init );
								}
							} catch(e){}

							try {
								if ( typeof tinyMCEPreInit.qtInit[ ed_id ] !== 'undefined' ) {
									quicktags( tinyMCEPreInit.qtInit[ ed_id ] );
								}
							} catch(e){};
						}
					}
				}
			} );
		},

		reload_editors: function( e, el ) {
			$( el ).find( '.fm-tinymce' ).each( function() {
				html_mode = ( 'html' === fm.richtextarea.mode_enabled( this ) );
				if ( html_mode ) {
					$( '#' + this.id + '-tmce' ).click();
				}

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

				if ( html_mode ) {
					$( '#' + this.id + '-html' ).click();
				}
			});
		},

		mode_enabled: function( el ) {
			return $( el ).closest( '.html-active' ).length ? 'html' : 'tinymce';
		}
	}
	$( document ).on( 'fm_collapsible_toggle fm_added_element fm_activate_tab fm_displayif_toggle', fm.richtextarea.add_rte_to_visible_textareas );
	$( document ).on( 'fm_sortable_drop', fm.richtextarea.reload_editors );

	$( document ).on( 'click', '.fm-richtext .wp-switch-editor', function() {
		var aid = this.id,
			l = aid.length,
			id = aid.substr( 0, l - 5 ),
			mode = 'html' === aid.substr( l - 4 ) ? 'html' : 'tinymce';

		// This only runs if the default editor is set to 'cookie'
		if ( 'fm-edit-dynamic' !== id.substr( 0, 15 ) && $( this ).closest( '.fm-richtext-remember-editor' ).length ) {
			setUserSetting( 'editor_' + id.replace( /-/g, '_' ).replace( /[^a-z0-9_]/ig, '' ), mode );
		}
	} );
} ) ( jQuery );