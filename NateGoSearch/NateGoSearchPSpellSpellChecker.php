<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'NateGoSearch/NateGoSearchSpellChecker.php';
require_once 'NateGoSearch/exceptions/NateGoSearchException.php';

/**
 * A spell checker to correct commonly misspelled words and phrases using
 * the <code>pspell</code> extension for PHP.
 *
 * This class uses the PHP interface to the GNU Aspell library for
 * spell-checking.
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
	 * An array of words the spell checker should never suggest
	 *
	 * This array is built from the words in system/no-suggest-words.txt.
	 *
	 * @var array
	 * @see NateGoSearchPSpellSpellChecker::loadBlacklistedSuggestions()
	 */
	private $blacklisted_suggestions = array();

	/**
	 * @var string
	 */
	private $custom_wordlist;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new Pspell spell checker
	 *
	 * @param string $language the language used by this spell checker. This
	 *                          should be a two-letter ISO 639 language code
	 *                          followed by an optional two digit ISO 3166
	 *                          country code separated by a dash or underscore.
	 *                          For example, 'en', 'en-CA' and 'en_CA' are
	 *                          valid languages.
	 * @param string $custom_wordlist optional. The filename of the personal
	 *                                 wordlist for this spell checker. If not
	 *                                 specified, no personal wordlist is used.
	 *                                 The personal wordlist may contain
	 *                                 spellings for words that are correct but
	 *                                 are not in the regular dictionary.
	 *
	 * @throws NateGoSearchException if the Pspell extension is not available.
	 * @throws NateGoSearchtException if a dictionary in the specified language
	 *                                could not be loaded.
	 */
	public function __construct($language, $path_to_data = '', $repl_pairs = '',
		$custom_wordlist = '')
	{
		if (!extension_loaded('pspell')) {
			throw new NateGoSearchException('The Pspell PHP extension is '.
				'required for NateGoSearchPSpellSpellChecker.');
		}

		$config = pspell_config_create($language, '', '', 'utf-8');
		pspell_config_mode($config, PSPELL_FAST);

		if ($path_to_data != '') {
			pspell_config_data_dir($config, $path_to_data);
			pspell_config_dict_dir($config, $path_to_data);
		}

		if ($repl_pairs != '')
			pspell_config_repl($config, $repl_pairs);

		if ($custom_wordlist != '') {
			pspell_config_personal($config, $custom_wordlist);
			$this->custom_wordlist = $custom_wordlist;
		}

		$this->dictionary = pspell_new_config($config);

		if ($this->dictionary === false) {
			throw new NateGoSearchException(sprintf(
				"Could not create Pspell dictionary with language '%s'.",
				$this->language));
		}

		$this->loadBlacklistedSuggestions();
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
				$suggestion = $this->getSuggestedSpelling($word);
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
				str_ireplace(' '.$incorrect.' ', ' '.$correct.' ', $phrase);

		$phrase = trim($phrase);

		return $phrase;
	}

	// }}}
	// {{{ public function addToPersonalWordlist()

	/**
	 * Adds a word to the personal wordlist
	 *
	 * @param string $word the word to add to the list.
	 */
	public function addToPersonalWordList($word)
	{
		if ($this->custom_wordlist == '') {
			throw new NateGoSearchException(sprintf("The word '%s' cannot ".
				"be added to the personal wordlist because no personal ".
				"wordlist is set.", $word));
		}

		if (!ctype_alpha($word)) {
			throw new NateGoSearchException(sprintf("The word '%s' cannot ".
				"be added to the custom wordlist because it contains non-".
				"alphabetic characters.", $word));
		}

		pspell_add_to_personal($this->dictionary, $word);
		pspell_save_wordlist($this->dictionary);
	}

	// }}}
	// {{{ private function getSuggestedSpelling()

	/**
	 * Checks to see if a word is spelled correctly and if it spelled
	 * incorrectly, suggests an alternative spelling
	 *
	 * Spell checking ignores case. The best suggestion is considered to be the
	 * first suggestion returned by pspell.
	 *
	 * @param string $word the word to check.
	 *
	 * @return string the best suggestion for the correct spelling of the word,
	 *                 or null if the word is correct or if no suggested
	 *                 spelling exists.
	 */
	private function getSuggestedSpelling($word)
	{
		$suggestion = null;

		if (!pspell_check($this->dictionary, $word)) {

			// get spelling suggestions from pspell
			$suggestions = pspell_suggest($this->dictionary, $word);

			// filter out potentially offensive suggestions
			$suggestions = $this->getFilteredSuggestions($suggestions);

			if (count($suggestions) === 0) {
				// if there are no spelling suggestions then we should stop
				// checking
				$suggestion = null;
			} elseif (strtolower($suggestions[0]) === strtolower($word)) {
				// if pspell is only correcting the capitalization, stop
				// checking
				$suggestion = null;
			} else {
				$suggestion = $this->getBestSuggestion($word, $suggestions);
			}
		}

		return $suggestion;
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
	 * @return string the best suggestion in the array of suggestions. If no
	 *                suitable suggestion is found, null is returned.
	 */
	private function getBestSuggestion($misspelling, array $suggestions)
	{
		$best_suggestion = null;

		if (count($suggestions) > 0) {
			// check to see if the user entered a lower-case word
			if (ctype_lower($misspelling[0])) {
				// if a lower-case word was entered, exclude proper nouns from
				// suggestions
				$best_suggestion = $this->getLowerCaseSuggestion($suggestions);

				// if there was no lower-case suggestion then use the first
				// suggestion
				if ($best_suggestion === null) {
					$best_suggestion = $suggestions[0];
				}
			} else {
				// otherwise, include proper nouns
				$best_suggestion = $suggestions[0];
			}
		}

		return $best_suggestion;
	}

	// }}}
	// {{{ private function getLowerCaseSuggestion()

	/**
	 * Gets the best lower-case suggestion from a list of suggestions
	 *
	 * This method is used if proper nouns should be excluded from spelling
	 * suggestions.
	 *
	 * @param array $suggestions an array of suggestions.
	 *
	 * @return string the best lower-case suggestion or null if no lower-case
	 *                suggestion exists.
	 */
	private function getLowerCaseSuggestion(array $suggestions)
	{
		$match = null;

		foreach ($suggestions as $suggestion) {
			if (ctype_lower($suggestion[0])) {
				$match = $suggestion;
				break;
			}
		}

		return $match;
	}

	// }}}
	// {{{ private function getFilteredSuggestions()

	/**
	 * Filters potentially offensive words out of an array of spelling
	 * suggestions
	 *
	 * @param array $suggestions the raw array of suggestions.
	 *
	 * @return array the filtered array of suggestions.
	 *
	 * @see NateGoSearchPSpellSpellChecker::loadBlacklistedSuggestions()
	 * @see NateGoSearchPSpellSpellChecker::$blacklisted_suggestions
	 */
	private function getFilteredSuggestions(array $suggestions)
	{
		$clean_suggestions = array();

		// filter out potentially offensive words we never want to suggest
		foreach ($suggestions as $suggestion) {
			$lower_suggestion = strtolower($suggestion);
			if (!in_array($lower_suggestion, $this->blacklisted_suggestions)) {
				$clean_suggestions[] = $suggestion;
			}
		}

		return $clean_suggestions;
	}

	// }}}
	// {{{ private function loadBlacklistedSuggestions()

	/**
	 * Loads the list of potentially offensive words that should never be used
	 * for spelling suggestions
	 *
	 * The list is build from the file 'no-suggest-words.txt' that is
	 * distributed with NateGoSearch.
	 *
	 * @see NateGoSearchPSpellSpellChecker::getFilteredSuggestions()
	 * @see NateGoSearchPSpellSpellChecker::$blacklisted_suggestions
	 */
	private function loadBlacklistedSuggestions()
	{
		if (substr('@DATA-DIR@', 0, 1) === '@') {
			$filename = dirname(__FILE__).'/../system/no-suggest-words.txt';
		} else {
			$filename = '@DATA-DIR@/NateGoSearch/system/no-suggest-words.txt';
		}

		$words = file($filename, FILE_IGNORE_NEW_LINES);

		if ($words !== false) {
			$this->blacklisted_suggestions = $words;
		}
	}

	// }}}
}

?>
