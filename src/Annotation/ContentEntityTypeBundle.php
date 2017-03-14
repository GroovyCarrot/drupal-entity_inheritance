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

namespace Drupal\entity_inheritance\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * @Annotation
 */
class ContentEntityTypeBundle extends Plugin {

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->definition['class'];
  }

  /**
   * The entity type that this class is for.
   *
   * @var string
   */
  public $entity_type_id;

  /**
   * The bundle id that this class is used for.
   *
   * @var string
   */
  public $bundle_id;

}
