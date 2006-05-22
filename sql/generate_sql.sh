#!/bin/sh

SRC="/so/packages/nate-go-search/work-dave/sql"
DST="${SRC}/statements.sql"

#
# TABLES
#

cat ${SRC}/tables/NateGoSearchIndex.sql > $DST
cat ${SRC}/tables/NateGoSearchQueue.sql >> $DST
cat ${SRC}/tables/NateGoSearchResult.sql >> $DST

#
# FUNCTIONS
#

cat ${SRC}/functions/nateGoSearch.sql >> $DST

