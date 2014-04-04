<?php

/**
 * @file
 * Contains \Drupal\payment_saferpay\Plugin\Payment\Type\SaferpayBusinessType.
 */

namespace Drupal\payment_saferpay\Plugin\Payment\Type;

use Drupal\payment\Plugin\Payment\Type\PaymentTypeBase;

/**
 * A Saferpay Business payment type.
 *
 * @PaymentType(
 *   id = "payment_saferpay_business_type",
 *   label = @Translation("Saferpay Business type")
 * )
 */
class SaferpayBusinessType extends PaymentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function paymentDescription($language_code = NULL) {
    return t('Handels payments (almost) without redirects to Saferpay. (Hidden Mode)');
  }

  /**
   * {@inheritdoc
   */
  public static function getOperations($plugin_id) {
    return array(
      'payment_saferpay_business_configure' => array(
        'title' => t('Configure'),
        'href' => '<front>',
      ),
    );
  }
}
