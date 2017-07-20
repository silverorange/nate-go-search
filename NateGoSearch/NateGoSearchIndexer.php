<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'MDB2.php';
require_once 'NateGoSearch.php';
require_once 'NateGoSearch/NateGoSearchTerm.php';
require_once 'NateGoSearch/NateGoSearchDocument.php';
require_once 'NateGoSearch/NateGoSearchKeyword.php';
require_once 'NateGoSearch/NateGoSearchSpellChecker.php';
require_once 'NateGoSearch/exceptions/NateGoSearchDBException.php';
require_once 'NateGoSearch/exceptions/NateGoSearchDocumentTypeException.php';

/**
 * Indexes documents using the NateGo search algorithm
 *
 * If the PECL <i>stem</i> package is loaded, English stemming is applied to all
 * indexed keywords. See {@link http://pecl.php.net/package/stem/} for details
 * about the PECL stem package. Support for stemming in other languages may
 * be added in later releases of NateGoSearch.
 *
 * Otherwise, if a PorterStemmer class is defined, it is applied to all indexed
 * keywords. The most commonly available PHP implementation of the
 * Porter-stemmer algorithm is licenced under the GPL, and is thus not
 * distributable with the LGPL licensed NateGoSearch.
 *
 * @package   NateGoSearch
 * @copyright 2006-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class NateGoSearchIndexer
{
	// {{{ protected properties

	/**
	 * A list of search terms to index documents by
	 *
	 * This is an array of {@link NateGoSearchTerm} objects.
	 *
	 * @var array
	 *
	 * @depracated use the field API on NateGoSearchDocument instead.
	 */
	protected $terms = array();

	/**
	 * An array of words to not index
	 *
	 * These words will be skipped by this indexer. Common examples of such
	 * words are: a, the, it
	 *
	 * @var array
	 */
	protected $unindexed_words = array();

	/**
	 * The maximum length of words that are indexed
	 *
	 * If the word length is set as null, there is no maximum word length. If a
	 * word is longer than the maximum length, it is truncated before being
	 * indexed. The default maximum word length is 32 characters.
	 *
	 * @var integer
	 *
	 * @see NateGoSearchIndexer::setMaximumWordLength()
	 */
	protected $max_word_length = 32;

	/**
	 * An array of keywords collected from the current index operation
	 *
	 * @var array NateGoSearchKeyword
	 */
	protected $keywords = array();

	/**
	 * An array of popular keywords to be added to the popular keywords table
	 *
	 * @var array
	 */
	protected $popular_keywords = array();

	/**
	 * A list of document ids we are indexing in the current operation
	 *
	 * When commit is called, indexed entries for these ids are removed from
	 * the index. The reason is because we are reindexing these documents.
	 *
	 * @var array
	 */
	protected $clear_document_ids = array();

	/**
	 * The document type to index by
	 *
	 * Document types are a unique identifier for search indexes. NateGoSearch
	 * stores all indexed words in the same index with a document type to
	 * identify what index the word belongs to. Document types allow the
	 * possiblilty of mixed search results ordered by relavence. For example,
	 * if you seach for <em>roses</em> you could get product results, category
	 * results and article results all in the same list of search results.
	 *
	 * @var mixed
	 */
	protected $document_type;

	/**
	 * The database connection used by this indexer
	 *
	 * @var MDB2_Driver_Common
	 */
	protected $db;

	/**
	 * Whether or not the old index is cleared when changes to the index are
	 * committed
	 *
	 * @var boolean
	 *
	 * @see NateGoSearchIndexer::__construct()
	 * @see NateGoSearchIndexer::commit()
	 * @see NateGoSearchIndexer::clear()
	 */
	protected $new = false;

	/**
	 * Whether or not keywords for indexed documents are appended to existing
	 * keywords
	 *
	 * @var boolean
	 *
	 * @see NateGoSearchIndexer::__construct()
	 * @see NateGoSearchIndexer::commit()
	 */
	protected $append = false;

	/**
	 * The spell checker for this indexer
	 *
	 * @var NateGoSearchSpellChecker
	 */
	protected $spell_checker;

	/**
	 * The words in the personal wordlist
	 *
	 * An array to hold every word that is added to the personal wordlist
	 *
	 * @var array
	 */
	protected $personal_wordlist = array();

	// }}}
	// {{{ private static properties

	/**
	 * Whether or not mb_string overloading is turned on for the strlen
	 * function
	 *
	 * This value is calculated and cached when the first indexer object is
	 * created.
	 *
	 * @var boolean
	 *
	 * @see NateGoSearchIndexer::getByteLength()
	 */
	private static $use_mb_string = null;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a search indexer with the given document type
	 *
	 * @param string $document_type the shortname of the document type to
	 *                               index by.
	 * @param MDB2_Driver_Common $db the database connection used by this
	 *                                indexer.
	 * @param boolean $new if true, this is a new search index and all indexed
	 *                      words for the given document type are removed. If
	 *                      false, we are appending to an existing index.
	 *                      Defaults to false.
	 * @param boolean $append if true, keywords keywords for documents that
	 *                         are indexed are appended to the keywords that
	 *                         may already exist for the document in the index.
	 *                         Defaults to false.
	 *
	 * @see NateGoSearch::createDocumentType()
	 *
	 * @throws NateGoSearchDocumentTypeException if the document type shortname
	 *                                           does not exist.
	 */
	public function __construct($document_type, MDB2_Driver_Common $db,
		$new = false, $append = false)
	{
		// cache mb_string overloading status
		if (self::$use_mb_string === null) {
			self::$use_mb_string = (extension_loaded('mbstring') &&
				(ini_get('mbstring.func_overload') & 2) === 2);
		}

		$type = NateGoSearch::getDocumentType($db, $document_type);

		if ($type === null) {
			throw new NateGoSearchDocumentTypeException(
				"Document type {$document_type} does not exist and cannot be ".
				"indexed. Document types must be created before being used.",
				0, $document_type);
		}

		$this->document_type = $type;
		$this->db = $db;
		$this->new = $new;
		$this->append = $append;
	}

	// }}}
	// {{{ public function setMaximumWordLength()

	/**
	 * Sets the maximum length of words in the index
	 *
	 * If the word length is set as null, there is no maximum word length. If a
	 * word is longer than the maximum length, it is truncated before being
	 * indexed. The default maximum word length is 32 characters.
	 *
	 * @param integer $length the maximum length of words in the index.
	 *
	 * @see NateGoSearchIndexer::$max_word_length
	 */
	public function setMaximumWordLength($length)
	{
		$this->max_word_length = ($length === null) ? null : (integer)$length;
	}

	// }}}
	// {{{ public function addTerm()

	/**
	 * Adds a search term to this index
	 *
	 * This method is deprecated. Use the 'fields' API on
	 * {@link NateGoSearchDocument} instead.
	 *
	 * Adding a term creates index entries for the words in the document
	 * matching the term. Index terms may have different weights.
	 *
	 * @param NateGoSearchTerm $term the term to add.
	 *
	 * @see NateGoSearchTerm
	 *
	 * @depracated use the {@link NateGoSearchDocument::addField()} method
	 *             instead.
	 */
	public function addTerm(NateGoSearchTerm $term)
	{
		$this->terms[] = $term;
	}

	// }}}
	// {{{ public function index()

	/**
	 * Indexes a document
	 *
	 * The document is indexed for all of its fields by this indexer.
	 *
	 * @param NateGoSearchDocument $document the document to index.
	 *
	 * @see NateGoSearchDocument
	 */
	public function index(NateGoSearchDocument $document)
	{
		// word location counter
		$location = 0;

		$id = $document->getId();
		if (!$this->append && !in_array($id, $this->clear_document_ids))
			$this->clear_document_ids[] = $id;

		// backwards compatibility with the deprecated 'terms' API
		foreach ($this->terms as $term) {
			$document->addField(
				new NateGoSearchField(
					$term->getDataField(),
					$term->getWeight(),
					$term->isPopular()
				)
			);
		}

		$field_id = 0;
		foreach ($document->getFields() as $field) {

			$text = $document->getFieldValue($field->getName());
			$word_list = $this->normalizeKeywords($text);

			foreach ($word_list as $word) {
				$keyword = self::stemKeyword($word['word']);

				if (ctype_space($keyword) || $keyword == '')
					continue;

				if (!in_array($keyword, $this->unindexed_words)) {
					$location += $word['proximity'];

					if ($this->max_word_length !== null &&
						strlen($keyword) > $this->max_word_length) {
						$keyword = substr($keyword, 0, $this->max_word_length);
					}

					$this->keywords[] = new NateGoSearchKeyword(
						$keyword,
						$id,
						$field->getWeight(),
						$location,
						$this->document_type,
						$field_id
					);
				}

				// add any words that the spell checker would flag
				// to the spell checker's personal wordlist
				if ($this->spell_checker instanceof NateGoSearchSpellChecker) {
					$speller = $this->spell_checker;
					$corrected = $speller->getProperSpelling($word['word']);

					// if it is missspelled and not already in the wordlist
					// and is a considered a 'word', add it to the wordlist
					if (($corrected != $word['word']) &&
						!in_array($word['word'], $this->personal_wordlist) &&
						ctype_alpha($word['word'])) {

						$this->personal_wordlist[] = $word['word'];
						$speller->addToPersonalWordlist($word['word']);
					}
				}

				// add any popular keywords to the popular keywords list
				if ($field->isPopular()
					&& !in_array($word['word'], $this->unindexed_words)
					&& !is_numeric($word['word'])) {
					$this->popular_keywords[] = $word['word'];
				}
			}

			$field_id++;
		}
	}

	// }}}
	// {{{ public function commit()

	/**
	 * Commits keywords indexed by this indexer to the database index table
	 *
	 * If this indexer was created with the <code>$new</code> parameter then
	 * the index is cleared for this indexer's document type before new
	 * keywords are inserted. Otherwise, the new keywords are simply appended
	 * to the existing index.
	 */
	public function commit()
	{
		try {
			$this->db->beginTransaction();

			if ($this->new) {
				$this->clear();
				$this->new = false;
			}

			$indexed_ids =
				$this->db->implodeArray($this->clear_document_ids, 'integer');

			$delete_sql = sprintf('delete from NateGoSearchIndex
				where document_id in (%s) and document_type = %s',
				$indexed_ids,
				$this->db->quote($this->document_type, 'integer'));

			$result = $this->db->exec($delete_sql);
			if (MDB2::isError($result))
				throw new NateGoSearchDBException($result);

			$keyword = array_pop($this->keywords);
			while ($keyword !== null) {
				$sql = sprintf('insert into NateGoSearchIndex (
						document_id,
						document_type,
						field_id,
						word,
						weight,
						location
					) values (%s, %s, %s, %s, %s, %s)',
					$this->db->quote($keyword->getDocumentId(), 'integer'),
					$this->db->quote($keyword->getDocumentType(), 'integer'),
					$this->db->quote($keyword->getTermId(), 'integer'),
					$this->db->quote($keyword->getWord(), 'text'),
					$this->db->quote($keyword->getWeight(), 'integer'),
					$this->db->quote($keyword->getLocation(), 'integer'));

				$result = $this->db->exec($sql);
				if (MDB2::isError($result))
					throw new NateGoSearchDBException($result);

				unset($keyword);

				$keyword = array_pop($this->keywords);
			}

			$popular_keyword = array_pop($this->popular_keywords);
			while ($popular_keyword !== null) {
				// TODO: there must be a better way to handle dupe words...
				$sql = sprintf(
					'select count(keyword) from NateGoSearchPopularKeywords
					where keyword = %s',
					$this->db->quote($popular_keyword, 'text'));

				$exists = $this->db->queryOne($sql);
				if (MDB2::isError($result))
					throw new NateGoSearchDBException($result);

				if (!$exists) {
					$sql = sprintf('insert into NateGoSearchPopularKeywords
						(keyword) values (%s)',
						$this->db->quote($popular_keyword, 'text'));

					$result = $this->db->exec($sql);
					if (MDB2::isError($result))
						throw new NateGoSearchDBException($result);
				}

				unset($popular_keyword);

				$popular_keyword = array_pop($this->popular_keywords);
			}

			$this->clear_document_ids = array();

			$this->db->commit();
		} catch (NateGoSearchDBException $e) {
			$this->db->rollback();
			throw $e;
		}
	}

	// }}}
	// {{{ public function addUnindexedWords()

	/**
	 * Adds words to the list of words that are not to be indexed
	 *
	 * These may be words such as 'the', 'and' and 'a'.
	 *
	 * @param string|array $words the list of words not to be indexed.
	 */
	public function addUnindexedWords($words)
	{
		if (!is_array($words))
			$words = array((string)$words);

		$this->unindexed_words = array_merge($this->unindexed_words, $words);
	}

	// }}}
	// {{{ public function setSpellChecker()

	/**
	 * Set the spell checker to be used by this indexer
	 *
	 * This is used to build a custom word list from detected misspellings in
	 * indexed words.
	 *
	 * @param NateGoSearchSpellChecker
	 */
	public function setSpellChecker(NateGoSearchSpellChecker $checker)
	{
		$this->spell_checker = $checker;
	}

	// }}}
	// {{{ public function __destruct()

	/**
	 * Object destructor calls commit() automatically
	 *
	 * @see NateGoSearchIndexer::commit()
	 */
	public function __destruct()
	{
		$this->commit();
	}

	// }}}
	// {{{ public static function stemKeyword()

	/**
	 * Stems a keyword
	 *
	 * The basic idea behind stemmming is described on the Wikipedia article on
	 * {@link http://en.wikipedia.org/wiki/Stemming Stemming}.
	 *
	 * If the PECL <code>stem</code> package is loaded, English stemming is
	 * performed on the <code>$keyword</code>. See
	 * {@link http://pecl.php.net/package/stem/} for details about the PECL
	 * stem package.
	 *
	 * Otherwise, if a <code>PorterStemmer</code< class is defined, it is
	 * applied to the <code>$keyword</code>. The most commonly available PHP
	 * implementation of the Porter-stemmer algorithm is licenced under the
	 * GPL, and is thus not distributable with the LGPL licensed NateGoSearch.
	 *
	 * If no stemming is available, stemming is not performed and the original
	 * keyword is returned.
	 *
	 * @param string $keyword the keyword to stem.
	 *
	 * @return string the stemmed keyword.
	 */
	public static function stemKeyword($keyword)
	{
		if (extension_loaded('stem'))
			$keyword = stem($keyword, STEM_ENGLISH);
		elseif (is_callable(array('PorterStemmer', 'Stem')))
			$keyword = PorterStemmer::Stem($keyword);

		return $keyword;
	}

	// }}}
	// {{{ public static function &getDefaultUnindexedWords()

	/**
	 * Gets a default list of words that are not indexed by a search indexer
	 *
	 * These words may be passed directly to the
	 * {@link NateGoSearchIndexer::addUnindexedWords()} method of an
	 * instantiated indexer.
	 *
	 * @return array a default list of words not to index.
	 */
	public static function &getDefaultUnindexedWords()
	{
		static $words = array();

		if (count($words) == 0) {
			if (substr('@DATA-DIR@', 0, 1) === '@')
				$filename = dirname(__FILE__).'/../system/blocked-words.txt';
			else
				$filename = '@DATA-DIR@/NateGoSearch/system/blocked-words.txt';

			$words = file($filename, true);
			// remove line breaks
			$words = array_map('rtrim', $words);
		}

		return $words;
	}

	// }}}
	// {{{ protected function clear()

	/**
	 * Clears this search index
	 *
	 * The index is cleared for this indexer's document type
	 *
	 * @see NateGoSearchIndexer::__construct()
	 *
	 * @throws NateGoSearchDBException if a database error occurs.
	 */
	protected function clear()
	{
		$sql = sprintf('delete from NateGoSearchIndex where document_type = %s',
			$this->db->quote($this->document_type, 'integer'));

		$result = $this->db->exec($sql);
		if (MDB2::isError($result))
			throw new NateGoSearchDBException($result);
	}

	// }}}
	// {{{ protected function normalizeKeywords()

	/**
	 * Normalizes a string to prepare it for indexing
	 *
	 * Normalization involves:
	 *
	 *  1. removing excess punctuation and markup, and
	 *  2. lowercasing all words.
	 *
	 * @param string  $text           the string to be normalized.
	 * @param integer $end_weight     the word proximity weighting relative to
	 *                                a single space to use for end-of-sentence
									  punctuation.
	 * @param integer $tab_weight     the word proximity weighting relative to
	 *                                a single space to use or tabs.
	 * @param integer $newline_weight the word proximity weighting relative to
	 *                                a single space to use for newlines.
	 * @param integer $mid_weight     the word proximity weighting relative to
	 *                                a single space to use or mid-sentence
	 *                                punctuation.
	 *
	 * @return array an array in the form ('word'      => $word,
	 *                                     'proximity' => $proximity)
	 */
	protected function normalizeKeywords($text,
										 $end_weight = 5,
										 $tab_weight = 5,
										 $newline_weight = 5,
										 $mid_weight = 2)
	{
		// get proximity weight strings
		$end_weight     = str_repeat(' ', max(intval($end_weight), 1));
		$tab_weight     = str_repeat(' ', max(intval($tab_weight), 1));
		$newline_weight = str_repeat(' ', max(intval($newline_weight), 1));
		$mid_weight     = str_repeat(' ', max(intval($mid_weight), 1));

		// lowercase
		$text = strtolower($text);

		// replace windows and mac style newlines with unix style newlines
		$text = preg_replace('/\r\n/u', '\n', $text);
		$text = preg_replace('/\r/u', '\n', $text);

		// replace html/xhtml/xml tags with spaces
		$text = preg_replace('/<\/?[^>]*>*/u', ' ', $text);

		// convert entities to UTF-8 equivalents
		$text = html_entity_decode($text, ENT_COMPAT, 'UTF-8');

		// remove apostrophe s's
		$text = preg_replace('/\'s\b/u', '', $text);

		// remove punctuation at the beginning and end of the string
		 $text = preg_replace('/^\W+/u', '', $text);
		$text = preg_replace('/\W+$/u', '', $text);

		// remove any odd (non-recognized punctuation) characters from the
		// string
		$text = preg_replace('/[^\s\w?.!;:,\/d-]/u', '', $text);

		// collapse spaces. Note: this needs to be done before proximity
		// weighting is done.
		$text = preg_replace('/ +/u', ' ', $text);

		// end-of-sentence punctuation (.?!)
		$text = preg_replace('/[!\.\?]+\s+/u', $end_weight, $text);

		// tabs
		$text = preg_replace('/\t+/u', $tab_weight, $text);

		// newlines (cr and lf)
		$text = preg_replace('/[\r\n]+/su', $newline_weight, $text);

		// mid sentence punctuation (;:,-). Note: this should not remove
		// hyphens from hyphenated words.
		$text = preg_replace('/(\s+[;:,-]+\s+|[;:,-]+\s+|\s+[;:,-]+)/u',
							 $mid_weight,
							 $text);

		// replace multiple dashes with a single dash
		$text = preg_replace('/-+/u', '-', $text);

		// create an array with the words and their offset (in bytes) in the
		// orginal string
		$text = preg_split('/ +/u', $text, -1, PREG_SPLIT_OFFSET_CAPTURE);

		// now create the array('word' => $word, 'proximity' => $proximity)
		$word_list = $this->createWordProximityList($text);

		return $word_list;
	}

	// }}}
	// {{{ protected function createWordProximityList()

	/**
	 * Creates the word/proximity wordlist
	 *
	 * Creates a list containing an array for each word in the specified
	 * <code>$text</code>. The array is in the form:
	 *
	 * <code>
	 * array(
	 *     'word'      => $word,
	 *     'proximity' => $proximity
	 * );
	 * </code>
	 *
	 * @param string $text the text to be converted into the list.
	 *
	 * @return array the word/proximity word list.
	 */
	protected function createWordProximityList($text)
	{
		$word_list = array();

		// default some values so the first word has a proximity of 0
		$old_proximity = 0;
		$old_word = '';

		foreach ($text as $word) {
			$new_word = $word[0];

			// we need the string length in bytes because the proximity offsets
			// are in bytes as provided by preg_split()
			$proximity =
				$word[1] - ($old_proximity + self::getByteLength($old_word));

			$word_list[] =
				array('word' => $new_word, 'proximity' => $proximity);

			$old_proximity = $word[1];
			$old_word = $new_word;
		}

		return $word_list;
	}

	// }}}
	// {{{ protected static function getByteLength()

	protected static function getByteLength($string)
	{
		if (self::$use_mb_string) {
			return mb_strlen($string, '8bit');
		}

		return strlen($string);
	}

	// }}}
}

?>
