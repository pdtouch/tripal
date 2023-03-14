<?php

namespace Drupal\tripal_chado\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\tripal_chado\TripalField\ChadoFormatterBase;

/**
 * Plugin implementation of default Tripal string type formatter.
 *
 * @FieldFormatter(
 *   id = "chado_local_source_data_default_formatter",
 *   label = @Translation("Chado Analysis local source data Formatter"),
 *   description = @Translation("Chado Analysis local source data formatter"),
 *   field_types = {
 *     "chado_local_source_data_default"
 *   }
 * )
 */
class ChadoLocalSourceDataDefaultFormatter extends ChadoFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach($items as $delta => $item) {
      $elements[$delta] = [
        "#markup" => $item->get('label')->getString()
      ];
    }

    return $elements;
  }
}
