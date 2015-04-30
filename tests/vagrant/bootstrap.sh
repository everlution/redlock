#!/usr/bin/env bash

apt-get update
apt-get upgrade

mkdir /etc/redis
mkdir /var/redis
mkdir /var/redis/6379

cd
wget http://download.redis.io/redis-stable.tar.gz
tar xvzf redis-stable.tar.gz
cd redis-stable
make
make install

cp utils/redis_init_script /etc/init.d/redis_6379
cp /vagrant/redis.conf /etc/redis/6379.conf
update-rc.d redis_6379 defaults
/etc/init.d/redis_6379 start
