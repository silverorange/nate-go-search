/**
 * Performs a fulltext search.
 * 
 * No results are returned by this procedure. The results are stored in a
 * separate table accessed by a unique id. The table is called
 * 'NateGoSearchResult' by default. This table does not get dropped after the
 * results are used. Subsequent searches will clear old search results from
 * the result table.
 *
 * The behaviour of this function is different from its MSSQL equivalent.
 *
 * Unlike older implementations of NateGoSearch, this PostgreSQL version does
 * not use execute statements to deal with contents of temporary tables. This
 * means only one query can be done per request. Any attempt to do multiple
 * queries will fail as a side effect of pl/pgsql caching execution plans in
 * functions. Not using execute statements results in considerable speed
 * improvements.
 *
 * To search multiple document types during the same request, perform a single
 * seach on all the tag types of the documents. Then inner join on the
 * NateGoSearchResult table based on document type and unique id for each
 * specific document-type search.
 *
 * @param_words varchar(255): A list of words to search for. Words are
 *                            delimited by space characters.
 * @param_document_types integer[]: This is an array of document types to be
 *                                  searched. Document types are decided when
 *                                  the search index is created. If the list of
 *                                  document types is null, all document types
 *                                  are searched.
 * @param_unique_id varchar(50): A unique id to identify the search results in
 *                               the search results table. This could be the
 *                               session id or some combination of time and a
 *                               hashing function.
 *
 * Returns NULL.
 */
create or replace function nateGoSearch (varchar(255), integer[], varchar(50)) RETURNS INT AS $$
	DECLARE
		param_keywords ALIAS FOR $1;
		param_document_types ALIAS FOR $2;
		param_unique_id ALIAS FOR $3;

		local_pos int;
		local_keywords varchar(255);
		local_word varchar(32);
		local_wordcount smallint;
	BEGIN

		local_keywords := param_keywords;
		local_pos := 1;
		local_wordcount := 0;

		--clear out old search results
		delete from NateGoSearchResult where createdate < (CURRENT_TIMESTAMP - interval '5 minutes');

		create temporary table TemporaryKeyword (
			document_id integer,
			document_type integer,
			word varchar(32),
			location integer,
			weight smallint
		);

		WHILE local_pos != 0 LOOP
			BEGIN
				local_pos := position(' ' in local_keywords);
				if local_pos = 0 then local_word := local_keywords;
				else local_word = substring(local_keywords from 0 for local_pos);
				end if;

				local_wordcount := local_wordcount + 1;
				local_keywords := substring(local_keywords
					from local_pos + 1 for (char_length(local_keywords) - local_pos));

				if param_document_types is null then
					-- search all document types
					insert into TemporaryKeyword (document_id, document_type, word, location, weight)
					select document_id, document_type, word, location, weight from NateGoSearchIndex
						where word = local_word;
				else
					-- search specific document types
					insert into TemporaryKeyword (document_id, document_type, word, location, weight)
					select document_id, document_type, word, location, weight from NateGoSearchIndex
						where word = local_word and document_type = any(param_document_types);
				end if;
			END;
		END LOOP;

		delete from TemporaryKeyword where document_id not in (
			select document_id from TemporaryKeyword
			group by document_id having count(distinct word) = local_wordcount);

		create temporary table TemporaryKeywordPair (
			document_id int,
			word1 varchar(32),
			word2 varchar(32),
			distance int
		);

		insert into TemporaryKeywordPair (document_id, word1, word2, distance)
		select a.document_id, a.word, b.word, min(abs(a.location - b.location))
			from TemporaryKeyword as a, TemporaryKeyword as b
			where a.word < b.word and a.document_id = b.document_id
			group by a.document_id, a.word, b.word;

		insert into NateGoSearchResult (document_id, document_type, displayorder1, displayorder2, unique_id, createdate)
		select TemporaryKeyword.document_id,
				TemporaryKeyword.document_type,
				coalesce(cast(avg(distance) as float) / cast(sum(weight) as float), 0) as displayorder1,
				1 / cast(sum(weight) as float) as displayorder2,
				param_unique_id,
				CURRENT_TIMESTAMP
			from TemporaryKeyword
				left outer join TemporaryKeywordPair
					on TemporaryKeyword.document_id = TemporaryKeywordPair.document_id
			group by TemporaryKeyword.document_id, TemporaryKeyword.document_type;

		drop table TemporaryKeywordPair;
		drop table TemporaryKeyword;

		return NULL;
	END;
$$ LANGUAGE 'plpgsql';
