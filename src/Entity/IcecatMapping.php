<?php

namespace Drupal\icecat\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Icecat mapping config entity.
 *
 * @ConfigEntityType(
 *   id ="icecat_mapping",
 *   label = @Translation("Icecat Mapping"),
 *   handlers = {
 *     "list_builder" = "Drupal\icecat\IcecatMappingListBuilder",
 *     "form" = {
 *       "default" = "Drupal\icecat\IcecatMappingForm",
 *       "add" = "Drupal\icecat\IcecatMappingForm",
 *       "edit" = "Drupal\icecat\IcecatMappingForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "icecat_mapping",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "delete-form" = "/admin/structure/icecat/mappings/{icecat_mapping}/delete",
 *     "edit-form" = "/admin/structure/icecat/mappings/{icecat_mapping}",
 *     "collection" = "/admin/structure/icecat/mappings",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "entity",
 *   }
 * )
 */
class IcecatMapping extends ConfigEntityBase implements IcecatMappingInterface {

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    return $this->get('entity');
  }

}
