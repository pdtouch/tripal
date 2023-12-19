<?php

namespace Drupal\Tests\tripal\Kernel;

use Drupal\Tests\tripal_chado\Kernel\ChadoTestKernelBase;
use Drupal\tripal\Services\TripalJob;

/**
 * Tests the publish service for chado-based content types.
 *
 * @group TripalPublish
 */
class TripalPublishServiceTest extends ChadoTestKernelBase {
  protected $defaultTheme = 'stark';

  protected static $modules = ['system', 'user', 'tripal', 'tripal_chado', 'views', 'field'];

  protected $connection;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Ensure we see all logging in tests.
    \Drupal::state()->set('is_a_test_environment', TRUE);

    // Grab the container.
    $container = \Drupal::getContainer();

    $this->installConfig('system');
    // ... we need entity types to publish them.
    $this->installEntitySchema('tripal_entity_type');
    $this->installEntitySchema('tripal_entity');
    // ... we need the config for tripal_chado since it defines the content types we will install.
    $this->installConfig('tripal_chado');
    // ... we need the tripal term tables
    $this->installSchema('tripal', ['tripal_id_space_collection', 'tripal_terms_idspaces', 'tripal_vocabulary_collection', 'tripal_terms_vocabs', 'tripal_terms']);

    // Get Chado in place
    $this->connection = $this->getTestSchema(ChadoTestKernelBase::PREPARE_TEST_CHADO);

    // Create a couple of organisms in chado to be published.
    for ($i=1; $i <= 3; $i++) {
      $this->connection->insert('1:organism')
        ->fields([
          'genus' => 'Tripalus',
          'species' => 'databasica ' . $i,
          'comment' => "Entry $i: we are adding a comment to ensure that we do have working fields that are not required.",
        ])->execute();
    }

    // Create a couple of projects in chado to be published.
    for ($i=1; $i <= 3; $i++) {
      $this->connection->insert('1:project')
        ->fields([
          'name' => 'Project No. ' . $i,
          'description' => "Entry $i: we are adding a comment to ensure that we do have working fields that are not required.",
        ])->execute();
    }

    // Create the terms for the field property storage types.
    $idsmanager = \Drupal::service('tripal.collection_plugin_manager.idspace');
    foreach(['OBI','local','TAXRANK','NCBITaxon','SIO','schema','data','NCIT','operation','OBCS','SWO','IAO'] as $termIdSpace) {
      $idsmanager->createCollection($termIdSpace, "chado_id_space");
    }
    $vmanager = \Drupal::service('tripal.collection_plugin_manager.vocabulary');
    foreach(['obi','local','taxonomic_rank','ncbitaxon','SIO','schema','EDAM','ncit','OBCS','swo','IAO'] as $termVocab) {
      $vmanager->createCollection($termVocab, "chado_vocabulary");
    }

    // Create the content types + fields that we need.
    $this->createContentTypeFromConfig('general_chado', 'organism', TRUE);
    $this->createContentTypeFromConfig('general_chado', 'project', TRUE);

  }

  /**
   * A very simple test to run the publish job and check it created entities
   * and populated fields.
   *
   * This test is not ideal but is better than nothing ;-)
   *
   * We are doing the test here to avoid mocking anything and to test
   * publishing of chado-focused content types.
   */
  public function testTripalPublishServiceSingleJob() {
    $drupal = \Drupal::service('database');

    // Submit the Tripal job by calling the callback directly.
    $current_user = \Drupal::currentUser();
    $values = ["schema_name" => $this->testSchemaName];
    $bundle = 'organism';
    $datastore = 'chado_storage';
    tripal_publish($bundle, $datastore, $values);

    // confirm the entities are added.
    $entities = \Drupal::entityTypeManager()->getStorage('tripal_entity')->loadByProperties(['type' => 'organism']);
    $this->assertCount(3, $entities,
      "We expected there to be the same number of organism entities as we inserted.");

    // Confirm there are records in the field tables.
    $tables = [
      'tripal_entity__organism_genus',
      'tripal_entity__organism_species',
      'tripal_entity__organism_comment',
    ];
    foreach ($tables as $table_name) {
      $query = $drupal->query('SELECT * FROM {' . $table_name . '}');
      $records = $query->fetchAll();
      $this->assertCount(3, $records,
        "We expected the number of records in the $table_name table to match the number of organisms we inserted.");
    }
  }

  /**
   * A very simple test to run TWO publish jobs and check it created entities
   * and populated fields.
   *
   * @see https://github.com/tripal/tripal/issues/1716
   *
   * This test is not ideal but is better than nothing ;-)
   *
   * We are doing the test here to avoid mocking anything and to test
   * publishing of chado-focused content types.
   */
  public function testTripalPublishService2Jobs() {
    $drupal = \Drupal::service('database');

    // Submit the Tripal job by calling the callback directly.
    $current_user = \Drupal::currentUser();
    $values = ["schema_name" => $this->testSchemaName];
    $bundle = 'organism';
    $datastore = 'chado_storage';
    tripal_publish($bundle, $datastore, $values);

    // confirm the entities are added.
    $entities = \Drupal::entityTypeManager()->getStorage('tripal_entity')->loadByProperties(['type' => 'organism']);
    $this->assertCount(3, $entities,
      "We expected there to be the same number of organism entities as we inserted.");

    // Check there are records in the field tables.
    $tables = [
      'tripal_entity__organism_genus',
      'tripal_entity__organism_species',
      'tripal_entity__organism_comment',
    ];
    foreach ($tables as $table_name) {
      $query = $drupal->query('SELECT * FROM {' . $table_name . '}');
      $records = $query->fetchAll();
      $this->assertCount(3, $records,
        "We expected the number of records in the $table_name table to match the number of organisms we inserted.");
    }

    // Submit the Tripal job by calling the callback directly.
    $bundle = 'project';
    tripal_publish($bundle, $datastore, $values);
  }
}
