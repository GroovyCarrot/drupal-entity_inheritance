<?php
/**
 * @file
 * Entity inheritance
 *
 * Created by Jake Wise 14/03/2017.
 *
 * You are permitted to use, modify, and distribute this file in accordance with
 * the terms of the license agreement accompanying it.
 */

namespace Drupal\entity_inheritance\Storage;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\entity_inheritance\EntityBundleClassLocating;
use Symfony\Component\DependencyInjection\ContainerInterface;


class ExtendableContentEntityStorage extends SqlContentEntityStorage {

  /** @var EntityBundleClassLocating */
  protected $contentEntityBundleClassLocator;

  /**
   * @inheritdoc
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('cache.entity'),
      $container->get('language_manager'),
      $container->get('entity_inheritance.plugin_manager.content_entity_type_bundle')
    );
  }

  public function __construct(
    EntityTypeInterface $entity_type,
    Connection $database,
    EntityManagerInterface $entity_manager,
    CacheBackendInterface $cache,
    LanguageManagerInterface $language_manager,
    EntityBundleClassLocating $contentEntityBundleSubClassManager
  ) {
    $this->contentEntityBundleClassLocator = $contentEntityBundleSubClassManager;
    parent::__construct($entity_type, $database, $entity_manager, $cache, $language_manager);
  }

  /**
   * @inheritdoc
   */
  protected function mapFromStorageRecords(array $records, $load_from_revision = FALSE) {
    if (!$records) {
      return [];
    }

    $values = [];
    foreach ($records as $id => $record) {
      $values[$id] = [];
      // Skip the item delta and item value levels (if possible) but let the
      // field assign the value as suiting. This avoids unnecessary array
      // hierarchies and saves memory here.
      foreach ($record as $name => $value) {
        // Handle columns named [field_name]__[column_name] (e.g for field types
        // that store several properties).
        if ($field_name = strstr($name, '__', TRUE)) {
          $property_name = substr($name, strpos($name, '__') + 2);
          $values[$id][$field_name][LanguageInterface::LANGCODE_DEFAULT][$property_name] = $value;
        }
        else {
          // Handle columns named directly after the field (e.g if the field
          // type only stores one property).
          $values[$id][$name][LanguageInterface::LANGCODE_DEFAULT] = $value;
        }
      }
    }

    // Initialize translations array.
    $translations = array_fill_keys(array_keys($values), []);

    // Load values from shared and dedicated tables.
    $this->loadFromSharedTables($values, $translations);
    $this->loadFromDedicatedTables($values, $load_from_revision);

    $entities = [];
    foreach ($values as $id => $entity_values) {
      $bundle = $this->bundleKey ? $entity_values[$this->bundleKey][LanguageInterface::LANGCODE_DEFAULT] : FALSE;

      // Turn the record into an entity class.
      $entityClass = $this->contentEntityBundleClassLocator->getClassForEntityBundle($this->entityTypeId, $bundle, $this->entityClass);
      $entities[$id] = new $entityClass($entity_values, $this->entityTypeId, $bundle, array_keys($translations[$id]));
    }

    return $entities;
  }

  /**
   * @inheritdoc
   */
  protected function doCreate(array $values) {
    // We have to determine the bundle first.
    $bundle = FALSE;
    if ($this->bundleKey) {
      if (!isset($values[$this->bundleKey])) {
        throw new EntityStorageException('Missing bundle for entity type ' . $this->entityTypeId);
      }
      $bundle = $values[$this->bundleKey];
    }
    $entityClass = $this->contentEntityBundleClassLocator->getClassForEntityBundle($this->entityTypeId, $bundle, $this->entityClass);
    $entity = new $entityClass(array(), $this->entityTypeId, $bundle);
    $this->initFieldValues($entity, $values);
    return $entity;
  }

}
