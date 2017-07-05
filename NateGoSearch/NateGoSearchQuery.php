<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'NateGoSearch.php';
require_once 'NateGoSearch/NateGoSearchIndexer.php';
require_once 'NateGoSearch/NateGoSearchResult.php';
require_once 'NateGoSearch/NateGoSearchSpellChecker.php';
require_once 'NateGoSearch/exceptions/NateGoSearchDBException.php';
require_once 'NateGoSearch/exceptions/NateGoSearchDocumentTypeException.php';

/**
 * Perform queries using a NateGoSearch index
 *
 * This is the class used to actually search indexed keywords. Instances of
 * this class may search the index using the {@link NateGoSearchQuery::query()}
 * method. For example, to search a database table called <em>Article</em>
 * indexed with a document type of <em>article</em>, use the following code:
 *
 * <code>
 * <?php
 * $query = new NateGoSearchQuery($db);
 * $query->addDocumentType('article');
 * $result = $query->query('some keywords');
 *
 * $sql = 'select id, title from Article ' .
 *     'inner join %s on Article.id = %s.document_id and '.
 *     '%s.unique_id = \'%s\' and %s.document_type = %s';
 *
 * $sql = sprintf($sql,
 *     $result->getResultTable(),
 *     $result->getResultTable(),
 *     $result->getResultTable(),
 *     $result->getUniqueId(),
 *     $result->getResultTable(),
 *     $result->getDocumentType('article'));
 *
 * $articles = $db->query($sql);
 * ?>
 * </code>
 *
 * Because of the specific PL/pgSQL implementation of the search algorithm,
 * the <code>query()</code> method may only be called once per page request.
 *
 * If the PECL <code>stem</code> package is loaded, English stemming is applied
 * to all query keywords. See {@link http://pecl.php.net/package/stem/} for
 * details about the PECL stem package. Support for stemming in other languages
 * may be added in later releases of NateGoSearch.
 *
 * Otherwise, if a <code>PorterStemmer</code> class is defined, it is applied
 * to all query keywords. The most commonly available PHP implementation of the
 * Porter-stemmer algorithm is licenced under the GPL, and is thus not
 * distributable with the LGPL licensed NateGoSearch.
 *
 * @package   NateGoSearch
 * @copyright 2006-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class NateGoSearchQuery
{
	// {{{ protected properties

	/**
	 * The document types searched by this query
	 *
	 * @var array
	 *
	 * @see NateGoSearch::getDocumentType()
	 * @see NateGoSearchQuery::addDocumentType()
	 */
	protected $document_types = array();

	/**
	 * Keywords that should not be included in the search performed by this
	 * query
	 *
	 * This is an array of blocked keywords.
	 *
	 * @var array
	 *
	 * @see NateGoSearchQuery::addBlockedWords()
	 */
	protected $blocked_words = array();

	/**
	 * Popular keywords that are searched often on the site
	 *
	 * This is an array of of popular keywords.
	 *
	 * @var array
	 *
	 * @see NateGoSearchQuery::getSearchHistoryPopularWords()
	 * @see NateGoSearchQuery::addPopularWords()
	 */
	protected $popular_words = array();

	/**
	 * Spell checked used to check the spelling of keywords used in this
	 * query
	 *
	 * Null by default meaning no spell-checking is done.
	 *
	 * @var NateGoSearchSpellChecker
	 *
	 * @see NateGoSearchQuery::setSpellChecker()
	 */
	protected $spell_checker;

	/**
	 * The MDB2 database driver to use
	 *
	 * Currently, NateGoSearch only supports PostgreSQL.
	 *
	 * @var MDB2_Driver_Common
	 *
	 * @see NateGoSearchQuery::__construct()
	 */
	protected $db;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new NateGoSearch fulltext query
	 *
	 * @param MDB2_Driver_Common $db the database driver to use.
	 */
	public function __construct(MDB2_Driver_Common $db)
	{
		$this->db = $db;
	}

	// }}}
	// {{{ public function addDocumentType()

	/**
	 * Adds a document type to be searched by this query
	 *
	 * @param string $type_shortname the shortname of the document type to add.
	 *
	 * @see NateGoSearch::createDocumentType()
	 *
	 * @throws NateGoSearchDocumentTypeException if the document type shortname
	 *                                           does not exist.
	 */
	public function addDocumentType($type_shortname)
	{
		$type_shortname = (string)$type_shortname;

		if (!array_key_exists($type_shortname, $this->document_types)) {
			$type = NateGoSearch::getDocumentType($this->db, $type_shortname);

			if ($type === null) {
				throw new NateGoSearchDocumentTypeException(
					"Document type {$type_shortname} does not exist and ".
					"cannot be added to a query. Document types must be ".
					"created before being used.", 0, $type_shortname);
			}

			$this->document_types[$type_shortname] = $type;
		}
	}

	// }}}
	// {{{ public function addBlockedWords()

	/**
	 * Adds words to the list of words that are not to be searched
	 *
	 * These may be words such as 'the', 'and' and 'a'.
	 *
	 * @param string|array $words the list of words not to be searched.
	 */
	public function addBlockedWords($words)
	{
		if (!is_array($words)) {
			$words = array((string)$words);
		}

		$this->blocked_words = array_merge($this->blocked_words, $words);
	}

	// }}}
	// {{{ public function addPopularWords()

	/**
	 * Adds words to the list of popular words that should be suggested
	 *
	 * @param string|array $words the list of popular words.
	 */
	public function addPopularWords($words)
	{
		if (!is_array($words)) {
			$words = array((string)$words);
		}

		$this->popular_words = array_merge(
			$this->popular_words,
			$this->cleanWords($words)
		);
	}

	// }}}
	// {{{ public function query()

	/**
	 * Queries the NateGoSearch index with a set of keywords
	 *
	 * Querying does not directly return a set of results. This is due to the
	 * way NateGoSearch is designed. The document ids from this search are
	 * stored in a results table and accessed through a unique identifier.
	 *
	 * @param string $keywords the search string to query.
	 *
	 * @return NateGoSearchResult an object containing result information.
	 *
	 * @see NateGoSearchResult::getUniqueId()
	 */
	public function query($keywords)
	{
		static $unique_counter = 0;

		$id = sha1(uniqid($unique_counter, true));

		$keywords = $this->normalizeKeywordsForSpelling($keywords);

		if ($this->spell_checker === null) {
			$misspellings = array();
		} else {
			$misspellings =
				$this->spell_checker->getMisspellingsInPhrase($keywords);
		}

		$misspellings = $this->getPopularReplacements($keywords, $misspellings);

		$keywords = $this->normalizeKeywordsForSearching($keywords);

		$document_type_ids = array_values($this->document_types);
		sort($document_type_ids);

		$unique_hash = sha1($keywords.':'.implode('-', $document_type_ids));

		$results = new NateGoSearchResult(
			$this->db,
			$id,
			$keywords,
			$this->document_types
		);

		$results->addMisspellings($misspellings);

		$searched_keywords = array();
		$keyword = strtok($keywords, ' ');
		while ($keyword) {
			if (in_array($keyword, $this->blocked_words)) {
				$results->addBlockedWords($keyword);
			} else {
				$searched_keywords[] =
					NateGoSearchIndexer::stemKeyword($keyword);

				$results->addSearchedWords($keyword);
			}

			$keyword = strtok(' ');
		}

		$keywords = implode(' ', $searched_keywords);

		if (count($this->document_types) > 0) {
			$this->db->loadModule('Function');

			$params = array(
				$this->db->quote($keywords, 'text'),
				$this->db->quote($unique_hash, 'text'),
				$this->quoteArray($this->document_types),
				$this->db->quote($id, 'text'),
			);

			$types = array('text');

			$rs = $this->db->function->executeStoredProc(
				'nateGoSearch', $params, $types);

			if (MDB2::isError($rs))
				throw new NateGoSearchDBException($rs);

			$unique_id = $rs->fetchOne();
			if (MDB2::isError($unique_id))
				throw new NateGoSearchDBException($unique_id);

			$unique_counter++;

			$results->setUniqueId($unique_id);

			$sql = sprintf('select count(document_id) from %s
				where unique_id = %s',
				$results->getResultTable(),
				$this->db->quote($unique_id, 'text'));

			$document_count = $this->db->queryOne($sql);
			if (MDB2::isError($document_count))
				throw new NateGoSearchDBException($document_count);

			$results->setDocumentCount($document_count);
		}

		return $results;
	}

	// }}}
	// {{{ public function setSpellChecker()

	/**
	 * Sets the spell checker used by this query
	 *
	 * @param NateGoSearchSpellChecker $spell_checker optional. The spell
	 *        checker to use for this query. If not specified or specified as
	 *        null, no spell checking is performed.
	 */
	public function setSpellChecker(
		NateGoSearchSpellChecker $spell_checker = null)
	{
		$this->spell_checker = $spell_checker;
	}

	// }}}
	// {{{ public static function &getDefaultBlockedWords()

	/**
	 * Gets a default list of words that are not searched by a search query
	 *
	 * These words may be passed directly to the
	 * {@link NateGoSearchQuery::addBlockedWords()} method.
	 *
	 * @return array a default list of words not to index.
	 */
	public static function &getDefaultBlockedWords()
	{
		static $words = array();

		if (count($words) == 0) {
			if (substr('@DATA-DIR@', 0, 1) === '@')
				$filename = dirname(__FILE__).'/../system/blocked-words.txt';
			else
				$filename = '@DATA-DIR@/NateGoSearch/system/blocked-words.txt';

			$words = file($filename, true);
			// remove line breaks
			$words = array_map('rtrim', $words);
		}

		return $words;
	}

	// }}}
	// {{{ protected function getSearchHistoryPopularWords()

	/**
	 * Get a list of popular/successful search keywords
	 *
	 * This is used to query the database for a list of keywords from the
	 * NateGoSearchHistory table. The results are based upon the document_count
	 * of each of the keywords and if the words have been searched recently.
	 *
	 * @param MDB2_Driver_Common $db the database driver to use.
	 * @param integer $document_threshold optional. The minimum number of
	 *                                     results in which a word must be
	 *                                     contained to be considered
	 *                                     popular. If not specified, 150
	 *                                     is used.
	 * @param string $date_threshold optional. Search keywords must be after
	 *                                this date to be considered popular. Uses
	 *                                strtotime format. If not specified,
	 *                                '6 months ago' is used.
	 *
	 * @return array an array of popular search words
	 */
	public static function getSearchHistoryPopularWords(
		MDB2_Driver_Common $db,
		$document_threshold = 150, $date_threshold = '6 months ago')
	{
		$date = strtotime($date_threshold);
		$date = date('c', $date);

		$sql = sprintf(
			'select distinct keywords from NateGoSearchHistory
			where document_count > %s and creation_date > %s',
			$db->quote($document_threshold, 'integer'),
			$db->quote($date, 'date')
		);

		$words = $db->queryCol($sql, 'text');
		if (MDB2::isError($words)) {
			throw new NateGoSearchDBException($words);
		}

		return $words;
	 }

	 // }}}
	// {{{ protected function normalizeKeywordsForSpelling()

	/**
	 * Performs initial normalization of a query string suitable for
	 * spell-checking
	 *
	 * This removes excess punctuation and markup. The resulting string may be
	 * tokenized by spaces. Before searching, query strings should be further
	 * normalized using
	 * {@link NateGoSearchQuery::normalizeKeywordsForSearching()}.
	 *
	 * @param string $text the string to be normalized.
	 *
	 * @return string the normalized string.
	 *
	 * @see NateGoSearchQuery::normalizeKeywordsForSearching()
	 */
	protected function normalizeKeywordsForSpelling($text)
	{
		// replace html/xhtml/xml tags with spaces
		$text = preg_replace('@</?[^>]*>*@u', ' ', $text);

		// remove entities
		$text = html_entity_decode($text, ENT_COMPAT, 'UTF-8');

		// remove punctuation at the beginning and end of the string
		$text = preg_replace('/^\W+/u', '', $text);
		$text = preg_replace('/\W+$/u', '', $text);

		// remove punctuation at the beginning and end of words
		$text = preg_replace('/\s+\W+/u', ' ', $text);
		$text = preg_replace('/\W+\s+/u', ' ', $text);

		// replace multiple dashes with a single dash
		$text = preg_replace('/-+/u', '-', $text);

		// replace whitespace with single spaces
		$text = preg_replace('/\s+/u', ' ', $text);

		return $text;
	}

	// }}}
	// {{{ protected function normalizeKeywordsForSearching()

	/**
	 * Performs additional normalization of a query string suitable for
	 * searching
	 *
	 * This converts all words to lower-case and removes apostrophe s's from
	 * all words. Keywords should have already been partially normalized
	 * using {@link NateGoSearchQuery::normalizeKeywordsForSpelling()}.
	 *
	 * @param string $text the string to be normalized.
	 *
	 * @return string the normalized string.
	 *
	 * @see NateGoSearchQuery::normalizeKeywordsForSpelling()
	 */
	protected function normalizeKeywordsForSearching($text)
	{
		// lowercase
		$text = strtolower($text);

		// replace apostrophe s's
		$text = preg_replace('/\'s\b/u', '', $text);

		return $text;
	}

	// }}}
	// {{{ protected function getPopularReplacements()

	/**
	 * Get popular replacements for words in the search keywords.
	 *
	 * This is used to check search keywords along with their coresponding
	 * spelling suggestion for matches in the popular words list. If a match
	 * is found we either replace the current misspelling, if one exists, or
	 * add an entry to the mispelling list with the new popular suggestion
	 * added.
	 *
	 * @param string $keywords the keywords to check for improved suggestions
	 * @param array $misspellings the misspellings for the given $keywords
	 *
	 * @param array $misspellings the misspellings with added suggestions for
	 *   popular words
	 */
	protected function getPopularReplacements($keywords, array $misspellings)
	{
		$words = explode(' ', $keywords);

		foreach ($words as $word) {
			$word = strtolower($word);
			if (!in_array($word, $this->blocked_words)
				&& !in_array($word, $this->popular_words)
				&& !is_numeric($word)) {
				foreach ($this->popular_words as $popular_word) {
					if (array_key_exists($word, $misspellings)) {
						if ($this->isPopularMatch($popular_word,
								$misspellings[$word])) {
							$misspellings[$word] = $popular_word;
							break;
						}
					} else {
						if ($this->isPopularMatch($popular_word, $word)) {
							$misspellings[$word] = $popular_word;
							break;
						}
					}
				}
			}
		}

		return $misspellings;
	}

	// }}}
	// {{{ protected function cleanWords()

	/**
	 * Clean a words list
	 *
	 * This is used to clean up an array of words to a list of unique words
	 * that contains only on word per array entry. Numbers and common words
	 * found in {@link NateGoSearchQuery::blocked_words} are removed from the
	 * list.
	 *
	 * @param array $words an array of words to clean
	 *
	 * @return array the cleaned results.
	 */
	protected function cleanWords(array $dirty_words)
	{
		$clean_words = array();

		foreach ($dirty_words as $dirty_word)
		{
			$dirty_word = preg_replace('/\s+/u', ' ', $dirty_word);
			$words = explode(' ', $dirty_word);

			foreach ($words as $word) {
				if (!in_array($word, $this->blocked_words)
					&& !is_numeric($word)
					&& !in_array($word, $clean_words)
					&& $word != '') {
					$clean_words[] = $word;
				}
			}
		}

		return $clean_words;
	}

	// }}}
	// {{{ protected isPopularMatch()

	/**
	 * Checks if two words are similar
	 *
	 * This is used to check if a one word matches a another word from the
	 * popular wordlist. Used to improve search suggestions by confirming
	 * whether two words are similar in sound and/or spelling.
	 *
	 * @param string $word1 the first word being compared for similarities
	 * @param string $word2 the second word being compared for similarities
	 *
	 * @return boolean whether or not the strings are similar
	 */
	protected function isPopularMatch($word1, $word2)
	{
		return (levenshtein($word1, $word2) < 2)
			|| (metaphone($word1) == metaphone($word2));
	}

	// }}}
	// {{{ protected function quoteArray()

	/**
	 * Quotes a PHP array into a PostgreSQL array
	 *
	 * This is used to quote the list of document types used in the internal
	 * SQL query.
	 *
	 * @param array $array the array to quote.
	 * @param string $type the SQL data type to use. The type is 'integer' by
	 *                      default.
	 *
	 * @return string the array quoted as an SQL array.
	 */
	protected function quoteArray($array, $type = 'integer')
	{
		$this->db->loadModule('Datatype');

		$return = 'ARRAY[';

		if (is_array($array))
			$return.= $this->db->datatype->implodeArray($array, $type);

		$return.= ']';

		return $return;
	}

	// }}}
}

?>
