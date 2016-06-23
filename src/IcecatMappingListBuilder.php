<?php

namespace Drupal\icecat;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class IcecatMappingListBuilder.
 *
 * @package Drupal\icecat
 */
class IcecatMappingListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['entity'] = $this->t('Entity');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['entity'] = $entity->getEntity();
    return $row + parent::buildRow($entity);
  }

}
