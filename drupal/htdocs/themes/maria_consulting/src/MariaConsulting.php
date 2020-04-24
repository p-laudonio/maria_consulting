<?php
/**
 * @file
 * Contains \Drupal\maria_consulting\MariaConsulting.
 */

namespace Drupal\maria_consulting;

/**
 * The primary class for the Drupal MariaConsulting sub theme.
 *
 * Provides many helper methods.
 *
 * @ingroup utility
 */
class MariaConsulting
{

  public static $field_tag = array();

  /**
   * Initializes the active theme.
   */
  final public static function initialize() {
    static $initialized = FALSE;
    if (!$initialized) {
      // Initialise some variables:

      $initialized = TRUE;
    }
  }

  /**
   * Implement hook_preprocess_views_view_field.
   */
  public static function preprocess_views_view_field(&$variables) {
    if($variables['view']->current_display == "home_slider_page"){
      $captions = array();
      $node = $variables["row"]->_entity;
      if($node instanceof \Drupal\node\Entity\Node){
        $body = $node->get('body');
        $body_it = $body->getIterator();
        $element = $body_it->offsetGet($variables['view']->row_index);
        $caption = render($element->view());
      }
      $variables['output'] = \Drupal\Core\Render\Markup::create($caption . $variables['field']->advancedRender($variables['row'])->__toString());

      // In taxonomy page remove the link to the same page:
    }elseif($variables['view']->current_display == "page_1"){
      $field_name = $variables['field']->realField;
      if($field_name == "field_tags_target_id"){
        $node = $variables["row"]->_entity;
        if($node instanceof \Drupal\node\Entity\Node){
          $type = $node->getType();
          if($type == 'service'){
            $field_tags = $node->get('field_tags')->getValue();
            $new_field_tags = array();
            $current_term = \Drupal::routeMatch()->getParameter('taxonomy_term');
            if($current_term){
              $current_tid = $current_term->id();
              if(in_array($current_tid, self::$field_tag)) self::$field_tag[] = $current_tid;
              foreach($field_tags as $field_tag){
                if (!in_array($field_tag['target_id'], self::$field_tag)){
                  $new_field_tags[] = $field_tag;
                  self::$field_tag[] = $field_tag['target_id'];
                }
              }
              if(count($new_field_tags) > 0){
                $node->get('field_tags')->setValue($new_field_tags);
                $variables["row"]->_entity = $node;
                $raw_html = $variables['field']->advancedRender($variables['row'])->__toString();
                $variables['output'] = \Drupal\Core\Render\Markup::create($raw_html);
              }
            }
          }
        }
      } // if field name is "field_tags_target_id"
    }
  }

