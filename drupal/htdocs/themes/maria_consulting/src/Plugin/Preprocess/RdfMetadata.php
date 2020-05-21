<?php

namespace Drupal\maria_consulting\Plugin\Preprocess;

use Drupal\maria_custom\MariaCustomService;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\bootstrap\Utility\Element;
use Drupal\bootstrap\Utility\Variables;
use Drupal\Core\Template\Attribute;
use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\bootstrap\Plugin\Preprocess\PreprocessInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Pre-processes variables for the "rdf_metadata" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @see rdf_metadata.html.twig
 *
 * @BootstrapPreprocess("rdf_metadata")
 */
class RdfMetadata extends PreprocessBase implements PreprocessInterface, ContainerFactoryPluginInterface {

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
    /** @var ContentEntityInterface $contentEntity */
    if ($contentEntity = $this->route_match->getParameter('taxonomy_term')) {
      if( $contentEntity->bundle() == 'tags') {
        $image_info_default = ['field_service_image', 'original'];
        $image_info = $this->customService->getImageData($contentEntity, $image_info_default);
        $new_atr = new Attribute();
        $new_atr->setAttribute('property', 'schema:primaryImageOfPage');
        $new_atr->setAttribute('content', $image_info['url']);
        $variables['metadata'][] = $new_atr;
      }
      else {
        $new_atr = new Attribute();
        $new_atr->setAttribute('property', 'schema:name');
        $new_atr->setAttribute('content', $contentEntity->label());
        $variables['metadata'][] = $new_atr;

        $new_atr = new Attribute();
        $new_atr->setAttribute('property', 'schema:description');
        $new_atr->setAttribute('content', $contentEntity->getDescription());
        $variables['metadata'][] = $new_atr;
      }
    }

  }

}
