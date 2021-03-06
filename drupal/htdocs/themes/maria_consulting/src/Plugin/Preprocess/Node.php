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
 * Pre-processes variables for the "item_list" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @see node.html.twig
 *
 * @BootstrapPreprocess("node")
 */
class Node extends PreprocessBase implements PreprocessInterface, ContainerFactoryPluginInterface {

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

    /** @var ContentEntityInterface $node */
    $node = isset($arr['#node']) ? $arr['#node'] : null;

    // We need to add the RDF properties only when the node view mode is full.
    if ($view_mode == 'full' && !empty($node) && $node instanceof ContentEntityInterface) {
      $rdf_type = $this->customService->getRdfType($node);

      if($node->bundle() == 'service') {
        $variables->setAttribute('typeof', 'schema:' . $rdf_type);
      }
      elseif ($node->bundle() == 'project') {
        $variables->setAttribute('typeof', 'schema:' . $rdf_type);
        $variables['work_experience'] = false;
        if ($job_node = $this->customService->getFirstReferencedEntity($node, 'field_job')) {
          $company_details = $this->customService->getCompanydetails($job_node);
          $variables['work_experience'] = [
            'url' => $company_details['company_url'],
            'title' => $job_node->label(),
            'company' => $company_details['company'] . ' in ' . $company_details['city'],
            'job_title' => $company_details['job_title'],
          ];
        }
      }

      $variables['rdf_type'] = $rdf_type;
      $variables['node_name'] = $node->label();
      $variables['node_description'] = $this->customService->getTeaserDescription($node);
      $variables['node_date_created'] = $this->customService->getDateCreated($node);
      $variables['node_date_modified'] = $this->customService->getDateModified($node);
    } // Node is in view mode full.

  }

}
