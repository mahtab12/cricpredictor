<?php

namespace Drupal\onesignal\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\onesignal\OneSignalService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The OneSignalNotificationForm class.
 *
 * @package Drupal\onesignal\Form
 */
class OneSignalNotificationForm extends FormBase {

  /**
   * The OneSignal Service.
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
   * Construct a OneSignalNotificationForm object.
   *
   * @param \Drupal\onesignal\OneSignalService $oneSignalService
   *   The OneSignal Service.
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
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'onesignal_notification_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL) {
    $notification = NULL;
    $language = $this->languageManager->getCurrentLanguage();
    if ($id) {
      $notification = $this->oneSignalService->getNotification($id);
    }

    $form['app_id'] = [
      '#type' => 'hidden',
      '#value' => $this->oneSignalService->getAppId(),
    ];

    if ($notification) {
      // Delivery Stats.
      $form['delivery_stats'] = [
        '#type' => 'details',
        '#title' => $this->t('Platform Delivery Stats'),
        '#open' => TRUE,
      ];
      $header = [
        'successful' => [
          'data' => $this->t('Successful'),
        ],
        'failed' => [
          'data' => $this->t('Failed'),
        ],
        'errored' => [
          'data' => $this->t('Errored'),
        ],
        'converted' => [
          'data' => $this->t('Converted'),
        ],
        'received' => [
          'data' => $this->t('Received'),
        ],
      ];
      $platforms = $notification['platform_delivery_stats'];
      foreach ($platforms as $platform_id => $platform_data) {
        $rows = [];
        $row = [
          'data' => [
            $platform_data['successful'],
            $platform_data['failed'],
            $platform_data['errored'],
            $platform_data['converted'],
            $platform_data['received'],
          ],
        ];
        $rows[] = $row;
        $form['delivery_stats'][$platform_id] = [
          '#theme' => 'table',
          '#caption' => strtoupper($platform_id),
          '#header' => $header,
          '#rows' => $rows,
        ];
      }
    }

    $form['headings'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Headings'),
      '#description' => $this->t("Web Push requires a heading but can be omitted from request since defaults to the Site Name set in OneSignal Settings."),
      '#default_value' => isset($notification['headings'][$language->getId()]) ? $notification['headings'][$language->getId()] : '',
      '#required' => TRUE,
    ];
    if ($notification) {
      $form['headings']['#attributes']['readonly'] = 'readonly';
    }

    // Required unless content_available=true or template_id is set.
    $form['contents'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Contents'),
      '#description' => $this->t("The notification's content (excluding the title), a map of language codes to text for each language."),
      '#default_value' => isset($notification['contents'][$language->getId()]) ? $notification['contents'][$language->getId()] : '',
      '#rows' => 5,
      '#resizable' => 'vertical',
      '#required' => TRUE,
    ];
    if ($notification) {
      $form['contents']['#attributes']['readonly'] = 'readonly';
    }

    $form['template_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Template Id'),
      '#description' => $this->t("Use a template you setup on our dashboard. The template_id is the UUID found in the URL when viewing a template on our dashboard."),
      '#default_value' => $notification['template_id'] ?? '',
    ];
    if ($notification) {
      $form['template_id']['#attributes']['readonly'] = 'readonly';
    }

    $form['web_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Web Url'),
      '#description' => $this->t('The URL to open in the browser when a user clicks on the notification. Same as url but only sent to web push platforms.'),
      '#default_value' => $notification['web_url'] ?? '',
    ];
    if ($notification) {
      $form['web_url']['#attributes']['readonly'] = 'readonly';
    }

    $form['platforms'] = [
      '#type' => 'details',
      '#title' => $this->t('Platform to Deliver To'),
      '#open' => TRUE,
      '#description' => $this->t('To only send to specific platforms, you may pass in true on one or more of these boolean parameters corresponding to the platform you wish to target. If you do so, all other platforms will be set to false and will not be delivered to.'),
    ];

    // isAnyWeb.
    $form['platforms']['isAnyWeb'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('isAnyWeb'),
      '#description' => $this->t('Indicates whether to send to all subscribed web browser users, including Chrome, Firefox, and Safari.'),
      '#default_value' => !empty($notification['isAnyWeb']),
    ];
    if ($notification) {
      $form['platforms']['isAnyWeb']['#attributes']['disabled'] = 'disabled';
    }

    // isChromeWeb.
    $form['platforms']['isChromeWeb'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('isChromeWeb'),
      '#description' => $this->t('Indicates whether to send to all Google Chrome, Chrome on Android, and Mozilla Firefox users registered under your Chrome & Firefox web push platform.'),
      '#default_value' => !empty($notification['isChromeWeb']),
    ];
    if ($notification) {
      $form['platforms']['isChromeWeb']['#attributes']['disabled'] = 'disabled';
    }

    // isChrome.
    $form['platforms']['isChrome'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('isChrome'),
      '#description' => $this->t('This flag is not used for web push Please see isChromeWeb for sending to web push users. This flag only applies to Google Chrome Apps & Extensions.'),
      '#default_value' => !empty($notification['isChrome']),
    ];
    if ($notification) {
      $form['platforms']['isChrome']['#attributes']['disabled'] = 'disabled';
    }

    if (empty($id)) {
      $form['submit'] = [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => $this->t('Create Notification'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $language = $this->languageManager->getCurrentLanguage();
    $values = $form_state->getValues();

    $data = [
      'app_id' => (string) $values['app_id'],
      'headings' => [
        $language->getId() => (string) $values['headings'],
      ],
      'contents' => [
        $language->getId() => (string) $values['contents'],
      ],
      'included_segments' => ['All'],
      'isChrome' => (bool) !empty($values['isChrome']),
      'isAnyWeb' => (bool) !empty($values['isAnyWeb']),
      'isChromeWeb' => (bool) !empty($values['isChromeWeb']),
    ];

    if (!empty($values['web_url'])) {
      $data['web_url'] = (string) $values['web_url'];
    }

    if (!empty($values['template_id'])) {
      $data['template_id'] = (string) $values['template_id'];
    }

    try {
      $response = $this->oneSignalService->addNotification($data);
      if ($response && !empty($response['id'])) {
        $this->messenger()
          ->addMessage($this->t('Notification saved successfully. Total recipients: %total', ['%total' => $response['recipients']]));
      }

      $options = [];
      if (!empty($language)) {
        $options['language'] = $language;
      }
      $form_state->setRedirect('onesignal.notifications', [], $options);
    }
    catch (\Exception $e) {
      $this->messenger()->addError($e->getMessage());
    }
  }

}
