<?php
/**
 * @file
 * Contains \Drupal\maria_consulting\Plugin\Preprocess\Page.
 */

namespace Drupal\maria_consulting\Plugin\Preprocess;

use Drupal\bootstrap\Utility\Element;
use Drupal\bootstrap\Utility\Variables;
use Drupal\Core\Render\Markup;
use Drupal\taxonomy\Entity\Term;

/**
 * Pre-processes variables for the "page" theme hook.
 * Please see https://drupal-bootstrap.org/api/bootstrap/docs%21plugins%21Preprocess.md/group/plugins_preprocess/8
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("page")
 */
class Page extends \Drupal\bootstrap\Plugin\Preprocess\Page
{

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables, $hook, array $info)
  {
    $variables['page_name'] = 'page-generic';
    $is_front = \Drupal::service('path.matcher')->isFrontPage();
    if ($is_front) {
      $variables['page_name'] = 'page-front';
    } elseif ($node = \Drupal::routeMatch()->getParameter('node')) {
      $content_type = $node->bundle();
      $nid = $node->id();
      $variables['page_name'] = 'page-' . $nid;
      if (in_array($content_type, array("page", 'work_experience'))) {
        $variables['page']['sidebar_second']['#region'] = 'sidebar_second_' . $content_type;
      }

      // For webform we need to print the body in a different place:
      if ($content_type == "webform" && isset($node->body)) {
        $webform = $node->get('webform');
        $iterator = $webform->getIterator();
        if ($iterator->offsetExists(0)) {
          /** @var \Drupal\webform\Plugin\Field\FieldType\WebformEntityReferenceItem $element */
          $element = $iterator->offsetGet(0);
          $element_view = $element->view();
          $raw_html = render($element_view);
          $variables['node_webform'] = Markup::create($raw_html);
        }
      }
    }
    /** @var Term $taxonomy_term */
    elseif ($taxonomy_term = \Drupal::routeMatch()->getParameter('taxonomy_term')) {
      $vocabularyId = $taxonomy_term->getVocabularyId();
      if ($vocabularyId == 'tags') {
        $variables['page_name'] = 'page-service-taxonomy';
        $variables['page']['service_page_title'] = Markup::create($variables['page']['#title']);
        $variables['page']['service_image'] = FALSE;

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
