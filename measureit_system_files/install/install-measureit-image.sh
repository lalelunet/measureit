#!/bin/sh

echo ">> update system >>"
apt-get update && apt-get -y upgrade && apt-get clean
echo ">> install software >>"
apt-get install -y vim daemontools-run php7.0-fpm php7.0-mysql nginx samba samba-common-bin python-mysqldb python-serial python-setuptools

echo ">> install python packages >>"
easy_install twython requests requests_oauthlib

echo ">> Set ip adresses >>"
cat static-network >> /etc/network/interfaces

echo ">> enable ipv6 >>"
echo "ipv6" >> /etc/modules

echo ">> create supervise directories >>"
mkdir /etc/servers
cd / && ln -s /etc/service service

echo ">> create directories for php change the default config and start php-fpm service  >>"
mkdir /web
useradd -d /web -s /bin/bash web
chown -R web:web /web
sed -i -e 's/;daemonize = yes/daemonize = no/g' /etc/php/7.0/fpm/php-fpm.conf
sed -i -e 's/www-data/web/g' /etc/php/7.0/fpm/pool.d/www.conf

# dear systemd. these services are controlled by supervise
echo ">> remove php control from systemd and start php server with supervise >>"
systemctl stop php7.0-fpm && systemctl mask php7.0-fpm && systemctl daemon-reload && rm /etc/init.d/php7.0-fpm
mkdir /etc/servers/php7-fpm
cat svc-run-php > /etc/servers/php7-fpm/run
chmod +x /etc/servers/php7-fpm/run
cd /etc/service
ln -s /etc/servers/php7-fpm/

echo ">> create directories for mariadb, install mariadb and start the mariadb service  >>"
echo mariadb-server-10.1 mysql-server/root_password password 'raspberry' | debconf-set-selections
echo mariadb-server-10.1 mysql-server/root_password_again password 'raspberry' | debconf-set-selections
apt-get install -y mariadb-server

# dear systemd. these services are controlled by supervise
echo ">> remove mariadb control from systemd and start mariadb server with supervise >>";
systemctl stop mysql && systemctl mask mysql && systemctl daemon-reload

mkdir /etc/servers/mariadb
cat svc-run-mariadb > /etc/servers/mariadb/run;
chmod +x /etc/servers/mariadb/run
cd /etc/service
ln -s /etc/servers/mariadb mariadb

echo ">> create directories for nginx and start the nginx service  >>"
sed -i -e 's/www-data/web/g' /etc/nginx/nginx.conf
echo "daemon off;" >> /etc/nginx/nginx.conf
cat config-nginx > /etc/nginx/sites-enabled/default

mkdir /etc/servers/nginx
cat svc-run-nginx > /etc/servers/nginx/run
chmod +x /etc/servers/nginx/run

echo ">> configure and start the samba service  >>"
(echo raspberry; echo raspberry) | smbpasswd -a -s web
cat config-samba >> /etc/samba/smb.conf

echo ">> install measureit  >>"
echo ">> clone measureit from github  >>"
chown -R web:web /web
su - web
cd /web
git clone git://github.com/lalelunet/measureit.git

echo ">> initialize measureit database  >>"
cat /web/measureit/measureit_system_files/install/createdb.sql | mysql -uroot -praspberry

echo ">> configure and start measureit  >>"
mkdir /etc/servers/measureit
cat svc-run-measureit > /etc/servers/measureit/run
chmod +x /etc/servers/measureit/run
cd /etc/service
ln -s /etc/servers/measureit measureit