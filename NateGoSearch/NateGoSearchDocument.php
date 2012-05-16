<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'NateGoSearch/NateGoSearchField.php';

/**
 * Represents a single searchable entity
 *
 * The default implementation should suffice for most appications but for
 * extremely custom document objects, this class may be extended.
 *
 * @package   NateGoSearch
 * @copyright 2006-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class NateGoSearchDocument
{
	// {{{ protected properties

	/**
	 * The name of the data field that contains the document identifier
	 *
	 * @var string
	 */
	protected $id_field;

	/**
	 * The data object this document represents
	 *
	 * The data object may be the row result of a database query or may be
	 * constructed by hand as a standard object.
	 *
	 * @var mixed
	 */
	protected $data;

	/**
	 * The fields of this document to index
	 *
	 * @var array
	 *
	 * @see NateGoSearchDocument::addField()
	 * @see NateGoSearchDocument::getFields()
	 */
	protected $fields = array();

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new search document
	 *
	 * @param mixed $data the data object this document represents.
	 * @param string $id_field the name of the id field of this document. This
	 *                          should be the name of a property of the data
	 *                          object.
	 */
	public function __construct($data, $id_field)
	{
		$this->data = $data;
		$this->id_field = $id_field;
	}

	// }}}
	// {{{ public function getId()

	/**
	 * Gets the identifier of this document
	 *
	 * @return mixed the identifier of this document.
	 *
	 * @see NateGoSearchDocument::$id_field
	 */
	public function getId()
	{
		return $this->getField($this->id_field);
	}

	// }}}
	// {{{ public function addField()

	/**
	 * Adds a field to be indexed to this document
	 *
	 * @param NateGoSearchField $field the field to add.
	 */
	public function addField(NateGoSearchField $field)
	{
		$this->fields[$field->getName()] = $field;
	}

	// }}}
	// {{{ public function getField()

	/**
	 * Gets a field of this document by name
	 *
	 * This method is deprecated and will be removed in a later release.
	 *
	 * @param string $name the name of the field to get.
	 *
	 * @return mixed the value of the field in this document with the given
	 *                name.
	 *
	 * @deprecated Use {@link NateGoSearchDocument::getFieldValue()} instead.
	 */
	public function getField($name)
	{
		return $this->getFieldValue($name);
	}

	// }}}
	// {{{ public function getFieldValue()

	/**
	 * Gets a field value of this document by name
	 *
	 * @param string $name the name of the field value to get.
	 *
	 * @return mixed the value of the field in this document with the given
	 *                name.
	 */
	public function getFieldValue($name)
	{
		return $this->data->$name;
	}

	// }}}
	// {{{ public function getFields()

	/**
	 * Gets the defined fields of this document, indexed by field name
	 *
	 * @return array the defined fields of this document, indexed by field
	 *               name.
	 */
	public function getFields()
	{
		return $this->fields;
	}

	// }}}
}

?>
