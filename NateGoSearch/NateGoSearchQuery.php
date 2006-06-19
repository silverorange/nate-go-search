<?php

require_once 'NateGoSearch/NateGoSearchIndexer.php';
require_once 'NateGoSearch/NateGoSearchResult.php';
require_once 'NateGoSearch/NateGoSearchSpellChecker.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Perform queries using a NateGoSearch index
 *
 * This is the class used to actually search indexed keywords. Instances of
 * this class may search the index using the find() method. For example, to
 * search a database table called 'Article' indexed with a document type of
 * 1, use the following code:
 *
 * <code>
 * $query = new NateGoSearchQuery($db);
 * $query->addDocumentType(1);
 * $result = $query->find('some keywords');
 *
 * $sql = 'select id, title from Article ' .
 *     'inner join %s on Article.id = %s.document_id and '.
 *     '%s.unique_id = \'%s\' and ' .
 *     '%s.document_type = %s';
 *
 * $sql = sprintf($sql,
 *     $result->getResultTable(),
 *     $result->getResultTable(),
 *     $result->getResultTable(),
 *     $result->getUniqueId(),
 *     $result->getResultTable());
 *
 * $articles = $db->query($sql);
 * </code>
 *
 * Because of the specific PL/PGSQL implementation of the search algorithm,
 * the {@link NateGoSearchQuery::find()} method may only be called once per
 * page request.
 *
 * If a Porter-Stemming class is defined, it is used on all query keywords. The
 * default PHP implementation of Porter-Stemming is licenced under the GPL and
 * is thus not distributable with NateGoSearch.
 *
 * @package   NateGoSearch
 * @copyright 2006 silverorange
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
	 * @param integer $type the document type to add.
	 */
	public function addDocumentType($type)
	{
		$type = (integer)$type;
		if (!in_array($type, $this->document_types))
			$this->document_types[] = $type;
	}

	// }}}
	// {{{ public function &getBlockedWords()

	protected function &getBlockedWords()
	{
		$blocked_words = array();
		return $blocked_words;
	}

	// }}}
	// {{{ public function query()

	/**
	 * Queries the NateGo index with a set of keywords
	 *
	 * Querying does not directly return a set of results. This is due to the
	 * way NateGoSearch is implemented. The document ids from this search are
	 * stored in a results table and accessed through a unique identifier.
	 *
	 * @param string $keywords the search string to query.
	 *
	 * @return NateGoSearchResult an object containing result information.
	 */
	public function query($keywords)
	{
		static $unique_counter = 0;

		$id = md5(uniqid($unique_counter, true));
		$keywords = NateGoSearchIndexer::formatKeywords($keywords);

		$results = new NateGoSearchResult($id, $keywords);

		if ($this->spell_checker !== null)
			$results->addMisspellings(
				$this->spell_checker->getMisspellingsInPhrase($keywords));

		$tok = strtok($keywords, ' ');
		while ($tok) {
			if (class_exists('PorterStemmer'))
				$keyword = PorterStemmer::Stem($tok);
			else
				$keyword = $tok;

			if (in_array($keyword, $this->blocked_words))
				$results->addBlockedWords($keyword);
			else
				$results->addSearchedWords($keyword);

			$tok = strtok(' ');
		}

		$keywords = implode(' ', $results->getSearchedWords());

		if (count($this->document_types) > 0) {
			SwatDB::executeStoredProc($this->db, 'nateGoSearch',
				array(
					$this->db->quote($keywords, 'text'),
					$this->quoteArray($this->document_types),
					$this->db->quote($id, 'text')));

			$unique_counter++;
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
		$return = 'ARRAY[';

		if (is_array($array))
			$return.= $this->db->implodeArray($array, $type);

		$return.= ']';

		return $return;
	}

	// }}}
}

?>
