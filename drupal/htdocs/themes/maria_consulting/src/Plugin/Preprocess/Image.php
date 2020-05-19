<?php

namespace Drupal\maria_consulting\Plugin\Preprocess;

use Drupal\maria_custom\MariaCustomService;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\bootstrap\Utility\Variables;
use Drupal\bootstrap\Plugin\Preprocess\PreprocessBase;
use Drupal\bootstrap\Plugin\Preprocess\PreprocessInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Pre-processes variables for the "image" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @see image.html.twig
 *
 * @BootstrapPreprocess("image")
 */
class Image extends PreprocessBase implements PreprocessInterface, ContainerFactoryPluginInterface
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
    $vars = $variables->getArrayCopy();
    $style_name = !empty($vars['style_name']) ? $vars['style_name'] : 'original';
    $rdf_type = '';

    /** @var \Drupal\bootstrap\Utility\Attributes $attr */
    $attr = $variables->getAttributes();
    $attr->removeAttribute('typeof');

    if ($contentEntity = $this->route_match->getParameter('user')) {

    }
    elseif (in_array($style_name, ['thumbnail_service'])) {
      $contentEntity = false;
    }
    elseif ($contentEntity = $this->route_match->getParameter('node')) {
      if (in_array($style_name, ['original', 'medium'])) {
        $title = $variables->getAttribute('title');
        if (empty($title)) {
          $title = $contentEntity->label();
          $alt = $variables->getAttribute('alt');
          if (empty($alt)) {
            $variables->setAttribute('alt', $title);
          }
          $variables->setAttribute('title', $title);
        }
      }
    }
    else
    {
      $contentEntity = $this->route_match->getParameter('taxonomy_term');
    }
    if ($contentEntity && $contentEntity instanceof ContentEntityInterface) {
      $bundle = $contentEntity->bundle();
      $rdf_type = $this->customService->getRdfType($contentEntity);
    }
    // Only when it is shown on the main WebPage then set the Image as primaryImageOfPage.
    if (in_array($rdf_type, ['WebPage']) && $style_name == 'original' && in_array($bundle, ['project', 'service'])) {
      $variables->setAttribute('property', 'schema:primaryImageOfPage');
      $variables->setAttribute('typeof', 'schema:ImageObject');
    }
    elseif (in_array($rdf_type, ['HowTo', 'Article', 'Person'])) {
      $variables->setAttribute('property','schema:image');
    }
    // When the image appears in its own then we must specify the type of.
    else {
      $variables->setAttribute('typeof', 'schema:Image');
    }
  }

}
