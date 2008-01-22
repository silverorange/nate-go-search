create table NateGoSearchHistory (
	id serial,
	keywords varchar(255) not null,
	document_count integer not null default 0,
	creation_date timestamp not null default LOCALTIMESTAMP,
	primary key (id)
);

create index NateGoSearchHistory_keywords on
	NateGoSearchHistory(keywords);

create index NateGoSearchHistory_document_count on
	NateGoSearchHistory(document_count);
