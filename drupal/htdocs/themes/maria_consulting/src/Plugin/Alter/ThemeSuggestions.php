<?php
/**
 * @file
 * Contains \Drupal\maria_consulting\Plugin\Alter\ThemeSuggestions.
 */

namespace Drupal\maria_consulting\Plugin\Alter;

use Drupal\bootstrap\Annotation\BootstrapAlter;
use Drupal\bootstrap\Bootstrap;
use Drupal\bootstrap\Plugin\PluginBase;
use Drupal\bootstrap\Utility\Unicode;
use Drupal\bootstrap\Utility\Variables;
use Drupal\Core\Entity\EntityInterface;

/**
 * Implements hook_theme_suggestions_alter().
 *
 * @ingroup plugins_alter
 *
 * @BootstrapAlter("theme_suggestions")
 */
class ThemeSuggestions extends \Drupal\bootstrap\Plugin\Alter\ThemeSuggestions {

  /**
   * {@inheritdoc}
   */
  public function alter(&$suggestions, &$variables = [], &$hook = NULL) {
    // Add some custom suggestions:
    if ($hook == 'page' && ($node = \Drupal::routeMatch()->getParameter('node'))) {
      $content_type = $node->bundle();
      $suggestions[] = 'page__'.$content_type;
    }
    parent::alter($suggestions, $variables, $hook);
  }

}
