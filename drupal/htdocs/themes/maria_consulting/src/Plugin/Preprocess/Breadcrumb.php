<?php
/**
 * @file
 * Contains \Drupal\maria_consulting\Plugin\Preprocess\Breadcrumb.
 */

namespace Drupal\maria_consulting\Plugin\Preprocess;

use Drupal\bootstrap\Annotation\BootstrapPreprocess;
use Drupal\bootstrap\Utility\Variables;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * Pre-processes variables for the "breadcrumb" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("breadcrumb")
 */
class Breadcrumb extends \Drupal\bootstrap\Plugin\Preprocess\Breadcrumb
{

  /**
   * {@inheritdoc}
   */
  public function preprocessVariables(Variables $variables)
  {
    // Add custom breadcrumb for wide Service pages:
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      $content_type = $node->bundle();
      if ($content_type == "service" && isset($node->field_tags)) {
        // Set the node ID if we're on a node page.
        $wide_template = in_array($node->id(), array(12, 33));
        if ($wide_template) {
          $field_tags = $node->get('field_tags');
          $iterator = $field_tags->getIterator();
          if ($iterator->offsetExists(0)) {
            $first_tag = $iterator->offsetGet(0);
            $my_view = $first_tag->view();
            $variables['breadcrumb'][] = array(
              'text' => render($my_view)
            );
          }
        }
      }
    }
    parent::preprocessVariables($variables);
  }

}
