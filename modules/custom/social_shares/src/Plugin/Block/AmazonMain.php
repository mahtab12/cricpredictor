<?php

namespace Drupal\social_shares\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Amazon Main' Block.
 *
 * @Block(
 *   id = "amazon_main",
 *   admin_label = @Translation("Amazon Main"),
 *   category = @Translation("Amazon Main"),
 * )
 */
class AmazonMain extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['amazon_main'] = [
      '#theme' => 'amazon_main',
      '#data' => null,
    ];
    return $build;
  }
}