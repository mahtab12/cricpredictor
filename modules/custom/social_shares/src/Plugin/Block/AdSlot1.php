<?php

namespace Drupal\social_shares\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Adslot1' Block.
 *
 * @Block(
 *   id = "adslot_1",
 *   admin_label = @Translation("Adslot1"),
 *   category = @Translation("Adslot1"),
 * )
 */
class AdSlot1 extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['adslot'] = [
      '#theme' => 'adslot',
      '#data' => null,
    ];
    $build['#cache']['max-age'] = 0;
    return $build;
  }
}