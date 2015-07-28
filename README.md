mbc-registration-mobile
=======================

Message Broker - Consumer

Consumer for Message Broker system that supports user activities that require interaction with mobile / SMS services.

Basic Message Broker consumer setup
------------------------------------
- from a clone of the mbc-registration-mobile repository on the target server configured for PHP applications
- Install the application using Composer
  $ composer install
  - A symbolic link to messagebroker-config will be created at the root of the application.
- create the daemon configuration file at `$/etc/init/mbc-registration-email.conf` based on other "mbc" conf files in the directory
  - edit the file to make it mbc-registration-mobile specific.
- start the daemon process
  $ sudo start mbc-registration-mobile

Contents of mobileCommopnsQueue will be processed immediately. The queue should be at or quickly become zero messages at all times.
