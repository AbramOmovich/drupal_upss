<?php

namespace Drupal\upss\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class PreferenceForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'upss_preferences_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @param array|null $preferences
   *
   * @return array The form structure.
   * The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $preferences = NULL) {
    $form = [];

    $form['collection_id'] =[
      '#type' => 'hidden',
      '#value' => $preferences['entities_id'],
    ];

    $preference_names = [];
    foreach ($preferences['preferences'] as $preference => $settings){

      $preference_names[] = $preference;
      $form['preferences'][$preference] = [
        '#type' => 'fieldset',
        '#title' => ucfirst($preference),
      ];

      $form['preferences'][$preference][$preference . '_|_' . 'display'] = [
        '#type' => 'select',
        '#title' => $this->t('Display this property'),
        '#options' => [
          'y' => $this->t('Yes'),
          'n' => $this->t('No'),

        ],
      ];

      $form['preferences'][$preference][$preference . '_|_' .'weight'] = [
        '#type' => 'range',
        '#title' => $this->t('Importance'),
        '#default_value' => $settings['weight'],
        '#min' => 0,
        '#max' => 1,
        '#step' => 0.1,
      ];

      if (isset($settings['direction'])){
        $form['preferences'][$preference][$preference . '_|_' .'direction'] = [
          '#type' => 'select',
          '#title' => $this->t('Select direction of property'),
          '#options' => [
            0 => $this->t('Min'),
            1 => $this->t('Max'),
          ],
          '#default_value' => $settings['direction']
        ];
      }

      if (isset($settings['match'])){
        $form['preferences'][$preference][$preference . '_|_' .'match'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Enter word to match'),
          '#default_value' => $settings['match']
        ];
      }
    }

    $form['names'] = [
      '#type' => 'hidden',
      '#value' => serialize($preference_names),
    ];
    $form['send'] = [
      '#type' => 'submit',
      '#value' => $this->t('Range objects'),
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
    $user_preferences = [];

    $preferences = $form_state->getUserInput();
    $user_preferences['entities_id'] = $preferences['collection_id'];
    unset($preferences['collection_id']);

    $names = unserialize( $preferences['names']);

    $preferences = array_filter($preferences, function ($element){
      if (preg_match('@_\|_@', $element)){
        return TRUE;
      }else {
        return FALSE;
      }
    }, ARRAY_FILTER_USE_KEY);

    $user_preferences['preferences'] = [];

    foreach ($names as $preference){
      if ($preferences[$preference . '_|_display'] == 'n'){
        $preferences = array_filter($preferences, function ($key) use ($preference){
          if (preg_match("@{$preference}_@", $key)){
            return FALSE;
          }
          return TRUE;
        }, ARRAY_FILTER_USE_KEY);
        continue;
      }

      if (isset($preferences[$preference . '_|_weight'])){
        $user_preferences['preferences'][$preference]['weight'] = $preferences[$preference . '_|_weight'];

        if (isset($preferences[$preference . '_|_direction'])){
          $user_preferences['preferences'][$preference]['direction'] = $preferences[$preference . '_|_direction'];
        }
        if (isset($preferences[$preference . '_|_match'])){
          $user_preferences['preferences'][$preference]['match'] = $preferences[$preference . '_|_match'];
        }
      }

    }

    $upss = \Drupal::service('upss.upss');
    $response = $upss->sendPreferences($user_preferences);

    $tempstore = \Drupal::service('user.private_tempstore')->get('upss_storage');
    $tempstore->set('preferences', $response['preferences']);
    $tempstore->set('objects', $response['objects']);

    return $form_state->setRedirect('upss.preferences');

  }

}