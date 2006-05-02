#!/bin/sh

SRC="/so/packages/nategosearch/work-dave/sql"

if [ -z $1 ]; then
    echo "need destination db name"
    exit 0
else
    DB=$1
fi

clear
echo "Database: $1"
echo
echo

${SRC}/generate_sql.sh

psql -U php -f ${SRC}/statements.sql $DB
rm ${SRC}/statements.sql

