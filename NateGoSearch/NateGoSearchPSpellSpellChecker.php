<?php

require_once 'NateGoSearch/NateGoSearchSpellChecker.php';
require_once 'Swat/exceptions/SwatException.php';

/**
 * A spell checker to correct commonly misspelled words and phrases using
 * the pspell extension for PHP.
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

	/**
	 * The language of the current dictionary
	 *
	 * @var string
	 */
	private $language;

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

		$this->language = $language;
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

		// make sure spell-checked word contains a letter
		$word_regexp = '/\pL/u';

		foreach ($exp_phrase as $word) {
			// only check spelling of words, ignore the case
			if (preg_match($word_regexp, $word) == 1) {
				$suggestion = $this->checkIgnoreCase($word);
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

		foreach ($misspellings as $incorrect => $correct)
			$phrase =
				str_replace(' '.$incorrect.' ', ' '.$correct.' ', $phrase);

		$phrase = trim($phrase);

		return $phrase;
	}

	// }}}
	// {{{ public function loadMisspellingsFromFile()

	public function loadMisspellingsFromFile($filename)
	{
		$config = pspell_config_create($this->language);

		// TODO: needs a better way to check if the loading of the file fails
		if (file_exists($filename)) {
			pspell_config_repl($config, $filename);
			$this->dictionary = pspell_new_config($config);
		} else {
			throw new SwatException("Could not load misspellings with ".
				"filename '{$filename}'.");
		}
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
	 *                suitable suggestion is found, a default word is returned.
	 */
	private function getBestSuggestion($misspelling, array $suggestions)
	{
		$best_suggestion = null;

		if (count($suggestions) > 0) {
			// checks to see if the user entered a lower case word
			if (ctype_lower(substr($misspelling, 0, 1)))
				$best_suggestion = $this->getLowerCaseSuggestion($suggestions);
			else
				$best_suggestion = $suggestions[0];

			// if there was no lowercase suggestion then use the first
			// suggestion
			if ($best_suggestion === null)
				$best_suggestion = $suggestions[0];
		}

		return $best_suggestion;
	}

	// }}}
	// {{{ private function checkIgnoreCase()

	/**
	 * Checks to see if a word is spelled correctly while ignoring the case
	 *
	 * @param string $word the word to be checked
	 *
	 * @return string the best suggestion for the correct spelling of the word
	 *                 or null if the word is correct.
	 */

	private function checkIgnoreCase($word)
	{
		$suggestion = null;

		if (!pspell_check($this->dictionary, $word)) {
			$suggestions = pspell_suggest($this->dictionary, $word);
			// if pspell has no suggestions then we should stop checking
			if (count($suggestions) === 0)
				$suggestion = null;
			elseif (strtolower($suggestions[0]) === $word)
				$suggestion = null;
			else
				$suggestion = $this->getBestSuggestion($word, $suggestions);
		}

		return $suggestion;
	}

	// }}}
	// {{{ private function getLowerCaseSuggestion()

	/**
	 * Gets the best lower case suggestion for a word
	 *
	 * @param array $suggestions an array of suggestions
	 *
	 * @return string the best lowercase suggestion for a word or null if no
	 *                 lower case suggestion exists.
	 */
	private function getLowerCaseSuggestion(array $suggestions)
	{
		$match = null;

		foreach ($suggestions as $suggestion) {
			if (ctype_lower(substr($suggestion, 0, 1))) {
				$match = $suggestion;
				break;
			}
		}

		return $match;
	}

	// }}}
}

?>
