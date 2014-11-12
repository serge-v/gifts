[[ ! -d prod~ ]] && mkdir prod~
ncftpget -u voilokov -p `pass gifts` ftp.voilokov.com prod~/ '/public_html/gifts/trunk/*.*'
diff -q src/ prod~/
