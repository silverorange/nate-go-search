<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

/**
 * Represents a single searchable entity
 *
 * The default implementation should suffice for most appications but for
 * extremely custom document objects, this class may be extended.
 *
 * @package   NateGoSearch
 * @copyright 2006 silverorange
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
	 * The data object this doucment represents
	 *
	 * The data object may be the row result of a database query or may be
	 * constructed by hand as a standard object.
	 *
	 * @var mixed
	 */
	protected $data;

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
	// {{{ public function getField()

	/**
	 * Gets a field of this document by name
	 *
	 * @param string $name the name of the field to get.
	 *
	 * @return mixed the value of the field in this document with the given
	 *                name.
	 */
	public function getField($field_name)
	{
		if (!property_exists($this->data, $field_name)) {
			throw new InvalidArgumentException(sprintf(
				"Document object (%s) does not contain a field '%s'.",
				get_class($this->data), $field_name));
		}

		return $this->data->$field_name;
	}

	// }}}
}

?>
