<?php

namespace Drupal\maria_consulting\Plugin\Preprocess;

use Drupal\maria_custom\MariaCustomService;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\bootstrap\Utility\Variables;
use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\bootstrap\Plugin\Preprocess\PreprocessInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Pre-processes variables for the "item_list__search_results" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @see item-list.html.twig
 *
 * @BootstrapPreprocess("item_list__search_results")
 */
class ItemListSearchResults extends PreprocessBase implements PreprocessInterface, ContainerFactoryPluginInterface {

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, MariaCustomService $customService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->route_match = $route_match;
    $this->customService = $customService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
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
  public function preprocessVariables(Variables $variables) {
    if (!empty($variables['items'])) {
      foreach ($variables['items'] as $delta => $item) {
        if (!empty($item['value']['#result']['node'])) {
          /** @var \Drupal\node\Entity\Node $node */
          $node = $item['value']['#result']['node'];
          $image_data = $this->customService->getImageData($node);
          if ($image_data['found']) {
            $variables['items'][$delta]['node_image'] = $image_data;
          }
          else {
            $variables['items'][$delta]['node_image'] = false;
          }
        }
      }
    }
  }

}
