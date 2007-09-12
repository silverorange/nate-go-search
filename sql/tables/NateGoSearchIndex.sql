create table NateGoSearchIndex (
	document_id integer not null,
	document_type integer not null constraint NateGoSearchResult_document_type
		references NateGoSearchType(id) on delete cascade,

	word varchar(32) not null,
	weight integer not null,
	location integer not null
);

create index NateGoSearchIndex_document_id_index on NateGoSearchIndex(document_id);
create index NateGoSearchIndex_word_index on NateGoSearchIndex(word);
