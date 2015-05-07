export PATH=/opt/mysql/bin:$PATH

mysqldump voilokov_gifts -h voilokov.com --no-data -u voilokov_dbadmin --password=`pass gifts-db` > db_schema.txt
mysqldump voilokov_gifts -h voilokov.com -u voilokov_dbadmin --password=`pass gifts-db` > db_data.sql
