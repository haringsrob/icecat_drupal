<?php

namespace Drupal\icecat\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use haringsrob\Icecat\Model\Fetcher;
use haringsrob\Icecat\Model\Result;

/**
 * Class IcecatController.
 *
 * @package Drupal\icecat\Controller
 */
class IcecatController implements ContainerInjectionInterface {

  /**
   * The entity used in the controller.
   *
   * @var Entity;
   */
  private $entity;

  /**
   * The entity mapping object.
   *
   * @var \Drupal\icecat\Entity\IcecatMapping;
   */
  private $entityMapping;

  /**
   * Initializes a IcecatController instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Sets the entity to work with.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to use.
   */
  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * Checks if the entity has mapping.
   *
   * @return bool
   *   True if mapping available. False otherwise.
   */
  public function hasMapping() {
    $mapping_link_storage = $this->entityTypeManager->getStorage('icecat_mapping');
    $mappings = $mapping_link_storage->loadByProperties([
      'entity_type' => $this->entity->getEntityTypeId(),
      'entity_type_bundle' => $this->entity->getType(),
    ]);
    if (!empty($mappings)) {
      $this->entityMapping = reset($mappings);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Maps the data.
   */
  public function mapEntityData() {
    if ($this->hasMapping()) {
      if ($ean_code = $this->entity->get($this->entityMapping->getDataInputField())->getValue()) {
        $config = \Drupal::config('icecat.settings');

        if ($config->get('username') && $config->get('password')) {
          // Initialize a new fetcher object.
          $fetcherSession = new Fetcher(
            $config->get('username'),
            $config->get('password'),
            $ean_code[0]['value'],
            $this->entity->language()->getId()
          );
          $fetcherSession->fetchBaseData();

          $icecatResult = new Result($fetcherSession->getBaseData());
        }
      }
    }
  }
}
