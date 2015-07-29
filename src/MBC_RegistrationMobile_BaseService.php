<?php

namespace DoSomething\MB_Toolbox;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox;

/*
 * MBC_UserAPICampaignActivity.class.in: Used to process the transactionalQueue
 * entries that match the campaign.*.* binding.
 */
abstract class MBC_RegistrationMobile_BaseService
{

   // The number of seconds to pause when throttling is triggered
   const THROTTLE_TIMEOUT = 5;

  /**
   * Message Broker connection to RabbitMQ
   *
   * @var object
   */
  protected $messageBroker;
  
  /**
   * StatHat object for logging of activity
   *
   * @var object
   */
  protected $statHat;
  
  /**
   * Message Broker Toolbox - collection of utility methods used by many of the
   * Message Broker producer and consumer applications.
   *
   * @var object
   */
  protected $toolbox;

  /**
   * Setting from external services - Mail chimp.
   *
   * @var array
   */
  protected $settings;
  
  /**
   * Value of message from queue to be consumed / processed.
   *
   * @var array
   */
  protected $message;

  /**
   * Constructor for MBC_BaseConsumer - all consumer applications should extend this base class.
   *
   * @param object $messageBroker
   *   The Message Broker object used to interface the RabbitMQ server exchanges and related queues.
   *  
   * @param object $statHat
   *   Track application activity by triggering counters in StatHat service.
   *
   * @param object $toolbox
   *   A collection of common tools for the Message Broker system.
   *   
   * @param array $settings
   *   Settings from internal and external services used by the application.
   */
  public function __construct($messageBroker, StatHat $statHat, MB_Toolbox $toolbox, $settings) {

    $this->messageBroker = $messageBroker;
    $this->statHat = $statHat;
    $this->toolbox = $toolbox;
    $this->settings = $settings;
  }
  
  /**
   * Method to determine if message can be processed. Tests based on requirements of the service.
   *
   * @param array $message
   *  The payload of the unseralized message being processed.
   *
   * @retun boolean
   */
  abstract protected function canProcess($message);

  /**
   * Sets values for processing based on contents of message from consumed queue.
   *
   * @param array $message
   *  The payload of the unseralized message being processed.
   */
  abstract protected function setter($message);

  /**
   * Process message from consumed queue.
   */
  abstract protected function process();

}
