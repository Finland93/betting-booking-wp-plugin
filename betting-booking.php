/* global jQuery, BettingBookings */
( function ( $ ) {
	'use strict';

	var cfg = window.BettingBookings || {};

	function post( action, data ) {
		return $.post(
			cfg.ajaxUrl,
			$.extend( { action: action, nonce: cfg.nonce }, data )
		);
	}

	$( function () {
		var $modal = $( '#bb_edit_modal' );

		// Normalise decimal commas to dots on amount fields.
		$( '#bet_amount, #potential_win' ).on( 'change', function () {
			this.value = String( this.value ).replace( /,/g, '.' );
		} );

		// --- Open edit modal ---
		$( '.bb-table' ).on( 'click', '.btn-edit', function () {
			var id = $( this ).closest( 'tr' ).data( 'id' );
			if ( ! id ) {
				return;
			}

			post( 'betting_bookings_get_slip', { slip_id: id } )
				.done( function ( res ) {
					if ( ! res || ! res.success ) {
						window.alert( ( res && res.data && res.data.message ) || cfg.i18n.error );
						return;
					}
					var d = res.data;
					$( '#bb_edit_id' ).val( d.id );
					$( '#bb_edit_rows' ).val( d.row_count );
					$( '#bb_edit_status' ).val( d.won_status );
					$( '#bb_edit_hits' ).val( d.hits ).attr( 'max', d.row_count );
					$( '.bb-rows-hint' ).text( '/ ' + d.row_count );
					$modal.attr( 'aria-hidden', 'false' ).addClass( 'is-open' );
				} )
				.fail( function () {
					window.alert( cfg.i18n.error );
				} );
		} );

		// --- Submit update ---
		$( '#bb_edit_form' ).on( 'submit', function ( e ) {
			e.preventDefault();
			var $spin = $( '.bb-spinner' ).addClass( 'is-active' );

			post( 'betting_bookings_update_slip', {
				slip_id: $( '#bb_edit_id' ).val(),
				won_status: $( '#bb_edit_status' ).val(),
				hits: $( '#bb_edit_hits' ).val()
			} )
				.done( function ( res ) {
					if ( res && res.success ) {
						window.location.reload();
					} else {
						$spin.removeClass( 'is-active' );
						window.alert( ( res && res.data && res.data.message ) || cfg.i18n.error );
					}
				} )
				.fail( function () {
					$spin.removeClass( 'is-active' );
					window.alert( cfg.i18n.error );
				} );
		} );

		// --- Remove slip ---
		$( '.bb-table' ).on( 'click', '.btn-remove', function () {
			var $row = $( this ).closest( 'tr' );
			var id = $row.data( 'id' );
			if ( ! id || ! window.confirm( cfg.i18n.confirmRemove ) ) {
				return;
			}

			post( 'betting_bookings_remove_slip', { slip_id: id } )
				.done( function ( res ) {
					if ( res && res.success ) {
						$row.fadeOut( 150, function () {
							$row.remove();
						} );
					} else {
						window.alert( ( res && res.data && res.data.message ) || cfg.i18n.error );
					}
				} )
				.fail( function () {
					window.alert( cfg.i18n.error );
				} );
		} );

		// --- Close modal ---
		function closeModal() {
			$modal.attr( 'aria-hidden', 'true' ).removeClass( 'is-open' );
		}
		$modal.on( 'click', '.bb-modal-close', closeModal );
		$modal.on( 'click', function ( e ) {
			if ( e.target === this ) {
				closeModal();
			}
		} );
		$( document ).on( 'keyup', function ( e ) {
			if ( 27 === e.keyCode ) {
				closeModal();
			}
		} );
	} );
} )( jQuery );
