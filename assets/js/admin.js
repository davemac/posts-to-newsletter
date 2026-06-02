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
	var $chip = $status.closest( '.savechip' );
	var $search = $( '#ptn-search' );
	var $searchClear = $( '#ptn-search-clear' );
	var $availableCount = $( '#ptn-available-count' );
	var $selectedCount = $( '#ptn-selected-count' );
	var $clearAll = $( '#ptn-clear' );
	var $drophint = $( '#ptn-drophint' );
	var $chips = $( '#ptn-chips' );
	var currentCat = 'all';
	var saveTimer = null;
	var searchTimer = null;

	// Inline icons, matching the server-rendered set in Curation::icon().
	var ICONS = {
		plus: '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M5 12h14"/></svg>',
		x: '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="M6 6l12 12"/></svg>',
		check: '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
		search: '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>'
	};

	function collectIds() {
		return $selected.find( '.ptn-item' ).map( function () {
			return parseInt( $( this ).attr( 'data-id' ), 10 );
		} ).get();
	}

	// Keep the column counts, save chip, Clear all button and empty drop hint in
	// sync with the current selection.
	function refreshState() {
		var selectedCount = $selected.children( '.ptn-item' ).length;
		$selectedCount.text( selectedCount );
		$availableCount.text( $available.children( '.ptn-item' ).not( '.is-filtered-out' ).length );
		$clearAll.prop( 'hidden', 0 === selectedCount );
		$drophint.prop( 'hidden', selectedCount > 0 );
	}

	// Client-side category filter: hide available rows whose data-cats list does
	// not include the active chip's category id. No request — it works on the
	// rows already loaded (and is re-applied after a search re-render or a move).
	function applyCategoryFilter() {
		var $rows = $available.children( '.ptn-item' );
		if ( 'all' === currentCat ) {
			$rows.removeClass( 'is-filtered-out' );
		} else {
			$rows.each( function () {
				var cats = ( $( this ).attr( 'data-cats' ) || '' ).split( ',' );
				$( this ).toggleClass( 'is-filtered-out', -1 === cats.indexOf( currentCat ) );
			} );
		}

		// Show the empty state when a filter hides every available row.
		$available.find( '.ptn-filter-empty' ).remove();
		if ( 'all' !== currentCat && $rows.length && ! $rows.not( '.is-filtered-out' ).length ) {
			$available.append( emptyState().addClass( 'ptn-filter-empty' ) );
		}

		refreshState();
	}

	$chips.on( 'click', '.chip', function () {
		var cat = $( this ).attr( 'data-cat' );
		// Clicking the active (non-All) chip clears back to All.
		if ( cat === currentCat && 'all' !== cat ) {
			cat = 'all';
		}
		currentCat = cat;
		$chips.find( '.chip' ).removeClass( 'is-on' ).attr( 'aria-pressed', 'false' );
		$chips.find( '.chip[data-cat="' + cat + '"]' ).addClass( 'is-on' ).attr( 'aria-pressed', 'true' );
		applyCategoryFilter();
	} );

	function save() {
		var ids = collectIds();
		$chip.addClass( 'is-saving' );
		$status.text( i18n.saving );
		$.ajax( {
			url: cfg.saveUrl,
			method: 'POST',
			contentType: 'application/json',
			data: JSON.stringify( { ids: ids } ),
			beforeSend: function ( xhr ) { xhr.setRequestHeader( 'X-WP-Nonce', cfg.nonce ); }
		} ).done( function ( res ) {
			var count = res && typeof res.count !== 'undefined' ? res.count : ids.length;
			$status.text( ( i18n.saved || 'Saved · %d selected' ).replace( '%d', count ) );
		} ).fail( function () {
			$status.text( i18n.saveFailed );
		} ).always( function () {
			$chip.removeClass( 'is-saving' );
		} );
	}

	function debounceSave() {
		window.clearTimeout( saveTimer );
		saveTimer = window.setTimeout( save, 400 );
	}

	function addButton() {
		return $( '<button type="button" class="ptn-add addbtn"></button>' )
			.html( ICONS.plus )
			.append( $( '<span></span>' ).text( i18n.add ) );
	}

	function removeButton() {
		return $( '<button type="button" class="ptn-remove removebtn"></button>' )
			.attr( 'aria-label', i18n.remove )
			.html( ICONS.x );
	}

	$available.on( 'click', '.ptn-add', function () {
		var $item = $( this ).closest( '.ptn-item' );
		$( this ).replaceWith( removeButton() );
		$selected.append( $item );
		applyCategoryFilter();
		debounceSave();
	} );

	$selected.on( 'click', '.ptn-remove', function () {
		var $item = $( this ).closest( '.ptn-item' );
		$( this ).replaceWith( addButton() );
		$available.prepend( $item );
		applyCategoryFilter();
		debounceSave();
	} );

	$clearAll.on( 'click', function () {
		$selected.children( '.ptn-item' ).each( function () {
			var $item = $( this );
			$item.find( '.ptn-remove' ).replaceWith( addButton() );
			$available.prepend( $item );
		} );
		applyCategoryFilter();
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

	function emptyState() {
		return $( '<li class="ptn-empty empty"></li>' )
			.html( ICONS.search )
			.append( $( '<div class="empty__title"></div>' ).text( i18n.noMatches ) )
			.append( $( '<div class="empty__hint"></div>' ).text( i18n.noMatchesHint || '' ) );
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
				$available.append( emptyState() );
				refreshState();
				return;
			}
			items.forEach( function ( item ) {
				if ( ! chosen[ String( item.id ) ] ) {
					$available.append( item.html );
				}
			} );
			applyCategoryFilter();
		} );
	}

	if ( $search.length ) {
		$search.on( 'input', function () {
			$searchClear.prop( 'hidden', '' === $search.val() );
			window.clearTimeout( searchTimer );
			searchTimer = window.setTimeout( runSearch, 300 );
		} );
	}

	$searchClear.on( 'click', function () {
		$search.val( '' );
		$searchClear.prop( 'hidden', true );
		runSearch();
		$search.trigger( 'focus' );
	} );

	// Copy the platform's preview/import URL to the clipboard, with a brief
	// icon swap to a green check.
	$( document ).on( 'click', '.ptn-copy-url', function () {
		var $btn = $( this );
		var url = $btn.attr( 'data-url' ) || '';
		if ( ! url ) {
			return;
		}
		var flash = function () {
			var original = $btn.data( 'icon' );
			if ( undefined === original ) {
				original = $btn.html();
				$btn.data( 'icon', original );
			}
			$btn.addClass( 'is-copied' ).html( ICONS.check );
			window.clearTimeout( $btn.data( 'timer' ) );
			$btn.data( 'timer', window.setTimeout( function () {
				$btn.removeClass( 'is-copied' ).html( original );
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
