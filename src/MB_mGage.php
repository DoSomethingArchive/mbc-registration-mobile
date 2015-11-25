<?php
/*
 *
 */

namespace DoSomething\MBC_RegistrationMobile;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox_cURL;
use DoSomething\MB_Toolbox\MB_Configuration;
use \Exception;

class MB_mGage
{

  const BASE_URL = 'https://communicatepro.mgage.com/api/';

  /**
   * Constructor for mGage
   *
   * @param array $config
   */
  public function __construct($config) {

    $this->config = $config;
  }

  /**
   *
   */
  public function optIn($message) {

    $username = $this->config['username'];
    $password = $this->config['password'];
    $optInListID = '';
    $mobileNumber = '';

    // Add user to list
    $optInCallURL = self::BASE_URL . 'optIn/' . $optInListID . '/' . $mobileNumber;
    $results = $this->makeWebRequest($username, $password, $optInCallURL);

    // Trap errors
    if (isset($results['error'])) {
      throw new Exception('Call to optIn returned error response: ' . $results['name'] . ': ' .  $results['error']);
    }
    elseif ($results == 0) {
      throw new Exception('Hmmm: No results returned from mGage optIn submission.');
    }

  }

  /**
   * Send a "MO" - Mobile Originated message. Used to programatically interact with the Communicate Pro platform as a
   * simulated user from a mobile device. An example, a user text a keyword to a 2-way program entry. The program has
   */
  public function  mobileOriginated($message) {

    $bla = FALSE;
if ($bla) {
  $bla = TRUE;
}

    $username = $this->config['username'];
    $password = $this->config['password'];

    // Gatway
    // $gatewayID = '115646';  // Gateway ID: https://communicatepro.mgage.com/consoleapp/viewTemplateProgram.jspx?id=115646
    $gatewayID = '115658';

    // $optInListID = $message['opt_in_path_id'];
    $optInListID = '38383'; // Brazil
    $optInListID = '66978'; // Friday 2 Shortcode

    // $optInKeyword = $message['opt_in_keyword'];
    // $optInKeyword = 'BZCGG'; // Brazil
    $optInKeyword = 'FREDFRI'; // US Test

    $mobileNumber = $message['mobile'];
    // $mobileNumber = '13479886705';  // Dee
    // $mobileNumber = '17024390195';     // Freddie

    // Add user to list
    $moURL = self::BASE_URL . 'externalMO';
    $contentType = NULL;
    $postContent = [
      'campid' => $gatewayID,
      'destination' => $optInListID,
      'originator' => $mobileNumber,
      'message' => $optInKeyword,
    ];

    $results = $this->makeWebRequest($username, $password, $moURL, $contentType, $postContent);

    /** 
     * On success, the system will return a 200 header, and the word �SUCCESS�.
     * On failure, the system will return a 500 header, and an English language error message
     * telling you what you�ve done wrong.
     */
    if (isset($results[1]) && $results[1] != 200) {
      $this->messageBroker->sendNack($message['payload']);
      $this->statHat->ezCount('MBC_RegistrationMobile_Service_mGage: mobileOriginated error: ' . $status['error']->attributes()->{'message'});
      throw new Exception('Call to mobileOriginated returned error response: ' . $results[0] . ': ' .  $results[1]);
    }
    elseif ($results == 0) {
      $this->messageBroker->sendNack($message['payload']);
      throw new Exception('Hmmm: No results returned from mGage mobileOriginated submission.');
    }

    return $results;
  }

  /**
   *
   */
  private function makeWebRequest($username, $password, $url, $contentType = NULL,$postContent = NULL) {

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);

    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postContent));

    // Set authentication options
    curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $password);

    // Indicate that the message should be returned to a variable
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    // Make request
    $results[0] = curl_exec($ch);
    $results[1] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $results;
  }

  /**
   * Takes XML string and returns a boolean result where valid XML returns true
   */
  private function is_valid_xml($xml) {

    libxml_use_internal_errors(true);
    $doc = new DOMDocument('1.0', 'utf-8');
    $doc->loadXML($xml);

    $errors = libxml_get_errors();
    
    return empty($errors);
  }
  
  /**
   *
   */
  private function xmlFormat($params) {
    
    $messageTemplateData =
      '<?xml version="1.0"?>
         <sendTemplateMessage xmlns="http://communicatepro.mgage.com/api">
           <recipient number="' . $mobileNumber . '">
             <xmlData>
               <![CDATA[' . "\n$xmlData\n" . ']]>
            </xmlData>
            <code>clientRecipId</code>
          </recipient>
        </sendTemplateMessage>';
    
  }
  
}
