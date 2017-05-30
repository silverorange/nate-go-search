NateGoSearch
============
NateGoSearch is a fulltext search engine written using MDB2 and PostgreSQL.

The following features are supported:

 - search indexing
 - query caching
 - fulltext search with keyword proximity weighting
 - stemming
 - spelling suggestions

Usage
-----
Before use, the search function and tables must be added to the PostgreSQL
database.

### Indexing
```php
<?php

$indexer = new NateGoSearchIndexer($document_type, $db);

// Index record objects that have 'id', 'title', and 'body' fields
foreach ($records as $record) {
  $document = new NateGoSearchDocument($record, 'id');

  $document->addField(new NateGoSearchField('title', 5));
  $document->addField(new NateGoSearchField('body', 1));

  $indexer->index($document);
}

$indexer->commit();

?>
```

### Searching
```php
<?php

$query = new NateGoSearchQuery($db);
$result = $query->query($search_keywords);
$id = $result->getUniqueId();

// The unique id can be used to select document ids from the NateGoSearchResult
// table in the database.

?>
```

Installation
------------
Make sure the silverorange composer repository is added to the `composer.json`
for the project and then run:

```sh
composer require silverorange/nate-go-search
```
