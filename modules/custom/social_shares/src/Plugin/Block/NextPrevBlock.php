<?php

namespace Drupal\social_shares\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Provides a 'PrevNextBlock' block.
 *
 * @Block(
 *  id = "prev_next_block",
 *  admin_label = @Translation("Prev next block"),
 * )
 */
class NextPrevBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $previous_link = NULL;
    $previous_title = NULL;
    $next_link = NULL;
    $next_title = NULL;
    $node = \Drupal::routeMatch()->getParameter('node');
    if (is_object($node)) {
      if ($node->getCreatedTime() != NULL) {
        $created = $node->getCreatedTime();
        $prev_id = \Drupal::entityQuery('node')
          ->condition('status', 1)
          ->condition('type', $node->getType())
          ->condition('created', $created, "<")
          ->sort('created', 'DESC')
          ->range(0, 1)
          ->execute();

        $next_id = \Drupal::entityQuery('node')
          ->condition('status', 1)
          ->condition('type', $node->getType())
          ->condition('created', $created, ">")
          ->sort('created')
          ->range(0, 1)
          ->execute();
        if (!empty($prev_id)) {
          $id = array_values($prev_id)[0];
          $prev = Node::load($id);
          $previous_link = Url::fromUserInput("/node/" . $prev->id());
          $previous_title = $prev->label();
        }

        if ($next_id) {
          $id = array_values($next_id)[0];
          $next = Node::load($id);
          $next_link = Url::fromUserInput("/node/" . $next->id());
          $next_title = $next->label();
        }

        $build['nav_pager'] = [
          '#theme' => 'nav_pager',
          '#previous_link' => $previous_link,
          '#previous_title' => $previous_title,
          '#next_link' => $next_link,
          '#next_title' => $next_title
        ];
      }
    }
    $build['#cache']['max-age'] = 0;
    return $build;
  }

}
