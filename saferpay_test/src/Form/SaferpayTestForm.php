<?php

/**
 * @file
 * Contains \Drupal\payment_saferpay_test\Form\SaferpayTestForm.
 */

namespace Drupal\payment_saferpay_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\payment\Entity\Payment;
use Drupal\payment_datatrans\DatatransHelper;
use Symfony\Component\HttpFoundation\Request;

/**
 * Builds the form to delete a forum term.
 */
class SaferpayTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'saferpay_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    foreach ($request->query->all() as $key => $value) {
      drupal_set_message($key . $value);
    }
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Pay'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message("Form submitted");
  }

}
