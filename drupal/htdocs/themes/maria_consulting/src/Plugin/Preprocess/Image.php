<?php

namespace Drupal\maria_consulting\Plugin\Preprocess;

use Drupal\bootstrap\Utility\Element;
use Drupal\bootstrap\Utility\Variables;
use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\bootstrap\Plugin\Preprocess\PreprocessInterface;

/**
 * Pre-processes variables for the "image" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @see image.html.twig
 *
 * @BootstrapPreprocess("image")
 */
class Image extends PreprocessBase implements PreprocessInterface {

  /**
   * {@inheritdoc}
   */
  public function preprocessVariables(Variables $variables) {
    if ($current_user = \Drupal::routeMatch()->getParameter('user')) {
      /** @var \Drupal\bootstrap\Utility\Attributes $attr */
      $attr = $variables->getAttributes();
      $attr->removeAttribute('typeof');

      $variables->setAttribute('itemprop','image');
      $variables->setAttribute('property','schema:image');
    }
    else {
      $title = $variables->getAttribute('title');
      if (empty($title)) {
        if ($current_node = \Drupal::routeMatch()->getParameter('node')) {
          $content_type = $current_node->bundle();
          if ($content_type == "service") {
            $title = $current_node->label();
            $alt = $variables->getAttribute('alt');
            if (empty($alt)) {
              $variables->setAttribute('alt', $title);
            }
            $variables->setAttribute('title', $title);
          }
        }
      }
    }
  }

}
