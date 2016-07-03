<?php

namespace Drupal\icecat\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Icecat mapping config entity.
 *
 * @ConfigEntityType(
 *   id ="icecat_mapping_link",
 *   label = @Translation("Icecat Mapping Link"),
 *   handlers = {
 *     "list_builder" = "Drupal\icecat\IcecatMappingLinkListBuilder",
 *     "form" = {
 *       "default" = "Drupal\icecat\IcecatMappingLinkForm",
 *       "add" = "Drupal\icecat\IcecatMappingLinkForm",
 *       "edit" = "Drupal\icecat\IcecatMappingLinkForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "icecat_mapping_link",
 *   admin_permission = "manage icecat mappings",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/icecat/mappings/{icecat_mapping}/links/add",
 *     "delete-form" = "/admin/structure/icecat/mappings/{icecat_mapping}/links/{icecat_mapping_link}/delete",
 *     "edit-form" = "/admin/structure/icecat/mappings/{icecat_mapping}/links/{icecat_mapping_link}",
 *     "collection" = "/admin/structure/icecat/mappings/{icecat_mapping}/links",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "local_field",
 *     "remote_field",
 *     "mapping",
 *   }
 * )
 */
class IcecatMappingLink extends ConfigEntityBase implements IcecatMappingLinkInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getLocalField() . '::' . $this->getRemoteField();
  }

  /**
   * {@inheritdoc}
   */
  public function urlRouteParameters($rel) {
    $parameters = parent::urlRouteParameters($rel);
    if (!isset($parameters['icecat_mapping'])) {
      $parameters['icecat_mapping'] = \Drupal::service('current_route_match')
        ->getParameters()
        ->get('icecat_mapping');
    }
    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocalField() {
    return $this->get('local_field');
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteField() {
    return $this->get('remote_field');
  }

  /**
   * {@inheritdoc}
   */
  public function getMapping() {
    return $this->get('mapping');
  }

}
