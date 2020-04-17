<?php
/**
 * @file
 * Contains \Drupal\maria_consulting\Plugin\Preprocess\Page.
 */

namespace Drupal\maria_consulting\Plugin\Preprocess;

use Drupal\bootstrap\Annotation\BootstrapPreprocess;
use Drupal\bootstrap\Utility\Element;
use Drupal\bootstrap\Utility\Variables;
use Drupal\Core\Render\Markup;
use Drupal\maria_consulting\MariaConsulting;

// use Drupal\bootstrap\Bootstrap;
// use Drupal\bootstrap\Plugin\PreprocessManager;

/**
 * Pre-processes variables for the "page" theme hook.
 * Please see https://drupal-bootstrap.org/api/bootstrap/docs%21plugins%21Preprocess.md/group/plugins_preprocess/8
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("page")
 */
class Page extends \Drupal\bootstrap\Plugin\Preprocess\Page {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info)
  {
    // Discover all the theme's preprocess plugins:
    /*
    $preprocess_manager = new PreprocessManager(Bootstrap::getTheme());
    $plugins = $preprocess_manager->getDefinitions();
    kint($plugins);
    */

    $is_front = \Drupal::service('path.matcher')->isFrontPage();
    if($is_front){
      $my_tids = array(9, 22, 10, 20);
      $tags_array = MariaConsulting::getServicesDetails();
      $variables['more_services'] = MariaConsulting::getMoreServices($tags_array, array(), $my_tids);

    }elseif ($node = \Drupal::routeMatch()->getParameter('node')) {
      $content_type = $node->bundle();
      $nid = $node->id();
      if ($content_type == "service" && isset($node->field_tags)) {
        // Set the node ID if we're on a node page.
        $nid = isset($variables['node']) ? $variables['node']->id() : '';

        $field_tags = $node->get('field_tags');
        $my_tags_list = $field_tags->getValue();

        /*
        $iterator = $field_tags->getIterator();
        $raw_html = '<div class="field--name-field-right-body"><ul class="list">';
        foreach ($iterator as $element) {
          $raw_html .= '<li>' . render($element->view()) . '</li>';
        }
        $raw_html .= '</ul></div>';
        $variables['page_service_tags'] = Markup::create($raw_html);
        */

        $my_tids = array();
        foreach($my_tags_list as $term){
          $my_tids[] = $term['target_id'];
        }

        if(!in_array($nid, array(12, 33))){
          $tags_array = MariaConsulting::getServicesDetails();
          $special_services = MariaConsulting::getSpecialServices();
          $variables['more_services'] = MariaConsulting::getMoreServices($tags_array, $special_services, $my_tids);
        }else{
          $variables['more_services'] = false;
        }

      }elseif (in_array($content_type, array("page", 'work_experience'))){
        $variables['page']['sidebar_second']['#region'] = 'sidebar_second_' . $content_type;
      }

      // For webform we need to print the body in a different place:
      if ($content_type == "webform" && isset($node->body)) {
        $webform = $node->get('webform');
        $iterator = $webform->getIterator();
        $element = $iterator->offsetGet(0);
        $raw_html = render($element->view());
        $variables['node_webform'] = Markup::create($raw_html);
      }
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
  public function preprocessVariables(Variables $variables) {
    parent::preprocessVariables($variables);
  }

  /**
   * {@inheritdoc}
   */
  protected function preprocessElement(Element $element, Variables $variables) {
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
