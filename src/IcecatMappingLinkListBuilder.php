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
    $header['mapping'] = $this->t('Mapping');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['mapping'] = $entity->getMapping();
    return $row + parent::buildRow($entity);
  }

}
