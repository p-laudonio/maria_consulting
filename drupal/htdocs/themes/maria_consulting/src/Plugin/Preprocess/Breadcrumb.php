<?php
/**
 * @file
 * Contains \Drupal\maria_consulting\Plugin\Preprocess\Breadcrumb.
 */

namespace Drupal\maria_consulting\Plugin\Preprocess;

use Drupal\maria_custom\MariaCustomService;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\bootstrap\Utility\Variables;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Pre-processes variables for the "breadcrumb" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("breadcrumb")
 */
class Breadcrumb extends \Drupal\bootstrap\Plugin\Preprocess\Breadcrumb implements ContainerFactoryPluginInterface
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
    parent::preprocessVariables($variables);

    if (!empty($variables['breadcrumb'])) {
      $current_url = Url::fromRoute('<current>')->toString();
      foreach ($variables['breadcrumb'] as $index => $item) {
        $item_url = $variables['breadcrumb'][$index]['url'];
        if ($item_url == $current_url) {
          $variables['breadcrumb'][$index] = [
            'text' => $variables['breadcrumb'][$index]['text'],
            'url' => false,
            'attributes' => new Attribute(['class' => ['active']]),
          ];
        }
      }
      /** @var ContentEntityInterface $contentEntity */
      if ($contentEntity = $this->route_match->getParameter('node')) {
      }
      else
      {
        $contentEntity = $this->route_match->getParameter('taxonomy_term');
      }
      $rdf_type = '';
      if ($contentEntity && $contentEntity instanceof ContentEntityInterface) {
        $rdf_type = $this->customService->getRdfType($contentEntity);
        // Set the Breadcrumd as Property ONLY for WebPage.
        if ($rdf_type == 'WebPage') {
          $variables->setAttribute('property', 'schema:breadcrumb');
        }
      }
      $variables['current_url'] = $current_url;
      $variables['rdf_type'] = $rdf_type;
    }

  }
}
