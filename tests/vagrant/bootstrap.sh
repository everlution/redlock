#!/usr/bin/env bash

apt-get update
apt-get upgrade

apt-get install -y redis-server

cp /vagrant/redis.conf /etc/redis/redis.conf
service redis-server restart
