<?php

/**
 * @file
 * Contains \Drupal\maria_custom\Plugin\Block\MoreServiceBlock.
 */

namespace Drupal\maria_custom\Plugin\Block;

use Drupal\maria_custom\MariaCustomService;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block with 4 elements showing more services.
 *
 * @Block(
 *   id = "maria_custom_service_block",
 *   admin_label = @Translation("More Services"),
 *   category = @Translation("Maria Custom block"),
 * )
 */
class MoreServiceBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * By Default we want to Display Up to 4 Elements.
   */
  const MAX_ELEMENTS = 4;

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
   * Creates a WebformBlock instance.
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
  public function build() {

    $more_services = [];

    if (!empty($this->configuration['promoted_services'])) {
      $promoted_nids = explode(',', $this->configuration['promoted_services']);
    }
    else {
      $promoted_nids = [];
    }

    if (!empty($this->configuration['promoted_terms'])) {
      $promoted_tids = explode(',', $this->configuration['promoted_terms']);
    }
    else {
      $promoted_tids = [];
    }

    // It should go through this page only when the Route Match is not a Node Service.
    $tot_promoted = count($promoted_tids) + count($promoted_nids);
    if ($tot_promoted > (self::MAX_ELEMENTS-1)) {

      if (!empty($promoted_tids)) {
        $tags_array = $this->getServicesDetailsByTid($promoted_tids);
      }
      else {
        $tags_array = [];
      }

      if (!empty($promoted_nids)) {
        $special_services = $this->getSpecialServicesByNIDs($promoted_nids, count($promoted_nids)+1);
      }
      else {
        $special_services = [];
      }

      $more_services = $this->getMoreServices($tags_array, $special_services);
    }

    // For Node Service we get the TIDs from field_tags and NIDs from field_more_services.
    elseif ($node = $this->route_match->getParameter('node')) {

      $content_type = $node->bundle();
      $nid = $node->id();
      if ($content_type == "service" && isset($node->field_tags)) {

        $field_tags = $node->get('field_tags');
        $my_tags_list = $field_tags->getValue();

        $my_tids = array();
        foreach ($my_tags_list as $term) {
          $my_tids[] = $term['target_id'];
        }

        // In special Service we remove the first link because it is already inside the BreadCrumb.
        $special_service_nids = $this->getSpecialServicesNIDs();

        $skip_first = in_array($node->id(), $special_service_nids);
        if ($skip_first) {
          // Deleting first array item
          array_shift($my_tids);
        }
        $tags_array = $this->getServicesDetailsByTid($my_tids);
        if (count($tags_array) < self::MAX_ELEMENTS) {
          $max = self::MAX_ELEMENTS - count($tags_array);
          // Load the More Services Node IDs.
          $field_more_services = $node->get('field_more_services');
          $more_services_list = $field_more_services->getValue();
          $my_nids = array();
          foreach ($more_services_list as $term) {
            $my_nids[] = $term['target_id'];
          }
          $special_services = $this->getSpecialServices($nid, $max, $my_nids);
          $more_services = $this->getMoreServices($tags_array, $special_services);
        } else {
          $more_services = $tags_array;
        }
      }
    }

    if (!empty($more_services)) {
      $build = [
        '#theme' => 'maria_custom_service_block',
        '#more_services' => $more_services,
      ];
      //$build['#cache']['max-age'] = 0;
    }
    else {
      $markup = $this->t('More service block did not find any services.');
      $build = [
        '#markup' => $markup,
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    //With this when your node change your block will rebuild
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      //if there is node add its cachetag
      return Cache::mergeTags(parent::getCacheTags(), array('node:' . $node->id()));
    } else {
      //Return default tags instead.
      return parent::getCacheTags();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    //if you depends on \Drupal::routeMatch()
    //you must set context of this block with 'route' context tag.
    //Every new route this block will rebuild
    return Cache::mergeContexts(parent::getCacheContexts(), array('route'));
  }

  /**
   * Helper function to get the first top services details by Taxonomy IDs.
   *
   * @return array $tags_array
   *   An associative array containing tid/taxonomy details value pairs.
   */
  private function getServicesDetailsByTid($my_tids, $limit = 4)
  {
    $result = [];
    $key = 1;
    foreach ($my_tids as $tid) {
      $more_service = $this->customService->getServiceDetails($tid);
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
   * Get the list of all spacial services details to be used in Related Services templates.
   * @param int $exclude_nid
   * @param int $max
   *
   * @return array $special_services
   *   An associative array containing nid/service details value pairs.
   */
  private function getSpecialServices($exclude_nid = FALSE, $max = 4, array $special_service_nids = [])
  {
    $special_services = [];

    // If this Service does not have any related nids just take them randomly.
    if (empty($special_service_nids)) {
      $special_service_nids = $this->getSpecialServicesNIDs();
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
          $array_services = $this->getSpecialServicesByNIDs([$nid], $key);
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
   * Helper function to get the list of Node ID of all spacial services.
   *
   * @return array $nids
   */
  private function getSpecialServicesNIDs()
  {
    if (!empty($this->configuration['special_service_nids'])) {
      $nids = explode(',', $this->configuration['special_service_nids']);
    }
    else {
      $nids = [33, 12, 18, 14];
    }
    return $nids;
  }

  /**
   * Return a specific list of Special Service Nodes.
   * @param  array $nids
   *
   * @return array $special_services
   */
  private function getSpecialServicesByNIDs($nids, $start_key = 1)
  {
    $special_services = [];
    $key = $start_key;
    foreach ($nids as $nid) {
      $service_item = $this->customService->getSpecialService($nid);
      if (!empty($service_item)) {
        $service_item['key'] = $key;
        $key++;
        $special_services[] = $service_item;
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
  private function getMoreServices(array $tags_array, array $special_services)
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

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {

    return [
      'promoted_terms' => '',
      'promoted_services' => '',
      'special_service_nids' => '33,12,18,14',
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['promoted_terms'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Promoted terms'),
      '#description' => $this->t('The list of Term IDs (comma separated) to promote inside this block.'),
      '#default_value' => isset($config['promoted_terms']) ? $config['promoted_terms'] : '',
    ];

    $form['promoted_services'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Promoted services'),
      '#description' => $this->t('The list of Service Node IDs (comma separated) to promote inside this block.'),
      '#default_value' => isset($config['promoted_services']) ? $config['promoted_services'] : '',
    ];

    $form['special_service_nids'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Special services'),
      '#description' => $this->t('The list of Service Node IDs (comma separated) that are allowed to be promoted.'),
      '#default_value' => isset($config['special_service_nids']) ? $config['special_service_nids'] : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['promoted_terms'] = $form_state->getValue('promoted_terms');
    $this->configuration['promoted_services'] = $form_state->getValue('promoted_services');
    $this->configuration['special_service_nids'] = $form_state->getValue('special_service_nids');
  }

}