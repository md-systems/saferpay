<?php

/**
 * @file
 * Contains \Drupal\payment_saferpay\Plugin\Payment\Type\SaferpayPaymentFormType.
 */

namespace Drupal\payment_saferpay\Plugin\Payment\Type;

use Drupal\Core\Session\AccountInterface;
use Drupal\payment\Plugin\Payment\Type\PaymentTypeBase;

/**
 * A testing payment type.
 *
 * @PaymentType(
 *   id = "payment_saferpay_payment_form",
 *   label = @Translation("Saferpay Payment Form"),
 *   description = @Translation("Saferpay Payment Form payment type.")
 * )
 */
class SaferpayPaymentFormType extends PaymentTypeBase {

  /**
   * {@inheritdoc}
   */
  public function paymentDescription($language_code = NULL) {
    // @todo - provide correct description
    return 'some nice description that I have no idea of what it should describe...';
  }

  /**
   * {@inheritdoc
   */
  public function resumeContextAccess(AccountInterface $account) {
    return FALSE;
  }

  /**
   * {@inheritdoc
   */
  public function doResumeContext() {
  }
}
