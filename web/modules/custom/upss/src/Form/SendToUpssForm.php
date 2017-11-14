<?php

namespace Drupal\upss\Form;

use Drupal\Console\Bootstrap\Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class SendToUpssForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'send_to_upss_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send catalog to UPSS'),
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $phoneCatalog = \Drupal::service('upss.phone_catalog');
    $phones = $phoneCatalog->getPhones(NULL, TRUE);

    if ($phones){
      $upss = \Drupal::service('upss.upss');
      $response = $upss->sendData($phones);
      if ($response){

        $tempstore = \Drupal::service('user.private_tempstore')->get('upss_storage');
        $tempstore->set('response', $response);

        $initial_preferences = array_keys($response['preferences']);
        foreach ($initial_preferences as $index => $init_preference){
          $initial_preferences[$init_preference] = $init_preference;
          unset($initial_preferences[$index]);
        }

        $tempstore->set('initial_preferences_names', $initial_preferences);
        $tempstore->set('initial_preferences', $response['preferences']);

        return $form_state->setRedirect('upss.preferences');
      }
    }
  }


}