<?php

require_once 'NateGo/NateGoSearchTerm.php';
require_once 'NateGo/NateGoSearchDocument.php';

/**
 * 
 *
 * @package   NateGo
 * @copyright 2006 silverorange
 */
class NateGoSearchIndexer
{
	protected $terms = array();
	protected $unindexed_words = array();
	protected $max_word_length = 32;
	protected $tag;

	public function __construct($tag)
	{
		$this->tag = $tag;
	}

	public function setMaximumWordLength($length)
	{
		$htis->max_word_length = $length;
	}

	public function addTerm(NateGoSearchTerm $term)
	{
		$this->terms[] = $term;
	}

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
