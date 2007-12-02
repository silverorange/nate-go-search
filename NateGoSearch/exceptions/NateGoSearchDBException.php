<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'NateGoSearch/exceptions/NateGoSearchException.php';

/**
 * Thrown when a database error occurs
 *
 * @package   NateGoSearch
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class NateGoSearchDBException extends NateGoSearchException
{
	// {{{ public function __construct()

	/**
	 * Creates a new databsae exception
	 *
	 * @param string|PEAR_Error $message either the error message or a PEAR
	 *                                    error describing the MDB2 error that
	 *                                    occurred.
	 * @param integer $code the error code of this exception.
	 */
	public function __construct($message = null, $code = 0)
	{
		if (is_object($message) && ($message instanceof PEAR_Error)) {
			$error = $message;
			$message = $error->getMessage();
			$message .= "\n".$error->getUserInfo();
			$code = $error->getCode();
		}

		parent::__construct($message, $code);
	}

	// }}}
}

?>
