<?php
/**
 * @file
 * Entity inheritance
 *
 * Created by Jake Wise 15/03/2017.
 *
 * You are permitted to use, modify, and distribute this file in accordance with
 * the terms of the license agreement accompanying it.
 */

namespace Drupal\entity_inheritance\DataProvider;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity_inheritance\ContentEntityBundleClassLocating;


class ContentEntityBundleFieldDefinitionProvider {

  /** @var ContentEntityBundleClassLocating */
  protected $entityBundleClassLocator;

  public function __construct(
    ContentEntityBundleClassLocating $entityBundleClassLocating
  ) {
    $this->entityBundleClassLocator = $entityBundleClassLocating;
  }

  /**
   * Get the bundle field definitions for a content entity type bundle.
   */
  public function getBundleFieldDefinitions(EntityTypeInterface $entityType, string $entityBundle, array $baseFieldDefinitions): array {
    /** @var ContentEntityInterface[] $bundleClasses */
    $bundleClasses = $this->entityBundleClassLocator->getBundleClassesForEntityType($entityType->id(), $entityType->getClass());

    foreach ($bundleClasses as $bundle => $bundleClass) {
      if ($bundle === $entityBundle) {
        return $bundleClass::bundleFieldDefinitions($entityType, $bundle, $baseFieldDefinitions);
      }
    }

    return [];
  }

}
