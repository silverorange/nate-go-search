<?php

require_once 'NateGo/NateGoSearchTerm.php';
require_once 'NateGo/NateGoSearchDocument.php';

/**
 * Indexes documents using the NateGo search algorithm 
 *
 * @package   NateGo
 * @copyright 2006 silverorange
 */
class NateGoSearchIndexer
{
	/**
	 * A list of search terms to index documents by
	 *
	 * @var array NateGoSearchTerm
	 */
	protected $terms = array();

	/**
	 * An array of words to not index
	 *
	 * These words will be skipped by the indexer. Common examples of such
	 * words are: a, the, it
	 *
	 * @var array
	 */
	protected $unindexed_words = array();

	/**
	 * The maximum length of words that are indexed
	 *
	 * If the word length is set as null, there is no maximum word length. This
	 * is hte default behaviour. If a word is longer than the maximum length,
	 * it is truncated before being indexed.
	 *
	 * @var integer
	 */
	protected $max_word_length;

	/**
	 * The tag to index by
	 *
	 * Tags are a unique identifier for search indexes. NateGo search stores
	 * all indexed words in the same index with a tag to identify what index
	 * the word belongs to. Tags allow the possiblilty of mixed search results
	 * ordered by relavence. For example, if you seach for "roses" you could
	 * get product results, category results and article results all in
	 * the same list of search results.
	 *
	 * @var mixed 
	 */
	protected $tag;

	/**
	 * Creates a search index with the given tag
	 *
	 * @param mixed $tag the tag to index by.
	 * @param boolean $clear if true, this is a new search index and all
	 *                        indexed words for the given tag are removed. If
	 *                        false, we are appending to an existing index.
	 *                        Defaults to false.
	 *
	 * @see NateGoSearchIndexer::$tag
	 */
	public function __construct($tag, $clear = false)
	{
		$this->tag = $tag;
	}

	/**
	 * Sets the maximum length of words in the index
	 *
	 * @param integer $length the maximum length of words in the index.
	 *
	 * @see NateGoSearchIndexer::$max_word_length
	 */ 
	public function setMaximumWordLength($length)
	{
		$this->max_word_length = ($length === null) ? null : (integer)$length;
	}

	/**
	 * Adds a search term to this index
	 *
	 * Adding a term creates index entries for the words in the document
	 * matching the term. Index terms may have different weights.
	 *
	 * @param NateGoSearchTerm $term the term to add.
	 *
	 * @see NateGoSearchTerm
	 */
	public function addTerm(NateGoSearchTerm $term)
	{
		$this->terms[] = $term;
	}

	/**
	 * Indexes a document
	 *
	 * The document is indexed by all the terms of this indexer.
	 *
	 * @param NateGoSearchDocument $document the document to index.
	 *
	 * @see NateGoSearchDocument
	 */
	public function index(NateGoSearchDocument $document)
	{
		// word location counter
		$location = 0;

		foreach ($this->terms as $term) {
			$id = $document->getId();
			$text = $document->getField($term->getDataField());
			$text = $this->filterText($text);

			$tok = strtok($text, ' ');
			while ($tok !== false) {
				if (!in_array($tok, $this->unindexed_words)) {
					$location++;
					if (strlen($tok) > $this->max_word_length)
						$tok = substr($tok, 0, $this->max_word_length);
					
					echo $id.':'.$tok.':'.$term->getWeight().':'.
						$location.':'.$this->tag."\n";
				}
				$tok = strtok(' ');
			}
		}
	}

	/**
	 * Filters a string to prepare if for indexing
	 *
	 * This removes excell punctuation and markup and lowercases all words.
	 * The resulting string may then be tokenized by spaces.
	 *
	 * @param string $text the string to be filtered.
	 *
	 * @return string the filtered string.
	 */
	protected function filterText($text)
	{
		$text = strtolower($text);

		// replace html/xhtml/xml tags with spaces
		$text = preg_replace('@</?[^>]*>*@u', ' ', $text);

		// replace apostrophe s's
		$text = preg_replace('/\'s\b/u', '', $text);

		// remove punctuation at the beginning and end of the string 
		$text = preg_replace('/^\W+/u', '', $text);
		$text = preg_replace('/\W+$/u', '', $text);

		// remove punctuation at the beginning and end of words
		$text = preg_replace('/\s+\W+/u', ' ', $text);
		$text = preg_replace('/\W+\s+/u', ' ', $text);

		// replace multiple dashes with a single dash
		$text = preg_replace('/-+/u', '-', $text);

		// replace whitespace with single spaces
		$text = preg_replace('/\s+/u', ' ', $text);

		return $text;
	}
}

?>
