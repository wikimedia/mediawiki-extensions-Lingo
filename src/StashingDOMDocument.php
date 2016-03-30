<?php
/**
 * File containing the StashingDOMDocument class
 *
 * @copyright (C) 2013 - 2016, Stephan Gambke
 * @license   GNU General Public License, version 3 (or any later version)
 *
 * The Lingo extension is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option) any
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
 * @file
 * @ingroup Lingo
 */

namespace Lingo;

/**
 * Class StashingDOMDocument
 *
 * @package Lingo
 * @ingroup Lingo
 * @since 2.0.2
 */
class StashingDOMDocument extends \DOMDocument {

	private $mStash = array();

	/**
	 * @param \DOMElement $element
	 * @param null $key
	 * @return null
	 */
	public function stashSet( \DOMElement $element, $key = null ) {

		if ( $key === null ) {
			$key = uniqid( '', true );
		}

		$this->mStash[ $key ] = $element;

		return $key;
	}

	/**
	 * @param $key
	 * @return \DOMElement | null
	 */
	public function stashGet( $key ) {
		return @$this->mStash[ $key ];
	}

	/**
	 * @param $key
	 */
	public function stashDelete( $key ) {
		unset ( $this->mStash[ $key ] );
	}

	/**
	 * @param $key
	 * @return bool
	 */
	public function isStashed ( $key ) {
		return isset( $this->mStash[ $key ] );
	}

}
