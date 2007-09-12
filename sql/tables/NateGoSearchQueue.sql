create table NateGoSearchQueue (
	document_id integer,
	document_type integer not null constraint NateGoSearchResult_document_type
		references NateGoSearchType(id),
);

create index NateGoSearchQueue_document_id_index on NateGoSearchQueue(document_id);
