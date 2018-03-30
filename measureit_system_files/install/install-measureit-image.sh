#!/bin/sh

# This is a dirty script to help me build a measureit image
# into a fresh raspian without much effort. It does not help you when you have problems with measureit!
# It is quick and dirty. Use it on your own risk. 
# If you run this script on a always configured system it will damage it. I promise ;)

## Usage: Log in the new raspian image. 
## Became root with 'sudo bash'
## Install git with 'apt-get install -y git'
## Clone measureit from github
# git clone https://github.com/lalelunet/measureit.git
# cd measureit/measureit_system_files/install
# run this script './install-measureit-image.sh'

printf "\n >> update system >>\n#########################\n"
apt-get -y upgrade
printf "\n >> done >>\n\n"

printf "\n>> install software >>\n######################\n"
apt-get update && apt-get install -y vim daemontools-run php7.0-fpm php7.0-mysql nginx samba samba-common-bin python-mysqldb python-serial python-setuptools
printf "\n >> done >>\n\n"

printf "\n >> install python packages >>\n#########################\n"
easy_install twython requests requests_oauthlib
printf "\n >> done >>\n\n"

printf "\n >> enable ipv6 >>\n#########################\n"
echo "ipv6" >> /etc/modules
printf "\n >> done >>\n\n"

printf "\n >> Set static ip adresses >>\n#########################\n"
cat config-static-network >> /etc/network/interfaces
systemctl daemon-reload
systemctl restart networking
printf "\n >> done >>\n\n"

printf "\n >> create supervise directories >>\n#########################\n"
mkdir /etc/servers
ln -s /etc/service /service
printf "\n >> done >>\n\n"

printf "\n >> create directories for php change the default config and start php-fpm service  >>\n#########################\n"
mkdir /web
useradd -d /web -s /bin/bash web
chown -R web:web /web
sed -i -e 's/;daemonize = yes/daemonize = no/g' /etc/php/7.0/fpm/php-fpm.conf
sed -i -e 's/www-data/web/g' /etc/php/7.0/fpm/pool.d/www.conf
printf "\n >> done >>\n\n"

# dear systemd. these services are controlled by supervise
printf "\n >> remove php control from systemd and start php server with supervise >>\n#########################\n"
systemctl stop php7.0-fpm && systemctl mask php7.0-fpm && systemctl daemon-reload && rm /etc/init.d/php7.0-fpm
mkdir /etc/servers/php7-fpm
cat svc-run-php > /etc/servers/php7-fpm/run
chmod +x /etc/servers/php7-fpm/run
ln -s /etc/servers/php7-fpm /etc/service/php7-fpm
printf "\n >> done >>\n\n"

printf "\n >> install mariadb >>\n#########################\n"
echo mariadb-server-10.1 mysql-server/root_password password 'raspberry' | debconf-set-selections
echo mariadb-server-10.1 mysql-server/root_password_again password 'raspberry' | debconf-set-selections
apt-get install -y mariadb-server
printf "\n >> done >>\n\n"

# dear systemd. these services are controlled by supervise
printf "\n >> remove mariadb control from systemd and start mariadb server with supervise >>\n#########################\n";
systemctl stop mysql && systemctl mask mysql && systemctl daemon-reload
printf "\n >> done >>\n\n"

printf "\n >> create directories for mariadb and start the mariadb service  >>\n#########################\n"
mkdir /etc/servers/mariadb
cat svc-run-mariadb > /etc/servers/mariadb/run;
chmod +x /etc/servers/mariadb/run
ln -s /etc/servers/mariadb /etc/service/mariadb
printf "\n >> done >>\n\n"

printf "\n >> create directories for nginx and start the nginx service  >>\n#########################\n"
sed -i -e 's/www-data/web/g' /etc/nginx/nginx.conf
echo "daemon off;" >> /etc/nginx/nginx.conf
cat config-nginx > /etc/nginx/sites-enabled/default
printf "\n >> done >>\n\n"

# dear systemd. these services are controlled by supervise
printf "\n >> remove nginx control from systemd and start nginx server with supervise >>\n#########################\n";
systemctl stop nginx && systemctl mask nginx && systemctl daemon-reload && rm /etc/init.d/nginx

mkdir /etc/servers/nginx
cat svc-run-nginx > /etc/servers/nginx/run
chmod +x /etc/servers/nginx/run
ln -s /etc/servers/nginx /etc/service/nginx
printf "\n >> done >>\n\n"

printf "\n >> configure and start the samba service  >>\n#########################\n"
(echo raspberry; echo raspberry) | smbpasswd -a -s web
cat config-samba >> /etc/samba/smb.conf
printf "\n >> done >>\n\n"

printf "\n >> start install measureit  >>\n#########################\n"
printf "\n >> clone measureit from github  >>\n#########################\n"
cd /web
git clone git://github.com/lalelunet/measureit.git
chown -R web:web /web
printf "\n >> done >>\n\n"

printf "\n >> initialize measureit database  >>\n#########################\n"
cat /web/measureit/measureit_system_files/install/createdb.sql | mysql -uroot -praspberry
printf "\n >> done >>\n\n"

printf "\n >> configure and start measureit  >>\n#########################\n"
mkdir /etc/servers/measureit
# change back to the pi user home dir where we clone measureit to
cd /home/pi/measureit/measureit_system_files/install
cat svc-run-measureit > /etc/servers/measureit/run
printf "\n >> done >>\n\n"

printf "\n >> clean up the system and shutdown  >>\n#########################\n"
apt-get clean
rm -r /home/pi/measureit/
rm -r /web/measureit/measureit_system_files/install/
echo '' > /home/pi/.bash_history
echo '' > /root/.bash_history
shutdown -h now
