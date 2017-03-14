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

namespace Drupal\entity_inheritance\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_inheritance\EntityBundleClassLocating;


class ContentEntityTypeBundleManager extends DefaultPluginManager implements EntityBundleClassLocating {

  use StringTranslationTrait;

  const BIN_REFLECTED_CLASSES = __CLASS__ . '::reflectedClasses';
  const BIN_REFLECTED_SUBCLASSES = __CLASS__ . '::reflectedSubClasses';

  protected $reflectedClasses = [];
  protected $reflectedSubClasses = [];

  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler
  ) {
    $this->cacheBackend = $cache_backend;

    parent::__construct(
      'Entity',
      $namespaces,
      $module_handler,
      NULL,
      'Drupal\entity_inheritance\Annotation\ContentEntityTypeBundle'
    );

    $this->alterInfo('content_entity_bundle_class');
  }

  /**
   * Load reflected classes from the cache.
   */
  public function onInit(): void {
    $reflectedClasses = $this->cacheBackend->get(self::BIN_REFLECTED_CLASSES);
    if ($reflectedClasses) {
      $this->reflectedClasses = $reflectedClasses->data;
    }

    $reflectedSubClasses = $this->cacheBackend->get(self::BIN_REFLECTED_SUBCLASSES);
    if ($reflectedSubClasses) {
      $this->reflectedSubClasses = $reflectedSubClasses->data;
    }
  }

  /**
   * On destruction, cache the reflected classes.
   */
  public function __destruct() {
    $this->cacheBackend->set(self::BIN_REFLECTED_CLASSES, $this->reflectedClasses);
    $this->cacheBackend->set(self::BIN_REFLECTED_SUBCLASSES, $this->reflectedSubClasses);
  }

  /**
   * @inheritdoc
   */
  public function getClassForEntityBundle(string $entityTypeId, string $bundle, string $entityBaseClass): string {
    $definitions = $this->getDefinitions();

    foreach ($definitions as $definition) {
      if ($definition['entity_type_id'] !== $entityTypeId || $definition['bundle_id'] !== $bundle) {
        continue;
      }

      $entityBundleClass = $definition['class'];

      if (!isset($this->reflectedSubClasses[$entityBaseClass][$entityBundleClass])) {
        $reflection = new \ReflectionClass($entityBundleClass);
        if (!$reflection->isInstantiable()) {
          throw new \InvalidArgumentException($this->t('@class for entity type @entity_type_id must be instantiable.', [
            '@class' => $reflection->getName(),
            '@entity_type_id' => $entityTypeId,
          ]));
        }

        if (!$reflection->isSubclassOf($entityBaseClass)) {
          throw new \InvalidArgumentException($this->t('@class for @entity_type_id is not a subclass of entity base class @base_class', [
            '@class' => $entityBundleClass,
            '@base_class'=> $entityBaseClass,
            '@entity_type_id' => $entityTypeId,
          ]));
        }

        $this->reflectedSubClasses[$entityBaseClass][$entityBundleClass] = TRUE;
      }

      return $entityBundleClass;
    }

    if (empty($this->reflectedClasses[$entityBaseClass])) {
      $reflection = new \ReflectionClass($entityBaseClass);
      if (!$reflection->isInstantiable()) {
        throw new \InvalidArgumentException($this->t('No bundle class found for @entity_type_id:@bundle, and base class @class cannot be instantiated.', [
          '@bundle' => $bundle,
          '@entity_type_id' => $entityTypeId,
          '@class' => $reflection->getName(),
        ]));
      }
    }

    $this->reflectedClasses[$entityBaseClass] = TRUE;
    return $entityBaseClass;
  }

}
