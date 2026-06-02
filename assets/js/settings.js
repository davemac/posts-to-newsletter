/**
 * Posts to Newsletter — settings page: logo/hero media pickers.
 */
( function ( $ ) {
	'use strict';

	var i18n = ( window.ptnSettings || {} ).i18n || {};

	// Colour fields: keep the native swatch and the hex text input in sync.
	$( '.color-row' ).each( function () {
		var $swatch = $( this ).find( '.ptn-color-swatch' );
		var $hex = $( this ).find( '.ptn-color-hex' );
		if ( ! $swatch.length || ! $hex.length ) {
			return;
		}
		$swatch.on( 'input', function () {
			$hex.val( $swatch.val().toUpperCase() );
		} );
		$hex.on( 'input', function () {
			var v = $hex.val().trim();
			if ( /^#?[0-9a-fA-F]{6}$/.test( v ) ) {
				$swatch.val( ( '#' === v.charAt( 0 ) ? v : '#' + v ).toLowerCase() );
			}
		} );
	} );

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
