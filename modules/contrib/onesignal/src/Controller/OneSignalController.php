<?php

namespace Drupal\onesignal\Controller;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\onesignal\OneSignalService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The OneSignalController class.
 *
 * @package Drupal\onesignal\Controller
 */
class OneSignalController extends ControllerBase {

  /**
   * The OneSignal service.
   *
   * @var \Drupal\onesignal\OneSignalService
   */
  protected $oneSignalService;

  /**
   * Class responsible for providing language support on language-unaware sites.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Construct a OneSignalController object.
   *
   * @param \Drupal\onesignal\OneSignalService $oneSignalService
   *   The OneSignal service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The responsible for providing language support on language-unaware sites.
   */
  public function __construct(OneSignalService $oneSignalService, LanguageManagerInterface $language_manager) {
    $this->oneSignalService = $oneSignalService;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('onesignal'),
      $container->get('language_manager')
    );
  }

  /**
   * List of available notifications.
   *
   * @return array
   *   Return a list of available notifications.
   */
  public function getNotifications(): array {
    $build = [];

    if ($this->oneSignalService->getApi()) {

      $data = $this->oneSignalService->getNotifications();

      $header = [
        'id' => [
          'data' => $this->t('Notification ID'),
        ],
        'contents' => [
          'data' => $this->t('Contents'),
        ],
        'send_after' => [
          'data' => $this->t('Send after'),
        ],
        'completed_at' => [
          'data' => $this->t('Completed at'),
        ],
        'failed' => [
          'data' => $this->t('Failed'),
        ],
        'successful' => [
          'data' => $this->t('Successful'),
        ],
        'remaining' => [
          'data' => $this->t('Remaining'),
        ],
        'canceled' => [
          'data' => $this->t('Canceled'),
        ],
        'operations' => [
          'data' => $this->t('Operations'),
          'colspan' => 2,
        ],
      ];

      $rows = [];
      foreach ($data['notifications'] as $notification) {
        $view_url = Url::fromRoute('onesignal.notification_edit', ['id' => $notification['id']], ['attributes' => ['class' => 'button']]);
        $stop_url = Url::fromRoute('onesignal.notification_stop', ['id' => $notification['id']], ['attributes' => ['class' => 'button']]);
        $row = [
          'data' => [
            $notification['id'],
            $notification['contents'][$this->languageManager
              ->getCurrentLanguage()
              ->getId()],
            $notification['send_after'],
            $notification['completed_at'],
            $notification['failed'],
            $notification['successful'],
            $notification['remaining'],
            ($notification['canceled']) ? $this->t('Yes') : $this->t('No'),
            Link::fromTextAndUrl($this->t('View Notification'), $view_url),
            ($notification['remaining'] > 0) ? Link::fromTextAndUrl($this->t('Stop Notification'), $stop_url) : '',
          ],
        ];
        $rows[] = $row;
      }

      $build = [
        'table' => [
          '#theme' => 'table',
          '#header' => $header,
          '#rows' => $rows,
        ],
      ];
    }

    return $build;
  }

  /**
   * List of available devices.
   *
   * @return array
   *   Returns a list of available devices.
   */
  public function getDevices(): array {

    $build = [];

    if ($this->oneSignalService->getApi()) {

      $data = $this->oneSignalService->getDevices();

      $header = [
        'id' => [
          'data' => $this->t('Device Id'),
        ],
        'language' => [
          'data' => $this->t('Language'),
        ],
        'last_active' => [
          'data' => $this->t('Last active'),
        ],
        'playtime' => [
          'data' => $this->t('Play time'),
        ],
        'created_at' => [
          'data' => $this->t('Created at'),
        ],
        'ip' => [
          'data' => $this->t('Ip Address'),
        ],
      ];

      $rows = [];
      foreach ($data['players'] as $device) {
        $row = [
          'data' => [
            $device['id'],
            $device['language'],
            $device['last_active'],
            $device['playtime'],
            $device['created_at'],
            $device['ip'],
          ],
        ];
        // Add row.
        $rows[] = $row;
      }

      $build = [
        'table' => [
          '#theme' => 'table',
          '#header' => $header,
          '#rows' => $rows,
        ],
      ];
    }

    return $build;
  }

}
