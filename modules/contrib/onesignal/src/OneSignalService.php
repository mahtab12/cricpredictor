<?php

namespace Drupal\onesignal;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;

use OneSignal\Config;
use OneSignal\OneSignal;
use Symfony\Component\HttpClient\Psr18Client;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * The OneSignalService class.
 */
class OneSignalService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The config factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The oneSignal API.
   *
   * @var \OneSignal\OneSignal
   */
  private $oneSignalApi;

  /**
   * Constructs a OneSignal object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Manages entity type plugin definitions.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   Defines the configuration object factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactory $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * Get OneSignal App Id.
   *
   * @return string|null
   *   Returns app id.
   */
  public function getAppId(): ?string {
    return $this->configFactory->get('onesignal.config')->get('onesignal_app_id');
  }

  /**
   * Get OneSignal REST API Key.
   *
   * @return string|null
   *   Returns REST Api Key.
   */
  public function getRestApiKey(): ?string {
    return $this->configFactory->get('onesignal.config')->get('onesignal_rest_api_key');
  }

  /**
   * Get OneSignal API connector.
   *
   * @return \OneSignal\OneSignal|null
   *   Returns the API connector.
   */
  public function getApi(): ?OneSignal {
    if (!$this->oneSignalApi) {
      $config = new Config($this->getAppId(), $this->getRestApiKey());
      $httpClient = new Psr18Client();
      $requestFactory = $streamFactory = new Psr17Factory();
      $this->oneSignalApi = new OneSignal($config, $httpClient, $requestFactory, $streamFactory);
    }
    return $this->oneSignalApi;
  }

  /**
   * View the details of all of your current OneSignal applications.
   *
   * @note Requires your OneSignal User Auth Key.
   *
   * @see https://documentation.onesignal.com/reference#view-apps-apps
   *
   * @return array
   *   Returns the details of all of your current OneSignal applications.
   */
  public function getApps(): array {
    return $this->getApi()->apps()->getAll();
  }

  /**
   * View the details of a single OneSignal application.
   *
   * @param string $app_id
   *   The id of OneSignal application.
   *
   * @see https://documentation.onesignal.com/reference#view-an-app
   *
   * @return array
   *   Returns the details of a single OneSignal application.
   */
  public function getSingleApp(string $app_id): array {
    return $this->getApi()->apps()->getOne($app_id);
  }

  /**
   * View the details of multiple devices in one of your OneSignal apps.
   *
   * @see https://documentation.onesignal.com/reference#view-devices
   *
   * @return array
   *   Returns the details of multiple devices in one of your OneSignal apps.
   */
  public function getDevices(): array {
    return $this->getApi()->devices()->getAll();
  }

  /**
   * View the details of an existing device.
   *
   * In your configured OneSignal application.
   *
   * @param string $device_id
   *   The device id.
   *
   * @see https://documentation.onesignal.com/reference#view-device
   *
   * @return array
   *   Return the details of an existing device.
   */
  public function getDevice(string $device_id): array {
    return $this->getApi()->devices()->getOne($device_id);
  }

  /**
   * View the details of multiple notifications.
   *
   * @see https://documentation.onesignal.com/reference#view-notifications
   *
   * @return array
   *   Return the details of multiple notifications.
   */
  public function getNotifications(): array {
    return $this->getApi()->notifications()->getAll();
  }

  /**
   * Get the details of a single notification.
   *
   * @param string $id
   *   The notification id.
   *
   * @see https://documentation.onesignal.com/reference#view-notification
   *
   * @return array
   *   Return the details of a single notification.
   */
  public function getNotification(string $id): array {
    return $this->getApi()->notifications()->getOne($id);
  }

  /**
   * Create and send notifications or emails to a segment or individual users.
   *
   * You may target users in one of three ways using this method: by Segment,
   * by Filter, or by Device
   * (at least one targeting parameter must be specified)
   *
   * @param array $data
   *   The info to be send.
   *
   * @return array
   *   Return the notification.
   *
   * @see https://documentation.onesignal.com/reference#create-notification
   */
  public function addNotification(array $data = []): array {
    return $this->getApi()->notifications()->add($data);
  }

  /**
   * Stop a scheduled or currently outgoing notification.
   *
   * @param mixed $id
   *   The notification id.
   *
   * @see https://documentation.onesignal.com/reference#cancel-notification
   *
   * @return array
   *   Returns the notification stopped.
   */
  public function stopNotification($id): array {
    return $this->getApi()->notifications()->cancel($id);
  }

}
