<?php
/**
 * @file
 * Contains \Drupal\maria_consulting\Plugin\Preprocess\Breadcrumb.
 */

namespace Drupal\maria_consulting\Plugin\Preprocess;

use Drupal\bootstrap\Annotation\BootstrapPreprocess;
use Drupal\bootstrap\Utility\Variables;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * Pre-processes variables for the "menu__main" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("menu__main")
 */
class Menu extends \Drupal\bootstrap\Plugin\Preprocess\Menu
{

  /**
   * {@inheritdoc}
   */
  public function preprocessVariables(Variables $variables)
  {
    parent::preprocessVariables($variables);
    foreach ($variables->items as $key => &$item) {
      if ($item['attributes'] instanceof Attribute && $item['url'] instanceof Url) {
        $url_name = trim($item['url']->toString(), "/");
        $item['attributes']->setAttribute('class', $url_name);
      }
    }
  }
}