  /**
   * Provides the list of all services details to be used in Related Services templates.
   *
   * @return array $tags_array
   *   An associative array containing tid/taxonomy details value pairs.
   */
  public static function getServicesDetails() {
    $tags_array = array(
      1 => array(
        'name' => "Our Services & Solutions",
        'image' => 'solutions.jpg',
        'caption' => 'Solutions',
        'href' => 'services',
        'description' => 'We offer custom Drupal 7 and 8 Solutions. We are specialised in Financial Architecture, migration from previous version to Drupal 8 version.',
      ),
      2 => array(
        'name' => "Web & Interactive Design",
        'image' => 'theming.jpg',
        'caption' => 'Web Design',
        'href' => 'services/theming',
        'description' => 'Drupal 8 is very well integrated with the latest Version of Bootstrap. There are so many responsive themes that you can use: there is not limit what you can achieve!',
      ),
      3 => array(
        'name' => "Web Strategy",
        'image' => 'web-strategy.jpg',
        'caption' => 'Strategy',
        'href' => 'services/web-strategy',
        'description' => 'Do you need a very simple marketing brochure website or a very big robust intranet system? Plan which modules you need to install and keep it simple!',
      ),
      4 => array(
        'name' => "Search Engine Optimization",
        'image' => 'search-engine-optimization.jpg',
        'caption' => 'SEO',
        'href' => 'services/search-engine-optimization',
        'description' => 'Drupal 8 has got extremely SEO Friendly functionalities such as url alias, metatags for entities, nodes, taxonomies, page views, redirects, google analytics, etc.',
      ),
      5 => array(
        'name' => "Information Architecture",
        'image' => 'information-architecture.jpg',
        'caption' => 'Architecture',
        'href' => 'services/information-architecture',
        'description' => 'Drupal 8 Architecture is very robust, fast and Object Oriented. The Drupal Core rely on many Symfony base Components that give a great entity abstraction.',
      ),
      6 => array(
        'name' => "Mobile Websites",
        'image' => 'responsive-themes.jpg',
        'caption' => 'Mobile',
        'href' => 'services/mobile-websites',
        'description' => 'Drupal is ideal to build mobile web sites. I recently built a sub-theme of the Drupal Bootstrap base theme using the latest CDN Starterkit.',
      ),
      7 => array(
        'name' => "Custom Modules",
        'image' => 'custom-modules.jpg',
        'caption' => 'Custom',
        'href' => 'services/custom-modules',
        'description' => 'We are specialised in Custom Module Development and more recently in custom migratation modules from Drupal 7 web site into Drupal 8.',
      ),
      8 => array(
        'name' => "Content Management",
        'image' => 'content-management.jpg',
        'caption' => 'Management',
        'href' => 'services/content-management',
        'description' => 'Manage your content with Drupal: create as many content types as you like, hundreds of Taxonomies, Nodes and plenty of views to disply them!',
      ),
      9 => array(
        'name' => "System (CMS)",
        'image' => 'system-cms.jpg',
        'caption' => 'Drupal',
        'href' => 'services/system-cms',
        'description' => 'The great advantage of using Drupal is that is not just free to download, but it really helps to solve real problems and simplify the build of a complex CMS.',
      ),
      10 => array(
        'name' => "Drupal consulting",
        'image' => 'drupal-consulting.jpg',
        'caption' => 'Consulting',
        'href' => 'services/drupal-consulting',
        'description' => 'Contact the Drupal Experts for a free consultation, get a fast and effective support. Get your custom solution, we can create the module that does the job.',
      ),
      11 => array(
        'name' => "eCommerce / Online Store",
        'image' => 'ecommerce.jpg',
        'caption' => 'eCommerce',
        'href' => 'services/ecommerce',
        'description' => 'Drupal 8 is very secure when it comes to secure login, manage online payments, basket, category list, products management. Recently I also used Drupal 7 in Enterprise Financial Solution such as P2P.',
      ),
      12 => array(
        'name' => "Audio & Video",
        'image' => 'system-cms.jpg',
        'caption' => 'Multimedia',
        'href' => 'services/audio-video',
        'description' => 'Drupal has evolved a lot in the recent years, the latest release follow the most recent and advanced web development techniques to embed Audio & Video.',
      ),
      13 => array(
        'name' => "User account registration",
        'image' => 'user-account-registration.jpg',
        'caption' => 'Open Source',
        'href' => 'services/user-account-registration',
        'description' => 'Scalable, efficient, highly performing and SEO friendly Dupal CMS. Our Government Intranet project has got more than 50,000 registered users.',
      ),
      14 => array(
        'name' => "Increase Donations",
        'image' => 'solutions.jpg',
        'caption' => 'Charity',
        'href' => 'services/charity',
        'description' => 'The advanced features of apache web server ensure a stable and secure architecture. Open source is ideal for organisations that have little budgets.',
      ),
      15 => array(
        'name' => "Increase Sales",
        'image' => 'search-engine-optimization.jpg',
        'caption' => 'Business',
        'href' => 'services/increase-sales',
        'description' => 'Drupal is free to download and the only cost for increasing your Sales is to pay for the consulting Drupal experts that can setting it up for you!',
      ),
      16 => array(
        'name' => "Email Marketing",
        'image' => 'marketing.jpg',
        'caption' => 'Marketing',
        'href' => 'services/email-marketing',
        'description' => 'Drupal 8 has got a robust built-in and secure user registration system. Collect customer information, user preferences to make a unique user journey.',
      ),
      17 => array(
        'name' => "Web Statistics & Analysis",
        'image' => 'analysis.jpg',
        'caption' => 'Analysis',
        'href' => 'services/web-statistics-analysis',
        'description' => 'Collect visitor statistics for your web site with Google Analytics. We offer free consultation for installing GA and Metatags modules on your Drupal web site.',
      ),
      18 => array(
        'name' => "Integrated Content Strategy",
        'image' => 'analysis.jpg',
        'caption' => 'Integrated',
        'href' => 'services/integrated-content-strategy',
        'description' => 'Plan your content types, permissions, roles, views and taxonomies. Set up different contexts, view modes, panel variants and view.',
      ),
      19 => array(
        'name' => "Accessibility",
        'image' => 'web-strategy.jpg',
        'caption' => 'Accessibility',
        'href' => 'services/accessibility',
        'description' => 'Drupal 8 custom drop down menus, blocks, breadcrumbs, light box, search results offer a wide range of options on how to accesss and find your relevant content.',
      ),
      20 => array(
        'name' => "Training & Consultation",
        'image' => 'training-consultation.jpg',
        'caption' => 'Support',
        'href' => 'services/training-consultation',
        'description' => 'Groups organize, meet, and work on projects based on interest or geographic location. It\'s a great way to get involved, learn more and get support.',
      ),
      21 => array(
        'name' => "Database Integration",
        'image' => 'database-integration.jpg',
        'caption' => 'Integration',
        'href' => 'services/database-integration',
        'description' => 'Drupal can be integrated with complex system that uses other databases such as PostgreSQL, MongoDB, Oracles or even other Custom MySQL Servers.',
      ),
      22 => array(
        'name' => "Multi-Language Websites",
        'image' => 'multi-language.jpg',
        'caption' => 'Multilingual',
        'href' => 'services/multi-language',
        'description' => 'Drupal is a very powerful tool to set up multilingual web sites: Internationalization module helps to easily translate content in different languages.',
      ),
      23 => array(
        'name' => "Mobile Development",
        'image' => 'responsive-themes.jpg',
        'caption' => 'Development',
        'href' => 'services/mobile-development',
        'description' => 'Drupal Bootstrap Sub Themes are ideal to implement responsive solutions for Mobile devices and tablets with all kind of screen resolutions from 320px up to 800px!',
      ),
      24 => array(
        'name' => "Usability Testing",
        'image' => 'testing.jpg',
        'caption' => 'Testing',
        'href' => 'services/usability-testing',
        'description' => 'Usability Testing is very important to identify and resolve issues and fix bugs before the web site is deployed on the live server: PHP unit Test, Behat testing.',
      ),
      25 => array(
        'name' => "Investments",
        'image' => 'investing.jpg',
        'caption' => 'Financial',
        'href' => 'services/investments',
        'description' => 'In the recent years I built very secure and scalable Drupal systems for Financial Businesses to handle their customers sensitive information.',
      ),
      26 => array(
        'name' => "Stocks and Shares",
        'image' => 'stocks-and-shares.jpg',
        'caption' => 'Markets',
        'href' => 'services/stocks-and-shares',
        'description' => 'Drupal dynamic Jquery Chart and Graph plugins for Stocks and Shares web sites: Charts and Graphs Flot, Views Charts, Poll Chart Block and a lot more.',
      ),
      27 => array(
        'name' => "Saving accounts",
        'image' => 'saving-accounts.jpg',
        'caption' => 'Savings',
        'href' => 'services/saving-accounts',
        'description' => 'Data management, risk analysis, Cash ISA, Loan Interest Calculator and other very complex finacial applications with Equifax and Cifas Direct.',
      ),
      28 => array(
        'name' => "Peer to peer lending",
        'image' => 'peer-to-peer-lending.jpg',
        'caption' => 'P2P Lending',
        'href' => 'services/peer-peer-lending',
        'description' => 'In Peer to peer lending money from Investors is split in hundreds of micro loans and borrowers repayments are proportionally repaid back to Lenders.',
      ),
      29 => array(
        'name' => "Property investment",
        'image' => 'property-investment.jpg',
        'caption' => 'Property',
        'href' => 'services/property-investment',
        'description' => 'Buy and sell properties online with Drupal Custom websites: The right place to meet for Sellers and buyers: Email alerts, marketing notifications and more.',
      ),
    );
    return $tags_array;
  }

