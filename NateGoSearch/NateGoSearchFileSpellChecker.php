<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'NateGoSearch/NateGoSearchSpellChecker.php';

/**
 * A light-weight, file-based spell checker to correct commonly misspelled
 * words
 *
 * @package   NateGoSearch
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class NateGoSearchFileSpellChecker extends NateGoSearchSpellChecker
{
	// {{{ protected properties

	/**
	 * A list of misspelled words and their replacement spelling
	 *
	 * The associative array is of the form incorrect => correct.
	 *
	 * @var array
	 */
	protected $misspellings = array();

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new light-weight spell checker
	 *
	 * @param array $misspellings an optional array of misspellings to use. You
	 *                             may add misspellings at any time with the
	 *                             {@link NateGoSearchFileSpellChecker::addMisspellings()}
	 *                             method.
	 */
	public function __construct(array $misspellings = array())
	{
		$this->misspellings = $misspellings;
	}

	// }}}
	// {{{ public function loadMisspellingsFromFile()

	/**
	 * Loads a list of misspellings from a text file
	 *
	 * The text file format is one misspelling per line with the incorrect
	 * word spelling placed before the correct word spelling having both words
	 * separated by a comma. For example:
	 *
	 * <pre>
	 * hte,the
	 * yuo,you
	 * acheive,achieve
	 * hasa,has a
	 * </pre>
	 *
	 * @param string $filename the name of the file to load the misspellings
	 *                          from.
	 */
	public function loadMisspellingsFromFile($filename)
	{
		$misspellings_file = file($filename, true);

		foreach ($misspellings_file as $line) {
			$words = explode(',', rtrim($line));
			if (count($words) == 2)
				$this->misspellings[$words[0]] = $words[1];
		}
	}

	// }}}
	// {{{ public function getDefaultMisspellingsFilename()

	/**
	 * Gets a default misspellings filename
	 *
	 * @return string the default filename for file-based search spelling
	 *                 corrections.
	 */
	public function getDefaultMisspellingsFilename()
	{
		if (substr('@DATA-DIR@', 0, 1) === '@')
			$filename = dirname(__FILE__).'/../system/misspellings.txt';
		else
			$filename = '@DATA-DIR@/NateGoSearch/system/misspellings.txt';

		return $filename;
	}

	// }}}
	// {{{ public function addMisspellings()

	/**
	 * Adds a list of misspellings to the list of misspellings in this spell
	 * checker
	 *
	 * @param array $misspellings the list of misspellings to add.
	 */
	public function addMisspellings(array $misspellings)
	{
		$this->misspellings = array_merge($this->misspellings, $misspellings);
	}

	// }}}
	// {{{ public function &getMisspellings()

	/**
	 * Gets the dictionary of misspelled words of this spell checker
	 *
	 * @return array the dictionary of misspelled words of this spell checker.
	 */
	public function &getMisspellings()
	{
		return $this->misspellings;
	}

	// }}}
	// {{{ public function &getMisspellingsInPhrase()

	/**
	 * Checks each word of a phrase for misspellings
	 *
	 * @param string $phrase the phrase to check.
	 *
	 * @return array a list of of misspelled words in the given phrase. The
	 *                array is of the form incorrect => correct.
	 */
	public function &getMisspellingsInPhrase($phrase)
	{
		$misspellings = array();

		$exp_phrase = explode(' ', $phrase);

		foreach ($exp_phrase as $word)
			if (array_key_exists($word, $this->misspellings))
				$misspellings[$word] = $this->misspellings[$word];

		return $misspellings;
	}

	// }}}
	// {{{ public function getProperSpelling()

	/**
	 * Gets a phrase with all its misspelled words corrected
	 *
	 * @param string $phrase the phrase to correct misspellings in.
	 *
	 * @return string corrected phrase.
	 */
	public function getProperSpelling($phrase)
	{
		$phrase = ' '.$phrase.' ';

		$misspellings = $this->getMisspellingsInPhrase($phrase);

		foreach ($misspellings as $incorrect => $correct)
			$phrase =
				str_replace(' '.$incorrect.' ', ' '.$correct.' ', $phrase);

		$phrase = trim($phrase);

		return $phrase;
	}

	// }}}
}

?>
