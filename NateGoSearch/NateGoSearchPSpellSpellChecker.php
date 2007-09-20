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
	 * The dictionary against which words are compared for this spell checker
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
	 *
	 * @throws SwatException if the Pspell extension is not available.
	 * @throws SwatException if a dictionary in the specified language could
	 *                       not be loaded.
	 */
	public function __construct($language)
	{
		if (!extension_loaded('pspell')) {
			throw SwatException('You need to install the Pspell extension '.
				'in order to use NateGoSearchPSpellSpellChecker.');
		}

		$this->dictionary = pspell_new($language, '', '', 'utf-8', PSPELL_FAST);

		if ($this->dictionary === false) {
			throw new SwatException("Could not create Pspell dictionary with ".
				"language '{$language}'.");
		}
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
			if (!pspell_check($this->dictionary, $word)) {
				$suggestions = pspell_suggest($this->dictionary, $word);
				$suggestion = $this->getBestSuggestion($word, $suggestions);
				if ($suggestion !== null) {
					$misspellings[$word] = $suggestion;
				}
			}
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
	// {{{ private function getBestSuggestion()

	/**
	 * Gets the best suggestion of a misspelling from a list of suggestions
	 *
	 * @param string $misspelling the misspelled word.
	 *
	 * @param array $suggestions an array of suggestions.
	 *
	 * @return string the best suggestion in the set of suggestions. If no
	 *                 suitable suggestion is found, null is returned.
	 */
	private function getBestSuggestion($misspelling, array $suggestions)
	{
		$suggestion = null;

		if (count($suggestions) > 0) {
			$suggestion = $suggestions[0];
		}

		return $suggestion;
	}

	// }}}
}

?>
