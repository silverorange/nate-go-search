<?php

require_once 'NateGoSearch/NateGoSearchSpellChecker.php';
require_once 'Swat/exceptions/SwatException.php';

/**
 * A spell checker to correct commonly misspelled words and phrases using 
 * the pspell extension for PHP. 
 *
 * @todo This class probably does not belong in NateGoSearch but lives here for
 *       now.
 *
 * This class adds the power of the Aspell libraries to spell checking, can be
 * used as an alternative to the light-weight NateGoSearchFileSpellChecker.
 *
 * @package   NateGoSearch
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class NateGoSearchPSpellSpellChecker extends NateGoSearchSpellChecker
{
	// {{{ private properties

	/**
	 * The dictionary words are checked against for this spell checker
	 *
	 * This is the dictionary link identifier returned by pspell_new().
	 *
	 * @var integer
	 */
	private $dictionary;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new Pspell spell checker
	 *
	 * @param string $language the language used by this spell checker. This
	 *                          should be a two-letter ISO 639 language code
	 *                          followed by an optional two digit ISO 3166
	 *                          country code separated by a desh or underscore.
	 *                          For example, 'en', 'en-CA' and 'en_CA' are
	 *                          valid languages.
	 */
	public function __construct($language)
	{
		if (!extension_loaded('pspell'))
		{
			throw SwatException('You need to install the PSpell extension '.
				'in order to use NateGoSearchPSpellChecker');
		}

		// TODO: work in the other arguments for the pspell_new() function
		$this->dictionary = pspell_new($language);
	}

	// }}}
	// {{{ public function &getMispellingsInPhrase()

	/**
	 * Checks each word of a phrase for mispellings
	 *
	 * @param string $phrase the phrase to check.
	 *
	 * @return array a list of mispelled words in the given phrase. The array is
	 *                in the form of incorrect => correct.
	 */
	public function &getMisspellingsInPhrase($phrase)
	{
		$misspellings = array();

		$exp_phrase = explode(' ', $phrase);

		foreach ($exp_phrase as $word) {
			if (!pspell_check($this->dictionary, $word))
				$misspellings[$word] = pspell_suggest($this->dictionary, $word);
		}

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

		// uses the first suggestion given by aspell as the replacement word
		foreach ($misspellings as $incorrect => $correct)
			$phrase =
				str_replace(' '.$incorrect.' ', ' '.$correct[0].' ', $phrase);

		$phrase = trim($phrase);

		return $phrase;
	}

	// }}}
}

?>
