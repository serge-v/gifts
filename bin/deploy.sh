pushd src
ncftpput -u voilokov -p `pass gifts` -m -y ftp.voilokov.com /public_html/gifts/ *.php *.css *.js *.awk
popd
