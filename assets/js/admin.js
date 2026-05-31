/**
 * Curated Newsletter — curation admin: drag-and-drop ordering, search, auto-save,
 * and push-to-platform buttons.
 */
( function ( $ ) {
	'use strict';

	var cfg = window.cnlNewsletter || {};
	var $available = $( '#cn-available' );
	var $selected = $( '#cn-selected' );
	var $status = $( '.cn-status' );
	var $search = $( '#cn-search' );
	var $pushResult = $( '.cn-push-result' );
	var saveTimer = null;
	var searchTimer = null;

	function collectIds() {
		return $selected.find( '.cn-item' ).map( function () {
			return parseInt( $( this ).attr( 'data-id' ), 10 );
		} ).get();
	}

	function save() {
		var ids = collectIds();
		$status.text( 'Saving…' );
		$.ajax( {
			url: cfg.saveUrl,
			method: 'POST',
			contentType: 'application/json',
			data: JSON.stringify( { ids: ids } ),
			beforeSend: function ( xhr ) { xhr.setRequestHeader( 'X-WP-Nonce', cfg.nonce ); }
		} ).done( function ( res ) {
			var count = res && typeof res.count !== 'undefined' ? res.count : ids.length;
			$status.text( 'Saved — ' + count + ' article' + ( 1 === count ? '' : 's' ) + ' selected' );
		} ).fail( function () {
			$status.text( 'Save failed — please try again' );
		} );
	}

	function debounceSave() {
		window.clearTimeout( saveTimer );
		saveTimer = window.setTimeout( save, 400 );
	}

	function addButton() {
		return $( '<button type="button" class="button cn-add"></button>' ).text( 'Add' );
	}

	function removeButton() {
		return $( '<button type="button" class="button-link cn-remove"></button>' ).attr( 'aria-label', 'Remove' ).html( '×' );
	}

	$available.on( 'click', '.cn-add', function () {
		var $item = $( this ).closest( '.cn-item' );
		$( this ).replaceWith( removeButton() );
		$selected.append( $item );
		debounceSave();
	} );

	$selected.on( 'click', '.cn-remove', function () {
		var $item = $( this ).closest( '.cn-item' );
		$( this ).replaceWith( addButton() );
		$available.prepend( $item );
		debounceSave();
	} );

	$selected.sortable( {
		handle: '.cn-handle',
		placeholder: 'cn-placeholder',
		forcePlaceholderSize: true,
		update: debounceSave
	} );

	// Search.
	function selectedMap() {
		var map = {};
		$selected.find( '.cn-item' ).each( function () { map[ $( this ).attr( 'data-id' ) ] = true; } );
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
				$available.append( '<li class="cn-empty">No matching articles.</li>' );
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

	// Push to platform.
	$( '.cn-push' ).on( 'click', function () {
		var platform = $( this ).attr( 'data-platform' );
		var url = 'mailchimp' === platform ? cfg.mailchimp : cfg.cm;
		var $btn = $( this );
		$btn.prop( 'disabled', true );
		$pushResult.removeClass( 'cn-error' ).text( 'Creating draft…' );
		$.ajax( {
			url: url,
			method: 'POST',
			beforeSend: function ( xhr ) { xhr.setRequestHeader( 'X-WP-Nonce', cfg.nonce ); }
		} ).done( function ( res ) {
			if ( res && res.url ) {
				$pushResult.html( 'Draft created. <a href="' + res.url + '" target="_blank" rel="noopener">Open draft</a>' );
			} else {
				$pushResult.text( res && res.message ? res.message : 'Draft created.' );
			}
		} ).fail( function ( xhr ) {
			var msg = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'Push failed.';
			$pushResult.addClass( 'cn-error' ).text( msg );
		} ).always( function () {
			$btn.prop( 'disabled', false );
		} );
	} );
} )( jQuery );
