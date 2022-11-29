<?php

namespace Drupal\onesignal\Config;

/**
 * Interface for manage Onesignal module configuration.
 *
 * @package Drupal\onesignal\Config
 */
interface ConfigManagerInterface {

  /**
   * Provides Onesignal App id.
   *
   * @return string
   *   Returns Onesignal App id.
   */
  public function getAppId();

  /**
   * Provides original Onesignal App id.
   *
   * @return string
   *   Returns original Onesignal App id.
   */
  public function getOriginalAppId();

  /**
   * Provides Onesignal Safari web id.
   *
   * @return string
   *   Returns Onesignal Safari web id.
   */
  public function getSafariWebId();

  /**
   * Provides original Onesignal Safari web id.
   *
   * @return string
   *   Returns original Onesignal Safari web id.
   */
  public function getOriginalSafariWebId();

  /**
   * Provides Onesignal REST Api key.
   *
   * @return string
   *   Returns Onesignal REST Api key.
   */
  public function getRestApiKey();

  /**
   * Provides auto register value.
   *
   * @return int|string
   *   Returns auto register value.
   */
  public function getAutoRegister();

  /**
   * Provides notify button visibility value.
   *
   * @return int|string
   *   Returns notify button visibility value
   */
  public function getNotifyButton();

  /**
   * Provides value of development setting 'Localhost secure origin'.
   *
   * @return int|string
   *   Returns value of development setting 'Localhost secure origin'.
   */
  public function getLocalhostSecure();

  /**
   * Provides text of the invitation to signup.
   *
   * @return string
   *   Returns text of the invitation to signup.
   */
  public function getActionMessage();

  /**
   * Provides text of the Accept button.
   *
   * @return string
   *   Returns text of the invitation to signup.
   */
  public function getAcceptButtonText();

  /**
   * Provides text of the Cancel button.
   *
   * @return string
   *   Returns text of the Cancel button.
   */
  public function getCancelButtonText();

  /**
   * Provides text of the welcome notification title.
   *
   * @return string
   *   Returns text of the welcome notification title.
   */
  public function getWelcomeTitle();

  /**
   * Provides text of the welcome notification message.
   *
   * @return string
   *   Returns text of the welcome notification message.
   */
  public function getWelcomeMessage();

}
