<?php

/**
 * An indexed keyword for a NateGoSearchIndexer
 *
 * This class is not meant to be used standalone; it is used internally by
 * NateGoSearchIndexer.
 *
 * @package   NateGo
 * @copyright 2006 silverorange
 * @see       NateGoSearchIndexer
 */
class NateGoSearchKeyword
{
	protected $word;
	protected $document_id;
	protected $weight;
	protected $location;
	protected $document_type;

	/**
	 * Creates a new indexed keyword
	 *
	 * @param string $word the word this keyword represents.
	 * @param mixed $document_id an identifier for the document containing this
	 *                            keyword.
	 * @param integer $weight a weight for this keyword.
	 * @param integer $location the word location of the keyword in the
	 *                           document. First word, second word, etc...
	 * @param integer $document_type the document type this keyword's document
	 *                                belongs to.
	 */
	public function __construct($word, $document_id, $weight, $location,
		$document_type)
	{
		$this->word = $word;
		$this->document_id = $document_id;
		$this->weight = $weight;
		$this->location = $location;
		$this->document_type = $document_type;
	}

	/**
	 * @return string the word this keyword represents.
	 */
	public function getWord()
	{
		return $this->word;
	}

	/**
	 * @return mixed an identifier for the document containing this keyword.
	 */
	public function getDocumentId()
	{
		return $this->document_id;
	}

	/**
	 * @return integer a weight for this keyword.
	 */
	public function getWeight()
	{
		return $this->weight;
	}

	/**
	 * @return integer the word location of the keyword in the document. First
	 *                  word, second word, etc...
	 */
	public function getLocation()
	{
		return $this->location;
	}

	/**
	 * @return integer the document type this keyword's document belongs to.
	 */
	public function getDocumentType()
	{
		return $this->document_type;
	}
}

?>
