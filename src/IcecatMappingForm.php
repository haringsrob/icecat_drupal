<?php

namespace Drupal\icecat;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
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
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManager $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeRepository $entityTypeRepository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInterface
   *   The entity bundle interface.
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

    $form['example_ean'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Example ean(s)'),
      '#description' => $this->t('A comma separated list of example ean codes. These products will be used for mapping configuration'),
      '#required' => FALSE,
      '#default_value' => $entity->getExampleEans(),
    ];

    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type of entity to map to'),
      '#options' => $this->entityTypeRepository->getEntityTypeLabels(TRUE),
      '#default_value' => $entity->getMappingEntityType(),
      '#required' => TRUE,
      '#size' => 1,
      '#ajax' => [
        'callback' => [$this, 'updateBundles'],
        'event' => 'change',
        'wrapper' => 'entity_type_bundle_ajax',
      ],
    ];

    $form['entity_type_bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Type of entity bundle to use'),
      '#default_value' => $entity->getMappingEntityBundle(),
      '#required' => TRUE,
      '#prefix' => '<div id="entity_type_bundle_ajax">',
      '#suffix' => '</div>',
      '#ajax' => [
        'callback' => [$this, 'updateInputField'],
        'event' => 'change',
        'wrapper' => 'data_input_field_ajax',
      ],
    ];

    $form['data_input_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Select the data source field.'),
      '#description' => $this->t('Here you can select the data source, this should contain the EAN code of the product.'),
      '#default_value' => $entity->getDataInputField(),
      '#required' => TRUE,
      '#prefix' => '<div id="data_input_field_ajax">',
      '#suffix' => '</div>',
    ];

    $this->updateBundles($form, $form_state);
    $this->updateInputField($form, $form_state);

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
    if ($is_new || $form_state->getValue('entity_type') !== $entity->load($entity->getOriginalId())->getMappingEntityType()) {
      // Configuration entities need an ID manually set.
      $machine_name = \Drupal::transliteration()->transliterate($entity->label(), LanguageInterface::LANGCODE_DEFAULT, '_');
      $entity->set('id', Unicode::strtolower($machine_name));

      // Show a message when it's a new entity.
      if ($is_new) {
        drupal_set_message($this->t('The %label mapping has been created.', ['%label' => $entity->label()]));
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
      drupal_set_message($this->t('Updated the %label mapping.', ['%label' => $entity->label()]));
    }

    // Save the entity.
    $entity->save();
  }

  /**
   * Updates the bundle field data.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The updated form element.
   */
  public function updateBundles(array &$form, FormStateInterface $form_state) {
    // Initialize the bundle list.
    $bundle_list = [' - select - '];

    // Get the user input.
    $input = $form_state->getUserInput();

    // Get the input or default value.
    $entity_type = $input['_drupal_ajax'] ? $input['entity_type'] : $this->entity->getMappingEntityType();

    if (!empty($entity_type) && $bundles = $this->entityTypeBundleInterface->getBundleInfo($entity_type)) {
      foreach ($bundles as $machine_name => $info) {
        $bundle_list[$machine_name] = $info['label'];
      }
    }

    // Update the form element.
    $form['entity_type_bundle']['#options'] = $bundle_list;

    // When this is updated. We have to update both.
    if (!empty($bundle_list) && $input['_triggering_element_name'] == 'entity_type') {
      // Update the input field with the new data.
      $this->updateInputField($form, $form_state, reset(array_keys($bundle_list)));

      /** @var \Drupal\Core\Render\RendererInterface $renderer */
      $ajax_response = new AjaxResponse();
      $ajax_response->addCommand(new HtmlCommand('#entity_type_bundle_ajax', $form['entity_type_bundle']));
      $ajax_response->addCommand(new HtmlCommand('#data_input_field_ajax', $form['data_input_field']));

      return $ajax_response;
    }

    // Last case is the normal return.
    return $form['entity_type_bundle'];
  }

  /**
   * Updates the input field data.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The updated form element.
   */
  public function updateInputField(array &$form, FormStateInterface $form_state, $default_value = NULL) {
    // Initialize the supported fields.
    $supported_fields = [' - select - '];

    // Get the user input.
    $input = $form_state->getUserInput();

    // Get the input or default values.
    $entity_type = $input['_drupal_ajax'] ? $input['entity_type'] : $this->entity->getMappingEntityType();

    if ($default_value && !$input['entity_type_bundle']) {
      $entity_bundle = $default_value;
    }
    else {
      $entity_bundle = $input['_drupal_ajax'] ? $input['entity_type_bundle'] : $this->entity->getMappingEntityBundle();
    }

    if (!empty($entity_type) && !empty($entity_bundle)) {
      // @todo: Move this to a global variable or constant.
      $supported_field_types = [
        'string',
        'string_long',
      ];

      // Get the base fields.
      $base_fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $entity_bundle);

      /* @var $field \Drupal\Core\Field\BaseFieldDefinition */
      foreach ($base_fields as $field) {
        if (
          !$field->isReadOnly() &&
          in_array($field->getType(), $supported_field_types) &&
          is_string($field->getLabel())
        ) {
          $supported_fields[$field->getName()] = $field->getLabel();
        }
      }
    }

    $form['data_input_field']['#options'] = $supported_fields;
    return $form['data_input_field'];
  }

}
