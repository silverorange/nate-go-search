<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

/**
 * Static methods for managing document types using NateGoSearch
 *
 * Document types are a unique identifier for search indexes. NateGoSearch
 * stores all indexed words in the same index with a document type to identify
 * what index the word belongs to.
 *
 * Document types allow mixed search results from a single query ordered by
 * relavence. For example, if you seach for <em>roses</em>, you could get
 * <em>product</em> results, <em>category</em> results and <em>article</em>
 * results all in the same list of search results.
 *
 * Document types must be created before being used. Create a document type
 * with the {@link NateGoSearch::createDocumentType()} method. Document types
 * only need to be created once when setting up a new website.
 *
 * @package   NateGoSearch
 * @copyright 2007-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class NateGoSearch
{
	// {{{ public static function createDocumentType()

	/**
	 * Creates a new document type
	 *
	 * If the document type already exists, it is not recreated and no warnings
	 * are raised.
	 *
	 * @param MDB2_Driver_Common $db the database driver to use when creating
	 *                                the new document type.
	 * @param string $type_shortname the shortname of the new document type.
	 *                                Document types are managed and manipulated
	 *                                using symbolic shortnames. Examples of
	 *                                shortnames are: <em>products</em>,
	 *                                <em>articles</em> and <em>categories</em>.
	 *
	 * @return integer the document type identifier of the new type.
	 *
	 * @throws NateGoSearchDBException if a database error occurs.
	 */
	public static function createDocumentType(
		MDB2_Driver_Common $db,
		$type_shortname
	) {
		$type = self::getDocumentType($db, $type_shortname);

		if ($type === null) {
			$type_shortname = (string)$type_shortname;
			$sql = sprintf(
				'insert into NateGoSearchType (shortname) values (%s)',
				$db->quote($type_shortname, 'text'));

			$result = $db->exec($sql);
			if (MDB2::isError($result))
				throw new NateGoSearchDBException($result);

			$type = $db->lastInsertID('NateGoSearchType');
			if (MDB2::isError($type))
				throw new NateGoSearchDBException($result);
		}

		return $type;
	}

	// }}}
	// {{{ public static function getDocumentType()

	/**
	 * Gets the identifier of a document type by the type's shortname
	 *
	 * @param MDB2_Driver_Common $db the database driver to use to get the
	 *                                document type identifier.
	 * @param string $type_shortname the shortname of the document type.
	 *
	 * @return integer the document type identifier of the type or null if no
	 *                  such type exists.
	 *
	 * @throws NateGoSearchDBException if a database error occurs.
	 */
	public static function getDocumentType(
		MDB2_Driver_Common $db,
		$type_shortname
	) {
		$type_shortname = (string)$type_shortname;

		$sql = sprintf('select id from NateGoSearchType
			where shortname = %s',
			$db->quote($type_shortname, 'text'));

		$type = $db->queryOne($sql);

		if (MDB2::isError($type))
			throw new NateGoSearchDBException($type);

		return $type;
	}

	// }}}
	// {{{ public static function removeDocumentType()

	/**
	 * Removes a document type
	 *
	 * After a document type is removed, documents of that type can no longer
	 * be indexed or queried.
	 *
	 * @param MDB2_Driver_Common $db the database driver to use to remove the
	 *                                document type identifier.
	 * @param string $type_shortname the shortname of the document type to
	 *                                remove.
	 *
	 * @throws NateGoSearchDBException if a database error occurs.
	 */
	public static function removeDocumentType(
		MDB2_Driver_Common $db,
		$type_shortname
	) {
		$type_shortname = (string)$type_shortname;

		$sql = sprintf('delete from NateGoSearchType where shortname = %s',
			$db->quote($type_shortname, 'text'));

		$result = $db->exec($sql);
		if (MDB2::isError($result))
			throw new NateGoSearchDBException($result);
	}

	// }}}
	// {{{ public static function getDocumentTypes()

	/**
	 * Gets the available document type shortnames
	 *
	 * @param MDB2_Driver_Common $db the database driver to use to get the
	 *                                available document type shortnames.
	 *
	 * @return array an array containing the available document type shortnames.
	 *
	 * @throws NateGoSearchDBException if a database error occurs.
	 */
	public static function getDocumentTypes(MDB2_Driver_Common $db)
	{
		$sql = 'select shortname from NateGoSearchType';
		$values = $db->queryCol($sql, 'text');
		if (MDB2::isError($values))
			throw new NateGoSearchDBException($values);

		return $values;
	}

	// }}}
	// {{{ private function __construct()

	/**
	 * Don't allow instantiation of the NateGoSearch object
	 *
	 * This class contains only static methods and should not be instantiated.
	 */
	private function __construct()
	{
	}

	// }}}
}

?>
