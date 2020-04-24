<?php
/**
 * @file
 * Contains \Drupal\maria_consulting\Plugin\Preprocess\Field.
 */

namespace Drupal\maria_consulting\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\bootstrap\Plugin\Preprocess\PreprocessInterface;
use Drupal\bootstrap\Utility\Variables;

/**
 * Pre-processes variables for the "field" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("field")
 */
class Field extends PreprocessBase implements PreprocessInterface
{

  /**
   * {@inheritdoc}
   */
  public function preprocessVariables(Variables $variables)
  {

    $element = $variables['element'];

    if ($element['#field_name'] == "field_job_title") {
      $current_nid = 0;
      if ($current_node = \Drupal::routeMatch()->getParameter('node')) {
        $current_nid = $current_node->id();
      }
      $node = $element['#object'];
      $nid = $node->id();
      if ($nid != $current_nid) {
        $path_alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $nid);
        $value = $variables['items'][0]['content']['#context']['value'];
        $variables['items'][0]['content'] = \Drupal\Core\Render\Markup::create("<a href='$path_alias'>$value</a>");
      }

      // For node services concatenates the body[$delta] with field_image[[$delta]]:
    } elseif ($element['#field_name'] == 'body' && $element['#bundle'] == "service") {
      $node = $element['#object'];
      $field_image = $node->get('field_image');
      $image_iterator = $field_image->getIterator();
      $total = $element['#items']->count();
      for ($delta = 0; $delta < $total; $delta++) {
        if ($image_iterator->offsetExists($delta)) {
          $element_image = $image_iterator->offsetGet($delta);
          $element_image_view = $element_image->view();
          $raw_html = render($element_image_view);
          $markup = \Drupal\Core\Render\Markup::create($raw_html);
          $variables['items'][$delta]['service_body_image'] = $markup;
        }
        else {
          $variables['items'][$delta]['service_body_image'] = FALSE;
        }
      }
    }
    parent::preprocessVariables($variables);
  }

}
