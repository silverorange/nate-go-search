create table NateGoSearchType (
	id serial,
	shortname varchar(50) not null,
	primary key(id)
);

create index NateGoSearchType_shortname_index on NateGoSearchType(shortname);
