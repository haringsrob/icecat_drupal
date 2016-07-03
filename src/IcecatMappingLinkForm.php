<?php

namespace Drupal\icecat;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Class IcecatMappingForm.
 *
 * @package Drupal\icecat
 */
class IcecatMappingLinkForm extends EntityForm {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The routematchinterface.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface;
   */
  protected $routeMatch;

  /**
   * Constructs a new IcecatMappingForm.
   */
  public function __construct(EntityTypeManager $entityTypeManager, EntityFieldManager $entityFieldManager, RouteMatchInterface $routeMatch) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\icecat\Entity\IcecatMappingLinkInterface $entity */
    $entity = $this->entity;

    // Get the mapping entity from the url.
    $mapping = $this->routeMatch->getParameters()->get('icecat_mapping');
    $mapping_entity = $this->entityTypeManager->getStorage('icecat_mapping')->load($mapping);

    // Our list of supported field types.
    $supported_field_types = [
      'string',
      'string_long',
      'integer',
    ];

    // Initialize the supported fields.
    $supported_fields = [];
    $mapping_link_storage = $this->entityTypeManager->getStorage('icecat_mapping_link');

    // Get all the available fields on the entity bundle.
    $base_fields = $this->entityFieldManager->getFieldDefinitions($mapping_entity->getMappingEntityType(), $mapping_entity->getMappingEntityBundle());

    // Loop and populate our supported fields list.
    foreach ($base_fields as $field) {
      if (in_array($field->getType(), $supported_field_types) &&
        !$field->isReadOnly() &&
        $mapping_entity->getDataInputField() !== $field->getName() &&
        ($entity->getLocalField() == $field->getName() || !$mapping_link_storage->loadByProperties([
          'mapping' => $mapping,
          'local_field' => $field->getName(),
        ])) &&
        // @todo: Add another way to handle this.
        is_string($field->getLabel())
      ) {
        $supported_fields[$field->getType()][$field->getName()] = $field->getLabel();
      }
    }

    $form['mapping'] = [
      '#type' => 'entity_autocomplete',
      '#title' => t('The parent mapping'),
      '#target_type' => 'icecat_mapping',
      '#default_value' => $mapping_entity ? $mapping_entity : '',
      '#required' => TRUE,
      '#disabled' => $mapping_entity ? TRUE : FALSE,
      '#size' => 55,
    ];

    $form['local_field'] = [
      '#type' => 'select',
      '#title' => t('Local field'),
      '#default_value' => $entity->getLocalField(),
      '#options' => $supported_fields,
      '#required' => TRUE,
      '#disabled' => empty($supported_fields) ? TRUE : FALSE,
    ];

    if (empty($supported_fields)) {
      $form_state->setErrorByName('local_field', $this->t('There are no available fields for mapping.'));
    }

    $remote_field_types = [
      'attribute' => $this->t('Attribute'),
      'specification' => $this->t('Specification'),
    ];

    $form['remote_field_type'] = [
      '#type' => 'select',
      '#title' => t('Remote field type'),
      '#default_value' => $entity->getRemoteFieldType(),
      '#options' => $remote_field_types,
      '#required' => TRUE,
      '#size' => 55,
    ];

    $form['remote_field'] = [
      '#type' => 'textfield',
      '#title' => t('Remote field'),
      '#default_value' => $entity->getRemoteField(),
      '#required' => TRUE,
      '#size' => 55,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $is_new = !$this->entity->getOriginalId();

    // Make sure we dont duplicate any mapping.
    if ($is_new && $this->entityTypeManager->getStorage('icecat_mapping_link')
        ->load($this->generateMachineName())
    ) {
      $form_state->setErrorByName('remote_field', $this->t('You already added a field with these properties'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $is_new = !$entity->getOriginalId();

    // If the bundle changed, we redirect to the edit page again. In other cases
    // we redirect to list.
    if ($is_new) {
      // Configuration entities need an ID manually set.
      $entity->set('id', Unicode::strtolower($this->generateMachineName()));

      // Also inform that the user cna now continue editing.
      drupal_set_message($this->t('Mapping link has been created'));
    }

    // Save the entity.
    $entity->save();
  }

  /**
   * Generates the machine name to use.
   */
  private function generateMachineName() {
    return \Drupal::transliteration()
      ->transliterate($this->routeMatch->getParameters()
          ->get('icecat_mapping') . '__' . $this->entity->getLocalField() . '_' . $this->entity->getRemoteField(), LanguageInterface::LANGCODE_DEFAULT, '_');
  }

}
