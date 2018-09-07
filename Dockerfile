#
# Apache & PHP image
#
# 
FROM php:7.2-apache

MAINTAINER Alten CalsoftLabs
#
RUN \
  apt-get update \
  && apt-get install -y git \
  && git clone https://github.com/santhosh-alten/lamp.git \
  && mv lamp/* /var/www/html/
EXPOSE 80
#End
