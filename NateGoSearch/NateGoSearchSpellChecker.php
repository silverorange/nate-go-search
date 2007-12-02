<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

/**
 * A light-weight spell checker to correct commonly misspelled words
 *
 * This class was added to abstract spell checking from the NateGoSearchQuery
 * class. It serves as an abstract base class for any spell checkers built for
 * NateGoSearch.
 *
 * @package   NateGoSearch
 * @copyright 2004-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class NateGoSearchSpellChecker
{
	// {{{ abstract public function &getMisspellingsInPhrase()

	/**
	 * Checks each word of a phrase for misspellings
	 *
	 * @param string $phrase the phrase to check.
	 *
	 * @return array a list of of misspelled words in the given phrase. The
	 *                array is of the form incorrect => correct.
	 */
	abstract public function &getMisspellingsInPhrase($phrase);

	// }}}
	// {{{ abstract public function getProperSpelling()

	/**
	 * Gets a phrase with all its misspelled words corrected
	 *
	 * @param string $phrase the phrase to correct misspellings in.
	 *
	 * @return string corrected phrase.
	 */
	abstract public function getProperSpelling($phrase);

	// }}}
}

?>
