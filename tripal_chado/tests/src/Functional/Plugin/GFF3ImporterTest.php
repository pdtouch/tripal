<?php

namespace Drupal\Tests\tripal_chado\Functional;

use Drupal\tripal_chado\TripalStorage\ChadoIntStoragePropertyType;
use Drupal\tripal_chado\TripalStorage\ChadoVarCharStoragePropertyType;
use Drupal\tripal_chado\TripalStorage\ChadoTextStoragePropertyType;
use Drupal\tripal\TripalStorage\StoragePropertyValue;
use Drupal\tripal\TripalVocabTerms\TripalTerm;
use Drupal\Tests\tripal_chado\Functional\MockClass\FieldConfigMock;

// FROM OLD CODE:
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Database\Database;
use Drupal\tripal_chado\api\ChadoSchema;
use GFF3Importer;

/**
 * Tests for the GFF3Importer class
 *
 * @group Tripal
 * @group Tripal Chado
 * @group Tripal Chado ChadoStorage
 */
class GFF3ImporterTest extends ChadoTestBrowserBase
{

  /**
   * Confirm basic GFF importer functionality.
   *
   * @group gff
   */
  public function testGFFImporterSimpleTest()
  {
    // GFF3 Specifications document: http://gmod.org/wiki/GFF3
    
    // Public schema connection
    $public = \Drupal::database();

    // Installs up the chado with the test chado data
    $chado = $this->getTestSchema(ChadoTestBrowserBase::PREPARE_TEST_CHADO);

    // Keep track of the schema name in case we need it
    $schema_name = $chado->getSchemaName();

    // Test to ensure cvterms are found in the cvterms table
    $cvterms_count_query = $chado->query("SELECT count(*) as c1 FROM {1:cvterm}");
    $cvterms_count_object = $cvterms_count_query->fetchObject();
    $this->assertNotEquals($cvterms_count_object->c1, 0);

    // Insert organism
    $organism_id = $chado->insert('1:organism')
      ->fields([
        'genus' => 'Citrus',
        'species' => 'sinensis',
        'common_name' => 'Sweet Orange',
      ])
      ->execute();

    // Insert Analysis
    $analysis_id = $chado->insert('1:analysis')
      ->fields([
        'name' => 'Test Analysis',
        'description' => 'Test Analysis',
        'program' => 'PROGRAM',
        'programversion' => '1.0',
      ])
      ->execute();


    // Verify that gene is now in the cvterm table (which gets imported from SO obo)
    $result_gene_cvterm = $chado->query("SELECT * FROM {1:cvterm} 
      WHERE name = 'gene' LIMIT 1;");
    $cvterm_object = null;
    $cvterm_object = $result_gene_cvterm->fetchObject();
    $this->assertNotEquals($cvterm_object, null);


    // Import landmarks from fixture
    // $chado->executeSqlFile(__DIR__ . '/../../../fixtures/gff3_loader/landmarks.sql');

    // Manually insert landmarks into features table
    $chado->query("INSERT INTO {1:feature} (dbxref_id, organism_id, name, uniquename, residues, seqlen, md5checksum, type_id, is_analysis, is_obsolete, timeaccessioned, timelastmodified) VALUES (NULL, 1, 'scaffold00001', 'scaffold00001', '', 0, 'd41d8cd98f00b204e9800998ecf8427e', 474, false, false, '2022-11-26 05:39:59.809424', '2022-11-26 05:39:59.809424');");
    $chado->query("INSERT INTO {1:feature} (dbxref_id, organism_id, name, uniquename, residues, seqlen, md5checksum, type_id, is_analysis, is_obsolete, timeaccessioned, timelastmodified) VALUES (NULL, 1, 'scaffold1', 'scaffold1', 'CAACAAGAAGTAAGCATAGGTTAATTATCATCCACGCATATTAATCAAGAATCGATGCTCGATTAATGTTTTTGAATTGACAAACAAAAGTTTTGTAAAAAGGACTTGTTGGTGGTGGTGGGGTGGTGGTGATGGTGTGGTGGGTAGGTCGCTGGTCGTCGCCGGCGTGGTGGAAGTCTCGCTGGCCGGTGTCTCGGCGGTCTGGTGGCGGCTGGTGGCGGTAGTTGTGAGTTTTTTCTTTCTTTTTTTGTTTTTTTTTTTTACTTTTTACTTTTTTTTCGTCTTGAACAAATTAAAAATAGAGTTTGTTTGTATTTGGTTATTATTTATTGATAAGGGTATATTCGTCCTGTTTGGTCTTGATGTAATAAAATTAAATTAATTTACGGGCTTCAACTAATAAACTCCTTCATGTTGGTTTGAACTAATAAAAAAAGGGGAAATTTGCTAGACACCCCTAATTTTGGACTTATATGGGTAGAAGTCCTAGTTGCTAGATGAATATAGGCCTAGGTCCATCCACATAAAAAAATAATATAAATTAAATAATAAAAATAATATATAGACATAAGTACCCTTATTGAATAAACATATTTTAGGGGATTCAGTTATATACGTAAAGTTGGGAAATCAAATCCCACTAATCACGATTGAAGGCAGAGTATCGTGTAAGACGTTTGGAAAACATATCTTAGTCGATTCCAGTGGAATATGAGATCA', 720, '83578d8afdaec399c682aa6c0ddd29c9', 474, false, false, '2022-11-28 21:44:51.006276', '2022-11-28 21:44:51.006276');");
    $chado->query("INSERT INTO {1:feature} (dbxref_id, organism_id, name, uniquename, residues, seqlen, md5checksum, type_id, is_analysis, is_obsolete, timeaccessioned, timelastmodified) VALUES (NULL, 1, 'Contig10036', 'Contig10036', '', 0, 'd41d8cd98f00b204e9800998ecf8427e', 474, false, false, '2022-11-26 05:39:55.810798', '2022-11-26 05:39:55.810798')");
    $chado->query("INSERT INTO {1:feature} (dbxref_id, organism_id, name, uniquename, residues, seqlen, md5checksum, type_id, is_analysis, is_obsolete, timeaccessioned, timelastmodified) VALUES (NULL, 1, 'Contig1', 'Contig1', '', 0, 'd41d8cd98f00b204e9800998ecf8427e', 474, false, false, '2022-11-26 05:39:57.335594', '2022-11-26 05:39:57.335594');");
    $chado->query("INSERT INTO {1:feature} (dbxref_id, organism_id, name, uniquename, residues, seqlen, md5checksum, type_id, is_analysis, is_obsolete, timeaccessioned, timelastmodified) VALUES (NULL, 1, 'Contig0', 'Contig0', '', 0, 'd41d8cd98f00b204e9800998ecf8427e', 474, false, false, '2022-11-26 05:39:59.809424', '2022-11-26 05:39:59.809424');");
    // $chado->query("INSERT INTO {1:feature} (dbxref_id, organism_id, name, uniquename, residues, seqlen, md5checksum, type_id, is_analysis, is_obsolete, timeaccessioned, timelastmodified) VALUES (NULL, 1, 'FRAEX38873_v2_000000010.1', 'FRAEX38873_v2_000000010.1', 'MDQNQFANELISSYFLQQWRHNSQTLTLNPTPSNSGTESDSARSDLEYEDEGEEFPTELDTVNSSGGFSVVGPGKLSVLYPNVNLHGHDVGVVHANCAAPSKRLLYYFEMYVKNAGAKGQIAIGFITSAFKVRRHPGWEANTYGYHGDDGLLYRGRGKGESFGPMYTTDDTKYTTGDTVGGGINYATQEFFFTKNGVVVGTVSKDVKSPVFPTVAVHSQGEEVTVNFGKDPFVFDIKAYEAEQRAIQQEKIDCISIPLDAGHGLVRSYLQHYGYEGTLEFFDMASKSTAPPISLVPENGFNEEDNVYAMNRRTLRELIRHGEIDETFAKLRELYPQIVQDDRSSICFLLHTQKFIELVRVGKLEEAVLYGRSEFEKFKRRSEFDDLVKDCAALLAYERPDNSSVGYLLRESQRELVADAVNAIILATNPNVKDPKCCLQSRLERLLRQLTACFLEKRSLNGGDGEAFHLRRILKSGKKG', 479, 'c5915348dc93ebb73a9bb17acfb29e84', 474, false, false, '2022-11-28 21:44:51.006276', '2022-11-28 21:44:51.006276');");
    // $chado->query("INSERT INTO {1:feature} (dbxref_id, organism_id, name, uniquename, residues, seqlen, md5checksum, type_id, is_analysis, is_obsolete, timeaccessioned, timelastmodified) VALUES (NULL, 1, 'FRAEX38873_v2_000000010.2', 'FRAEX38873_v2_000000010.2', 'MDQNQFANELISSYFLQQWRHNSQTLTLNPTPSNSGTESDSARSDLEYEDEGEEFPTELDTVNSSGGFSVVGPGKLSVLYPNVNLHGHDVGVVHANCAAPSKRLLYYFEMYVKNAGAKGQIAIGFITSAFKVRRHPGWEANTYGYHGDDGLLYRGRGKGESFGPMYTTDDTKYTTGDTVGGGINYATQEFFFTKNGVVVGTVSKDVKSPVFPTVAVHSQGEEVTVNFGKDPFVFDIKAYEAEQRAIQQEKIDCISIPLDAGHGLVRSYLQHYGYEGTLEFFDMASKSTAPPISLVPENGFNEEDNVYAMNRRTLRELIRHGEIDETFAKLRELYPQIVQDDRSSICFLLHTQKFIELVRVGKLEEAVLYGRSEFEKFKRRSEFDDLVKDCAALLAYERPDNSSVGYLLRESQRELVADAVNAIILATNPNVKDPKCCLQSRLERLLRQLTACFLEKRSLNGGDGEAFHLRRILKSGKKG', 479, 'c5915348dc93ebb73a9bb17acfb29e84', 474, false, false, '2022-11-28 21:44:51.006276', '2022-11-28 21:44:51.006276');");

    // // Test to ensure scaffold1 is found in the features table after landmarks loaded
    // $scaffold_query = $chado->query("SELECT count(*) as c1 FROM {1:feature}");
    // $scaffold_object = $scaffold_query->fetchObject();

    // print_r("Scaffold object\n");
    // print_r($scaffold_object);    


    // Perform the GFF3 test by creating an instance of the GFF3 loader
    $importer_manager = \Drupal::service('tripal.importer');
    $gff3_importer = $importer_manager->createInstance('chado_gff3_loader');
    $run_args = [
      'files' => [
        0 => [
          'file_path' => __DIR__ . '/../../../fixtures/gff3_loader/small_gene.gff'
        ]
      ],
      'schema_name' => $schema_name,
      'analysis_id' => $analysis_id,
      'organism_id' => $organism_id,
      'use_transaction' => 1,
      'add_only' => 0,
      'update' => 1,
      'create_organism' => 0,
      'create_target' => 0,
      // regexps for mRNA and protein.
      're_mrna' => NULL,
      're_protein' => NULL,
      // optional
      'target_organism_id' => NULL,
      'target_type' => NULL,
      'start_line' => NULL,
      'line_number' => NULL, // Previous error without this
      'landmark_type' => 'supercontig',
      'alt_id_attr' => NULL,
      'skip_protein' => NULL,
    ];

    $file_details = [
      'file_local' => __DIR__ . '/../../../fixtures/gff3_loader/small_gene.gff',
    ];

    $gff3_importer->create($run_args, $file_details);
    $gff3_importer->prepareFiles();
    $gff3_importer->run();
    $gff3_importer->postRun();

    // This check determines if scaffold1 was added to the features table 
    // (this was done manually above)
    $results = $chado->query("SELECT * FROM {1:feature} WHERE uniquename='scaffold1';");
    $results_object = $results->fetchObject();
    $scaffold_feature_id = $results_object->feature_id;
    $this->assertEquals($results_object->uniquename, 'scaffold1');
    unset($results);
    unset($results_object);

    // This checks to ensure the test_gene_001 (gene) feature was inserted 
    // into the feature table
    $results = $chado->query("SELECT * FROM {1:feature} 
      WHERE uniquename='test_gene_001';");
    $results_object = $results->fetchObject();
    $gene_feature_id = $results_object->feature_id;
    $this->assertEquals($results_object->uniquename, 'test_gene_001');
    unset($results);
    unset($results_object);

    // This checks to see whether the test_mrna_001.1 (mrna) feature got 
    // inserted into the feature table
    $results = $chado->query("SELECT * FROM {1:feature} 
      WHERE uniquename='test_mrna_001.1';");
    $results_object = $results->fetchObject();
    $mrna_feature_id = $results_object->feature_id;
    $this->assertEquals($results_object->uniquename, 'test_mrna_001.1');
    unset($results);
    unset($results_object);

    // This checks to see whether the test_protein_001.1 (polypeptide) feature 
    // got inserted into the feature table
    $results = $chado->query("SELECT * FROM {1:feature} 
      WHERE uniquename='test_protein_001.1';");
    $results_object = $results->fetchObject();
    $polypeptide_feature_id = $results_object->feature_id;
    $this->assertEquals($results_object->uniquename, 'test_protein_001.1');
    unset($results);
    unset($results_object);

    // Do checks on the featureprop table as well
    // Ensures the bio type value got added
    $results = $chado->query("SELECT * FROM {1:featureprop} 
      WHERE feature_id = :feature_id AND value LIKE :value;", [
      ':feature_id' => $gene_feature_id,
      ':value' => 'protein_coding'
    ]);
    $has_exception = false;
    try {
      $results_object = $results->fetchObject();
    } catch (\Exception $ex) {
      $has_exception = true;
    }
    $this->assertEquals($has_exception, false, "biotype value was not added.");
    unset($results);
    unset($results_object);


    // Ensures the GAP value got added
    $results = $chado->query("SELECT * FROM {1:featureprop} 
      WHERE feature_id = :feature_id AND value LIKE :value;", [
      ':feature_id' => $gene_feature_id,
      ':value' => 'test_gap_1'
    ]);
    $has_exception = false;
    try {
      $results_object = $results->fetchObject();
    } 
    catch (\Exception $ex) {
      $has_exception = true;
    }
    $this->assertEquals($has_exception, false, "GAP value was not added.");
    unset($results);
    unset($results_object);

    // Ensures the NOTE value got added
    $results = $chado->query("SELECT * FROM {1:featureprop} 
      WHERE feature_id = :feature_id AND value LIKE :value;", [
      ':feature_id' => $gene_feature_id,
      ':value' => 'test_gene_001_note'
    ]);
    $has_exception = false;
    try {
      $results_object = $results->fetchObject();
    } 
    catch (\Exception $ex) {
      $has_exception = true;
    }
    $this->assertEquals($has_exception, false, "NOTE value was not added.");
    unset($results);
    unset($results_object);

    /**
     * Run the GFF loader on gff_duplicate_ids.gff for testing.
     *
     * This tests whether the GFF loader detects duplicate IDs which makes a 
     * GFF file invalid since IDs should be unique. The GFF loader should throw 
     * and exception which this test checks for
     */    
    // BEGIN NEW FILE: Perform import on gff_duplicate_ids
    $gff3_importer = $importer_manager->createInstance('chado_gff3_loader');
    $run_args = [
      'files' => [
        0 => [
          'file_path' => __DIR__ . '/../../../fixtures/gff3_loader/gff_duplicate_ids.gff'
        ]
      ],
      'schema_name' => $schema_name,
      'analysis_id' => $analysis_id,
      'organism_id' => $organism_id,
      'use_transaction' => 1,
      'add_only' => 0,
      'update' => 1,
      'create_organism' => 0,
      'create_target' => 0,
      // regexps for mRNA and protein.
      're_mrna' => NULL,
      're_protein' => NULL,
      // optional
      'target_organism_id' => NULL,
      'target_type' => NULL,
      'start_line' => NULL,
      'line_number' => NULL, // Previous error without this
      'landmark_type' => 'supercontig',
      'alt_id_attr' => NULL,
      'skip_protein' => NULL,
    ];

    $file_details = [
      'file_local' => __DIR__ . '/../../../fixtures/gff3_loader/gff_duplicate_ids.gff',
    ];

    
    $has_exception = false;
    try {
      $gff3_importer->create($run_args, $file_details);
      $gff3_importer->prepareFiles();
      $gff3_importer->run();
      $gff3_importer->postRun();
    } 
    catch (\Exception $ex) {
      $message = $ex->getMessage();
      $has_exception = true;
    }
    // TODO
    // $this->assertEquals($has_exception, true, "Duplicate ID was not detected 
    // and did not throw an error which it should have done.");

    /**
     * Run the GFF loader on gff_tag_unescaped_character.gff for testing.
     *
     * This tests whether the GFF loader adds IDs that contain a comma. 
     * The GFF loader should allow it
     */  
    // BEGIN NEW FILE: Perform import on gff_tag_unescaped_character
    $gff3_importer = $importer_manager->createInstance('chado_gff3_loader');
    $run_args = [
      'files' => [
        0 => [
          'file_path' => __DIR__ . '/../../../fixtures/gff3_loader/gff_tag_unescaped_character.gff'
        ]
      ],
      'schema_name' => $schema_name,
      'analysis_id' => $analysis_id,
      'organism_id' => $organism_id,
      'use_transaction' => 1,
      'add_only' => 0,
      'update' => 1,
      'create_organism' => 0,
      'create_target' => 0,
      // regexps for mRNA and protein.
      're_mrna' => NULL,
      're_protein' => NULL,
      // optional
      'target_organism_id' => NULL,
      'target_type' => NULL,
      'start_line' => NULL,
      'line_number' => NULL, // Previous error without this
      'landmark_type' => 'supercontig',
      'alt_id_attr' => NULL,
      'skip_protein' => NULL,
    ];

    $file_details = [
      'file_local' => __DIR__ . '/../../../fixtures/gff3_loader/gff_tag_unescaped_character.gff',
    ];

    $has_exception = false;
    try {
      $gff3_importer->create($run_args, $file_details);
      $gff3_importer->prepareFiles();
      $gff3_importer->run();
      $gff3_importer->postRun();
    } 
    catch (\Exception $ex) {
      $message = $ex->getMessage();
      $has_exception = true;
    }
    // TODO
    // $this->assertEquals($has_exception, true, "Should not have saved the 
    // unescaped character");

    /**
     * Run the GFF loader on gff_invalidstartend.gff for testing.
     *
     * This tests whether the GFF loader fixes start end values 
     */  
    // BEGIN NEW FILE: Perform import on gff_invalidstartend
    $gff3_importer = $importer_manager->createInstance('chado_gff3_loader');
    $run_args = [
      'files' => [
        0 => [
          'file_path' => __DIR__ . '/../../../fixtures/gff3_loader/gff_invalidstartend.gff'
        ]
      ],
      'schema_name' => $schema_name,
      'analysis_id' => $analysis_id,
      'organism_id' => $organism_id,
      'use_transaction' => 1,
      'add_only' => 0,
      'update' => 1,
      'create_organism' => 0,
      'create_target' => 0,
      // regexps for mRNA and protein.
      're_mrna' => NULL,
      're_protein' => NULL,
      // optional
      'target_organism_id' => NULL,
      'target_type' => NULL,
      'start_line' => NULL,
      'line_number' => NULL, // Previous error without this
      'landmark_type' => 'supercontig',
      'alt_id_attr' => NULL,
      'skip_protein' => NULL,
    ];

    $file_details = [
      'file_local' => __DIR__ . '/../../../fixtures/gff3_loader/gff_invalidstartend.gff',
    ];

    $has_exception = false;
    try {
      $gff3_importer->create($run_args, $file_details);
      $gff3_importer->prepareFiles();
      $gff3_importer->run();
      $gff3_importer->postRun();
    } 
    catch (\Exception $ex) {
      $message = $ex->getMessage();
      $has_exception = true;
    }
    // TODO
    // $this->assertEquals($has_exception, true, "Should not complete when there is invalid start and end values but did throw error.");

    /**
     * Run the GFF loader on gff_phase_invalid_character.gff for testing.
     *
     * This tests whether the GFF loader interprets the phase values correctly
     * for CDS rows when a character outside of the range 0,1,2 is specified.
     */
    // BEGIN NEW FILE: Perform import on gff_phase_invalid_character
    $gff3_importer = $importer_manager->createInstance('chado_gff3_loader');
    $run_args = [
      'files' => [
        0 => [
          'file_path' => __DIR__ . '/../../../fixtures/gff3_loader/gff_phase_invalid_character.gff'
        ]
      ],
      'schema_name' => $schema_name,
      'analysis_id' => $analysis_id,
      'organism_id' => $organism_id,
      'use_transaction' => 1,
      'add_only' => 0,
      'update' => 1,
      'create_organism' => 0,
      'create_target' => 0,
      // regexps for mRNA and protein.
      're_mrna' => NULL,
      're_protein' => NULL,
      // optional
      'target_organism_id' => NULL,
      'target_type' => NULL,
      'start_line' => NULL,
      'line_number' => NULL, // Previous error without this
      'landmark_type' => 'supercontig',
      'alt_id_attr' => NULL,
      'skip_protein' => NULL,
    ];

    $file_details = [
      'file_local' => __DIR__ . '/../../../fixtures/gff3_loader/gff_phase_invalid_character.gff',
    ];

    $has_exception = false;
    try {
      $gff3_importer->create($run_args, $file_details);
      $gff3_importer->prepareFiles();
      $gff3_importer->run();
      $gff3_importer->postRun();
    } 
    catch (\Exception $ex) {
      $message = $ex->getMessage();
      $has_exception = true;
    }
    $this->assertEquals($has_exception, true, "Should not complete when there 
      is invalid phase value (in this case character a) but did throw error.");

    /**
     * Run the GFF loader on gff_phase_invalid_number.gff for testing.
     *
     * This tests whether the GFF loader interprets the phase values correctly
     * for CDS rows when a number outside of the range 0,1,2 is specified.
     */ 
    // BEGIN NEW FILE: Perform import on gff_phase_invalid_number
    $gff3_importer = $importer_manager->createInstance('chado_gff3_loader');
    $run_args = [
      'files' => [
        0 => [
          'file_path' => __DIR__ . '/../../../fixtures/gff3_loader/gff_phase_invalid_number.gff'
        ]
      ],
      'schema_name' => $schema_name,
      'analysis_id' => $analysis_id,
      'organism_id' => $organism_id,
      'use_transaction' => 1,
      'add_only' => 0,
      'update' => 1,
      'create_organism' => 0,
      'create_target' => 0,
      // regexps for mRNA and protein.
      're_mrna' => NULL,
      're_protein' => NULL,
      // optional
      'target_organism_id' => NULL,
      'target_type' => NULL,
      'start_line' => NULL,
      'line_number' => NULL, // Previous error without this
      'landmark_type' => 'supercontig',
      'alt_id_attr' => NULL,
      'skip_protein' => NULL,
    ];

    $file_details = [
      'file_local' => __DIR__ . '/../../../fixtures/gff3_loader/gff_phase_invalid_number.gff',
    ];

    $has_exception = false;
    try {
      $gff3_importer->create($run_args, $file_details);
      $gff3_importer->prepareFiles();
      $gff3_importer->run();
      $gff3_importer->postRun();
    } 
    catch (\Exception $ex) {
      $message = $ex->getMessage();
      $has_exception = true;
    }
    $this->assertEquals($has_exception, true, "Should not complete when there 
      is invalid phase value (in this case a number > 2) but did not throw 
      error which should have happened.");

    
    /**
     * Test that when checked, explicit proteins are created when specified within
     * the GFF file. Explicit proteins will not respect the skip_protein argument
     * and will therefore be added to the database.
     */
    // BEGIN NEW FILE: Perform import on gff_phase
    $gff3_importer = $importer_manager->createInstance('chado_gff3_loader');
    $run_args = [
      'files' => [
        0 => [
          'file_path' => __DIR__ . '/../../../fixtures/gff3_loader/gff_phase.gff'
        ]
      ],
      'schema_name' => $schema_name,
      'analysis_id' => $analysis_id,
      'organism_id' => $organism_id,
      'use_transaction' => 1,
      'add_only' => 0,
      'update' => 1,
      'create_organism' => 0,
      'create_target' => 0,
      // regexps for mRNA and protein.
      're_mrna' => NULL,
      're_protein' => NULL,
      // optional
      'target_organism_id' => NULL,
      'target_type' => NULL,
      'start_line' => NULL,
      'line_number' => NULL, // Previous error without this
      'landmark_type' => 'supercontig',
      'alt_id_attr' => NULL,
      'skip_protein' => NULL,
    ];

    $file_details = [
      'file_local' => __DIR__ . '/../../../fixtures/gff3_loader/gff_phase.gff',
    ];

    $has_exception = false;
    try {
      $gff3_importer->create($run_args, $file_details);
      $gff3_importer->prepareFiles();
      $gff3_importer->run();
      $gff3_importer->postRun();
    } 
    catch (\Exception $ex) {
      $message = $ex->getMessage();
      $has_exception = true;
    }
    // TODO
    // $this->assertEquals($has_exception, false, "This is a valid phase 
    //  file that should not produce an exception but did.");



    // $results = $chado->query("SELECT * FROM {1:featureprop};");
    // while ($object = $results->fetchObject()) {
    //   print_r($object);
    // }

    /**
     * Add a skip protein option.  Test that when checked, implicit proteins are
     * not created, but that they are created when unchecked.
     */
    $gff3_importer = $importer_manager->createInstance('chado_gff3_loader');
    $run_args = [
      'files' => [
        0 => [
          'file_path' => __DIR__ . '/../../../fixtures/gff3_loader/gff_protein_generation.gff'
        ]
      ],
      //Skip protein feature generation
      'skip_protein' => 1,
      'schema_name' => $schema_name,
      'analysis_id' => $analysis_id,
      'organism_id' => $organism_id,
      'use_transaction' => 1,
      'add_only' => 0,
      'update' => 1,
      'create_organism' => 0,
      'create_target' => 0,
      // regexps for mRNA and protein.
      're_mrna' => NULL,
      're_protein' => NULL,
      // optional
      'target_organism_id' => NULL,
      'target_type' => NULL,
      'start_line' => NULL,
      'line_number' => NULL, // Previous error without this
      'landmark_type' => 'supercontig',
      'alt_id_attr' => NULL,
      'skip_protein' => NULL,
    ];

    $file_details = [
      'file_local' => __DIR__ . '/../../../fixtures/gff3_loader/gff_protein_generation.gff',
    ];

    $has_exception = false;
    try {
      $gff3_importer->create($run_args, $file_details);
      $gff3_importer->prepareFiles();
      $gff3_importer->run();
      $gff3_importer->postRun();
    } 
    catch (\Exception $ex) {
      $message = $ex->getMessage();
      // print_r($ex->__toString());
      $has_exception = true;
    }
    // TODO - Review this one with the Tripal 3 test since it does more checks after 
    // solving the undefined organism_id issue. 
    // $this->assertEquals($has_exception, false, "This should create a protein");

    /**
     * Run the GFF loader on gff_rightarrow_ids.gff for testing.
     *
     * This tests whether the GFF loader fails if ID contains  
     * arrow >. It should not fail.
     */  


    // $results = $chado->query("SELECT * FROM {1:feature} 
    // WHERE uniquename LIKE 'FRAEX38873_v2_000000010'");
    // foreach($results as $row) {
    //   print_r($row);
    // }

    $gff3_importer = $importer_manager->createInstance('chado_gff3_loader');
    $run_args = [
      'files' => [
        0 => [
          'file_path' => __DIR__ . '/../../../fixtures/gff3_loader/gff_rightarrow_id.gff'
        ]
      ],
      'schema_name' => $schema_name,
      'analysis_id' => $analysis_id,
      'organism_id' => $organism_id,
      'use_transaction' => 1,
      'add_only' => 0,
      'update' => 1,
      'create_organism' => 0,
      'create_target' => 0,
      // regexps for mRNA and protein.
      're_mrna' => NULL,
      're_protein' => NULL,
      // optional
      'target_organism_id' => NULL,
      'target_type' => NULL,
      'start_line' => NULL,
      'line_number' => NULL, // Previous error without this
      'landmark_type' => 'supercontig',
      'alt_id_attr' => NULL,
      'skip_protein' => NULL,
    ];

    $file_details = [
      'file_local' => __DIR__ . '/../../../fixtures/gff3_loader/gff_rightarrow_id.gff',
    ];

    $has_exception = false;
    try {
      $gff3_importer->create($run_args, $file_details);
      $gff3_importer->prepareFiles();
      $gff3_importer->run();
      $gff3_importer->postRun();

      $results = $chado->query("SELECT count(*) as c1 FROM {1:feature} 
      WHERE uniquename = '>FRAEX38873_v2_000000010';");
    
      foreach($results as $row) {
        $this->assertEquals($row->c1, 1, 'A feature with uniquename 
          >FRAEX38873_v2_000000010 should have been added but was not found.');
      }
    } 
    catch (\Exception $ex) {
      $message = $ex->getMessage();
      // echo $message . "\n";
      // echo $ex->getTraceAsString();
      $has_exception = true;
    }

    $this->assertEquals($has_exception, false, "This should not fail and the 
    right arrow should be added.");





    /**
     * Run the GFF loader on gff_score.gff for testing.
     *
     * This tests whether the GFF loader interprets the score values
     */
    $gff3_importer = $importer_manager->createInstance('chado_gff3_loader');
    $run_args = [
      'files' => [
        0 => [
          'file_path' => __DIR__ . '/../../../fixtures/gff3_loader/gff_score.gff'
        ]
      ],
      'schema_name' => $schema_name,
      'analysis_id' => $analysis_id,
      'organism_id' => $organism_id,
      'use_transaction' => 1,
      'add_only' => 0,
      'update' => 1,
      'create_organism' => 0,
      'create_target' => 0,
      // regexps for mRNA and protein.
      're_mrna' => NULL,
      're_protein' => NULL,
      // optional
      'target_organism_id' => NULL,
      'target_type' => NULL,
      'start_line' => NULL,
      'line_number' => NULL, // Previous error without this
      'landmark_type' => 'supercontig',
      'alt_id_attr' => NULL,
      'skip_protein' => NULL,
    ];

    $file_details = [
      'file_local' => __DIR__ . '/../../../fixtures/gff3_loader/gff_score.gff',
    ];

    $has_exception = false;
    try {
      $gff3_importer->create($run_args, $file_details);
      $gff3_importer->prepareFiles();
      $gff3_importer->run();
      $gff3_importer->postRun();

      $results = $chado->query("SELECT * FROM {1:analysisfeature} 
        WHERE significance = :significance LIMIT 1", [
        ':significance' => 2
      ]);
      foreach ($results as $row) {
        // print_r($row);
        $this->assertEquals($row->significance,2, 'No significance value of 2 
          could be found in the db. Import failed.');
      }
      unset($results);

      $results = $chado->query("SELECT * FROM {1:analysisfeature} 
        WHERE significance = :significance LIMIT 1", [
        ':significance' => 2.5
      ]);
      foreach ($results as $row) {
        // print_r($row);
        $this->assertEquals($row->significance,2.5, 'No significance value of 2.5 
        could be found in the db. Import failed.');
      }
      unset($results);

      $results = $chado->query("SELECT * FROM {1:analysisfeature} 
        WHERE significance = :significance LIMIT 1", [
        ':significance' => -2.5
      ]);
      foreach ($results as $row) {
        // print_r($row);
        $this->assertEquals($row->significance,-2.5, 'No significance value of 
        -2.5 could be found in the db. Import failed.');
      }
      unset($results);
    } 
    catch (\Exception $ex) {
      $message = $ex->getMessage();
      $has_exception = true;
    }
    $this->assertEquals($has_exception,false,'An exception occurred while 
      importing gff_score which should not have happened');


    /**
     * Run the GFF loader on gff_seqid_invalid_character.gff for testing.
     * Seqids seem to also be called landmarks within GFF loader.
     * This tests whether the GFF loader has any issues with characters like  
     * single quotes.
     */ 
    $gff3_importer = $importer_manager->createInstance('chado_gff3_loader');
    $run_args = [
      'files' => [
        0 => [
          'file_path' => __DIR__ . '/../../../fixtures/gff3_loader/gff_seqid_invalid_character.gff'
        ]
      ],
      'schema_name' => $schema_name,
      'analysis_id' => $analysis_id,
      'organism_id' => $organism_id,
      'use_transaction' => 1,
      'add_only' => 0,
      'update' => 1,
      'create_organism' => 0,
      'create_target' => 0,
      // regexps for mRNA and protein.
      're_mrna' => NULL,
      're_protein' => NULL,
      // optional
      'target_organism_id' => NULL,
      'target_type' => NULL,
      'start_line' => NULL,
      'line_number' => NULL, // Previous error without this
      'landmark_type' => 'supercontig',
      'alt_id_attr' => NULL,
      'skip_protein' => NULL,
    ];

    $file_details = [
      'file_local' => __DIR__ . '/../../../fixtures/gff3_loader/gff_seqid_invalid_character.gff',
    ];

    $has_exception = false;
    try {
      $gff3_importer->create($run_args, $file_details);
      $gff3_importer->prepareFiles();
      $gff3_importer->run();
      $gff3_importer->postRun();
    } 
    catch (\Exception $ex) {
      $message = $ex->getMessage();
      $has_exception = true;
    }
    $this->assertEquals($has_exception, true, 'An invalid seqid in the 
      gff_seqid_invalid_character should have caused an 
      exception but did not.');

    /**
     * Run the GFF loader on gff_strand_invalid.gff for testing.
     *
     * This tests whether the GFF loader interprets the strand values
     */ 
    $gff3_importer = $importer_manager->createInstance('chado_gff3_loader');
    $run_args = [
      'files' => [
        0 => [
          'file_path' => __DIR__ . '/../../../fixtures/gff3_loader/gff_strand_invalid.gff'
        ]
      ],
      'schema_name' => $schema_name,
      'analysis_id' => $analysis_id,
      'organism_id' => $organism_id,
      'use_transaction' => 1,
      'add_only' => 0,
      'update' => 1,
      'create_organism' => 0,
      'create_target' => 0,
      // regexps for mRNA and protein.
      're_mrna' => NULL,
      're_protein' => NULL,
      // optional
      'target_organism_id' => NULL,
      'target_type' => NULL,
      'start_line' => NULL,
      'line_number' => NULL, // Previous error without this
      'landmark_type' => 'supercontig',
      'alt_id_attr' => NULL,
      'skip_protein' => NULL,
    ];

    $file_details = [
      'file_local' => __DIR__ . '/../../../fixtures/gff3_loader/gff_strand_invalid.gff',
    ];

    $has_exception = false;
    try {
      $gff3_importer->create($run_args, $file_details);
      $gff3_importer->prepareFiles();
      $gff3_importer->run();
      $gff3_importer->postRun();
    } 
    catch (\Exception $ex) {
      $message = $ex->getMessage();
      $has_exception = true;
    }
    $this->assertEquals($has_exception, true, 'An invalid strand in the 
      gff_strand_invalid.gff file should have caused an 
      exception but did not.');


    /**
     * Run the GFF loader on gff_strand.gff for testing.
     *
     * This tests whether the GFF loader interprets the strand values
     */ 
    $gff3_importer = $importer_manager->createInstance('chado_gff3_loader');
    $run_args = [
      'files' => [
        0 => [
          'file_path' => __DIR__ . '/../../../fixtures/gff3_loader/gff_strand.gff'
        ]
      ],
      'schema_name' => $schema_name,
      'analysis_id' => $analysis_id,
      'organism_id' => $organism_id,
      'use_transaction' => 1,
      'add_only' => 0,
      'update' => 1,
      'create_organism' => 0,
      'create_target' => 0,
      // regexps for mRNA and protein.
      're_mrna' => NULL,
      're_protein' => NULL,
      // optional
      'target_organism_id' => NULL,
      'target_type' => NULL,
      'start_line' => NULL,
      'line_number' => NULL, // Previous error without this
      'landmark_type' => 'supercontig',
      'alt_id_attr' => NULL,
      'skip_protein' => NULL,
    ];

    $file_details = [
      'file_local' => __DIR__ . '/../../../fixtures/gff3_loader/gff_strand.gff',
    ];

    $has_exception = false;
    try {
      $gff3_importer->create($run_args, $file_details);
      $gff3_importer->prepareFiles();
      $gff3_importer->run();
      $gff3_importer->postRun();
      
      // Test that integer values for strand that get placed in the db
      // Strand data gets saved in chado.featureloc
      $results = $chado->query('SELECT * FROM chado.featureloc fl 
        LEFT JOIN chado.feature f ON (fl.feature_id = f.feature_id)
        WHERE uniquename = :uniquename LIMIT 1', 
        array(
          ':uniquename' => 'FRAEX38873_v2_000000010'
        )
      );

      foreach ($results as $row) {
        $this->assertEquals($row->strand, 1); // +
      }

      $results = $chado->query('SELECT * FROM chado.featureloc fl 
        LEFT JOIN chado.feature f ON (fl.feature_id = f.feature_id)
        WHERE uniquename = :uniquename LIMIT 1', 
        array(
          ':uniquename' => 'FRAEX38873_v2_000000010.1'
        )
      );

      foreach ($results as $row) {
        $this->assertEquals($row->strand,-1); // -
      } 
      
      $results = $chado->query('SELECT * FROM chado.featureloc fl 
        LEFT JOIN chado.feature f ON (fl.feature_id = f.feature_id)
        WHERE uniquename = :uniquename LIMIT 1', 
        array(
          ':uniquename' => 'FRAEX38873_v2_000000010.2'
        )
      );

      foreach ($results as $row) {
        $this->assertEquals($row->strand, 0); // ?
      }
      
      $results = $chado->query('SELECT * FROM chado.featureloc fl 
        LEFT JOIN chado.feature f ON (fl.feature_id = f.feature_id)
        WHERE uniquename = :uniquename LIMIT 1', 
        array(
          ':uniquename' => 'FRAEX38873_v2_000000010.3'
        )
      );

      foreach ($results as $row) {
        $this->assertEquals($row->strand, 0); // .
      } 

    } 
    catch (\Exception $ex) {
      $message = $ex->getMessage();
      $has_exception = true;
    }
    $this->assertEquals($has_exception, false, 'The gff_strand.gff produced an 
      exception which should not happen since it is a valid file.');


    /**
     * Run the GFF loader on gff_tag_parent_verification.gff for testing.
     *
     * This tests whether the GFF loader adds Parent attributes
     * The GFF loader should allow it
     */  
    $gff3_importer = $importer_manager->createInstance('chado_gff3_loader');
    $run_args = [
      'files' => [
        0 => [
          'file_path' => __DIR__ . '/../../../fixtures/gff3_loader/gff_tag_parent_verification.gff'
        ]
      ],
      'schema_name' => $schema_name,
      'analysis_id' => $analysis_id,
      'organism_id' => $organism_id,
      'use_transaction' => 1,
      'add_only' => 0,
      'update' => 1,
      'create_organism' => 0,
      'create_target' => 0,
      // regexps for mRNA and protein.
      're_mrna' => NULL,
      're_protein' => NULL,
      // optional
      'target_organism_id' => NULL,
      'target_type' => NULL,
      'start_line' => NULL,
      'line_number' => NULL, // Previous error without this
      'landmark_type' => 'supercontig',
      'alt_id_attr' => NULL,
      'skip_protein' => NULL,
    ];

    $file_details = [
      'file_local' => __DIR__ . '/../../../fixtures/gff3_loader/gff_tag_parent_verification.gff',
    ];

    $has_exception = false;
    try {
      $gff3_importer->create($run_args, $file_details);
      $gff3_importer->prepareFiles();
      $gff3_importer->run();
      $gff3_importer->postRun();
      $results = $chado->query("SELECT COUNT(*) as c1 FROM 
      (SELECT * FROM {1:feature_relationship} fr
      LEFT JOIN {1:feature} f ON (fr.object_id = f.feature_id)
      WHERE f.uniquename = 'FRAEX38873_v2_000000010' LIMIT 1
      ) as table1;",[]);

      foreach ($results as $row) {
        $this->assertEquals($row->c1, 1);
      }
    } 
    catch (\Exception $ex) {
      $message = $ex->getMessage();
      $has_exception = true;
    }
    $this->assertEquals($has_exception, false, 'This file 
      gff_tag_parent_verification.gff is a valid file and should not produce 
      an exception but did.');      
    

  /**
   * Run the GFF loader on gff_tagvalue_encoded_character.gff for testing.
   *
   * This tests whether the GFF loader adds IDs that contain encoded character. 
   * The GFF loader should allow it
   */    
    $gff3_importer = $importer_manager->createInstance('chado_gff3_loader');
    $run_args = [
      'files' => [
        0 => [
          'file_path' => __DIR__ . '/../../../fixtures/gff3_loader/gff_tagvalue_encoded_character.gff'
        ]
      ],
      'schema_name' => $schema_name,
      'analysis_id' => $analysis_id,
      'organism_id' => $organism_id,
      'use_transaction' => 1,
      'add_only' => 0,
      'update' => 1,
      'create_organism' => 0,
      'create_target' => 0,
      // regexps for mRNA and protein.
      're_mrna' => NULL,
      're_protein' => NULL,
      // optional
      'target_organism_id' => NULL,
      'target_type' => NULL,
      'start_line' => NULL,
      'line_number' => NULL, // Previous error without this
      'landmark_type' => 'supercontig',
      'alt_id_attr' => NULL,
      'skip_protein' => NULL,
    ];

    $file_details = [
      'file_local' => __DIR__ . '/../../../fixtures/gff3_loader/gff_tagvalue_encoded_character.gff',
    ];

    $has_exception = false;
    try {
      $gff3_importer->create($run_args, $file_details);
      $gff3_importer->prepareFiles();
      $gff3_importer->run();
      $gff3_importer->postRun();

      $results = $chado->query("SELECT COUNT(*) as c1 FROM {1:feature} 
      WHERE uniquename = 'FRAEX38873_v2_000000010,20';",[]);

      foreach ($results as $row) {
        $this->assertEquals($row->c1, 1);
      }  
    } 
    catch (\Exception $ex) {
      $message = $ex->getMessage();
      $has_exception = true;
    }
    $this->assertEquals($has_exception, false, 'This file 
      gff_tagvalue_encoded_character.gff is a valid file and should not produce 
      an exception but did.');      
    
    /**
     * Run the GFF loader on gff_tagvalue_comma_character.gff for testing.
     *
     * This tests whether the GFF loader adds tag values contain comma seperation 
     * character. 
     * The GFF loader should allow it
     */    
    $gff3_importer = $importer_manager->createInstance('chado_gff3_loader');
    $run_args = [
      'files' => [
        0 => [
          'file_path' => __DIR__ . '/../../../fixtures/gff3_loader/gff_tagvalue_comma_character.gff'
        ]
      ],
      'schema_name' => $schema_name,
      'analysis_id' => $analysis_id,
      'organism_id' => $organism_id,
      'use_transaction' => 1,
      'add_only' => 0,
      'update' => 1,
      'create_organism' => 0,
      'create_target' => 0,
      // regexps for mRNA and protein.
      're_mrna' => NULL,
      're_protein' => NULL,
      // optional
      'target_organism_id' => NULL,
      'target_type' => NULL,
      'start_line' => NULL,
      'line_number' => NULL, // Previous error without this
      'landmark_type' => 'supercontig',
      'alt_id_attr' => NULL,
      'skip_protein' => NULL,
    ];

    $file_details = [
      'file_local' => __DIR__ . '/../../../fixtures/gff3_loader/gff_tagvalue_comma_character.gff',
    ];

    $has_exception = false;
    try {
      $gff3_importer->create($run_args, $file_details);
      $gff3_importer->prepareFiles();
      $gff3_importer->run();
      $gff3_importer->postRun();

      $results = $chado->query("SELECT COUNT(*) as c1 FROM {1:featureprop} 
        WHERE value ILIKE :value",[
        ':value' => 'T'
      ]);
      foreach ($results as $row) {
        $this->assertEquals($row->c1, 1);
      }

      $results = $chado->query("SELECT COUNT(*) as c1 FROM {1:featureprop} 
        WHERE value ILIKE :value",[
        ':value' => 'EST'
      ]);
      foreach ($results as $row) {
        $this->assertEquals($row->c1, 1);
      }

    } 
    catch (\Exception $ex) {
      $message = $ex->getMessage();
      // print_r($message);
      $has_exception = true;
    }
    $this->assertEquals($has_exception, false, 'This file 
      gff_tagvalue_comma_character.gff should not produce 
      an exception but did.');      

    /**
     * Run the GFF loader on gff_tagvalue_comma_character.gff for testing.
     *
     * This tests whether the GFF loader adds tag values containing encoded comma
     * character. 
     * The GFF loader should allow it
     */    
    $gff3_importer = $importer_manager->createInstance('chado_gff3_loader');
    $run_args = [
      'files' => [
        0 => [
          'file_path' => __DIR__ . '/../../../fixtures/gff3_loader/gff_tagvalue_encoded_comma.gff'
        ]
      ],
      'schema_name' => $schema_name,
      'analysis_id' => $analysis_id,
      'organism_id' => $organism_id,
      'use_transaction' => 1,
      'add_only' => 0,
      'update' => 1,
      'create_organism' => 0,
      'create_target' => 0,
      // regexps for mRNA and protein.
      're_mrna' => NULL,
      're_protein' => NULL,
      // optional
      'target_organism_id' => NULL,
      'target_type' => NULL,
      'start_line' => NULL,
      'line_number' => NULL, // Previous error without this
      'landmark_type' => 'supercontig',
      'alt_id_attr' => NULL,
      'skip_protein' => NULL,
    ];

    $file_details = [
      'file_local' => __DIR__ . '/../../../fixtures/gff3_loader/gff_tagvalue_encoded_comma.gff',
    ];

    $has_exception = false;
    try {
      $gff3_importer->create($run_args, $file_details);
      $gff3_importer->prepareFiles();
      $gff3_importer->run();
      $gff3_importer->postRun();

      $results = $chado->query("SELECT COUNT(*) as c1 FROM {1:featureprop} 
        WHERE value ILIKE :value",[
        ':value' => 'T,EST'
      ]);
      foreach ($results as $row) {
        $this->assertEquals($row->c1, 1);
      }

    } 
    catch (\Exception $ex) {
      $message = $ex->getMessage();
      // print_r($message);
      $has_exception = true;
    }
    $this->assertEquals($has_exception, false, 'This file 
      gff_tagvalue_encoded_comma.gff should not produce 
      an exception but did.');   
      
      
    /**
     * Run the GFF loader on gff_1380_landmark_test.gff for testing.
     *
     * This tests whether the GFF loader adds landmarks directly from the GFF file
     * character. 
     * The GFF loader should allow it
     */    
    $gff3_importer = $importer_manager->createInstance('chado_gff3_loader');
    $run_args = [
      'files' => [
        0 => [
          'file_path' => __DIR__ . '/../../../fixtures/gff3_loader/gff_1380_landmark_test.gff'
        ]
      ],
      'schema_name' => $schema_name,
      'analysis_id' => $analysis_id,
      'organism_id' => $organism_id,
      'use_transaction' => 1,
      'add_only' => 0,
      'update' => 1,
      'create_organism' => 0,
      'create_target' => 0,
      // regexps for mRNA and protein.
      're_mrna' => NULL,
      're_protein' => NULL,
      // optional
      'target_organism_id' => NULL,
      'target_type' => NULL,
      'start_line' => NULL,
      'line_number' => NULL, // Previous error without this
      'landmark_type' => NULL,
      'alt_id_attr' => NULL,
      'skip_protein' => NULL,
    ];

    $file_details = [
      'file_local' => __DIR__ . '/../../../fixtures/gff3_loader/gff_1380_landmark_test.gff',
    ];

    $has_exception = false;
    try {
      $gff3_importer->create($run_args, $file_details);
      $gff3_importer->prepareFiles();
      $gff3_importer->run();
      $gff3_importer->postRun();

      $results = $chado->query("SELECT count(*) as c1 FROM {1:feature} 
        WHERE uniquename ILIKE :value",[
        ':value' => 'chr1_h1'
      ]);
      foreach ($results as $row) {
        $this->assertEquals($row->c1, 1);
      }

    } 
    catch (\Exception $ex) {
      $message = $ex->getMessage();
      // print_r($message);
      $has_exception = true;
    }
    $this->assertEquals($has_exception, false, 'This file 
      gff_1380_landmark_test.gff should not produce 
      an exception but did.');
      
    /**
     * Run the GFF loader on Citrus GFF3 for testing.
     *
     * This tests whether the GFF loader adds Citrus data
     * character. 
     * The GFF loader should allow it
     */    
    $gff3_importer = $importer_manager->createInstance('chado_gff3_loader');
    $run_args = [
      'files' => [
        0 => [
          'file_path' => __DIR__ . '/../../../fixtures/gff3_loader/gff_Citrus_sinensis-orange1.1g015632m.g.gff3'
        ]
      ],
      'schema_name' => $schema_name,
      'analysis_id' => $analysis_id,
      'organism_id' => $organism_id,
      'use_transaction' => 1,
      'add_only' => 0,
      'update' => 1,
      'create_organism' => 0,
      'create_target' => 0,
      // regexps for mRNA and protein.
      're_mrna' => NULL,
      're_protein' => NULL,
      // optional
      'target_organism_id' => NULL,
      'target_type' => NULL,
      'start_line' => NULL,
      'line_number' => NULL, // Previous error without this
      'landmark_type' => 'supercontig',
      'alt_id_attr' => NULL,
      'skip_protein' => NULL,
    ];

    $file_details = [
      'file_local' => __DIR__ . '/../../../fixtures/gff3_loader/gff_Citrus_sinensis-orange1.1g015632m.g.gff3',
    ];

    $has_exception = false;
    try {
      $gff3_importer->create($run_args, $file_details);
      $gff3_importer->prepareFiles();
      $gff3_importer->run();
      $gff3_importer->postRun();

      // $results = $chado->query("SELECT count(*) as c1 FROM {1:feature} 
      //   WHERE uniquename ILIKE :value",[
      //   ':value' => 'chr1_h1'
      // ]);
      // foreach ($results as $row) {
      //   $this->assertEquals($row->c1, 1);
      // }

    } 
    catch (\Exception $ex) {
      $message = $ex->getMessage();
      print_r($message);
      print_r($ex->getTraceAsString());
      $has_exception = true;
    }
    $this->assertEquals($has_exception, false, 'This file 
      gff_Citrus_sinensis-orange1.1g015632m.g.gff3 should not produce 
      an exception but did.');
      

   
  }
}