<?php

class NateGoSearchResult
{
	protected $unique_id;
	protected $query;
	protected $blocked_words = array();
	protected $searched_words = array();

	public function __construct($unique_id)
	{
		$this->unique_id = $unique_id;
	}

	public function addBlockedWords($words)
	{
		if (!is_array($words))
			$words = array((string)$words);

		$this->blocked_words += $words;
	}

	public function addSearchedWords($words)
	{
		if (!is_array($words))
			$words = array((string)$words);

		$this->searched_words += $words;
	}

	public function &getBlockedWords()
	{
		return $this->blocked_words;
	}

	public function &getSearchedWords()
	{
		return $this->searched_words;
	}

	public function getUniqueId()
	{
		return $this->unique_id;
	}

	public function getResultsTable()
	{
	}

	public function getDocumentIdField()
	{
	}

	public static function formatMisspellings($misspellings)
	{
		if (!is_array($misspellings))
			$misspellings = array($misspellings);

	}
}

?>
