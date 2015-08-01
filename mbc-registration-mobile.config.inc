<?php
/**
 * Message Broker configuration settings for mbc-import-logging
 */


use DoSomething\MB_Toolbox\MB_Configuration;
use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox;

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require_once __DIR__ . '/messagebroker-config/mb-secure-config.inc';

$mbConfig = MB_Configuration::getInstance();

// Settings
$mbConfig->setProperty('rabbit_credentials', array(
  'host' =>  getenv("RABBITMQ_HOST"),
  'port' => getenv("RABBITMQ_PORT"),
  'username' => getenv("RABBITMQ_USERNAME"),
  'password' => getenv("RABBITMQ_PASSWORD"),
  'vhost' => getenv("RABBITMQ_VHOST"),
));
$mbRabbitConfig = $mbConfig->constructRabbitConfig('transactionalExchange', array('mobileCommonsQueue'));
$mbConfig->setProperty('rabbit_config', $mbRabbitConfig);

$mbConfig->setProperty('mobileCommons_config', array(
  'US' => array(
    'user' => getenv("MOBILE_COMMONS_US_USER"),
    'password' => getenv("MOBILE_COMMONS_US_PASSWORD"),
    'compant' => getenv("MOBILE_COMMONS_US_COMPANY_KEY"),
  ),
  'CA' => array(
    'user' => getenv("MOBILE_COMMONS_CA_USER"),
    'password' => getenv("MOBILE_COMMONS_CA_PASSWORD"),
    'compant' => getenv("MOBILE_COMMONS_CA_COMPANY_KEY"),
  )
));

// Create untility objects for access anywhere within the application as a single instance
$mbConfig->setProperty('messageBroker_config', $mbConfig->constructRabbitConfig('transactionalExchange', array('mobileCommonsQueue')));
$rabbitCredentials = $mbConfig->getProperty('rabbit_credentials');
$messageBrokerConfig = $mbConfig->getProperty('messageBroker_config');
$mbConfig->setProperty('messageBroker', new MessageBroker($rabbitCredentials, $messageBrokerConfig));

$mbConfig->setProperty('statHat', new StatHat([
  'ez_key' => getenv("STATHAT_EZKEY"),
  'debug' => getenv('DISABLE_STAT_TRACKING')
]));

$mbConfig->setProperty('mbToolbox', new MB_Toolbox());
