<?php

/**
 * Represents the result of a search using NateGoSearch
 *
 * This class is designed to be returned from a call to
 * {@link NateGoSearchQuery::find()} and is not designed to be used on its own.
 *
 * @package   NateGoSearch
 * @copyright 2006 silverorange
 */
class NateGoSearchResult
{
	protected $unique_id;
	protected $blocked_words = array();
	protected $searched_words = array();

	/**
	 * Builds a new search result
	 *
	 * @param string $unique_id the unique identifier of the search results in
	 *                           the results table.
	 *
	 * @see NateGoSearchResult::getUniqueId()
	 */
	public function __construct($unique_id)
	{
		$this->unique_id = $unique_id;
	}

	/**
	 * Adds a word to the list of blocked words
	 *
	 * @param string|array $words either an array containing words that were
	 *                             not searched for or a string containing a
	 *                             word that was not searched for.
	 *
	 * @see NateGoSearchResult::getBlockedWords()
	 */
	public function addBlockedWords($words)
	{
		if (!is_array($words))
			$words = array((string)$words);

		$this->blocked_words = array_merge($this->blocked_words, $words);
	}

	/**
	 * Adds a word to the list of searched words
	 *
	 * @param string|array $words either an array containing words that were
	 *                             searched for or a string containing a word
	 *                             that was searched for.
	 *
	 * @see NateGoSearchResult::getSearchedWords()
	 */
	public function addSearchedWords($words)
	{
		if (!is_array($words))
			$words = array((string)$words);

		$this->searched_words = array_merge($this->searched_words, $words);
	}

	/**
	 * Gets words that were entered but were not searched for
	 *
	 * These words might be words like: 'the', 'a', 'to', etc...
	 *
	 * @return array words that were entered but were not searched for.
	 */
	public function &getBlockedWords()
	{
		return $this->blocked_words;
	}

	/**
	 * Gets words that were entered and were searched for
	 *
	 * The NateGoSearchQuery class blocks certain common words from being
	 * searched to increase the validity of search results. This list of words
	 * is the filtered list of keywords that were actually used in the search.
	 *
	 * @return array words that were entered and were searched for.
	 */
	public function &getSearchedWords()
	{
		return $this->searched_words;
	}

	/**
	 * Gets the unique identifier of this search result in the results table
	 *
	 * NateGoSearch stores all search results in the same table. The results of
	 * a specific query may be gathered by selecting results from the database
	 * with the appropriate unique id.
	 *
	 * @return string the unique identifier of this search result in the
	 *                 results table.
	 *
	 * @see NateGoSearchResult::getResultTable()
	 */
	public function getUniqueId()
	{
		return $this->unique_id;
	}

	/**
	 * Gets the name of the table that NateGoSearch results are stored in
	 *
	 * By default this is 'NateGoSearchResult'.
	 *
	 * @return string the name of the table that NateGoSearch results are
	 *                 stored in.
	 */
	public function getResultTable()
	{
		return 'NateGoSearchResult';
	}

	/**
	 * Gets the name of the document identifier field within the results table
	 *
	 * By default this is 'document_id'.
	 *
	 * @return string the name of the document identifier field within the
	 *                 results table.
	 *
	 * @see NateGoSearchResult::getResultTable()
	 */
	public function getDocumentIdField()
	{
		return 'document_id';
	}

	/**
	 * Formats a list of misspelled keywords into an XHTML string
	 *
	 * @param array $misspellings a list of misspelled words to format.
	 *
	 * @return string the given misspellings formatted into an XHTML string.
	 */
	public static function formatMisspellings($misspellings)
	{
		if (!is_array($misspellings))
			$misspellings = array($misspellings);
	}
}

?>
