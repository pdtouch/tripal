<?php

namespace Drupal\tripal\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Controller routines related to Tripal Entity and Tripal Entity Type UIs.
 */
class TripalEntityUIController extends ControllerBase {

  /**
   * Constructs the TripalEntityUIController.
   */
  public function __construct() {
  }

  /**
   * The Tripal Content Add page where content types are listed.
   *
   * Route: entity.tripal_entity.add_page
   * Template: tripal-entity-content-add-list.html.twig
   *
   * @return Drupal\Core\Render\Element
   *   Returns a rendered listing of Tripal Content Types linking to add forms.
   */
  public function tripalContentAddPage() {

    // Get a list of all types.
    $bundle_entities = \Drupal::entityTypeManager()
      ->getStorage('tripal_entity_type')
      ->loadByProperties([]);


    // Now compile them into variables to be used in twig.
    $bundles = [];
    foreach ($bundle_entities as $entity) {
      $category = $entity->getCategory();
      $bundles[$category]['title'] = $category;
      $bundles[$category]['members'][] = [
        'title' => $entity->getLabel(),
        'help' => $entity->getHelpText(),
        'url' => Url::fromRoute('entity.tripal_entity.add_form', ['tripal_entity_type' => $entity->id()]),
      ];
    }

    // If there are no tripal content types / bundles
    if (count($bundle_entities) <= 0) {
      //$url_vocab_management = Url::fromRoute('entity.tripal_vocab.collection');
      //$link = Link::fromTextAndUrl('creating a vocabulary',
      //          $url_vocab_management)->toString();

      $url_type_management = Url::fromRoute('entity.tripal_entity_type.add_form');
      $link = Link::fromTextAndUrl('creating one',
                $url_type_management)->toString();

      // Because this message contains a link, we need to render it before
      // displaying it using the messenger.
      $message = 'There are currently no Tripal Content Types, ' .
                 'please begin by ' . $link . '.';
      $rendered_message = \Drupal\Core\Render\Markup::create($message);

      // Display the message to create a vocabulary
      $messenger = \Drupal::messenger();
      $messenger->addMessage($rendered_message,'warning');
    }


    // Finally, let tripal-entity-content-add-list.html.twig add the markup.
    return [
      '#theme' => 'tripal_entity_content_add_list',
      '#types' => $bundles,
    ];
  }

  /**
   * Checks for to see if new fields need to be added to a Tripal Content Type.
   *
   * @todo call this from somewhere.
   * @todo update all code.
   */
  public function tripalCheckForFields($tripal_entity_type) {

    $bundle_name = $tripal_entity_type->id();
    $term = $tripal_entity_type->getTerm();

    //$added = tripal_create_bundle_fields($bundle, $term);
    //if (count($added) == 0) {
      //$this->messenger->addMessage('No new fields were added');
    //}
    //foreach ($added as $field_name) {
      //$this->messenger->addMessage('Added field: ' . $field_name);
    //}

    \Drupal::messenger()->addWarning(t('This functionality is not implemented yet.'));

    return $this->redirect('entity.tripal_entity.field_ui_fields',
      ['tripal_entity_type' => $bundle_name]);
  }
}
