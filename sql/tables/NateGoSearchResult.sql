create table NateGoSearchResult (
	document_id integer not null,
	document_type integer not null,
	displayorder1 float not null,
	displayorder2 float not null,
	unique_id varchar(50) not null,
	createdate timestamp not null
);
