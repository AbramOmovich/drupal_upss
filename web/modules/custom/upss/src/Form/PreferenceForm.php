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
   * @param array|null $initial_preferences
   *
   * @return array The form structure.
   * The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $preferences = NULL, array $initial_preferences = NULL) {
    $form = [];

    $selected = [];
    if (!empty($preferences['preferences'])){
      $selected = array_keys($preferences['preferences']);
    }

    $form['properties_shown'] = [
      '#type' => 'checkboxes',
      '#options' => $initial_preferences,
      '#title' => $this->t('Select properties to set settings'),
      '#default_value' => $selected,
    ];

    $form['collection_id'] =[
      '#type' => 'hidden',
      '#value' => $preferences['entities_id'],
    ];

    $range_suffix = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => 'Less ======> More'
    ];

    $renderer = \Drupal::service('renderer');
    $range_suffix = $renderer->render($range_suffix);


    $preference_names = [];
    foreach ($preferences['preferences'] as $preference => $settings){

      $preference_names[] = $preference;
      $form['preferences'][$preference] = [
        '#type' => 'fieldset',
        '#title' => ucfirst($preference),
      ];

      $form['preferences'][$preference][$preference . '_|_' .'weight'] = [
        '#type' => 'range',
        '#title' => $this->t('Importance'),
        '#default_value' => $settings['weight'],
        '#min' => 0,
        '#max' => 1,
        '#step' => 0.05,
        '#suffix' => $range_suffix,
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

    //filter shown properties
    $preferences['properties_shown'] = array_filter($preferences['properties_shown'], function ($prop){
      return !is_null($prop);
    });
    $properties_shown = $preferences['properties_shown'];

    $user_preferences['entities_id'] = $preferences['collection_id'];
    unset($preferences['collection_id']);

    $names = unserialize( $preferences['names']);

    //leave only settings in form input
    $preferences = array_filter($preferences, function ($element){
      if (preg_match('@_|_@u', $element)){
        return TRUE;
      }else {
        return FALSE;
      }
    }, ARRAY_FILTER_USE_KEY);

    //put send preferences in appropriate structure
    $user_preferences['preferences'] = [];
    foreach ($names as $preference){
      if (!isset($properties_shown[$preference])){
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

    //add properties if them was removed from setting list
    $tempstore = \Drupal::service('user.private_tempstore')->get('upss_storage');
    $initial_preferences = $tempstore->get('initial_preferences');
    foreach ($properties_shown as $shown_property){
      if (!in_array($shown_property, $names)) {

        $user_preferences['preferences'][$shown_property] = $initial_preferences[$shown_property];
        $user_preferences['preferences'][$shown_property]['weight'] = 0;
      }
    }


    //send preferences from submitted form to UPSS
    $upss = \Drupal::service('upss.upss');
    $response = $upss->sendPreferences($user_preferences);

    //save sent preferences and received objects sequence in storage
    if ($response){
      $tempstore = \Drupal::service('user.private_tempstore')->get('upss_storage');
      $tempstore->set('response', $response);
    }else {
      drupal_set_message($this->t('Error occurred. Please try again later'), 'error');
    }

    return $form_state->setRedirect('upss.preferences');

  }

}