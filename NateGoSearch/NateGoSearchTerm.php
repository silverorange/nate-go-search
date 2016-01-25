<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

/**
 * A search index term
 *
 * Search index terms are added to the indexer which then indexes documents
 * based on all terms.
 *
 * @package   NateGoSearch
 * @copyright 2006-2016 silverorange
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

	/**
	 * If this field should be used for popular keyword suggestions.
	 *
	 * Whether or not the words of this field should be added to the popular
	 * keywords table.
	 *
	 * @var boolean
	 */
	protected $is_popular = false;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new search term
	 *
	 * @param string $data_field the name of the document field to index.
	 * @param integer $weight the relative weight of this term. Defaults to 1.
	 * @param boolean $is_popular if this words in this field should be added to
	 *                             the popular keywords tables. Defaults to
	 *                             false.
	 */
	public function __construct($data_field, $weight = 1, $is_popular = false)
	{
		$this->data_field = $data_field;
		$this->weight = $weight;
		$this->is_popular = $is_popular;
	}

	// }}}
	// {{{ public function setWeight()

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
	// {{{ public function isPopular()

	/**
	 * Returns whether or not this field should be added to the popular keyword
	 *  table.
	 *
	 * @return boolean whether this field should to be added to the popular
	 *                  keyword table.
	 */
	public function isPopular()
	{
		return $this->is_popular;
	}

	// }}}
}

?>