  /**
   * Provides the list of all spacial services details to be used in Related Services templates.
   *
   * @return array $special_services
   *   An associative array containing nid/service details value pairs.
   */
  public static function getSpecialServices() {
    $special_services = array(
      0 => array(
        'nid' => 33,
        'key' => 4,
        'image' => 'migrate-drupal.jpg',
        'caption' => 'Migrate',
        'href' => 'migrate-drupal',
        'description' => 'Migrate Drupal by follow our very simple Six Steps! Set up your YML configuration to define your custom migrate modules to import data from Drupal 7.',
      ),
      1 => array(
        'nid' => 12,
        'key' => 1,
        'image' => 'responsive-themes.jpg',
        'caption' => 'Responsive',
        'href' => 'responsive-design',
        'description' => 'We are specialised in building custom Drupal Bootstrap Responsive Sub Themes: we use open-source HTML / CSS framework to build responsive website.',
      ),
      2 => array(
        'nid' => 18,
        'key' => 2,
        'image' => 'install-drupal.jpg',
        'caption' => 'Install Drupal 8',
        'href' => 'installing-drupal',
        'description' => 'Drupal 8 is an open source CMC, see how it is easy to set up on my Guide on how to install Drupal 8.',
      ),
    );
    return $special_services;
  }

  /**
   * Return the list of Node ID of all spacial services.
   *
   * @return array $nids
   */
  public static function getSpecialServicesNIDs() {
    $special_services = self::getSpecialServices();
    $nids = [];
    foreach ($special_services as $special_service) {
      if (!empty($special_service['nid'])) {
        $nids[] = $special_service['nid'];
      }
    }
    return $nids;
  }

  /**
   * Provides the list of 4 top main services to be used in Related Services template.
   *
   * @return array $special_services
   *   An associative array containing 4 services.
   */
  public static function getMoreServices(array $tags_array, array $special_services, array $my_tids) {
    $more_services = array();
    $index = 0;
    foreach($my_tids as $tid){
      if($index < 4 && isset($tags_array[$tid])){
        $element = $tags_array[$tid];
        $element['key'] = $index + 1;
        $more_services[$index] = $element;
        $index++;
      }
    }

    // Make sure you always have 4 services:
    $count = count($more_services);
    $index = 0;
    for($i = $count; $i < 5; $i++){
      if (isset($special_services[$index])) {
        $element = $special_services[$index];
        $element['key'] = $i + 1;
        $more_services[$i] = $element;
        $index++;
      }
    }

    return $more_services;
  }

}