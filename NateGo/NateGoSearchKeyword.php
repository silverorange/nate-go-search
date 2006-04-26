<?php

/**
 *
 * @package   NateGo
 * @copyright 2006 silverorange
 */
class NateGoSearchKeyword
{
	protected $word;
	protected $document_id;
	protected $weight;
	protected $location;
	protected $tag;

	public function __construct($word, $document_id, $weight, $location, $tag)
	{
		$this->word = $word;
		$this->document_id = $document_id;
		$this->weight = $weight;
		$this->location = $location;
		$this->tag = $tag;
	}

	public function getWord()
	{
		return $this->word;
	}

	public function getDocumentId()
	{
		return $this->document_id;
	}

	public function getWeight()
	{
		return $this->weight;
	}

	public function getLocation()
	{
		return $this->location;
	}

	public function getTag()
	{
		return $this->tag;
	}
}

?>
