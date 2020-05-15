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
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Pre-processes variables for the "user" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @see views-view-unformatted.html.twig
 *
 * @BootstrapPreprocess("views_view_unformatted")
 */
class ViewsViewUnformatted extends PreprocessBase implements PreprocessInterface, ContainerFactoryPluginInterface {

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
    /** @var ViewExecutable $view */
    $view = $variables['view'];
    $rows = $variables['rows'];

    if ($view->id() == 'my_work_experience' && $view->current_display == 'my_recent_roles_block' ) {

      /** @var \Drupal\user\Entity\User $user */
      $user = \Drupal::routeMatch()->getParameter('user');
      $full_name = false;
      if ($user && $user->hasField('field_first_name') &&
        $user->hasField('field_last_name')) {
        $full_name = (string) t('@first_name @last_name',
          ['@first_name' => $user->field_first_name->value,
            '@last_name' => $user->field_last_name->value,
          ]);
      }

      foreach ($rows as $id => $row) {

        /** @var \Drupal\node\Entity\Node $node */
        $node = $row['content']['#row']->_entity;

        // Show the Company Work Experience only on the user page.
        $variables['rows'][$id]['company_details'] = false;
        $variables['rows'][$id]['full_name'] = $full_name;
        if ($full_name && $node->hasField('field_company_details')) {
          $values = explode(',', $node->field_company_details->value);
          if (count($values) > 3) {
            list($company, $address, $city, $post_code) = $values;
            $variables['rows'][$id]['company_details'] = [
              'company' => $company,
              'address' => $address,
              'city' => $city,
              'post_code' => $post_code
            ];
          }
        }

        $periods = $node->field_period->getValue();
        if ($full_name && !empty($periods)) {
          $variables['rows'][$id]['company_details']['start'] = $periods[0]['value'];
          $variables['rows'][$id]['company_details']['end'] = $periods[0]['end_value'];
        }
        else {
          $variables['rows'][$id]['company_details']['start'] = false;
          $variables['rows'][$id]['company_details']['end'] = false;
        }

      }

    }

  }

  /**
   * {@inheritdoc}
   */
  protected function preprocessElement(Element $element, Variables $variables) {

  }

}
