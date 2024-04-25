<?php

namespace Drupal\active_campaign_api_integration\Form;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @file
 * Registration form inforamtional fields.
 */

/**
 * Class to render all registration fields.
 *
 * @class Registration mapping class.
 */
class MapperFormRegistration extends FormBase {

  /**
   * Contruct for this class.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Object.
   */
  public function __construct(
    protected readonly EntityFieldManagerInterface $entityFieldManager ,
    ) {
  }

  /**
   * Build up dependencies.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container.
   *
   * @return MapperFormRegistration|static
   *   Buildup dependency.
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('entity_field.manager')
    );
  }

  /**
   * This form id is returned.
   *
   * @return string
   *   Form id returned
   */
  public function getFormId(): string {
    return "profiles_campaign_form";
  }

  /**
   * Build this form.
   *
   * @param array $form
   *   This form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   This formstate.
   *
   * @return array
   *   Returned array of fields definitions.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Load user registration fields.
    // Attach a library to the form element.
    $form['#attached']['library'][] = 'active_campaign_api_integration/manager_assets';

    $user_fields = $this->loadUserRegistrationFields();
    $form['user_fields'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Configuration for ActiveCampaign'),
      '#description' => $this->t("Please check the
      boxes that correspond to the field you
      want to have its data send to ActiveCampaign when user register, Note, this process of sending data will be done automatically.
      Flee free to disable this action by checking Disabled box. under Internal
      Settings"),
    ];
    $form['user_fields']['registration'] = [
      '#type' => 'fieldset',
      '#title' => $this->t("Registration Form Fields"),
    ];

    // Add checkboxes for user registration fields.
    foreach ($user_fields as $field) {
      if ($field['machine_name'] !== "init"
        && $field['machine_name'] !== "login"
        && $field['machine_name'] !== "changed"
        && $field['machine_name'] !== "status"
        && $field['machine_name'] !== "timezone"
        && $field['machine_name'] !== "pass"
        && $field['machine_name'] !== "preferred_admin_langcode"
        && $field['machine_name'] !== "preferred_langcode"
        && $field['machine_name'] !== "langcode"
        && $field['machine_name'] !== "uuid"
        && $field['machine_name'] !== "roles"
        && $field['machine_name'] !== "access"
        && $field['machine_name'] !== "default_langcode"
        && $field['machine_name'] !== "user_picture") {
        $form['user_fields']['registration'][$field['machine_name']] = [
          '#type' => 'checkbox',
          '#title' => $field['title'],
        ];
      }
    }
    $form['user_fields']['list_number'] = [
      '#type' => 'hidden',
      '#default_value' => '1',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * Validation of this form.
   *
   * @param array $form
   *   This form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   This formstate.
   *
   * @return void
   *   Returned nothing
   */
  public function validateForm(array &$form,
                               FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);
    if (empty($form_state->getValue("list_number"))) {
      $form_state->setErrorByName("list_number",
        "This can not be empty");
    }
  }

  /**
   * Handling this form submission.
   *
   * @param array $form
   *   This form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   This formstate.
   *
   * @return void
   *   Returns nothing.
   */
  public function submitForm(array &$form,
                             FormStateInterface $form_state): void {

    $fields = [];
    $list_number = $form_state->getValue("list_number") ?? NULL;
    if (!empty($list_number)) {
      $fields = $this->collectFieldsSave($form_state);
      $fields['list_id'] = $form_state->getValue("list_number") ?? 0;
    }
    $_SESSION['fields_campaign'] = $fields;
    $form_state->setRedirect("active_campaign_api_integration.campaign_attaching");
  }

  /**
   * Collecting registration fields.
   *
   * @return array
   *   Collection of all fields on registration form.
   */
  protected function loadUserRegistrationFields(): array {
    // Load user registration fields.
    $field_storage_configs = $this->entityFieldManager
      ->getFieldStorageDefinitions('user');
    $user_registration_fields = [];
    foreach ($field_storage_configs as $field_storage_config) {
      if ($field_storage_config instanceof BaseFieldDefinition) {
        $user_registration_fields[] = [
          'machine_name' => $field_storage_config->getName(),
          'title' => $field_storage_config->getLabel(),
          'type' => $field_storage_config->getType(),
        ];
      }
      if ($field_storage_config instanceof FieldStorageConfig) {
        $user_registration_fields[] = [
          'machine_name' => $field_storage_config->getName(),
          'title' => $field_storage_config->getLabel(),
          'type' => $field_storage_config->getType(),
        ];
      }
    }
    return $user_registration_fields;
  }

  /**
   * To collect and map all fields.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   This form state.
   *
   * @return array
   *   Returns collected fields.
   */
  private function collectFieldsSave(FormStateInterface $form_state): array {
    $user_fields = $this->loadUserRegistrationFields();
    $fields = [];

    foreach ($user_fields as $field) {
      $value = $form_state->getValue($field['machine_name']) ?? NULL;
      if (!empty($value)) {
        $fields['users'][] = [
          'machine_name' => $field['machine_name'],
          'label' => $field['title'],
        ];
      }
    }
    return $fields;
  }

}
