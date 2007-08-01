<?php

/**
 * A search index term
 *
 * Search index terms are added to the indexer which then indexes documents
 * based on all terms.
 *
 * @package   NateGoSearch
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       NateGoSearchIndexer
 */
class NateGoSearchTerm
{
	// {{{ protected properties

	/**
	 * The name of the document field to index
	 *
	 * @var string
	 */
	protected $data_field;

	/**
	 * The weight of this term
	 *
	 * Term weights are relative to the weights of other terms in an indexer.
	 * The more weight one term has compared to other terms, the higher the
	 * score matches on this term will have for a given document.
	 *
	 * @var integer
	 */
	protected $weight;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new search term
	 *
	 * @param string $data_field the name of the document field to index.
	 * @param integer $weight the relative weight of this term. Defaults to 1.
	 */
	public function __construct($data_field, $weight = 1)
	{
		$this->data_field = $data_field;
		$this->weight = $weight;
	}

	// }}}
	// {{{ public function segtWeight()

	/**
	 * Sets the weight of this term
	 *
	 * @param integer $weight the new weight of this term.
	 *
	 * @see NateGoSearchTerm::$weight
	 */
	public function setWeight($weight)
	{
		$this->weight = $weight;
	}

	// }}}
	// {{{ public function getDataField()

	/**
	 * Gets the name of the document field this term represents
	 *
	 * @return string the name of the document field this term represents.
	 */
	public function getDataField()
	{
		return $this->data_field;
	}

	// }}}
	// {{{ public function getWeight()

	/**
	 * Gets the weight of this term
	 *
	 * @return integer the weight of this term.
	 *
	 * @see NateGoSearchTerm::$weight
	 */
	public function getWeight()
	{
		return $this->weight;
	}

	// }}}
}

?>
