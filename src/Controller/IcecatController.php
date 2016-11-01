<?php

namespace Drupal\icecat\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\icecat\IcecatFetcher;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class IcecatController.
 *
 * @package Drupal\icecat\Controller
 */
class IcecatController implements ContainerInjectionInterface {

  /**
   * The entity used in the controller.
   *
   * @var Entity
   */
  private $entity;

  /**
   * The entity mapping object.
   *
   * @var \Drupal\icecat\Entity\IcecatMapping
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
  private function hasMapping() {
    // @todo: Ignore for non nodes.. But we want to allow this..
    if (!$this->entity instanceof ContentEntityInterface) {
      return FALSE;
    }
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
   * Gets the mapping links.
   *
   * @return EntityInterface
   *   The mapping links.
   */
  private function getMappingLinks() {
    $mapping_link_storage = $this->entityTypeManager->getStorage('icecat_mapping_link');
    $mapping_links = $mapping_link_storage->loadByProperties([
      'mapping' => $this->entityMapping->id(),
    ]);
    return $mapping_links;
  }

  /**
   * Maps the data.
   */
  public function mapEntityData() {
    if ($this->hasMapping()) {
      $entity = $this->entity;
      if ($ean_code = $entity->get($this->entityMapping->getDataInputField())->getValue()) {
        // Initialize a new fetcher object.
        $fetcherSession = new IcecatFetcher($ean_code[0]['value']);
        $result = $fetcherSession->getResult();

        foreach ($this->getMappingLinks() as $mapping) {
          if ($entity->get($mapping->getLocalField())) {
            $entity->set($mapping->getLocalField(), $result->getSpec($mapping->getRemoteField()));
          }
        }
      }
    }
  }

}