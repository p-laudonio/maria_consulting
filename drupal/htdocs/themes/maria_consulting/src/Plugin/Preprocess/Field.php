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
      $nid = $node->id();
      $field_image = $node->get('field_image');
      $image_iterator = $field_image->getIterator();
      $iterator = $element['#items']->getIterator();
      $total = $element['#items']->count();
      for ($delta = 0; $delta < $total; $delta++) {
        $element = $iterator->offsetExists($delta) ? $iterator->offsetGet($delta) : FALSE;
        $element_image = $image_iterator->offsetExists($delta) ? $image_iterator->offsetGet($delta) : FALSE;
        if ($element_image && $element) {
          if (in_array($nid, array(7, 8, 9, 10))) {
            $col1 = 2;
            $col2 = 10;
          } else {
            $col1 = 5;
            $col2 = 7;
          }
          $element_image_view = $element_image->view();
          $element_view = $element->view();
          $raw_html = '<div class="block-6 views-row">
                             <div class="description col-sm-' . $col1 . '">
                             <figure class="img-polaroid">
                             ' . render($element_image_view) . '
                             </figure>
                             </div>
                             <div class="field-content related-services list col-sm-' . $col2 . '">
                             ' . render($element_view) . '
                             </div>
                             </div>';
          $markup = \Drupal\Core\Render\Markup::create($raw_html);
          $variables['items'][$delta]['content'] = $markup;
        }
      }
    }
    parent::preprocessVariables($variables);
  }

}
