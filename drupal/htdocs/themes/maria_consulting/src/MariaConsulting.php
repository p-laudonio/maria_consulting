<?php
/**
 * @file
 * Contains \Drupal\maria_consulting\MariaConsulting.
 */

namespace Drupal\maria_consulting;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermStorage;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\image\Entity\ImageStyle;
use Drupal\file\Entity\File;
use Drupal\Core\Url;

/**
 * The primary class for the Drupal MariaConsulting sub theme.
 *
 * Provides many helper methods.
 *
 * @ingroup utility
 */
class MariaConsulting
{

  public static $field_tag = array();

  /**
   * Initializes the active theme.
   */
  final public static function initialize()
  {
    static $initialized = FALSE;
    if (!$initialized) {
      // Initialise some variables:

      $initialized = TRUE;
    }
  }

  /**
   * Implement hook_preprocess_views_view_field.
   */
  public static function preprocess_views_view_field(&$variables)
  {
    if ($variables['view']->current_display == "home_slider_page") {
      $captions = array();
      $node = $variables["row"]->_entity;
      if ($node instanceof \Drupal\node\Entity\Node) {
        $body = $node->get('body');
        $body_it = $body->getIterator();
        $element = $body_it->offsetGet($variables['view']->row_index);
        $caption = render($element->view());
      }
      $variables['output'] = \Drupal\Core\Render\Markup::create($caption . $variables['field']->advancedRender($variables['row'])->__toString());

      // In taxonomy page remove the link to the same page:
    } elseif ($variables['view']->current_display == "page_1") {
      $field_name = $variables['field']->realField;
      if ($field_name == "field_tags_target_id") {
        $node = $variables["row"]->_entity;
        if ($node instanceof \Drupal\node\Entity\Node) {
          $type = $node->getType();
          if ($type == 'service') {
            $field_tags = $node->get('field_tags')->getValue();
            $new_field_tags = array();
            $current_term = \Drupal::routeMatch()->getParameter('taxonomy_term');
            if ($current_term) {
              $current_tid = $current_term->id();
              if (in_array($current_tid, self::$field_tag)) self::$field_tag[] = $current_tid;
              foreach ($field_tags as $field_tag) {
                if (!in_array($field_tag['target_id'], self::$field_tag)) {
                  $new_field_tags[] = $field_tag;
                  self::$field_tag[] = $field_tag['target_id'];
                }
              }
              if (count($new_field_tags) > 0) {
                $node->get('field_tags')->setValue($new_field_tags);
                $variables["row"]->_entity = $node;
                $raw_html = $variables['field']->advancedRender($variables['row'])->__toString();
                $variables['output'] = \Drupal\Core\Render\Markup::create($raw_html);
              }
            }
          }
        }
      } // if field name is "field_tags_target_id"
    }
  }

}
