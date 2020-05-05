<?php

namespace Drupal\maria_consulting\Plugin\Preprocess;

use Drupal\maria_custom\MariaCustomService;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\views\Render\ViewsRenderPipelineMarkup;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\bootstrap\Utility\Variables;
use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\bootstrap\Plugin\Preprocess\PreprocessInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Pre-processes variables for the "item_list" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @see item-list.html.twig
 *
 * @BootstrapPreprocess("item_list")
 */
class ItemList extends PreprocessBase implements PreprocessInterface, ContainerFactoryPluginInterface {

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
    /** @var ContentEntityInterface $term */
    if ($term = $this->route_match->getParameter('taxonomy_term')) {
      if ($term->bundle() == 'tags') {

        static $all_links = [];
        $current_url = $term->toUrl()->toString();
        // https://www.drupal.org/docs/8/api/cache-api/cache-contexts
        $variables->addCacheContexts(['url.path']);

        $tot = count($variables['items']);
        for ($delta = 0; ($delta < $tot); $delta++) {
          /** @var ViewsRenderPipelineMarkup $myItem */
          $myItem = $variables['items'][$delta]['value'];

          $alias = $this->customService->findURLfromHTML($myItem->jsonSerialize());
          if (!empty($alias)) {

            // We do not show link to the current page.
            if ($alias == $current_url) {
              $variables['items'][$delta]['hide'] = true;
            }
            // Show the link only on the first occurrence.
            elseif(($my_term = $this->customService->getEntityByAlias($alias)) && $my_term instanceof ContentEntityInterface) {
              $my_tid = $my_term->id();
              if (!empty($all_links[$my_tid])) {
                // https://www.drupal.org/docs/8/api/render-api/cacheability-of-render-arrays
                // $variables->addCacheableDependency($my_term);
                $variables['items'][$delta]['hide'] = true;
              }
              else {
                $all_links[$my_tid] = 1;
              }
            }

          }

        }
      }
    }
  }

}
