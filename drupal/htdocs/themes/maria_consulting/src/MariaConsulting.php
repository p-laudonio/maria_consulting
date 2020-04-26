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

  /**
   * Provides the list of all services details to be used in Related Services templates.
   * @param int $tid
   *
   * @return array $term_item
   *   An associative array containing tid/taxonomy details value pairs.
   */
  public static function getServiceDetails($tid)
  {
    $term_item = [];
    try {
      /** @var TermStorage $termManager */
      $termManager = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
      $term = $termManager->load($tid);

      if ($term instanceof Term) {
        $field_service_image = $term->get('field_service_image');
        if (!$field_service_image->isEmpty()) {
          $image_iterator = $field_service_image->getIterator();
          if ($image_iterator->offsetExists(0)) {
            /** @var ImageItem $element_image */
            $element_image = $image_iterator->offsetGet(0);
            $value = $element_image->getValue();
            /** @var File $file */
            $file = File::load($value['target_id']);

            $term_id = $term->id();
            $term_url = Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $term_id])->toString();

            $term_item = array(
              "tid" => $term_id,
              'key' => 1,
              "name" => $term->label(),
              "image" => '/sites/default/files/styles/medium/public/service/' . $file->getFilename(),
              'caption' => $term->field_term_teaser_title->value,
              'alt' => $value['alt'],
              'title' => $value['title'],
              'href' => $term_url,
              'description' => strip_tags($term->field_term_teaser->value),
            );

          }
        }
      }
    }
    catch (PluginNotFoundException $exception) {
      throw new NotFoundHttpException("Plugin Not Found Exception");
    }
    catch (InvalidPluginDefinitionException $exception) {
      throw new NotFoundHttpException("Invalid Plugin Definition Exception");
    }

    return $term_item;
  }

  /**
   * Get the first top services details by Taxonomy IDs.
   *
   * @return array $tags_array
   *   An associative array containing tid/taxonomy details value pairs.
   */
  public static function getServicesDetailsByTid($my_tids, $limit = 4)
  {
    $result = [];
    $key = 1;
    foreach ($my_tids as $tid) {
      $more_service = self::getServiceDetails($tid);
      if (!empty($more_service) && $key < ($limit + 1)) {
        $more_service['key'] = $key;
        $result[] = $more_service;
        $key++;
      } elseif ($key > $limit) {
        break;
      }
    }

    return $result;
  }

  /**
   * Provides the list of all spacial services details to be used in Related Services templates.
   * @param int $exclude_nid
   * @param int $max
   *
   * @return array $special_services
   *   An associative array containing nid/service details value pairs.
   */
  public static function getSpecialServices($exclude_nid = FALSE, $max = 4, array $special_service_nids = [])
  {
    $special_services = [];

    // If this Service does not have any related nids just take them randomly.
    if (empty($special_service_nids)) {
      $special_service_nids = self::getSpecialServicesNIDs();
      if ($exclude_nid) {
        $special_service_nids = array_diff($special_service_nids, [$exclude_nid]);
      }
      // Randomise the order:
      shuffle($special_service_nids);
    }

    if ($max < 5) {
      $key = 5 - $max;
      foreach ($special_service_nids as $nid) {
        if (count($special_services) < $max) {
          $array_services = self::getSpecialServicesByNIDs([$nid], $key);
          $special_services[] = reset($array_services);
          $key++;
        } elseif (count($special_services) > $max) {
          break;
        }
      }
    }

    return $special_services;
  }

  /**
   * Return the list of Node ID of all spacial services.
   *
   * @return array $nids
   */
  public static function getSpecialServicesNIDs()
  {
    $nids = [33, 12, 18, 14];
    return $nids;
  }

  /**
   * Return a specific list of Special Service Nodes.
   * @param  array $nids
   *
   * @return array $special_services
   */
  public static function getSpecialServicesByNIDs($nids, $start_key = 1)
  {
    $special_services = [];
    $key = $start_key;
    foreach ($nids as $nid) {
      $node = Node::load($nid);
      if ($node instanceof Node && $node->hasField('field_image')) {
        $field_image = $node->get('field_image');
        if (!$field_image->isEmpty()) {
          $image_iterator = $field_image->getIterator();
          if ($image_iterator->offsetExists(0)) {
            /** @var ImageItem $element_image */
            $element_image = $image_iterator->offsetGet(0);
            $value = $element_image->getValue();
            /** @var File $file */
            $file = File::load($value['target_id']);

            $nid = $node->id();
            $node_url = $node->toUrl()->toString();

            if (empty($value['title'])) {
              $value['title'] = $node->label();
            }
            // Caption is the first word in the image title.
            $title_parts = explode(' ', $value['title']);
            $caption = $title_parts[0];

            $image_uri = $file->getFileUri();
            $image_style = ImageStyle::load('medium');
            $image_url = $image_style->buildUrl($image_uri);

            $service_item = array(
              "nid" => $nid,
              'key' => $key,
              "name" => $node->label(),
              "image" => $image_url,
              'caption' => $caption,
              'alt' => $value['alt'],
              'title' => $value['title'],
              'href' => $node_url,
              'description' => $node->field_image_text_preview->value,
            );
            $key++;
            $special_services[] = $service_item;
          }
        }
      }
    }
    return $special_services;
  }

  /**
   * Provides the list of 4 top main services to be used in Related Services template.
   *
   * @return array $special_services
   *   An associative array containing 4 services.
   */
  public static function getMoreServices(array $tags_array, array $special_services)
  {
    $more_services = array_merge($tags_array, $special_services);

    // Sort Special Services by Key:
    usort($more_services, "self::compareService");

    // Return only the first 4:
    $result = [];
    $key = 1;
    foreach ($more_services as $more_service) {
      if ($key < 5) {
        $more_service['key'] = $key;
        $result[] = $more_service;
      } else {
        break;
      }
      $key++;
    }

    return $result;
  }

  /**
   * Compare 2 Services by key.
   *
   * @return int
   */
  public static function compareService($a, $b)
  {
    return $a["key"] - $b["key"];
  }

}
