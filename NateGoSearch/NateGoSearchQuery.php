<?php

require_once 'NateGo/NateGoSearchIndexer.php';
require_once 'NateGo/NateGoSearchResult.php';
require_once 'SwatDB/SwatDB.php';

/**
 *
 *
 * @package   NateGoSearch
 * @copyright 2006 silverorange
 */
class NateGoSearchQuery
{
	protected $document_types = array();
	protected $blocked_words = array();

	protected $db;

	public function __construct(MDB2_Driver_Common $db)
	{
		$this->db = $db;
	}

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

	protected function &getBlockedWords()
	{
		$blocked_words = array();
		return $blocked_words;
	}

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

		$tok = strtok($keywords, ' ');
		while ($tok) {
			if (in_array($tok, $this->blocked_words))
				$results->addBlockedWords($tok);
			else
				$results->addSearchedWords($tok);

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
}

?>
