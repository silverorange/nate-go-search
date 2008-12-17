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
	 */
	private $no_suggest = array();

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

		if ($custom_wordlist != '')
			pspell_config_personal($config, $custom_wordlist);

		$this->dictionary = pspell_new_config($config);

		if ($this->dictionary === false) {
			throw new NateGoSearchException(sprintf(
				"Could not create Pspell dictionary with language '%s'.",
				$this->language));
		}

		$this->buildNoSuggestList();
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
	 * Add a word to the personal wordlist
	 *
	 * @param string $word the word to add to the list
	 */
	public function addToPersonalWordList($word)
	{
		if (strlen($this->path_to_personal_wordlist) > 0) {
			if (ctype_alpha($word)) {
				pspell_add_to_personal($this->dictionary, $word);
				pspell_save_wordlist($this->dictionary);
			} else {
				throw new NateGoSearchException(sprintf("The word '%s' cannot ".
					"be added to the custom wordlist. The word may contain ".
					"non-alphabetic characters.", $word));
			}
		} else {
			throw new NateGoSearchException(sprintf("The word '%s' cannot ".
				"be added to the personal wordlist because no personal ".
				"wordlist is set.", $word));
		}
	}

	// }}}
	// {{{ private function getSuggestedSpelling()

	/**
	 * Checks to see if a word is spelled correctly and if it spelled
	 * incorrectly, suggests an alternative spelling
	 *
	 * Spell checking ignores case. The best suggestion is considered to be the
	 * first suggestion.
	 *
	 * @param string $word the word to check.
	 *
	 * @return string the best suggestion for the correct spelling of the word
	 *                 or null if the word is correct or if no suggested
	 *                 spelling exists.
	 */
	private function getSuggestedSpelling($word)
	{
		$suggestion = null;

		if (!pspell_check($this->dictionary, $word)) {
			$suggestions = pspell_suggest($this->dictionary, $word);
			$suggestions = $this->getCleanSuggestions($suggestions);

			// if pspell has no suggestions then we should stop checking
			if (count($suggestions) === 0)
				$suggestion = null;
			elseif (strtolower($suggestions[0]) === strtolower($word))
				$suggestion = null;
			else
				$suggestion = $this->getBestSuggestion($word, $suggestions);
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
	 * @return string the best suggestion in the set of suggestions. If no
	 *                suitable suggestion is found, a default word is returned.
	 */
	private function getBestSuggestion($misspelling, array $suggestions)
	{
		$best_suggestion = null;

		if (count($suggestions) > 0) {
			// checks to see if the user entered a lower case word
			if (ctype_lower($misspelling[0]))
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
	// {{{ private function getLowerCaseSuggestion()

	/**
	 * Gets the best lower-case suggestion for a word
	 *
	 * @param array $suggestions an array of suggestions.
	 *
	 * @return string the best lowercase suggestion for a word or null if no
	 *                 lower case suggestion exists.
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
	// {{{ private function getCleanSuggestions()

	private function getCleanSuggestions(array $suggestions)
	{
		$clean_list = array();

		foreach ($suggestions as $suggestion) {
			if (!in_array(strtolower($suggestion), $this->no_suggest))
				$clean_list[] = $suggestion;
		}

		return $clean_list;
	}

	// }}}
	// {{{ private function buildNoSuggestList()

	private function buildNoSuggestList()
	{
		if (substr('@DATA-DIR@', 0, 1) === '@')
			$filename = dirname(__FILE__).'/../system/no-suggest-words.txt';
		else
			$filename = '@DATA-DIR@/NateGoSearch/system/no-suggest-words.txt';

		$words = file($filename, FILE_IGNORE_NEW_LINES);
		if ($words !== false)
			$this->no_suggest = $words;
	}

	// }}}
}

?>
