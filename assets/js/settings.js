/**
 * Curated Newsletter — settings page: logo/hero media pickers.
 */
( function ( $ ) {
	'use strict';

	// Colour pickers.
	if ( $.fn.wpColorPicker ) {
		$( '.cnl-color' ).wpColorPicker();
	}

	var frames = {};

	$( '.cnl-choose' ).on( 'click', function ( e ) {
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
			title: 'Choose image',
			button: { text: 'Use this image' },
			library: { type: 'image' },
			multiple: false
		} );
		frames[ target ].on( 'select', function () {
			var att = frames[ target ].state().get( 'selection' ).first().toJSON();
			var src = att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url;
			$( '#cnl-' + target + '-id' ).val( att.id );
			$( '#cnl-' + target + '-preview' ).attr( 'src', src );
		} );
		frames[ target ].open();
	} );

	$( '.cnl-clear' ).on( 'click', function () {
		var target = $( this ).attr( 'data-target' );
		$( '#cnl-' + target + '-id' ).val( 0 );
		$( '#cnl-' + target + '-preview' ).attr( 'src', '' );
	} );
} )( jQuery );
