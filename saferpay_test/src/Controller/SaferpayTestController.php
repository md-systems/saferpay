<?php

/**
 * @file
 * Contains \Drupal\payment_saferpay_test\Controller\SaferpayTestController.
 */

namespace Drupal\payment_saferpay_test\Controller;

use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Crypt;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for testing purposes.
 */
class SaferpayTestController {

  public $config;

  public function __construct() {
    $this->config = \Drupal::config('payment_saferpay.settings');
  }

  /**
   * For more documentation regarding this test controller see: \Drupal\payment_saferpay\README.txt
   */

  /***
   * With CreatePayInit() a payment link can be generated.
   *
   * @param \Symfony\Component\HttpFoundation\Request
   *  The request
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *  Response to the request
   */
  public function createPayInit(Request $request = NULL) {
    return new Response(Url::fromRoute('saferpay_test.saferpay_test_form', array(), array(
      'query' => $request->query->all()))->setAbsolute()->toString());
  }

  /**
   * Simulates the Saferpay PayConfirm endpoint
   *
   * Verifies the digital signature of the confirmation message (MSGTYPE=PayConfirm)
   * returned to the shop via SUCCESSLINK or NOTIFYURL in order to ensure
   * that the response has not been manipulated.
   *
   * @param \Symfony\Component\HttpFoundation\Request
   *  The request
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *  Response to the request
   */
  public function verifyPayConfirm(Request $request = NULL) {
    $data_xml = simplexml_load_string($request->query->get('DATA'));
    if (Crypt::hashBase64($request->query->get('DATA')) == $request->query->get('SIGNATURE')) {
      if ((string) $data_xml['ACCOUNTID'] == $request->query->get('ACCOUNTID')) {
        return new Response(SafeMarkup::format("OK:ID=@ID&TOKEN=@TOKEN", array(
          '@ID' => (string) $data_xml['@ID'],
          '@TOKEN' => (string) $data_xml['@TOKEN'],
          )));
      }
      else {
        return new Response("ERROR: Verification failed, Account ID doesn't match");
      }
    }
    else {
      return new Response("ERROR: Verification failed, signature invalid");
    }
  }

  /**
   * Settles the payment.
   *
   * @param \Symfony\Component\HttpFoundation\Request
   *  The request
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *  Response to the request
   */
  public function payComplete(Request $request = NULL) {
    if($request->query->get('ACCOUNTID') == '99867-94913159')
    if($request->query->get('ID') == 'zzUIU8br3YGdvAx6t13QAC3vt0nA') {
      return new Response("OK");
    }
    else {
      return new Response("ERROR: Could not complete payment");
    }
  }
}
