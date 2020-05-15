<?php
namespace Drupal\maria_custom\Breadcrumb;

use Drupal\system\PathBasedBreadcrumbBuilder;

use Drupal\maria_custom\MariaCustomService;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Link;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;


class mariaBreadcrumbBuilder implements BreadcrumbBuilderInterface {
  use StringTranslationTrait;

  /**
   * By Default we want to set the Resume Page to node 53.
   */
  const RESUME_NODE_ID = 53;

  /**
   * By Default we want to set the Service Solution Page to taxonomy term 1.
   */
  const SERVICES_TERM_ID = 1;

  /**
   * The node request Object.
   *
   * @var Node
   */
  protected $node = false;

  /**
   * The taxonomy_term request Object.
   *
   * @var Term
   */
  protected $taxonomy_term = false;

  /**
   * Bundles are a type of container.
   * For node is the content type.
   * For taxonomy_term is vocabulary Id.
   *
   * @var string
   */
  protected $bundle = '';

  /**
   * Custom Module to handle all the Storage managers.
   *
   * @var MariaCustomService
   */
  protected $customService;

  /**
   * The router request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $context;

  /**
   * The menu link access service.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * The dynamic router service.
   *
   * @var \Symfony\Component\Routing\Matcher\RequestMatcherInterface
   */
  protected $router;

  /**
   * The inbound path processor.
   *
   * @var \Drupal\Core\PathProcessor\InboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * Site config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * The current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The current path service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The patch matcher service.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Constructs the PathBasedBreadcrumbBuilder.
   *
   * @param \Drupal\Core\Routing\RequestContext $context
   *   The router request context.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The menu link access service.
   * @param \Symfony\Component\Routing\Matcher\RequestMatcherInterface $router
   *   The dynamic router service.
   * @param \Drupal\Core\PathProcessor\InboundPathProcessorInterface $path_processor
   *   The inbound path processor.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user object.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher service.
   * @param MariaCustomService $customService
   *   The path matcher service.
   */

  public function __construct(RequestContext $context, AccessManagerInterface $access_manager, RequestMatcherInterface $router, InboundPathProcessorInterface $path_processor, ConfigFactoryInterface $config_factory, TitleResolverInterface $title_resolver, AccountInterface $current_user, CurrentPathStack $current_path, PathMatcherInterface $path_matcher = NULL, MariaCustomService $customService = NULL) {
    $this->context = $context;
    $this->accessManager = $access_manager;
    $this->router = $router;
    $this->pathProcessor = $path_processor;
    $this->config = $config_factory->get('system.site');
    $this->titleResolver = $title_resolver;
    $this->currentUser = $current_user;
    $this->currentPath = $current_path;
    $this->pathMatcher = $path_matcher ?: \Drupal::service('path.matcher');
    $this->customService = $customService;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $attributes) {
    //$debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
    $parameters = $attributes->getParameters()->all();

    // Determine if the current page is a node page
    if (isset($parameters['node']) && !empty($parameters['node'])) {
      $this->node = $parameters['node'];
      $this->bundle = $this->node->bundle();
      return TRUE;
    }

    // Determine if the current page is a node page
    elseif (isset($parameters['taxonomy_term']) && !empty($parameters['taxonomy_term'])) {
      $this->taxonomy_term = $parameters['taxonomy_term'];
      $this->bundle = $this->taxonomy_term->getVocabularyId();
      return true;
    }

    // Still here? This does not apply.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    // Define a new object of type Breadcrumb
    $breadcrumb = new Breadcrumb();

    if (in_array($this->bundle, ['work_experience', 'project', 'service', 'tags'])) {

      // Add the Home link.
      $home_link = Link::createFromRoute($this->t('Drupal Freelance'), '<front>');
      $breadcrumb->addLink($home_link);

      switch ( $this->bundle ) {
        case 'work_experience':
          if ($resume_link = $this->getResumeLink()) $breadcrumb->addLink($resume_link);
          if ($this->node) {
            $menu_link = $this->customService->getMenuLinkContent($this->node);
            if ($menu_link) {
              $links = $this->customService->getMenuLinkTrail($menu_link);
              // Deleting first array item
              // $removed = array_shift($links);
              foreach ($links as $link) {
                $breadcrumb->addLink($link);
              }
            }
          }
          break;

        case 'project':
          if ($resume_link = $this->getResumeLink()) $breadcrumb->addLink($resume_link);
          if ($this->node && $this->node->hasField('field_job')) {
            $job_nodes = $this->node->field_job->referencedEntities();
            if (!empty($job_nodes) && $job_node = reset($job_nodes)) {
              if ($job_node instanceof Node && $job_node->bundle() == 'work_experience') {
                $menu_link = $this->customService->getMenuLinkContent($job_node);
                if ($menu_link) {
                  $links = $this->customService->getMenuLinkTrail($menu_link);
                  foreach ($links as $link) {
                    $breadcrumb->addLink($link);
                  }
                }
              }
            }
          }
          break;

        case 'service':
          if ($services_url = $this->getServiceLink(self::SERVICES_TERM_ID)) $breadcrumb->addLink($services_url);
          if ($this->node && $this->node->hasField('field_tags')) {
            $field_tags = $this->node->field_tags->referencedEntities();
            if (!empty($field_tags)) {
              /** @var Term $field_tag */
              foreach ($field_tags as $field_tag) {
                // Do not add twice Service Term.
                if ($field_tag instanceof Term && $field_tag->id() != self::SERVICES_TERM_ID) {
                  $service_link = $this->getServiceLink($field_tag->id(), $field_tag->label());
                  $breadcrumb->addLink($service_link);
                  break;
                }
              }
            }
          }
          break;

        case 'tags':
          if ($this->taxonomy_term) {
            // Add the Service Link unless is not the Service page.
            if ($this->taxonomy_term->id() != self::SERVICES_TERM_ID) {
              if ($services_url = $this->getServiceLink(self::SERVICES_TERM_ID)) $breadcrumb->addLink($services_url);
            }

            $parents = $this->taxonomy_term->parent->referencedEntities();
            if (!empty($parents)) {
              $parent = reset($parents);
              if ($parent instanceof Term && $parent->id() != self::SERVICES_TERM_ID) {
                if ($services_url = $this->getServiceLink($parent->id(), $parent->label())) $breadcrumb->addLink($services_url);
              }
            }

            if ($services_url = $this->getServiceLink($this->taxonomy_term->id(), $this->taxonomy_term->label())) $breadcrumb->addLink($services_url);
          }
          break;
      }

    }

    // Add cache control by a route otherwise all pages will have the same breadcrumb.
    $breadcrumb->addCacheContexts(['route']);

    return $breadcrumb;
  }

  /**
   * Utility: Return the Resume Link.
   *
   * @return Link $resume_link
   */
  private function getResumeLink() {
    $resume_url = Url::fromRoute('entity.node.canonical', ['node' => self::RESUME_NODE_ID]);
    if ($resume_url) {
      $resume_link = Link::fromTextAndUrl ($this->t('Resume'), $resume_url);
    }
    else {
      $resume_link = false;
    }
    return $resume_link;
  }

  /**
   * Utility: Return the Services Link.
   *  * @param int $term_id
   *  The Term ID
   *
   * @return Link $service_link
   */
  private function getServiceLink($term_id, $term_name = 'Services') {
    $service_url = Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $term_id]);
    if ($service_url) {
      $service_link = Link::fromTextAndUrl ($this->t($term_name), $service_url);
    }
    else {
      $service_link = false;
    }
    return $service_link;
  }

}
