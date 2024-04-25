<?php

namespace Drupal\active_campaign_api_integration\Form;

use Drupal\active_campaign_api_integration\Plugin\ApiCaller;
use Drupal\active_campaign_api_integration\Plugin\StoragesHandlers;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @file
 * Mapper for registrations.
 */

/**
 * Attaching Active fields with form fields.
 *
 * @class MapperFormAttachment to attach Active fields.
 */
class MapperFormAttachment extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    private readonly Connection $database,
    protected $messenger
  ) {

  }

  /**
   * To build up dependencies.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container.
   *
   * @return static
   *   Static of buildUp class.
   */
  public static function create(ContainerInterface $container): static {
    return new self(
      $container->get('database'),
      $container->get('messenger')
    );
  }

  /**
   * Form id is returned.
   *
   * @return string
   *   Returns form id.
   */
  public function getFormId() {
    return "campaign_attachments_mapping";
  }

  /**
   * Build up this form.
   *
   * @param array $form
   *   This form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   This form state.
   *
   * @return array
   *   Returns array of this form definition.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['user_fields'] = [
      '#type' => 'fieldset',
      '#title' => 'Configuration for ActiveCampaign',
      '#description' => $this->t("ActiveCampaign have fields
      which you have to attach here"),
    ];

    $form['user_fields']['default_fields'] = [
      '#type' => 'fieldset',
      '#title' => 'Default fields on ActiveCampaign',
      '#description' => $this->t("You are required to set for
      Firstname and Email."),
    ];

    $list = [
      "firstName" => "First Name",
      "lastName" => "Last Name",
      "email" => "Email",
      "phone" => "Phone",
    ];

    $fields = (new ApiCaller())->customFieldsList();

    foreach ($list as $key => $item) {
      $required = $key === "FIRSTNAME" || $key === "EMAIL";
      $form['user_fields']['default_fields']["$key"] = [
        '#type' => 'select',
        '#title' => $item,
        '#options' => ['' => $this->t('None')] + $this->getUserRoleOptions(),
        '#required' => $required,
        '#default_value' => '',
      ];
    }

    $form['user_fields']['custom_fields'] = [
      '#type' => 'fieldset',
      '#title' => 'Custom fields on ActiveCampaign',
    ];

    foreach ($fields as $field) {
      $form['user_fields']['custom_fields'][$field['id']] = [
        '#type' => 'select',
        '#title' => $field['title'] ?? "Unknown",
        '#options' => ['' => $this->t('None')] + $this->getUserRoleOptions(),
        '#required' => $required,
        '#default_value' => '',
      ];
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    // Attach a library to the form element.
    $form['#attached']['library'][] = 'active_campaign_api_integration/manager_assets';

    return $form;

  }

  /**
   * Handles this form submission.
   *
   * @param array $form
   *   This form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   This form state.
   *
   * @return void
   *   Returns nothing
   *
   * @throws \Exception
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $list = [
      "firstName" => "First Name",
      "lastName" => "Last Name",
      "email" => "Email",
      "phone" => "Phone",
    ];
    $data = [];
    $fields = (new ApiCaller())->customFieldsList();
    foreach ($fields as $item) {
      $list[$item['id']] = $item['title'];
    }

    foreach ($list as $key => $item) {
      if (!empty($form_state->getValue($key))) {
        $data[$key] = $form_state->getValue($key);
      }
    }

    $storage = new StoragesHandlers($this->database);
    $final['map'] = json_encode($data, JSON_PRETTY_PRINT);
    $final['list'] = intval($_SESSION['fields_campaign']['list_id']);
    $final['active'] = "no";
    $final['name'] = "Created - " . (new \DateTime("now"))
      ->format("m d, Y h:i");
    $result = $storage->saveRegistrationMapping($final);
    if (!empty($result)) {
      if ($final['active'] === "yes") {
        $this->messenger->addMessage("ActiveCampaign Configurations Saved with
        name ({$final['name']}) and set
        as active.");
      }
      else {
        $this->messenger->addMessage("ActiveCampaign Configurations Saved with
        name ({$final['name']}). If you want to set it active go to
        ActiveCampaign Mapping Listing");
      }
    }
  }

  /**
   * Registration form fields array.
   *
   * @return array
   *   Array of fields keys with labels.
   */
  private function getUserRoleOptions(): array {
    $registration = $_SESSION['fields_campaign'] ?? [];
    $quickList = [];
    if (!empty($registration['users'])) {
      foreach ($registration['users'] as $value) {
        if ($value['label'] instanceof TranslatableMarkup) {
          $quickList[$value['machine_name']] = $value['label']->__toString();
        }
        else {
          $quickList[$value['machine_name']] = $value['label'];
        }
      }
    }
    return $quickList;
  }

}
