<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

/**
 * A field in a document to index
 *
 * @package   NateGoSearch
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       NateGoSearchDocument
 */
class NateGoSearchField
{
	// {{{ protected properties

	/**
	 * The name of the document field to index
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The weight of this field
	 *
	 * Field weights are relative to the weights of other field in a document.
	 * The more weight one field has compared to other fields, the higher the
	 * score matches on this field will have for a given document.
	 *
	 * @var integer
	 */
	protected $weight = 1;

	/**
	 * Whether or not the words of this field should be added to the popular
	 * keywords table
	 *
	 * @var boolean
	 */
	protected $is_popular = false;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new search field
	 *
	 * @param string $name the name of the document field to index.
	 * @param integer $weight optional. The relative weight of this field. If
	 *                         not specified, defaults to 1.
	 * @param boolean $is_popular optional. Whether or not the this words in
	 *                             this field should be added to the popular
	 *                             keywords index. Defaults to false.
	 */
	public function __construct($name, $weight = 1, $is_popular = false)
	{
		$this->name       = $name;
		$this->weight     = $weight;
		$this->is_popular = $is_popular;
	}

	// }}}
	// {{{ public function setWeight()

	/**
	 * Sets the weight of this field
	 *
	 * @param integer $weight the new weight of this field.
	 */
	public function setWeight($weight)
	{
		$this->weight = intval($weight);
	}

	// }}}
	// {{{ public function setIsPopular()

	/**
	 * Sets whether or not the keywords of this field are added to the popular
	 * keywords index
	 *
	 * @param boolean $is_popular
	 */
	public function setIsPopular($is_popular)
	{
		$this->is_popular = ($is_popular) ? true : false;
	}

	// }}}
	// {{{ public function getName()

	/**
	 * Gets the name of the document field
	 *
	 * @return string the name of the document field.
	 */
	public function getName()
	{
		return $this->name;
	}

	// }}}
	// {{{ public function getWeight()

	/**
	 * Gets the weight of this field
	 *
	 * @return integer the weight of this term.
	 */
	public function getWeight()
	{
		return $this->weight;
	}

	// }}}
	// {{{ public function isPopular()

	/**
	 * Gets whether or not the keywords in this field should be added to the
	 * popular keywords index
	 *
	 * @return boolean whether or not the keywords in this field should to be
	 *                 added to the popular keywords index.
	 */
	public function isPopular()
	{
		return $this->is_popular;
	}

	// }}}
}

?>
