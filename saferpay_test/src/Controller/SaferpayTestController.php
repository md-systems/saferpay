<?php

/**
 * @file
 * Contains \Drupal\payment_saferpay_test\Controller\SaferpayTestController.
 */

namespace Drupal\payment_saferpay_test\Controller;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
   * @return RedirectResponse
   */
  public function createPayInit() {
    $data = $_GET;

    return new RedirectResponse(Url::fromRoute('saferpay_test.saferpay_test_form')->toString());
  }

  /**
   * Verifies the digital signature of the confirmation message (MSGTYPE=PayConfirm)
   * returned to the shop via SUCCESSLINK or NOTIFYURL in order to ensure
   * that the response has not been manipulated.
   *
   * @return Response
   */
  public function verifyPayConfirm() {
    return new Response("Verify Pay Confirm");
  }

  /**
   * Settles the payment.
   */
  public function payComplete() {
    return new Response("Pay Complete");
  }
}
