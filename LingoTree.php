<?php

/**
 * File holding the LingoTree class
 *
 * @author Stephan Gambke
 *
 * @file
 * @ingroup Lingo
 */
if ( !defined( 'LINGO_VERSION' ) ) {
	die( 'This file is part of the Lingo extension, it is not a valid entry point.' );
}

/**
 * The LingoTree class.
 *
 * Vocabulary:
 * Term - The term as a normal string
 * Definition - Its definition object
 * Element - An element (leaf) in the glossary tree
 * Path - The path in the tree to the leaf representing a term
 *
 * @ingroup Lingo
 */
class LingoTree {

	private $mTree = array();
	private $mList = array();
	private $mMinLength = 1000;

	/**
	 * Adds a string to the Lingo Tree
	 * @param String $term
	 */
	function addTerm( &$term, $definition ) {
		if ( !$term ) {
			return;
		}

		if ( isset( $this->mList[$term] ) ) { // term exists, store 2nd definition

			$this->mList[$term][-1]->addDefinition( $definition );

		} else {

			$matches;
			preg_match_all( '/[[:alpha:]]+|[^[:alpha:]]/u', $term, $matches );

			$this->mList[$term] = $this->addElement( $matches[0], $term, $definition );

			$this->mMinLength = min( array($this->mMinLength, strlen( $term )) );
		}
	}

	/**
	 * Adds an element to the Lingo Tree
	 *
	 * @param array $path
	 * @param <type> $index
	 * @return Array the tree node the element was stored in
	 */
	protected function &addElement( Array &$path, &$term, &$definition ) {

		$tree = &$this->mTree;

		// end of path, store description; end of recursion
		while ( $step = array_shift( $path ) ) {

			if ( !isset( $tree[$step] ) ) {
				$tree[$step] = array();
			}

			$tree = &$tree[$step];

		}

		if ( isset( $tree[-1] ) ) {
			$tree[-1]->addDefinition( $definition );
		} else {
			$tree[-1] = new LingoElement( $term, $definition );
		}

		return $tree;
	}

	function getMinTermLength() {
		return $this->mMinLength;
	}

	function findNextTerm( &$lexemes, $index, $countLexemes ) {
		wfProfileIn( __METHOD__ );

		$start = $lastindex = $index;
		$definition = null;

		// skip until ther start of a term is found
		while ( $index < $countLexemes && !$definition ) {
			$currLex = &$lexemes[$index][0];

			// Did we find the start of a term?
			if ( array_key_exists( $currLex, $this->mTree ) ) {
				list( $lastindex, $definition ) = $this->findNextTermNoSkip( $this->mTree[$currLex], $lexemes, $index, $countLexemes );
			}

			// this will increase the index even if we found something;
			// will be corrected after the loop
			$index++;
		}

		wfProfileOut( __METHOD__ );
		if ( $definition ) {
			return array($index - $start - 1, $lastindex - $index + 2, $definition);
		} else {
			return array($index - $start, 0, null);
		}
	}

	function findNextTermNoSkip( Array &$tree, &$lexemes, $index, $countLexemes ) {
		wfProfileIn( __METHOD__ );

		if ( $index + 1 < $countLexemes && array_key_exists( $currLex = $lexemes[$index + 1][0], $tree ) ) {
			$ret = $this->findNextTermNoSkip( $tree[$currLex], $lexemes, $index + 1, $countLexemes );
		} else {
			$ret = array($index, &$tree[-1]);
		}
		wfProfileOut( __METHOD__ );
		return $ret;
	}

}
