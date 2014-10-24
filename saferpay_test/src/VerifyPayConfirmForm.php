<?php
/**
 * @file
 * Contains \Drupal\saferpay_test\VerifyPayConfirmForm.
 */

namespace Drupal\payment_saferpay_test;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\payment\Entity\Payment;
use GuzzleHttp\Message\Request;

/**
 * Implements an example form.
 */
class VerifyPayConfirmForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'verify_pay_confirm_form';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message("Validate Form");
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message("Submit Form");
  }
}
