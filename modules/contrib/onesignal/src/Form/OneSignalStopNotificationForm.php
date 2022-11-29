<?php

namespace Drupal\onesignal\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\onesignal\OneSignalService;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a confirmation form to confirm to stop OneSignal notification.
 */
class OneSignalStopNotificationForm extends ConfirmFormBase {

  /**
   * ID of the notification.
   *
   * @var string
   */
  protected $id;

  /**
   * The OneSignal Service.
   *
   * @var \Drupal\onesignal\OneSignalService
   */
  protected $oneSignalService;

  /**
   * Construct a OneSignalStopNotificationForm object.
   *
   * @param \Drupal\onesignal\OneSignalService $oneSignalService
   *   The OneSignal Service.
   */
  public function __construct(OneSignalService $oneSignalService) {
    $this->oneSignalService = $oneSignalService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('onesignal'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $id = NULL) {
    $this->id = $id;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = NULL;
    try {
      $response = $this->oneSignalService->stopNotification($this->id);
    }
    catch (\Exception $e) {
      $this->messenger()->addError($e->getMessage());
    }
    if ($response) {
      $this->messenger()->addMessage($this->t('Notification stopped successfully.'));
    }
    $options = [];
    $language = \Drupal::languageManager()->getCurrentLanguage();
    if (!empty($language)) {
      $options['language'] = $language;
    }
    $form_state->setRedirect('onesignal.notifications', [], $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return "onesignal_stop_notification_form";
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('onesignal.notifications');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you want to stop notification %id?', ['%id' => $this->id]);
  }

}
