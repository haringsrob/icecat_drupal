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

    /** @var \Drupal\icecat\Entity\IcecatMappingInterface $entity */
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#required' => TRUE,
      '#default_value' => $entity->label(),
    ];

    // Get the mapping entity from the url.
    $mapping_entity = $this->entityTypeManager->getStorage('icecat_mapping')->load($this->routeMatch->getParameters()->get('icecat_mapping'));

    // Get the base fields for the entity type.
    $base_fields = $this->entityFieldManager->getBaseFieldDefinitions($mapping_entity->getMappingEntityType());

    // Our list of supported field types.
    $supported_field_types = [
      'string',
    ];

    // Initialize the supported fields.
    $supported_fields = [];

    foreach ($base_fields as $field) {
      if (in_array($field->getType(), $supported_field_types)) {
        $supported_fields[$field->getType()][] = $field->getName();
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
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $is_new = !$entity->getOriginalId();

    // If the bundle changed, we redirect to the edit page again. In other cases
    // we redirect to list.
    if ($is_new) {
      // Configuration entities need an ID manually set.
      $machine_name = \Drupal::transliteration()
        ->transliterate($entity->label(), LanguageInterface::LANGCODE_DEFAULT, '_');
      $entity->set('id', Unicode::strtolower($machine_name));

      // Also inform that the user cna now continue editing.
      drupal_set_message($this->t('Mapping link has been added'));
    }

    // Save the entity.
    $entity->save();
  }

}
