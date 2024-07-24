/**
 * Javascript handler for the Lingo extension
 *
 * This file is part of the MediaWiki extension Lingo.
 *
 * @copyright 2011 - 2018, Stephan Gambke
 * @license GPL-2.0-or-later
 *
 * The Lingo extension is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option) any
 * later version.
 *
 * The Lingo extension is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Stephan Gambke
 * @file
 * @param $
 */

( function ( $ ) {
	'use strict';

	var lastFocus = null;

	// eslint-disable-next-line no-shadow
	$( function ( $ ) {
		$( 'a.mw-lingo-term' ).each( function () { // eslint-disable-line no-jquery/no-global-selector
			var termId = $( this ).attr( 'data-lingo-term-id' ),
				tooltip = $( '#' + termId ); // eslint-disable-line no-jquery/variable-pattern

			$( this ).qtip( {
				content: tooltip.html(),
				position: {
					my: 'top left', // Position tooltip's top left...
					at: 'bottom left' // at the bottom left of target
				},
				hide: {
					fixed: true,
					delay: 300
				},
				// eslint-disable-next-line mediawiki/class-doc
				style: {
					classes: tooltip.attr( 'class' ) + ' qtip-shadow',
					def: false
				}

			} );

			$( this ).on( 'focus', function() {
				lastFocus = $( this );
				$( this ).qtip( 'show' );
			} );

			$( this ).on( 'blur', function() {
				$( this ).qtip( 'hide' );
			} );

		} );

		$( document ).on( 'keydown', function( e ) {
			if ( e.key === 'Escape' && lastFocus ) {
				lastFocus.qtip( 'hide' );
				lastFocus.blur();
			}
		} );

	} );
}( jQuery ) );
