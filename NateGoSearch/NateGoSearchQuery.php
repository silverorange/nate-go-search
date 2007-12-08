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
 * this class may search the index using the find() method. For example, to
 * search a database table called 'Article' indexed with a document type of
 * 'article', use the following code:
 *
 * <code>
 * $query = new NateGoSearchQuery($db);
 * $query->addDocumentType('article');
 * $result = $query->find('some keywords');
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
 * </code>
 *
 * Because of the specific PL/pgSQL implementation of the search algorithm,
 * the {@link NateGoSearchQuery::find()} method may only be called once per
 * page request.
 *
 * If the PECL <i>stem</i> package is loaded, English stemming is applied to all
 * query keywords. See {@link http://pecl.php.net/package/stem/} for details
 * about the PECL stem package. Support for stemming in other languages may
 * be added in later releases of NateGoSearch.
 *
 * Otherwise, if a PorterStemmer class is defined, it is applied to all query
 * keywords. The most commonly available PHP implementation of the
 * Porter-stemmer algorithm is licenced under the GPL, and is thus not
 * distributable with the LGPL licensed NateGoSearch.
 *
 * @package   NateGoSearch
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class NateGoSearchQuery
{
	// {{{ protected properties

	protected $document_types = array();
	protected $blocked_words = array();
	protected $spell_checker;
	protected $db;

	// }}}
	// {{{ public function __construct()

	/**
	 * @param MDB2_Driver_Common $db
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
		if (!is_array($words))
			$words = array((string)$words);

		$this->blocked_words = array_merge($this->blocked_words, $words);
	}

	// }}}
	// {{{ public function query()

	/**
	 * Queries the NateGo index with a set of keywords
	 *
	 * Querying does not directly return a set of results. This is due to the
	 * way NateGoSearch is designed. The document ids from this search are
	 * stored in a results table and accessed through a unique identifier.
	 *
	 * @param string $keywords the search string to query.
	 *
	 * @return NateGoSearchResult an object containing result information.
	 */
	public function query($keywords)
	{
		static $unique_counter = 0;

		$id = sha1(uniqid($unique_counter, true));
		$keywords = NateGoSearchIndexer::formatKeywords($keywords);
		$keywords_hash = sha1($keywords);

		$results = new NateGoSearchResult($id, $keywords,
			$this->document_types);

		if ($this->spell_checker !== null)
			$results->addMisspellings(
				$this->spell_checker->getMisspellingsInPhrase($keywords));

		$searched_keywords = array();
		$keyword = strtok($keywords, ' ');
		while ($keyword) {
			if (in_array($keyword, $this->blocked_words)) {
				$results->addBlockedWords($keyword);
			} else {
				$searched_keywords[] = $this->stemKeyword($keyword);
				$results->addSearchedWords($keyword);
			}

			$keyword = strtok(' ');
		}

		$keywords = implode(' ', $searched_keywords);

		if (count($this->document_types) > 0) {
			$this->db->loadModule('Function');

			$params = array(
				$this->db->quote($keywords, 'text'),
				$this->db->quote($keywords_hash, 'text'),
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
		}

		return $results;
	}

	// }}}
	// {{{ public function setSpellChecker()

	/**
	 * Sets the spell checker used by this query
	 *
	 * @param NateGoSearchSpellChecker $spell_checker the spell checker to use
	 *                                                 for this query. If set
	 *                                                 to null, no spell
	 *                                                 checking is done.
	 */
	public function setSpellChecker(NateGoSearchSpellChecker $spell_checker)
	{
		$this->spell_checker = $spell_checker;
	}

	// }}}
	// {{{ public function quoteArray()

	/**
	 * Quotes a PHP array into a PostgreSQL array
	 *
	 * @param array $array the array to quote.
	 * @param string $type the SQL data type to use. The type is 'integer' by
	 *                      default.
	 *
	 * @return string the array quoted as an SQL array.
	 */
	private function quoteArray($array, $type = 'integer')
	{
		$this->db->loadModule('Datatype');

		$return = 'ARRAY[';

		if (is_array($array))
			$return.= $this->db->datatype->implodeArray($array, $type);

		$return.= ']';

		return $return;
	}

	// }}}
	// {{{ public static function &getDefaultBlockedWords()

	/**
	 * Gets a defalt list of words that are not searched by a search query 
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
	// {{{ protected function stemKeyword()

	/**
	 * Stems a keyword
	 *
	 * The basic idea behind stemmming is described on the Wikipedia article on
	 * {@link http://en.wikipedia.org/wiki/Stemming Stemming}.
	 *
	 * If the PECL <i>stem</i> package is loaded, English stemming is performed
	 * on the <i>$keyword</i>. See {@link http://pecl.php.net/package/stem/}
	 * for details about the PECL stem package.
	 *
	 * Otherwise, if a PorterStemmer class is defined, it is applied to the
	 * <i>$keyword</i>. The most commonly available PHP implementation of the
	 * Porter-stemmer algorithm is licenced under the GPL, and is thus not
	 * distributable with the LGPL licensed NateGoSearch.
	 *
	 * If no stemming is available, stemming is not performed and the original
	 * keyword is returned.
	 *
	 * @param string $keyword the keyword to stem.
	 *
	 * @return string the stemmed keyword.
	 */
	protected function stemKeyword($keyword)
	{
		if (extension_loaded('stem'))
			$keyword = stem($keyword, STEM_ENGLISH);
		elseif (is_callable(array('PorterStemmer', 'Stem')))
			$keyword = PorterStemmer::Stem($keyword);

		return $keyword;
	}

	// }}}
}

?>
