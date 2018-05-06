/**
 * Javascript handler for the Lingo extension
 *
 * This file is part of the MediaWiki extension Lingo.
 *
 * @copyright 2011 - 2018, Stephan Gambke
 * @license   GNU General Public License, version 2 (or any later version)
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
 *
 * @file
 * @ingroup Lingo
 */

/*global confirm */

( function ( $ ) {

	'use strict';

	$( function ( $ ) {

		$( 'span.mw-lingo-term' ).each( function () {

			var termId = $(this).attr( 'data-lingo-term-id');

			var tooltip = $( '#' + termId );

			$( this ).qtip( {
				content : tooltip.html(),
				position: {
					my: 'top left',  // Position tooltip's top left...
					at: 'bottom left' // at the bottom left of target
				},
				hide    : {
					fixed: true,
					delay: 300
				},
				style   : {
					classes: tooltip.attr( 'class' ) + ' qtip-shadow',
					def    : false
				}

			} );

		} );

	} );
}( jQuery ) );
