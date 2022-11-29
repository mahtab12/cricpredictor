<?php

namespace Drupal\social_shares\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Amazon Sidebar' Block.
 *
 * @Block(
 *   id = "betting_block",
 *   admin_label = @Translation("Betting Block"),
 *   category = @Translation("Betting Block"),
 * )
 */
class BettingBlock extends BlockBase {

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