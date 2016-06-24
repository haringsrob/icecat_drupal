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
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "icecat_mapping",
 *   admin_permission = "manage icecat mappings",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/icecat/mappings/add",
 *     "delete-form" = "/admin/structure/icecat/mappings/{icecat_mapping}/delete",
 *     "edit-form" = "/admin/structure/icecat/mappings/{icecat_mapping}",
 *     "collection" = "/admin/structure/icecat/mappings",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "entity_type",
 *   }
 * )
 */
class IcecatMapping extends ConfigEntityBase implements IcecatMappingInterface {

  /**
   * {@inheritdoc}
   */
  public function getMappingEntityType() {
    return $this->get('entity_type');
  }

}
