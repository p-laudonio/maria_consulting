<?php
/**
 * @file
 * Contains \Drupal\maria_consulting\Plugin\Alter\MariaThemeSuggestions.
 */

namespace Drupal\maria_consulting\Plugin\Alter;

use Drupal\bootstrap\Plugin\Alter\ThemeSuggestions;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements hook_theme_suggestions_alter().
 *
 * @ingroup plugins_alter
 *
 * @BootstrapAlter("theme_suggestions")
 */
class MariaThemeSuggestions extends ThemeSuggestions implements ContainerFactoryPluginInterface
{
  /**
   * Current Route Match.
   *
   * @var RouteMatchInterface
   */
  protected $route_match;

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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->route_match = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
  {
    /** @var RouteMatchInterface $route_match */
    $route_match = $container->get('current_route_match');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $route_match
    );
  }

  /**
   * {@inheritdoc}
   */
  public function alter(&$suggestions, &$variables = [], &$hook = NULL)
  {
    // Add some custom suggestions:
    if ($hook == 'page_title' && ($taxonomy = $this->route_match->getParameter('taxonomy_term'))) {
      $vocabularyId = $taxonomy->getVocabularyId();
      $suggestions[] = 'page_title__taxonomy__term__' . $vocabularyId;
    }

    elseif ($hook == 'item_list' && ($taxonomy = $this->route_match->getParameter('taxonomy_term'))) {
      $vocabularyId = $taxonomy->getVocabularyId();
      $suggestions[] = $variables['theme_hook_original'] . '__taxonomy__term__' . $vocabularyId;
    }

    /** @var Term $taxonomy */
    elseif ($hook == 'page' && ($taxonomy = $this->route_match->getParameter('taxonomy_term'))) {
      $vocabularyId = $taxonomy->getVocabularyId();
      $suggestions[] = 'page__taxonomy__term__' . $vocabularyId;
    }

    elseif ($hook == 'page' && ($node = $this->route_match->getParameter('node'))) {
      $content_type = $node->bundle();
      $suggestions[] = 'page__' . $content_type;
    }

    elseif ($hook == 'views_view_unformatted') {

      if (!empty($variables['view'])) {
        /** @var \Drupal\views\ViewExecutable $view */
        $view = $variables['view'];
        $view_id = $view->id();

        if ($view_id == 'taxonomy_term') {
          $taxonomy = $this->route_match->getParameter('taxonomy_term');
          $vocabularyId = $taxonomy->getVocabularyId();
          $suggestions[] = $variables['theme_hook_original'] . '__taxonomy__term__' . $vocabularyId;
        }
      }

    }

    parent::alter($suggestions, $variables, $hook);
  }

}
