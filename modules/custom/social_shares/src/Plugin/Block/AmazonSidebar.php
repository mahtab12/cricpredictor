<?php

namespace Drupal\social_shares\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Amazon Sidebar' Block.
 *
 * @Block(
 *   id = "amazon_sidebar",
 *   admin_label = @Translation("Amazon Sidebar"),
 *   category = @Translation("Amazon Sidebar"),
 * )
 */
class AmazonSidebar extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['amazon_sidebar'] = [
      '#theme' => 'amazon_sidebar',
      '#data' => null,
    ];
    return $build;
  }
}