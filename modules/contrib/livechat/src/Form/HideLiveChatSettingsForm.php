<?php

namespace Drupal\livechat\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * HideLiveChatSettingsForm.
 */
class HideLiveChatSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hide_livechat_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'hidelivechat.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('hidelivechat.settings');
    $form['hide_live_chat'] = [
      '#type' => 'checkbox',
      '#title' => t('Hide Livechat window for logged in users '),
      '#default_value' => $config->get('hide_live_chat'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue('hide_live_chat');
    $value = $this->config('hidelivechat.settings')
      ->set('hide_live_chat', $values)
      ->save();
    parent::submitForm($form, $form_state);
  }

}
