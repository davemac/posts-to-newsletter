/**
 * Posts to Newsletter — settings page: logo/hero media pickers.
 */
( function ( $ ) {
	'use strict';

	var i18n = ( window.ptnSettings || {} ).i18n || {};

	// Colour pickers.
	if ( $.fn.wpColorPicker ) {
		$( '.ptn-color' ).wpColorPicker();
	}

	var frames = {};

	$( '.ptn-choose' ).on( 'click', function ( e ) {
		e.preventDefault();
		var target = $( this ).attr( 'data-target' ); // 'logo' or 'hero'.
		if ( typeof wp === 'undefined' || ! wp.media ) {
			return;
		}
		if ( frames[ target ] ) {
			frames[ target ].open();
			return;
		}
		frames[ target ] = wp.media( {
			title: i18n.chooseImage,
			button: { text: i18n.useImage },
			library: { type: 'image' },
			multiple: false
		} );
		frames[ target ].on( 'select', function () {
			var att = frames[ target ].state().get( 'selection' ).first().toJSON();
			var src = att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url;
			$( '#ptn-' + target + '-id' ).val( att.id );
			$( '#ptn-' + target + '-preview' ).attr( 'src', src );
		} );
		frames[ target ].open();
	} );

	$( '.ptn-clear' ).on( 'click', function () {
		var target = $( this ).attr( 'data-target' );
		$( '#ptn-' + target + '-id' ).val( 0 );
		$( '#ptn-' + target + '-preview' ).attr( 'src', '' );
		markDirty();
	} );

	// Sticky save bar: flag unsaved changes once the form is touched. The note
	// resets to its "all saved" state on the post-save reload.
	var $note = $( '#ptn-save-note' );
	var dirty = false;
	function markDirty() {
		if ( dirty || ! $note.length ) {
			return;
		}
		dirty = true;
		$note.html( $( '<strong></strong>' ).text( ( window.ptnSettings && window.ptnSettings.i18n && window.ptnSettings.i18n.unsaved ) || 'Unsaved changes' ) );
	}
	$( '#ptn-settings-form' ).on( 'input change', markDirty );
} )( jQuery );
