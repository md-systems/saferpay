<?php

/**
 * @file
 * Contains \Drupal\payment_saferpay_test\Form\SaferpayTestForm.
 */

namespace Drupal\payment_saferpay_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Test It!'),
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
