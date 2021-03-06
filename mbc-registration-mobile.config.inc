<?php
/**
 * Message Broker configuration settings for mbc-registration-mobile
 */

use DoSomething\MessageBroker\MessageBroker;
use DoSomething\MB_Toolbox\MB_Configuration;
use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox;
use DoSomething\MB_Toolbox\MB_MobileCommons;
use DoSomething\MB_Toolbox\MB_RabbitMQManagementAPI;
use DoSomething\Gateway\Gambit;

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require_once CONFIG_PATH . '/mb-secure-config/' . ENVIRONMENT . '/mb-secure-config.inc';

$mbConfig = MB_Configuration::getInstance();

// Settings
$mbConfig->setProperty('generalSettings', []);
$mbConfig->setProperty('rabbit_credentials', [
  'host' =>  getenv("RABBITMQ_HOST"),
  'port' => getenv("RABBITMQ_PORT"),
  'username' => getenv("RABBITMQ_USERNAME"),
  'password' => getenv("RABBITMQ_PASSWORD"),
  'vhost' => getenv("RABBITMQ_VHOST"),
]);
$mbConfig->setProperty('rabbitapi_credentials', [
  'host' =>  getenv("MB_RABBITMQ_MANAGEMENT_API_HOST"),
  'port' => getenv("MB_RABBITMQ_MANAGEMENT_API_PORT"),
  'username' => getenv("MB_RABBITMQ_MANAGEMENT_API_USERNAME"),
  'password' => getenv("MB_RABBITMQ_MANAGEMENT_API_PASSWORD"),
]);
$mbRabbitConfig = $mbConfig->constructRabbitConfig('transactionalExchange', array('mobileCommonsQueue'));
$mbConfig->setProperty('rabbit_config', $mbRabbitConfig);

$mbConfig->setProperty('mobileCommons_config', [
  'US' => [
    'username' => getenv("MOBILE_COMMONS_US_USER"),
    'password' => getenv("MOBILE_COMMONS_US_PASSWORD"),
    'company_key' => getenv("MOBILE_COMMONS_US_COMPANY_KEY"),
  ],
  'MUI' => [
    'username' => getenv("MOBILE_COMMONS_US_USER"),
    'password' => getenv("MOBILE_COMMONS_US_PASSWORD"),
    'company_key' => getenv("MOBILE_COMMONS_US_COMPANY_KEY"),
  ],
  'CGG' => [
    'username' => getenv("MOBILE_COMMONS_US_USER"),
    'password' => getenv("MOBILE_COMMONS_US_PASSWORD"),
    'company_key' => getenv("MOBILE_COMMONS_US_COMPANY_KEY"),
  ],
  'AGG' => [
    'username' => getenv("MOBILE_COMMONS_US_USER"),
    'password' => getenv("MOBILE_COMMONS_US_PASSWORD"),
    'company_key' => getenv("MOBILE_COMMONS_US_COMPANY_KEY"),
  ],
  'FLF' => [
    'username' => getenv("MOBILE_COMMONS_US_USER"),
    'password' => getenv("MOBILE_COMMONS_US_PASSWORD"),
    'company_key' => getenv("MOBILE_COMMONS_US_COMPANY_KEY"),
  ],
  'CA' => [
    'username' => getenv("MOBILE_COMMONS_CA_USER"),
    'password' => getenv("MOBILE_COMMONS_CA_PASSWORD"),
    'company_key' => getenv("MOBILE_COMMONS_CA_COMPANY_KEY"),
  ]
]);

$mobileConfig = $mbConfig->gatherSettings('mobile');
$mbConfig->setProperty('communicatePro_config', [
  'settings' => $mobileConfig->mGage,
  'username' => getenv("COMMUNICATE_PRO_USER"),
  'password' => getenv("COMMUNICATE_PRO_PASSWORD")
]);

// Create untility objects for access anywhere within the application as a single instance
$messageBrokerConfig = $mbConfig->constructRabbitConfig('transactionalExchange', ['mobileCommonsQueue']);
$rabbitCredentials = $mbConfig->getProperty('rabbit_credentials');
$mbConfig->setProperty('messageBroker', new MessageBroker($rabbitCredentials, $messageBrokerConfig));

$messageBrokerConfig = $mbConfig->constructRabbitConfig('directDeadLetterExchange', ['deadLetterQueue']);
$mbConfig->setProperty('messageBroker_deadLetter', new MessageBroker($rabbitCredentials, $messageBrokerConfig));

$mbConfig->setProperty('statHat', new StatHat([
  'ez_key' => getenv("STATHAT_EZKEY"),
  'debug' => getenv('DISABLE_STAT_TRACKING')
]));

$mbConfig->setProperty('mbToolbox', new MB_Toolbox());
$mbConfig->setProperty('mbMobileCommons', new MB_MobileCommons());
$mbConfig->setProperty('mbRabbitMQManagementAPI', new MB_RabbitMQManagementAPI([
  'domain' => getenv("MB_RABBITMQ_MANAGEMENT_API_HOST"),
  'port' => getenv('MB_RABBITMQ_MANAGEMENT_API_PORT'),
  'vhost' => getenv('MB_RABBITMQ_MANAGEMENT_API_VHOST'),
  'username' => getenv('MB_RABBITMQ_MANAGEMENT_API_USERNAME'),
  'password' => getenv('MB_RABBITMQ_MANAGEMENT_API_PASSWORD')
]));

// Gambit.
$gambit = new Gambit([
  'url'    => getenv('GAMBIT_API_URL'),
  'apiKey' => getenv('GAMBIT_API_KEY'),
]);
$mbConfig->setProperty('gambit', $gambit);

echo "Loading Gambit campaigns cache..." . PHP_EOL;
$gambitCampaigns = $gambit->getAllCampaigns(['campaignbot' => true]);
$gambitCampaignsCache = [];
foreach ($gambitCampaigns as $campaign) {
  if ($campaign->status != 'closed') {
    $gambitCampaignsCache[$campaign->id] = $campaign;
  }
}

if (count($gambitCampaignsCache) < 1) {
  // Basically, die.
  throw new Exception('No gambit connection.');
}
$mbConfig->setProperty('gambitCampaignsCache', $gambitCampaignsCache);
