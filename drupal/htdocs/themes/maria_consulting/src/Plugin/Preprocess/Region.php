<?php
/**
 * @file
 * Contains \Drupal\maria_consulting\Plugin\Preprocess\Region.
 */

namespace Drupal\maria_consulting\Plugin\Preprocess;

use Drupal\bootstrap\Annotation\BootstrapPreprocess;
use Drupal\bootstrap\Bootstrap;
use Drupal\bootstrap\Utility\Variables;

/**
 * Pre-processes variables for the "region" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("region")
 */
class Region extends \Drupal\bootstrap\Plugin\Preprocess\Region  {

  /**
   * {@inheritdoc}
   */
  public function preprocessVariables(Variables $variables) {
    $region = $variables['elements']['#region'];
    if($region == "sidebar_second"){
      $term = \Drupal::routeMatch()->getParameter('taxonomy_term');
      if($term){
        $rightBody = $term->get('field_right_body');
        $iterator = $rightBody->getIterator();
        $element = $iterator->offsetGet(0);
        if($element){
          $raw_html = render($element->view());
          $variables['elements']['#children'] = \Drupal\Core\Render\Markup::create($raw_html . $variables['content']->__toString());
        }
      }
    }
    parent::preprocessVariables($variables);
  }

}
