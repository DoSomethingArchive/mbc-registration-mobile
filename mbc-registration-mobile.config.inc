<?php
/**
 * Message Broker configuration settings for mbc-import-logging
 */


use DoSomething\MB_Toolbox\MB_Configuration;

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require_once __DIR__ . '/messagebroker-config/mb-secure-config.inc';

// Settings
$credentials = array(
  'host' =>  getenv("RABBITMQ_HOST"),
  'port' => getenv("RABBITMQ_PORT"),
  'username' => getenv("RABBITMQ_USERNAME"),
  'password' => getenv("RABBITMQ_PASSWORD"),
  'vhost' => getenv("RABBITMQ_VHOST"),
);

$settings = array(
  'stathat_ez_key' => getenv("STATHAT_EZKEY"),
  'stathat_disable_tracking' => getenv('DISABLE_STAT_TRACKING'),
  'mobile_commons_us_user' => getenv("MOBILE_COMMONS_US_USER"),
  'mobile_commons_us_password' => getenv("MOBILE_COMMONS_US_PASSWORD"),
  'mobile_commons_us_company_key' => getenv("MOBILE_COMMONS_US_COMPANY_KEY"),
  'mobile_commons_ca_user' => getenv("MOBILE_COMMONS_CA_USER"),
  'mobile_commons_ca_password' => getenv("MOBILE_COMMONS_CA_PASSWORD"),
  'mobile_commons_ca_company_key' => getenv("MOBILE_COMMONS_CA_COMPANY_KEY"),
);

$mbConfig = new MB_Configuration($settings, CONFIG_PATH . '/mb_config.json');
$config = $mbConfig->constructConfig('transactionalExchange', array('mobileCommonsQueue'));
