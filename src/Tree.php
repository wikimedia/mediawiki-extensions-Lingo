<?php

/**
 * File holding the Lingo\Tree class
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
 *
 * @file
 * @ingroup Lingo
 */
namespace Lingo;

/**
 * Vocabulary:
 * Term - The term as a normal string
 * Definition - Its definition (any object)
 * Element - An element (leaf) in the glossary tree
 * Path - The path in the tree to the leaf representing a term
 *
 * The glossary is organized as a tree (nested arrays) where the path to the
 * definition of a term is the lexemes of the term followed by -1 as the end
 * marker.
 *
 * Example:
 * The path to the definition of the term "foo bar baz" would be
 * 'foo'.' '.'bar'.' '.'baz'.'-1'. It could thus be accessed as
 * $mTree['foo'][' ']['bar'][' ']['baz'][-1]
 *
 * @ingroup Lingo
 */
class Tree {

	public const TREE_VERSION = 3.23;

	/** @var array */
	private $mTree = [];
	/** @var array */
	private $mList = [];
	/** @var int */
	private $mMinLength = 1000;

	/**
	 * Adds a string to the Lingo Tree
	 *
	 * @param string $term
	 * @param array $definition
	 */
	public function addTerm( $term, $definition ) {
		if ( !$term ) {
			return;
		}

		if ( isset( $this->mList[ $term ] ) ) { // term exists, store 2nd definition
			$this->mList[ $term ]->addDefinition( $definition );
		} else {
			$matches = [];
			preg_match_all( LingoParser::getInstance()->regex, $term, $matches );

			$element = $this->addElement( $matches[ 0 ], $term, $definition );
			$this->mList[ $term ] = &$element[ -1 ];

			$this->mMinLength = min( [ $this->mMinLength, strlen( $term ) ] );
		}
	}

	/**
	 * Adds an element to the Lingo Tree
	 *
	 * @param array &$path An array containing the constituing lexemes of the term
	 * @param string $term
	 * @param array $definition
	 * @return array the tree node the element was stored in
	 */
	private function &addElement( array &$path, $term, $definition ) {
		$tree = &$this->mTree;

		// end of path, store description; end of recursion
		while ( ( $step = array_shift( $path ) ) !== null ) {
			if ( !isset( $tree[ $step ] ) ) {
				$tree[ $step ] = [];
			}

			$tree = &$tree[ $step ];
		}

		if ( isset( $tree[ -1 ] ) ) {
			$tree[ -1 ]->addDefinition( $definition );
		} else {
			$tree[ -1 ] = new Element( $term, $definition );
		}

		return $tree;
	}

	/**
	 * @return int
	 */
	public function getMinTermLength() {
		return $this->mMinLength;
	}

	/**
	 * @return array
	 */
	public function getTermList() {
		return $this->mList;
	}

	/**
	 * @param array &$lexemes
	 * @param int $index
	 * @param int $countLexemes
	 *
	 * @return array
	 */
	public function findNextTerm( &$lexemes, $index, $countLexemes ) {
		$start = $lastindex = $index;
		$definition = null;

		// skip until the start of a term is found
		while ( $index < $countLexemes && !$definition ) {
			$currLex = &$lexemes[ $index ][ 0 ];

			// Did we find the start of a term?
			if ( array_key_exists( $currLex, $this->mTree ) ) {
				[ $lastindex, $definition ] = $this->findNextTermNoSkip( $this->mTree[ $currLex ], $lexemes, $index, $countLexemes );
			}

			// this will increase the index even if we found something;
			// will be corrected after the loop
			$index++;
		}

		if ( $definition ) {
			return [ $index - $start - 1, $lastindex - $index + 2, $definition ];
		} else {
			return [ $index - $start, 0, null ];
		}
	}

	/**
	 * @param array &$tree
	 * @param array &$lexemes
	 * @param int $index
	 * @param int $countLexemes
	 *
	 * @return array
	 */
	private function findNextTermNoSkip( array &$tree, &$lexemes, $index, $countLexemes ) {
		if ( $index + 1 < $countLexemes && array_key_exists( $currLex = $lexemes[ $index + 1 ][ 0 ], $tree ) ) {
			$ret = $this->findNextTermNoSkip( $tree[ $currLex ], $lexemes, $index + 1, $countLexemes );
			if ( $ret[1] === null ) {
				// We didn't match. Backtrack in case previous prefix matched
				if ( isset( $tree[-1] ) ) {
					$ret = [ $index, &$tree[-1] ];
				} else {
					$ret = [ $index, null ];
				}
			}
		} else {
			if ( isset( $tree[-1] ) ) {
				$ret = [ $index, &$tree[ -1 ] ];
			} else {
				// Failed to match. We need to backtrack
				// in case a previous prefix did match.
				$ret = [ $index, null ];
			}
		}

		return $ret;
	}

}
