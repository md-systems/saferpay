<?php

/**
 * @file
 * Contains \Drupal\payment_saferpay\Plugin\Payment\Method\SaferpayPPMethod.
 */

namespace Drupal\payment_saferpay\Plugin\Payment\Method;

use Drupal\payment\Plugin\Payment\Method\PaymentMethodBase;

/**
 * A Saferpay PP payment method.
 *
 * @PaymentMethod(
 *   id = "payment_saferpay_pp_method",
 *   label = @Translation("Saferpay PP method")
 * )
 */
class SaferpayPPMethod extends PaymentMethodBase {

  /**
   * {@inheritdoc}
   */
  protected function currencies() {
    return array();
  }
}
