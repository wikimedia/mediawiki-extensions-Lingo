<?php

/**
 * File holding the LingoBackend class
 *
 * @author Stephan Gambke
 * @file
 * @ingroup Lingo
 */
if ( !defined( 'LINGO_VERSION' ) ) {
	die( 'This file is part of the Lingo extension, it is not a valid entry point.' );
}

/**
 * The LingoBackend class.
 *
 * @ingroup Lingo
 */
abstract class LingoBackend {

	protected $mMessageLog;

	public function __construct( LingoMessageLog &$messages = null ) {

		$this->mMessageLog = $messages;

	}

	/**
	 *
	 * @return the next element or null
	 */
	abstract public function next();

}

