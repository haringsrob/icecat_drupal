<?php

namespace Drupal\icecat;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

/**
 * Class IcecatMappingForm.
 *
 * @package Drupal\icecat
 */
class IcecatMappingForm extends EntityForm {

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
   * Constructs a new IcecatMappingForm.
   */
  public function __construct(EntityTypeManager $entityTypeManager, EntityFieldManager $entityFieldManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
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

    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => t('Type of entity to map to'),
      '#options' => \Drupal::entityManager()->getEntityTypeLabels(TRUE),
      '#default_value' => $entity->getMappingEntityType(),
      '#required' => TRUE,
      '#size' => 1,
    ];

    if ($entity->getMappingEntityType()) {
      // Get the base fields for the entity type.
      $base_fields = $this->entityFieldManager->getBaseFieldDefinitions($entity->getMappingEntityType());

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

      if (!empty($supported_fields)) {
        // Add the form element.
        $form['field'] = [
          '#type' => 'select',
          '#title' => t('Left field'),
          '#options' => $supported_fields,
          '#default_value' => '',
          '#required' => TRUE,
          '#size' => 1,
        ];
      }

    }
    else {
      $form['info'] = [
        '#markup' => $this->t('Please press save and complete additional configurations'),
      ];
    }

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
    if ($is_new || $form_state->getValue('entity') !== $entity->load($entity->getOriginalId())
        ->getMappingEntityType()
    ) {
      // Configuration entities need an ID manually set.
      $machine_name = \Drupal::transliteration()
        ->transliterate($entity->label(), LanguageInterface::LANGCODE_DEFAULT, '_');
      $entity->set('id', Unicode::strtolower($machine_name));

      // Show a message when it's a new entity.
      if ($is_new) {
        drupal_set_message($this->t('The %label mapping has been created.', array('%label' => $entity->label())));
      }

      // Also inform that the user cna now continue editing.
      drupal_set_message($this->t('You can now continue the mapping configuration.'));

      // Set the redirect.
      $form_state->setRedirect(
        'entity.icecat_mapping.edit_form',
        ['icecat_mapping' => $entity->id()]
      );
    }
    else {
      // If it's a normal edit, we redirect to the list.
      $form_state->setRedirectUrl($this->entity->toUrl('collection'));
      drupal_set_message($this->t('Updated the %label mapping.', array('%label' => $entity->label())));
    }

    // Save the entity.
    $entity->save();
  }

}
