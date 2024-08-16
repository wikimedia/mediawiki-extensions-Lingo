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
	var isLastFocusInside = false;

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

			$( this ).on( 'focus focusout', function ( e ) {
				if ( e.type === 'focus' ) {
					lastFocus = $( this ); // eslint-disable-line no-jquery/variable-pattern
					$( this ).qtip( 'show' );
				} else if ( e.type === 'focusout' && isLastFocusInside ) {
					var relatedTarget = $( e.relatedTarget ), // eslint-disable-line no-jquery/variable-pattern
						api = lastFocus.qtip( 'api' ),
						tooltipContent = api && api.elements.content;

					if ( !relatedTarget.closest( tooltipContent ).length ) {
						$( this ).qtip( 'hide' );
						isLastFocusInside = false;
					}
				}
			} );

		} );

		$( document ).on( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && lastFocus ) {
				lastFocus.qtip( 'hide' );
				lastFocus.blur();
				lastFocus.focus();
				lastFocus = null;
			} else if ( e.key === 'Tab' && lastFocus ) {
				var api = lastFocus.qtip( 'api' ),
					tooltipContent = api && api.elements.content,
					tooltipDefinitions = tooltipContent && tooltipContent.find( '.mw-lingo-definition' ),
					curFocus = $( ':focus' ); // eslint-disable-line no-jquery/variable-pattern, no-jquery/no-global-selector

				if ( tooltipDefinitions.length > 0 ) {
					var focusableElements = tooltipDefinitions.find( '.mw-lingo-definition-link > a' ),
						currentIndex = focusableElements.index( curFocus );

					if ( e.shiftKey ) {
						if ( currentIndex > 0 ) {
							focusableElements.eq( currentIndex - 1 ).attr( 'tabindex', '-1' ).focus();
							isLastFocusInside = true;
						} else {
							tooltipDefinitions = null;
							unfocusLast();
						}
					} else {
						if ( currentIndex < focusableElements.length - 1 ) {
							focusableElements.eq( currentIndex + 1 ).attr( 'tabindex', '-1' ).focus();
							isLastFocusInside = true;
						} else {
							tooltipDefinitions = null;
							unfocusLast();
						}
					}
					e.preventDefault();
				}
			}
		} );

		function unfocusLast() {
			lastFocus.qtip( 'hide' );
			lastFocus.blur();
			lastFocus.focus();
			lastFocus = null;
			isLastFocusInside = false;
		}

	} );
}( jQuery ) );
