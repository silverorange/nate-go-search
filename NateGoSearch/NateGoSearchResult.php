<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'NateGoSearch/exceptions/NateGoSearchDocumentTypeException.php';

/**
 * Represents the result of a search using NateGoSearch
 *
 * This class is designed to be returned from a call to
 * {@link NateGoSearchQuery::query()} and is not designed to be used on its own.
 *
 * @package   NateGoSearch
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class NateGoSearchResult
{
	// {{{ protected properties

	protected $unique_id;
	protected $query_string;
	protected $blocked_words = array();
	protected $searched_words = array();
	protected $misspellings = array();
	protected $document_types = array();
	protected $db;

	// }}}
	// {{{ public function __construct()

	/**
	 * Builds a new search result
	 *
	 * @param MDB2_Driver_Common $db the database driver for this result.
	 * @param string $unique_id the unique identifier of the search results in
	 *                           the results table.
	 * @param string $query_string the query that was entered by the user after
	 *                              normalizing and formatting.
	 * @param array $document_types an array of document types in this search
	 *                               result. The array is indexed by type
	 *                               shortname with type ids as values.
	 *
	 * @see NateGoSearchResult::getUniqueId()
	 */
	public function __construct(MDB2_Driver_Common $db, $unique_id,
		$query_string, array $document_types)
	{
		$this->unique_id = $unique_id;
		$this->query_string = $query_string;
		$this->document_types = $document_types;
		$this->db = $db;
	}

	// }}}
	// {{{ public function addBlockedWords()

	/**
	 * Adds a word to the list of blocked words
	 *
	 * @param string|array $words either an array containing words that were
	 *                             not searched for, or a string containing a
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

	// }}}
	// {{{ public function addSearchWords()

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

	// }}}
	// {{{ public function addMisspellings()

	/**
	 * Adds misspellings to this result object
	 *
	 * @param array $misspellings a list of misspellings to add in the form
	 *                             incorrect => correct.
	 */
	public function addMisspellings($misspellings)
	{
		$this->misspellings = array_merge($this->misspellings, $misspellings);
	}

	// }}}
	// {{{ public function &getBlockedWords()

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

	// }}}
	// {{{ public function &getSearchedWords()

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

	// }}}
	// {{{ public function &getMisspellings()

	/**
	 * Gets words that were misspelled in the search query
	 *
	 * @return array words that were misspelled in the search query. The array
	 *                is of the form incorrect => correct.
	 */
	public function &getMisspellings()
	{
		return $this->misspellings;
	}

	// }}}
	// {{{ public function getUniqueId()

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

	// }}}
	// {{{ public function getQueryString()

	/**
	 * Gets the query string entered by the user
	 *
	 * The query string is a normalized version of the keywords entered by the
	 * user. The query string keywords are not stemmed even if a porter stemmer
	 * exists. This string is useful to collect normalized search statistics.
	 *
	 * @return string
	 */
	public function getQueryString()
	{
		return $this->query_string;
	}

	// }}}
	// {{{ public function getResultTable()

	/**
	 * Gets the name of the table that NateGoSearch results are stored in
	 *
	 * By default this is 'nategosearchresult'.
	 *
	 * @return string the name of the table that NateGoSearch results are
	 *                 stored in.
	 */
	public function getResultTable()
	{
		return 'NateGoSearchResult';
	}

	// }}}
	// {{{ public function getDocumentIdField()

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

	// }}}
	// {{{ public function getDocumentType()

	/**
	 * Gets a document type identifier of this search result by shortname
	 *
	 * @param string $type_shortname the shortname of the document type to get.
	 *
	 * @return integer the identifier of the document type.
	 *
	 * @throws NateGoSearchDocumentTypeException if the provided shortname does
	 *                                           not represent a document type
	 *                                           of this search result.
	 */
	public function getDocumentType($type_shortname)
	{
		if (!array_key_exists($type_shortname, $this->document_types)) {
			throw new NateGoSearchDocumentTypeException(
				"Document type {$type_shortname} does not exist in this ".
				"search result. Add the document type to the query object ".
				"before calling the query() method to include this document ".
				"in search results.", 0, $type_shortname);
		}

		return $this->document_types[$type_shortname];
	}

	// }}}
	// {{{ public function getDocumentCount()

	/**
	 * Gets the number of unique documents returned by this search result
	 *
	 * A unique document is a unique combination of <code>$document_id</code>
	 * and <code>$document_type</code<.
	 *
	 * @return integer the number of unique documents returned by this search
	 *                 result.
	 */
	public function getDocumentCount()
	{
		return $this->document_count;
	}

	// }}}
	// {{{ public function setUniqueId()

	/**
	 * Sets the unique identifier of this search result in the results table
	 *
	 * NateGoSearch stores all search results in the same table. The results of
	 * a specific query may be gathered by selecting results from the database
	 * with the appropriate unique id.
	 *
	 * @param string $id the unique identifier of this search result in the
	 *                    results table.
	 *
	 * @see NateGoSearchResult::getUniqueId()
	 */
	public function setUniqueId($id)
	{
		$this->unique_id = (string)$id;
	}

	// }}}
	// {{{ public function setDocumentCount()

	/**
	 * Sets the document count of this search result in the results table
	 *
	 * @param integer $count the number of unique documents returned by this
	 *                        search result.
	 *
	 * @see NateGoSearchResult::getDocumentCount()
	 */
	public function setDocumentCount($count)
	{
		$this->document_count = (integer)$count;
	}

	// }}}
	// {{{ public function saveHistory()

	/**
	 * Saves this search result for search trend statistics and tracking
	 */
	public function saveHistory()
	{
		$sql = sprintf('insert into NateGoSearchHistory
			(keywords, document_count) values
			(%s, %s)',
			$this->db->quote($this->query_string, 'text'),
			$this->db->quote($this->document_count, 'integer'));

		$result = $this->db->exec($sql);
		if (MDB2::isError($result))
			throw new NateGoSearchDBException($result);
	}

	// }}}
	// {{{ public static function formatMisspellings()

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

	// }}}
}

?>
