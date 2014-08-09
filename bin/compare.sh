[[ ! -d prod~ ]] && mkdir prod~
ncftpget -u voilokov -p `pass gifts` ftp.voilokov.com prod~/ '/public_html/gifts/src/*.*'
diff -q src/ prod/
