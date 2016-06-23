<?php

namespace Drupal\icecat;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Class IcecatMappingForm.
 *
 * @package Drupal\icecat
 */
class IcecatMappingForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\icecat\Entity\IcecatMappingInterface $entity */
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => t('Label'),
      '#required' => TRUE,
      '#default_value' => $entity->label(),
    ];
    $form['entity'] = [
      '#type' => 'textarea',
      '#title' => t('Entity'),
      '#required' => TRUE,
      '#default_value' => $entity->getEntity(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $is_new = !$entity->getOriginalId();

    if ($is_new) {
      // Configuration entities need an ID manually set.
      $machine_name = \Drupal::transliteration()
        ->transliterate($entity->label(), LanguageInterface::LANGCODE_DEFAULT, '_');
      $entity->set('id', Unicode::strtolower($machine_name));

      drupal_set_message(t('The %label mapping has been created.', array('%label' => $entity->label())));
    }
    else {
      drupal_set_message(t('Updated the %label mapping.', array('%label' => $entity->label())));
    }

    $entity->save();

    // Redirect to edit form so we can populate colors.
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
  }

}
