<?php

namespace Drupal\icecat;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class IcecatMappingLinkListBuilder.
 *
 * @package Drupal\icecat
 */
class IcecatMappingLinkListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['local_field'] = $this->t('Local field');
    $header['remote_field'] = $this->t('Remote field');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['local_field'] = $entity->getLocalField();
    $row['remote_field'] = $entity->getRemoteField();
    return $row + parent::buildRow($entity);
  }

}
