/**
 * Posts to Newsletter — Compose Edition admin.
 *
 * The centre canvas (#ptn-selected) is the editor: add articles from the left,
 * drag the cards to reorder and remove them inline. Everything autosaves.
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
	var $clearAll = $( '#ptn-clear' );
	var $drophint = $( '#ptn-drophint' );
	var $chips = $( '#ptn-chips' );
	var $subject = $( '#ptn-subject' );
	var $preview = $( '#ptn-preview' );
	var $template = $( '#ptn-template' );
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

	// The selected order is read from the canvas cards (the source of truth).
	function collectIds() {
		return $selected.find( '.ptn-pv-card' ).map( function () {
			return parseInt( $( this ).attr( 'data-id' ), 10 );
		} ).get();
	}

	function selectedMap() {
		var map = {};
		$selected.find( '.ptn-pv-card' ).each( function () { map[ $( this ).attr( 'data-id' ) ] = true; } );
		return map;
	}

	function refreshState() {
		var selectedCount = $selected.children( '.ptn-pv-card' ).length;
		$availableCount.text( $available.children( '.ptn-item' ).not( '.is-filtered-out' ).length );
		$clearAll.prop( 'hidden', 0 === selectedCount );
		$drophint.prop( 'hidden', selectedCount > 0 );
	}

	// An available row whose article is in the edition shows an "Added" state so it
	// cannot be added twice; removing it from the canvas restores the Add button.
	function markAdded( $row ) {
		$row.addClass( 'is-added' );
		$row.find( '.ptn-add' ).prop( 'disabled', true ).html( ICONS.check ).append( $( '<span></span>' ).text( i18n.added || 'Added' ) );
	}

	function unmarkAdded( $row ) {
		$row.removeClass( 'is-added' );
		$row.find( '.ptn-add' ).prop( 'disabled', false ).html( ICONS.plus ).append( $( '<span></span>' ).text( i18n.add || 'Add' ) );
	}

	function syncAddedStates() {
		var chosen = selectedMap();
		$available.children( '.ptn-item' ).each( function () {
			var $row = $( this );
			var isAdded = !! chosen[ $row.attr( 'data-id' ) ];
			if ( isAdded && ! $row.hasClass( 'is-added' ) ) {
				markAdded( $row );
			} else if ( ! isAdded && $row.hasClass( 'is-added' ) ) {
				unmarkAdded( $row );
			}
		} );
	}

	// Client-side category filter: hide available rows whose data-cats list does
	// not include the active chip's category id. No request — it works on the rows
	// already loaded (and is re-applied after a search re-render).
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
		var payload = { ids: ids };
		if ( $subject.length ) {
			payload.subject = $subject.val();
		}
		if ( $preview.length ) {
			payload.preview_text = $preview.val();
		}
		if ( $template.length ) {
			payload.template = $template.val();
		}
		$chip.addClass( 'is-saving' );
		$status.text( i18n.saving );
		$.ajax( {
			url: cfg.saveUrl,
			method: 'POST',
			contentType: 'application/json',
			data: JSON.stringify( payload ),
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

	// Add: fetch the article's canvas card and append it to the edition. The Add
	// button flips to "Added" immediately; a failed fetch reverts it.
	$available.on( 'click', '.ptn-add', function () {
		var $row = $( this ).closest( '.ptn-item' );
		if ( $row.hasClass( 'is-added' ) ) {
			return;
		}
		var id = $row.attr( 'data-id' );
		markAdded( $row );

		$.ajax( {
			url: cfg.cardUrl + '?id=' + encodeURIComponent( id ),
			method: 'GET',
			beforeSend: function ( xhr ) { xhr.setRequestHeader( 'X-WP-Nonce', cfg.nonce ); }
		} ).done( function ( res ) {
			if ( res && res.html ) {
				$selected.append( res.html );
				refreshState();
				debounceSave();
			} else {
				unmarkAdded( $row );
			}
		} ).fail( function () {
			unmarkAdded( $row );
		} );
	} );

	// Remove a card from the canvas; restore the matching Add button.
	$selected.on( 'click', '.ptn-pv-remove', function () {
		var $card = $( this ).closest( '.ptn-pv-card' );
		var id = $card.attr( 'data-id' );
		$card.remove();
		var $row = $available.children( '[data-id="' + id + '"]' );
		if ( $row.length ) {
			unmarkAdded( $row );
		}
		refreshState();
		debounceSave();
	} );

	$clearAll.on( 'click', function () {
		$selected.empty();
		syncAddedStates();
		refreshState();
		debounceSave();
	} );

	// Drag to reorder the two-up grid of email cards.
	$selected.sortable( {
		items: '> .ptn-pv-card',
		handle: '.ptn-handle',
		placeholder: 'ptn-pv-placeholder',
		forcePlaceholderSize: true,
		tolerance: 'pointer',
		update: debounceSave
	} );

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
			$available.empty();
			if ( ! items.length ) {
				$available.append( emptyState() );
				refreshState();
				return;
			}
			items.forEach( function ( item ) {
				$available.append( item.html );
			} );
			syncAddedStates();
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

	// Copy a platform's import URL to the clipboard, with a brief check-icon swap.
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

	// Subject, preview text and template all ride the same debounced autosave.
	$subject.on( 'input', debounceSave );
	$preview.on( 'input', debounceSave );
	$template.on( 'change', debounceSave );

	// Desktop/Mobile preview width is a class on the canvas; the cards are already
	// responsive, so narrowing it fires their own compact breakpoint.
	$( '.ptn-viewport-toggle' ).on( 'click', function () {
		var mode = $( this ).attr( 'data-mode' );
		$( '.ptn-viewport-toggle' ).removeClass( 'is-on' ).attr( 'aria-pressed', 'false' );
		$( this ).addClass( 'is-on' ).attr( 'aria-pressed', 'true' );
		$( '.ptn-pv' ).attr( 'data-mode', mode );
	} );

	// Reflect the server-rendered selection in the Add buttons on first load.
	syncAddedStates();
} )( jQuery );
