/**
 * Posts to Newsletter — curation admin: drag-and-drop ordering, search, auto-save.
 */
( function ( $ ) {
	'use strict';

	var cfg = window.ptnNewsletter || {};
	var i18n = cfg.i18n || {};
	var $available = $( '#ptn-available' );
	var $selected = $( '#ptn-selected' );
	var $status = $( '.ptn-status' );
	var $search = $( '#ptn-search' );
	var saveTimer = null;
	var searchTimer = null;

	function collectIds() {
		return $selected.find( '.ptn-item' ).map( function () {
			return parseInt( $( this ).attr( 'data-id' ), 10 );
		} ).get();
	}

	function save() {
		var ids = collectIds();
		$status.text( i18n.saving );
		$.ajax( {
			url: cfg.saveUrl,
			method: 'POST',
			contentType: 'application/json',
			data: JSON.stringify( { ids: ids } ),
			beforeSend: function ( xhr ) { xhr.setRequestHeader( 'X-WP-Nonce', cfg.nonce ); }
		} ).done( function ( res ) {
			var count = res && typeof res.count !== 'undefined' ? res.count : ids.length;
			$status.text( ( 1 === count ? i18n.savedOne : i18n.savedMany ).replace( '%d', count ) );
		} ).fail( function () {
			$status.text( i18n.saveFailed );
		} );
	}

	function debounceSave() {
		window.clearTimeout( saveTimer );
		saveTimer = window.setTimeout( save, 400 );
	}

	function addButton() {
		return $( '<button type="button" class="button ptn-add"></button>' ).text( i18n.add );
	}

	function removeButton() {
		return $( '<button type="button" class="button-link ptn-remove"></button>' ).attr( 'aria-label', i18n.remove ).html( '×' );
	}

	$available.on( 'click', '.ptn-add', function () {
		var $item = $( this ).closest( '.ptn-item' );
		$( this ).replaceWith( removeButton() );
		$selected.append( $item );
		debounceSave();
	} );

	$selected.on( 'click', '.ptn-remove', function () {
		var $item = $( this ).closest( '.ptn-item' );
		$( this ).replaceWith( addButton() );
		$available.prepend( $item );
		debounceSave();
	} );

	$selected.sortable( {
		handle: '.ptn-handle',
		placeholder: 'ptn-placeholder',
		forcePlaceholderSize: true,
		update: debounceSave
	} );

	// Search.
	function selectedMap() {
		var map = {};
		$selected.find( '.ptn-item' ).each( function () { map[ $( this ).attr( 'data-id' ) ] = true; } );
		return map;
	}

	function runSearch() {
		$.ajax( {
			url: cfg.searchUrl + '?q=' + encodeURIComponent( $search.val() ),
			method: 'GET',
			beforeSend: function ( xhr ) { xhr.setRequestHeader( 'X-WP-Nonce', cfg.nonce ); }
		} ).done( function ( items ) {
			var chosen = selectedMap();
			$available.empty();
			if ( ! items.length ) {
				$available.append( $( '<li class="ptn-empty"></li>' ).text( i18n.noMatches ) );
				return;
			}
			items.forEach( function ( item ) {
				if ( ! chosen[ String( item.id ) ] ) {
					$available.append( item.html );
				}
			} );
		} );
	}

	if ( $search.length ) {
		$search.on( 'input', function () {
			window.clearTimeout( searchTimer );
			searchTimer = window.setTimeout( runSearch, 300 );
		} );
	}

	// Copy the platform's preview/import URL to the clipboard, with brief feedback.
	$( document ).on( 'click', '.ptn-copy-url', function () {
		var $btn = $( this );
		var url = $btn.attr( 'data-url' ) || '';
		if ( ! url ) {
			return;
		}
		var flash = function () {
			var original = $btn.data( 'label' );
			if ( undefined === original ) {
				original = $btn.text();
				$btn.data( 'label', original );
			}
			$btn.text( i18n.copied || 'Copied!' );
			window.clearTimeout( $btn.data( 'timer' ) );
			$btn.data( 'timer', window.setTimeout( function () {
				$btn.text( original );
			}, 1500 ) );
		};
		if ( window.navigator.clipboard && window.navigator.clipboard.writeText ) {
			window.navigator.clipboard.writeText( url ).then( flash );
		} else {
			// Fallback for non-secure contexts: copy via a temporary field.
			var $tmp = $( '<input type="text" />' ).val( url ).appendTo( 'body' ).select();
			try { document.execCommand( 'copy' ); } catch ( e ) {}
			$tmp.remove();
			flash();
		}
	} );
} )( jQuery );
