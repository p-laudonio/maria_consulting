<?php
/**
 * @file
 * Contains \Drupal\maria_consulting\Plugin\Preprocess\Field.
 */

namespace Drupal\maria_consulting\Plugin\Preprocess;

use Drupal\Core\Url;
use Drupal\maria_custom\MariaCustomService;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\bootstrap\Plugin\Preprocess\PreprocessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\bootstrap\Utility\Variables;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Pre-processes variables for the "field" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("field")
 */
class Field extends PreprocessBase implements PreprocessInterface, ContainerFactoryPluginInterface
{

  /**
   * Current Route Match.
   *
   * @var RouteMatchInterface
   */
  protected $route_match;

  /**
   * Custom Module to handle all the Storage managers.
   *
   * @var MariaCustomService
   */
  protected $customService;

  /**
   * Creates a ItemList instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, MariaCustomService $customService)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->route_match = $route_match;
    $this->customService = $customService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    /** @var RouteMatchInterface $route_match */
    $route_match = $container->get('current_route_match');
    /** @var MariaCustomService $custom_service */
    $custom_service = $container->get('maria_custom.service');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $route_match,
      $custom_service
    );
  }

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
        } else {
          $variables['items'][$delta]['service_body_image'] = FALSE;
        }
      }
    }

    // If you show this fields inside the same Term page hide the current links.
    /** @var ContentEntityInterface $term */
    elseif ($element['#field_name'] == 'field_tags'
      && $term = $this->route_match->getParameter('taxonomy_term')) {
      if ($term->bundle() == 'tags') {

        static $all_links = [];
        $variables->addCacheContexts(['url.path']);

        $tot = count($variables['items']);
        for ($delta = 0; ($delta < $tot); $delta++) {
          /** @var Url $my_url */
          $my_url = $variables['items'][$delta]['content']['#url'];
          $params = $my_url->getRouteParameters();
          if (!empty($params['taxonomy_term'])) {
            $my_tid = intval($params['taxonomy_term']);
            // We do not show link to the current page.
            if ($my_tid == $term->id()) {
              $variables['items'][$delta]['hide'] = true;
            } // Show the link only on the first occurrence.
            elseif (!empty($all_links[$my_tid])) {
              $variables['items'][$delta]['hide'] = true;
            } else {
              $all_links[$my_tid] = 1;
              $variables['items'][$delta]['hide'] = false;
            }
          }
        }

      }
    }
    parent::preprocessVariables($variables);
  }

}
