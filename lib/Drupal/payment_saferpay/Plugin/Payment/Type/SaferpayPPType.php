<?php

/**
 * @file
 * Contains \Drupal\payment_saferpay\Plugin\Payment\Type\SaferpayPPType.
 */

namespace Drupal\payment_saferpay\Plugin\Payment\Type;

use Drupal\payment\Plugin\Payment\Type\PaymentTypeBase;

/**
 * A Saferpay PP payment type.
 *
 * @PaymentType(
 *   id = "payment_saferpay_pp_type",
 *   label = @Translation("Saferpay PP type")
 * )
 */
class SaferpayPPType extends PaymentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function paymentDescription($language_code = NULL) {
    return t('Redirect users to submit payments through Saferpay.');
  }

  /**
   * {@inheritdoc
   */
  public static function getOperations($plugin_id) {
    return array(
      'payment_saferpay_pp_configure' => array(
        'title' => t('Configure'),
        'href' => '<front>',
      ),
    );
  }
}
