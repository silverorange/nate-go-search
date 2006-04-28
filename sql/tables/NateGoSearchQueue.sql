create table NateGoSearchQueue (
	document_id integer,
	document_type integer
);

create index NateGoSearchQueue_document_id_index on NateGoSearchQueue(document_id);
create index NateGoSearchQueue_document_type_index on NateGoSearchQueue(document_type);
