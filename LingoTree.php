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
	private $mDefinition = null;
	private $mMinLength = -1;

	/**
	 * Adds a string to the Lingo Tree
	 * @param String $term
	 */
	function addTerm( &$term, $definition ) {
		if ( !$term ) {
			return;
		}

		$matches;
		preg_match_all( '/[[:alpha:]]+|[^[:alpha:]]/u', $term, $matches );

		$this->addElement( $matches[0], $term, $definition );

		if ( $this->mMinLength > -1 ) {
			$this->mMinLength = min( array( $this->mMinLength, strlen( $term ) ) );
		} else {
			$this->mMinLength = strlen( $term );
		}
	}

	/**
	 * Recursively adds an element to the Lingo Tree
	 *
	 * @param array $path
	 * @param <type> $index
	 */
	protected function addElement( Array &$path, &$term, &$definition ) {
		// end of path, store description; end of recursion
		if ( $path == null ) {
			$this -> addDefinition( $term, $definition );
		} else {
			$step = array_shift( $path );

			if ( !array_key_exists( $step, $this->mTree ) ) {
				$this->mTree[$step] = new LingoTree();
			}

			$this->mTree[$step]->addElement( $path, $term, $definition );
		}
	}

	/**
	 * Adds a defintion to the treenodes list of definitions
	 * @param <type> $definition
	 */
	protected function addDefinition( &$term, &$definition ) {
		if ( $this->mDefinition ) {
			$this->mDefinition->addDefinition( $definition );
		} else {
			$this->mDefinition = new LingoElement( $term, $definition );
		}
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
				list( $lastindex, $definition ) = $this->mTree[$currLex]->findNextTermNoSkip( $lexemes, $index, $countLexemes );
			}

			// this will increase the index even if we found something;
			// will be corrected after the loop
			$index++;
		}

		wfProfileOut( __METHOD__ );
		if ( $definition ) {
			return array( $index - $start - 1, $lastindex - $index + 2, $definition );
		} else {
			return array( $index - $start, 0, null );
		}
	}

	function findNextTermNoSkip( &$lexemes, $index, $countLexemes ) {
		wfProfileIn( __METHOD__ );

		if ( $index + 1 < $countLexemes && array_key_exists( $currLex = $lexemes[$index + 1][0], $this->mTree ) ) {
			$ret = $this->mTree[$currLex]->findNextTermNoSkip( $lexemes, $index + 1, $countLexemes );
		} else {
			$ret = array( $index, &$this->mDefinition );
		}
		wfProfileOut( __METHOD__ );
		return $ret;
	}

}
