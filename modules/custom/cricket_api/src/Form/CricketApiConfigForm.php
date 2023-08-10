<?php

namespace Drupal\cricket_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class CricketApiConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cricket_api.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cricket_api_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cricket_api.settings');

    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('X-RapidAPI-Key'),
      '#default_value' => $config->get('client_id'),
      '#required' => TRUE,
    ];

    $form['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('X-RapidAPI-Host'),
      '#default_value' => $config->get('client_secret'),
      '#required' => TRUE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Configuration'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('cricket_api.settings');
    $config->set('client_id', $form_state->getValue('client_id'));
    $config->set('client_secret', $form_state->getValue('client_secret'));
    $config->save();

    //drupal_set_message($this->t('Configuration saved successfully.'));
  }
}
