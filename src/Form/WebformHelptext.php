<?php

namespace Drupal\webform_helptext\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Serialization\Yaml;

class WebformHelptext extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_helptext_configuration';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $webform_name = \Drupal::request()->get('webform');
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load($webform_name);
    $webform_elements = $webform->get('elements');
    $elements = Yaml::decode($webform_elements);

    $storage = array('webform' => $webform, 'elements' => $elements);
    $form_state->setStorage($storage);

    $form['fields'] = array(
      '#type' => 'fieldset',
      '#title' => $webform->get('title') . ' help text configuration',
      '#collapsible' => TRUE,
      '#tree' => TRUE,
      '#collapsed' => FALSE,
    );

    foreach ($elements as $key => $element) {
      // Exclude hidden fields.
      if ($element['#type'] === 'fieldset' || $element['#type'] === 'hidden') {
        continue;
      }

      $form['fields'][$key]['value'] = array(
        '#type' => 'textfield',
        '#title' => $element['#title'],
        '#default_value' => !empty($element['#attributes']) ?
          $element['#attributes']["data-help-text-{$key}"] : '',
        '#description' => t('Enter help text for ' . $element['#title'] . ' field.')
      );
    }

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getValues())) {
      $storage = $form_state->getStorage();
      $webform = $storage['webform'];
      $elements = $storage['elements'];
      $values = $form_state->getValues();

      foreach ($elements as $key => $element) {
        // Add help text as data attribute on the Webform component.
        $elements[$key]['#attributes'] = array(
          'data-info-icon' => 'info-icon-' . $key,
          "data-help-text-{$key}" => $values['fields'][$key]['value'],
        );
      }

      $webform_elements = Yaml::encode($elements);
      $webform->set('elements', $webform_elements);
      $webform->save();

      drupal_set_message('Help text configuration is saved.', 'status');
    }
  }
}
