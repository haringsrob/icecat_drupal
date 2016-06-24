<?php

namespace Drupal\icecat\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface IcecatMappingInterface.
 *
 * @package Drupal\icecat\Entity
 */
interface IcecatMappingInterface extends ConfigEntityInterface {

  /**
   * Gets the local entity value.
   */
  public function getMappingEntityType();

}
