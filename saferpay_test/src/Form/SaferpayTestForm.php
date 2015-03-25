<?php

/**
 * @file
 * Contains \Drupal\payment_saferpay_test\Form\SaferpayTestForm.
 */

namespace Drupal\payment_saferpay_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\payment\Entity\Payment;
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

    $form_elements = array(
      'amount' => $request->query->get('amount'),
      'currency' => $request->query->get('currency'),
      'status' => 'success',
    );

    foreach ($form_elements as $key => $value) {
      $form[$key] = array(
        '#type' => 'hidden',
        '#value' => $value,
      );
    }

    // Don't generate the route, use the submitted url.
    $response_url_key = \Drupal::state()->get('saferpay.return_url_key') ?: 'success';
    $response_url = $request->query->get($response_url_key . 'Url');

    $form['#action'] = $response_url;
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message("Validate Form");
  }

}
