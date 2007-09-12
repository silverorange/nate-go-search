<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Swat/exceptions/SwatException.php';

/**
 * Thrown when a document type is invalid
 *
 * @package   NateGoSearch
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class NateGoSearchDocumentTypeException extends SwatException
{
	// {{{ protected properties

	/**
	 * The shortname of the invalid document type
	 *
	 * @var string
	 */
	protected $shortname= null;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new document type exception
	 *
	 * @param string $message the message of the exception.
	 * @param integer $code the code of the exception.
	 * @param string $shortname the shortname of the invalid document type.
	 */
	public function __construct($message = null, $code = 0,
		$shortname = null)
	{
		parent::__construct($message, $code);
		$this->shortname = $shortname;
	}

	// }}}
	// {{{ public function getShortname()

	/**
	 * Gets the shortname of the invalid document type
	 *
	 * @return string the shortname of the invalid document type.
	 */
	public function getShortname()
	{
		return $this->shortname;
	}

	// }}}
}

?>
