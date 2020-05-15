<?php
/**
 * @file
 * Contains \Drupal\maria_consulting\Plugin\Preprocess\Page.
 */

namespace Drupal\maria_consulting\Plugin\Preprocess;

use Drupal\maria_custom\MariaCustomService;
use Drupal\bootstrap\Utility\Element;
use Drupal\bootstrap\Utility\Variables;
use Drupal\Core\Render\Markup;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Pre-processes variables for the "page" theme hook.
 * Please see https://drupal-bootstrap.org/api/bootstrap/docs%21plugins%21Preprocess.md/group/plugins_preprocess/8
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("page")
 */
class Page extends \Drupal\bootstrap\Plugin\Preprocess\Page implements ContainerFactoryPluginInterface
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
  public function preprocess(array &$variables, $hook, array $info)
  {
    $variables['page']['header_image'] = FALSE;
    $variables['page_name'] = 'page-generic';
    $is_front = \Drupal::service('path.matcher')->isFrontPage();
    if ($is_front) {
      $variables['page_name'] = 'page-front';
    } elseif ($node = $this->route_match->getParameter('node')) {
      $content_type = $node->bundle();
      $nid = $node->id();
      $variables['page_name'] = 'page-' . $nid;
      if (in_array($content_type, array("page", 'work_experience')) && !empty($variables['page']['sidebar_second'])) {
        $variables['page']['sidebar_second']['#region'] = 'sidebar_second_' . $content_type;
      }

      elseif ($content_type == "project" && $node->hasField('field_header_image')) {
        $field_image = $node->get('field_header_image');
        if (!$field_image->isEmpty()) {
          $image_iterator = $field_image->getIterator();
          if ($image_iterator->offsetExists(0)) {
            $element_image = $image_iterator->offsetGet(0);
            $element_image_view = $element_image->view();
            $raw_html = render($element_image_view);
            $markup = \Drupal\Core\Render\Markup::create($raw_html);
            $variables['page']['header_image'] = $markup;
            $variables['page_name'] .= ' has-header-image';
          }
        }
      }

    }
    /** @var Term $taxonomy_term */
    elseif ($taxonomy_term = $this->route_match->getParameter('taxonomy_term')) {
      $vocabularyId = $taxonomy_term->getVocabularyId();
      if ($vocabularyId == 'tags') {
        $variables['page_name'] = 'page-service-taxonomy';
        $variables['page']['service_page_title'] = Markup::create($variables['page']['#title']);
        $variables['page']['service_image'] = FALSE;
        $variables['page']['service_title_position'] = '';

        if ($taxonomy_term->hasField('field_service_image')) {
          $field_image = $taxonomy_term->get('field_service_image');
          if (!$field_image->isEmpty()) {
            $image_iterator = $field_image->getIterator();
            if ($image_iterator->offsetExists(0)) {
              $element_image = $image_iterator->offsetGet(0);
              $element_image_view = $element_image->view();
              $raw_html = render($element_image_view);
              $markup = \Drupal\Core\Render\Markup::create($raw_html);
              $variables['page']['service_image'] = $markup;
              $variables['page_name'] = 'page-service-taxonomy has-header-image taxonomy-term-' . $taxonomy_term->id();
            }
          }
        }

        if ($taxonomy_term->hasField('field_title_position')) {
          $field_title_position_values = $taxonomy_term->get('field_title_position')->getValue();
          if (!empty($field_title_position_values)) {
            $field_title_position_value = reset($field_title_position_values);
            if (isset($field_title_position_value['value'])) {
              $variables['page']['service_title_position'] = ' ' . $field_title_position_value['value'];
            }
          }
        }

      }
    }

    /** @var User $user */
    elseif ($user = $this->route_match->getParameter('user')) {
      if ($user->hasField('field_job_title')) {
        $variables['page']['content']['maria_consulting_page_title'] = ['#markup' =>
          '<h1 class="page-header">' . $user->field_job_title->value . '</h1>',
        ];
      }
      if ($user->hasField('field_first_name')) {
        $variables['page']['user_first_name'] = $user->field_first_name->value;
      }
      if ($user->hasField('field_last_name')) {
        $variables['page']['user_last_name'] = $user->field_last_name->value;
      }
      if ($user->hasField('field_right_body')) {
        $variables['page']['user_right_body'] = ['#markup' => $user->field_right_body->value];
      }

      if (!empty($variables['page']['sidebar_second'])) {
        $variables['page']['sidebar_second']['#region'] = 'sidebar_second_user';
      }

      // Pass User Attributes.
      $about = $user->toUrl()->toString();
      $variables['user_attributes']['typeof'] = 'schema:Person';
      $variables['user_attributes']['about'] = $about;
    }

    // If you are extending and overriding a preprocess method from the base
    // theme, it is imperative that you also call the parent (base theme) method
    // at some point in the process, typically after you have finished with your
    // preprocessing.
    parent::preprocess($variables, $hook, $info);
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessVariables(Variables $variables)
  {
    parent::preprocessVariables($variables);
  }

  /**
   * {@inheritdoc}
   */
  protected function preprocessElement(Element $element, Variables $variables)
  {
    // This method is only ever invoked if either $variables['element'] or
    // $variables['elements'] exists. These keys are usually only found in forms
    // or render arrays when there is a #type being used. This introduces the
    // Element utility class in the base theme. It too has a bucket-load of
    // features, specific to the unique characteristics of render arrays with
    // their "properties" (keys starting with #). This will quickly allow you to
    // access some of the nested element data and reduce the overhead required
    // for commonly used logic.
    parent::preprocessElement($element, $variables);
  }

}
