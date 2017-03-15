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

namespace Drupal\entity_inheritance;


interface ContentEntityBundleClassLocating {

  /**
   * Get the instance class for an entity bundle.
   */
  public function getClassForEntityBundle(string $entityTypeId, string $bundle, string $entityBaseClass): string;

  /**
   * Get a list of classes for entity bundles.
   *
   * @return array<string, classname<ContentEntityInterface>>
   */
  public function getBundleClassesForEntityType(string $entityTypeId, string $entityBaseClass): array;

}
