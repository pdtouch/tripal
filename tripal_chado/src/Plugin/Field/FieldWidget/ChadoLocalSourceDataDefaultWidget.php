<?php

namespace Drupal\tripal_chado\Plugin\Field\FieldWidget;

use Drupal\tripal\TripalField\TripalWidgetBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tripal_chado\TripalField\ChadoWidgetBase;

/**
 * Plugin implementation of default Tripal string type widget.
 *
 * @FieldWidget(
 *   id = "chado_local_source_data_default_widget",
 *   label = @Translation("Chado Analysis Local Source Data Widget"),
 *   description = @Translation("A chado analysis default local source data widget"),
 *   field_types = {
 *     "chado_local_source_data_default"
 *   }
 * )
 */

class ChadoLocalSourceDataDefaultWidget extends ChadoWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    // Get the list of organisms.
    $analyses = [];
    $chado = \Drupal::service('tripal_chado.database');
    $query = $chado->select('analysis', 'a');
    $query->fields('a', ['analysis_id', 'sourcename', 'sourceuri', 'sourceversion']);
    $results = $query->execute();

    while ($analysis = $results->fetchObject()) {
      $analys_src = $analysis->sourcename ;

      if ($analysis->sourceuri) {
        $analys_src .= ' ' . $analysis->sourceuri;
      }
      if ($analysis->sourceversion) {
        $analys_src .= ' ' . $analysis->sourceversion;
      }
      $analyses[$analysis->analysis_id] = $analys_src;
    }

    $item_vals = $items[$delta]->getValue();
    $record_id = $item_vals['record_id'] ?? 0;
    $analysis_id = $item_vals['analysis_id'] ?? 0;

    $elements = [];
    $elements['record_id'] = [
      '#type' => 'value',
      '#default_value' => $record_id,
    ];    
    $elements['analysis_id'] = $element + [
      '#type' => 'select',
      '#options' => $analyses,
      '#default_value' => $analysis_id,    
      '#placeholder' => $this->getSetting('placeholder'),
      '#empty_option' => '-- Select --',
    ];

    return $elements;
  }
}
