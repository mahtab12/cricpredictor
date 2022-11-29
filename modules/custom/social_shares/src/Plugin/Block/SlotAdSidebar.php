<?php

namespace Drupal\social_shares\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Adslot_Sidebar' Block.
 *
 * @Block(
 *   id = "adslot_sidebar",
 *   admin_label = @Translation("Adslot Sidebar"),
 *   category = @Translation("Adslot Sidebar"),
 * )
 */
class SlotAdSidebar extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['adslot_sidebar'] = [
      '#theme' => 'adslot_sidebar',
      '#data' => null,
    ];
    return $build;
  }
}