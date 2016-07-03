<?php

namespace Drupal\icecat;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeRepository;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;

/**
 * Class IcecatMappingForm.
 *
 * @package Drupal\icecat
 */
class IcecatMappingForm extends EntityForm {

  /**
   * The entity type repository.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepository
   */
  protected $entityTypeRepository;

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
   * The entity type bundle interface.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInterface;

  /**
   * Constructs a new IcecatMappingForm.
   */
  public function __construct(EntityTypeManager $entityTypeManager, EntityFieldManager $entityFieldManager, EntityTypeRepository $entityTypeRepository, EntityTypeBundleInfoInterface $entityTypeBundleInterface) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeRepository = $entityTypeRepository;
    $this->entityTypeBundleInterface = $entityTypeBundleInterface;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.repository'),
      $container->get('entity_type.bundle.info')
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
      '#title' => $this->t('Label'),
      '#required' => TRUE,
      '#default_value' => $entity->label(),
    ];

    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type of entity to map to'),
      '#options' => $this->entityTypeRepository->getEntityTypeLabels(TRUE),
      '#default_value' => $entity->getMappingEntityType(),
      '#required' => TRUE,
      '#size' => 1,
    ];

    if ($entity->getMappingEntityType()) {

      if ($bundles = $this->entityTypeBundleInterface->getBundleInfo($entity->getMappingEntityType())) {
        $bundle_list = [];
        foreach ($bundles as $machine_name => $info) {
          $bundle_list[$machine_name] = $info['label'];
        }

        $form['entity_type_bundle'] = [
          '#type' => 'select',
          '#title' => $this->t('Type of entity bundle to use'),
          '#options' => $bundle_list,
          '#default_value' => $entity->getMappingEntityBundle(),
          '#required' => TRUE,
          '#size' => 1,
        ];
        // Only after we have received the entity bundle, we can select the
        // source data field.
        if ($entity->getMappingEntityBundle()) {

          // @todo: Move this to a global variable or constant.
          $supported_field_types = [
            'integer',
          ];

          // Initialize the supported fields.
          $supported_fields = [];

          // Get the base fields.
          $base_fields = $this->entityFieldManager->getFieldDefinitions($entity->getMappingEntityType(), $entity->getMappingEntityBundle());

          foreach ($base_fields as $field) {
            if (
              in_array($field->getType(), $supported_field_types) &&
              is_string($field->getLabel())
            ) {
              $supported_fields[$field->getType()][$field->getName()] = $field->getLabel();
            }
          }

          $form['data_input_field'] = [
            '#type' => 'select',
            '#title' => $this->t('Select the data source field.'),
            '#description' => $this->t('Here you can select the data source, this should contain the EAN code of the product.'),
            '#options' => $supported_fields,
            '#default_value' => $entity->getMappingEntityBundle(),
            '#required' => TRUE,
            '#size' => 1,
          ];
        }

      }
    }
    else {
      $form['info'] = [
        '#markup' => $this->t('Please press save to add the bundle.'),
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
    if ($is_new || $form_state->getValue('entity_type') !== $entity->load($entity->getOriginalId())
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
