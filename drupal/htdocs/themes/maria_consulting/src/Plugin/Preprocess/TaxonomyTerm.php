<?php

namespace Drupal\maria_consulting\Plugin\Preprocess;

use Drupal\maria_custom\MariaCustomService;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\bootstrap\Utility\Element;
use Drupal\bootstrap\Utility\Variables;
use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\bootstrap\Plugin\Preprocess\PreprocessInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Pre-processes variables for the "taxonomy_term" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @see taxonomy-term.html.twig
 *
 * @BootstrapPreprocess("taxonomy_term")
 */
class TaxonomyTerm extends PreprocessBase implements PreprocessInterface, ContainerFactoryPluginInterface {

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

  }

  /**
   * {@inheritdoc}
   */
  protected function preprocessElement(Element $element, Variables $variables) {
    $arr = $element->getArray();
    $view_mode = !empty($arr['#view_mode']) ? $arr['#view_mode'] : '';

    /** @var ContentEntityInterface $taxonomy_term */
    $taxonomy_term = isset($arr['#taxonomy_term']) ? $arr['#taxonomy_term'] : null;

    // We need to add the RDF properties only when the node view mode is full.
    if ($view_mode == 'full' && !empty($taxonomy_term) && $taxonomy_term instanceof ContentEntityInterface) {
      $rdf_type = $this->customService->getRdfType($taxonomy_term);
      $variables->setAttribute('typeof', 'schema:' . $rdf_type);
    }

  }

}
