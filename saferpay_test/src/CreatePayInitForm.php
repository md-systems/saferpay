<?php
/**
 * @file
 * Contains \Drupal\saferpay_test\CreatePayInitForm.
 */

namespace Drupal\payment_saferpay_test;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\payment\Entity\Payment;
use GuzzleHttp\Message\Request;

/**
 * Implements an example form.
 */
class CreatePayInitForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'create_pay_init_form';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {


    return;
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
