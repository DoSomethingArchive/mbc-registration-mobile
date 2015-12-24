<?php
/**
 * Message Broker configuration settings for mbc-registration-mobile
 */

use DoSomething\MB_Toolbox\MB_Configuration;
use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox;
use DoSomething\MB_Toolbox\MB_RabbitMQManagementAPI;

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
$mbConfig->setProperty('rabbitapi_credentials', [
  'host' =>  getenv("MB_RABBITMQ_MANAGEMENT_API_HOST"),
  'port' => getenv("MB_RABBITMQ_MANAGEMENT_API_PORT"),
  'username' => getenv("MB_RABBITMQ_MANAGEMENT_API_USERNAME"),
  'password' => getenv("MB_RABBITMQ_MANAGEMENT_API_PASSWORD"),
]);
$mbRabbitConfig = $mbConfig->constructRabbitConfig('transactionalExchange', array('mobileCommonsQueue'));
$mbConfig->setProperty('rabbit_config', $mbRabbitConfig);

$mbConfig->setProperty('mobileCommons_config', array(
  'US' => array(
    'username' => getenv("MOBILE_COMMONS_US_USER"),
    'password' => getenv("MOBILE_COMMONS_US_PASSWORD"),
    'company_key' => getenv("MOBILE_COMMONS_US_COMPANY_KEY"),
  ),
  'MUI' => array(
    'username' => getenv("MOBILE_COMMONS_US_USER"),
    'password' => getenv("MOBILE_COMMONS_US_PASSWORD"),
    'company_key' => getenv("MOBILE_COMMONS_US_COMPANY_KEY"),
  ),
  'CGG' => array(
    'username' => getenv("MOBILE_COMMONS_US_USER"),
    'password' => getenv("MOBILE_COMMONS_US_PASSWORD"),
    'company_key' => getenv("MOBILE_COMMONS_US_COMPANY_KEY"),
  ),
  'AGG' => array(
    'username' => getenv("MOBILE_COMMONS_US_USER"),
    'password' => getenv("MOBILE_COMMONS_US_PASSWORD"),
    'company_key' => getenv("MOBILE_COMMONS_US_COMPANY_KEY"),
  ),
  'CA' => array(
    'username' => getenv("MOBILE_COMMONS_CA_USER"),
    'password' => getenv("MOBILE_COMMONS_CA_PASSWORD"),
    'company_key' => getenv("MOBILE_COMMONS_CA_COMPANY_KEY"),
  )
));

$mobileConfig = $mbConfig->gatherSettings('mobile');
$mbConfig->setProperty('communicatePro_config', array(
  'settings' => $mobileConfig->mGage,
  'username' => getenv("COMMUNICATE_PRO_USER"),
  'password' => getenv("COMMUNICATE_PRO_PASSWORD")
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
$mbConfig->setProperty('mbRabbitMQManagementAPI', new MB_RabbitMQManagementAPI([
  'domain' => getenv("MB_RABBITMQ_MANAGEMENT_API_HOST"),
  'port' => getenv('MB_RABBITMQ_MANAGEMENT_API_PORT'),
  'vhost' => getenv('MB_RABBITMQ_MANAGEMENT_API_VHOST'),
  'username' => getenv('MB_RABBITMQ_MANAGEMENT_API_USERNAME'),
  'password' => getenv('MB_RABBITMQ_MANAGEMENT_API_PASSWORD')
]));
