mysqldump voilokov_gifts -h voilokov.com --no-data -u voilokov_dbadmin --password=`cat dbadmin.txt` > db_schema.txt
mysqldump voilokov_gifts -h voilokov.com -u voilokov_dbadmin --password=`cat dbadmin.txt` > db_data.sql
