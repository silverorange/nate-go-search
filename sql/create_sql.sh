#!/bin/sh

WHOAMI=`whoami`
WD="/so/packages/nate-go-search/work-${WHOAMI}/sql"
DST="${WD}/statements.sql"

if [ -z $1 ]; then
	echo "need destination db name"
	exit 0
else
	DB=$1
fi

# Create an empty site.
echo "" > $DST

cat ${WD}/tables/tables.txt | while read LINE; do
	cat ${WD}/tables/${LINE} >> $DST
done

cat ${WD}/functions/functions.txt | while read LINE; do
	cat ${WD}/functions/${LINE} >> $DST
done

echo "Creating the generic search tables"
psql -U php -f $DST $DB
rm $DST

