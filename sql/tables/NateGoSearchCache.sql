create table NateGoSearchCache (
	unique_id varchar(50) not null,
	keywords_hash varchar(50) not null,
	createdate timestamp not null
);

create index NateGoSearchCache_unique_id_index on NateGoSearchCache(unique_id);
create index NateGoSearchCache_keywords_hash_index on NateGoSearchCache(keywords_hash);
