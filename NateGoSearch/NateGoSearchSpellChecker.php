<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

/**
 * Abstract base class for spell-checking of keywords in search queries and
 * indexers
 *
 * @package   NateGoSearch
 * @copyright 2004-2016 silverorange
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
	// {{{ abstract public function addToPersonalWordlist()

	/**
	 * Adds a word to the personal wordlist
	 *
	 * @param string $word the word to add to the list.
	 */
	abstract public function addToPersonalWordList($word);

	// }}}
}

?>
