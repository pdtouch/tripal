<?php

namespace Drupal\tripal\TripalStorage;

use Drupal\tripal\TripalStorage\StoragePropertyTypeBase;

/**
 * Defines the text Tripal storage property type. A text type is a string with
 * unlimited length.
 */
class TextStoragePropertyType extends StoragePropertyTypeBase {

  /**
   * Constructs a new text tripal storage property type.
   *
   * @param string entityType
   *   The entity type associated with this property type.
   *
   * @param string fieldType
   *   The field type associated with this property type.
   *
   * @param string key
   *   The key associated with this property type.
   */
  public function __construct($entityType,$fieldType,$key) {
    parent::__construct($entityType,$fieldType,$key,"text");
  }

}
