( function( $ ) {
	fm.richtextarea = {
		add_rte_to_visible_textareas: function() {
			$( 'textarea.fm-richtext:visible' ).each( function() {
				if ( ! $( this ).hasClass( 'fm-tinymce' ) ) {
					var init, ed_id, mce_options, qt_options, proto_id;
					$( this ).addClass( 'fm-tinymce' );
					ed_id = $( this ).attr( 'id' );

					if ( typeof tinymce !== 'undefined' ) {

						if ( typeof tinyMCEPreInit.mceInit[ ed_id ] === 'undefined' ) {
							proto_id = $( this ).data( 'proto-id' );

							// Clean up the proto id which appears in some of the wp_editor generated HTML
							$( this ).closest( '.fm-wrapper' ).html( $( this ).closest( '.fm-wrapper' ).html().replace( new RegExp( proto_id, 'g' ), ed_id ) );

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
						}

						try {
							if ( 'html' !== fm.richtextarea.mode_enabled( this ) ) {
								tinymce.init( tinyMCEPreInit.mceInit[ ed_id ] );
								$( this ).closest( '.wp-editor-wrap' ).on( 'click.wp-editor', function() {
									if ( this.id ) {
										window.wpActiveEditor = this.id.slice( 3, -5 );
									}
								} );
							}
						} catch(e){}

						try {
							if ( typeof tinyMCEPreInit.qtInit[ ed_id ] !== 'undefined' ) {
								quicktags( tinyMCEPreInit.qtInit[ ed_id ] );
								// _buttonsInit() only needs to be called on dynamic editors
								// quicktags() handles it for us on the first initialization
								if ( typeof QTags !== 'undefined' && -1 !== ed_id.indexOf( '-dynamic-' ) ) {
									QTags._buttonsInit();
								}
							}
						} catch(e){};
					}
				}
			} );
		},

		reload_editors: function( e, wrap ) {
			if ( ! wrap || 'undefined' === typeof wrap.nodeType ) {
				return;
			}

			$( '.fm-tinymce', wrap ).each( function() {
				var html_mode = ( 'html' === fm.richtextarea.mode_enabled( this ) )
					, ed = tinymce.get( this.id )
					, content = ed.getContent()
					, cmd;

				if ( html_mode ) {
					$( '#' + this.id + '-tmce' ).click();
				}

				// Disable the editor
				cmd = 'mceRemoveControl';
				if ( parseInt( tinymce.majorVersion ) >= 4 ) {
					cmd = 'mceRemoveEditor';
				}
				tinymce.execCommand( cmd, false, this.id );

				// Immediately re-enable the editor
				cmd = 'mceAddControl';
				if ( parseInt( tinymce.majorVersion ) >= 4 ) {
					cmd = 'mceAddEditor';
				}
				tinymce.execCommand( cmd, false, this.id );

				// Replace the content with what it was to correct paragraphs
				ed = tinymce.get( this.id );
				ed.setContent( content );

				if ( html_mode ) {
					$( '#' + this.id + '-html' ).click();
				}
			});
		},

		mode_enabled: function( el ) {
			return $( el ).closest( '.html-active' ).length ? 'html' : 'tinymce';
		},

		/**
		 * Ensure that the main editor's state remains unaffected by any FM editors
		 */
		reset_core_editor_mode: function() {
			if ( 'html' === core_editor_state || 'tinymce' === core_editor_state ) {
				setUserSetting( 'editor', core_editor_state );
			}
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

		// Reset the core editor's state so it remains unaffected by this event.
		// We delay by 50ms to ensure that this event has enough time to run.
		// WordPress won't change the state of the editor until the end of the
		// event delegation.
		setTimeout( fm.richtextarea.reset_core_editor_mode, 50 );
	} );

	var core_editor_state = getUserSetting( 'editor' );

	/**
	 * If the main editor's state changes, note that change.
	 */
	$( document ).on( 'click', '#content-tmce,#content-html', function() {
		var aid = this.id,
			l = aid.length,
			id = aid.substr( 0, l - 5 ),
			mode = 'html' === aid.substr( l - 4 ) ? 'html' : 'tinymce';

		core_editor_state = mode;
	} );

	/**
	 * On document.load, init the editors and make the global meta box drag-drop
	 * event reload the editors.
	 */
	$( function() {
		fm.richtextarea.add_rte_to_visible_textareas();
		$( '.meta-box-sortables' ).on( 'sortstop', function( e, obj ) {
			fm.richtextarea.reload_editors( e, obj.item[0] );
		} );
	} );
} ) ( jQuery );