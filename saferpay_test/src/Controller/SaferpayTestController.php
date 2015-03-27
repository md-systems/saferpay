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
   * @param Request
   *
   * @return Response
   */
  public function createPayInit(Request $request = NULL) {
    return new Response(Url::fromRoute('saferpay_test.saferpay_test_form', array(), array(
      'query' => $request->query->all()))->setAbsolute()->toString());
  }



  /**
   * Verifies the digital signature of the confirmation message (MSGTYPE=PayConfirm)
   * returned to the shop via SUCCESSLINK or NOTIFYURL in order to ensure
   * that the response has not been manipulated.
   *
   * @param Request
   *
   * @return Response
   */
  public function verifyPayConfirm(Request $request = NULL) {
    return new Response(SafeMarkup::format("OK:ID=@ID&TOKEN=@TOKEN", $request->query->get('DATA')));
  }

  /**
   * Settles the payment.
   *
   * @return Response
   */
  public function payComplete() {
    return new Response("OK");
  }
}
