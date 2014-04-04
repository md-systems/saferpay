<?php

/**
 * @file
 * Contains \Drupal\payment_saferpay\Plugin\Payment\Method\SaferpayBusinessMethod.
 */

namespace Drupal\payment_saferpay\Plugin\Payment\Method;

use Drupal\payment\Plugin\Payment\Method\PaymentMethodBase;

/**
 * A Saferpay Business payment method.
 *
 * @PaymentMethod(
 *   id = "payment_saferpay_business_method",
 *   label = @Translation("Saferpay Business method")
 * )
 */
class SaferpayBusinessMethod extends PaymentMethodBase {

  /**
   * {@inheritdoc}
   */
  protected function currencies() {
    return array();
  }
}
