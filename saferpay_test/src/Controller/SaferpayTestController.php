<?php

/**
 * @file
 * Contains \Drupal\payment_saferpay_test\Controller\SaferpayTestController.
 */

namespace Drupal\payment_saferpay_test\Controller;

use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for testing purposes.
 */
class SaferpayTestController {

  public $config;

  public function __construct() {
    $this->config = \Drupal::config('payment_saferpay.settings');
  }

  /***
   * @return Response
   */
  public function createPayInit() {
    $data = $_GET;
    if ($data['ACCOUNTID'] == '99867-94913159' && !empty($data['AMOUNT']) && !empty($data['CURRENCY']) && !empty($data['SUCCESSLINK'])) {

    }

    // Form
    return new Response($GLOBALS['base_root'] . $this->config->get('verify_pay_confirm'));
  }

  /**
   *
   */
  public function verifyPayConfirm() {
    return new Response("Verify Pay Confirm");
  }

  /**
   *
   */
  public function payComplete() {
    return new Response("Pay Complete");
  }
}
