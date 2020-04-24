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
  final public static function initialize()
  {
    static $initialized = FALSE;
    if (!$initialized) {
      // Initialise some variables:

      $initialized = TRUE;
    }
  }

  /**
   * Implement hook_preprocess_views_view_field.
   */
  public static function preprocess_views_view_field(&$variables)
  {
    if ($variables['view']->current_display == "home_slider_page") {
      $captions = array();
      $node = $variables["row"]->_entity;
      if ($node instanceof \Drupal\node\Entity\Node) {
        $body = $node->get('body');
        $body_it = $body->getIterator();
        $element = $body_it->offsetGet($variables['view']->row_index);
        $caption = render($element->view());
      }
      $variables['output'] = \Drupal\Core\Render\Markup::create($caption . $variables['field']->advancedRender($variables['row'])->__toString());

      // In taxonomy page remove the link to the same page:
    } elseif ($variables['view']->current_display == "page_1") {
      $field_name = $variables['field']->realField;
      if ($field_name == "field_tags_target_id") {
        $node = $variables["row"]->_entity;
        if ($node instanceof \Drupal\node\Entity\Node) {
          $type = $node->getType();
          if ($type == 'service') {
            $field_tags = $node->get('field_tags')->getValue();
            $new_field_tags = array();
            $current_term = \Drupal::routeMatch()->getParameter('taxonomy_term');
            if ($current_term) {
              $current_tid = $current_term->id();
              if (in_array($current_tid, self::$field_tag)) self::$field_tag[] = $current_tid;
              foreach ($field_tags as $field_tag) {
                if (!in_array($field_tag['target_id'], self::$field_tag)) {
                  $new_field_tags[] = $field_tag;
                  self::$field_tag[] = $field_tag['target_id'];
                }
              }
              if (count($new_field_tags) > 0) {
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
  public static function getServicesDetails()
  {
    $tags_array = array(
      1 => array(
        'tid' => 1,
        'key' => 1,
        'name' => "Our Services & Solutions",
        'image' => 'solutions.jpg',
        'caption' => 'Solutions',
        'alt' => 'Solutions',
        'href' => 'services',
        'description' => 'We offer custom Drupal 7 and 8 Solutions. We are specialised in Financial Architecture, migration from previous version to Drupal 8 version.',
      ),
      2 => array(
        'tid' => 2,
        'key' => 2,
        'name' => "Web & Interactive Design",
        'image' => 'theming.jpg',
        'caption' => 'Web Design',
        'alt' => 'Web Design',
        'href' => 'services/theming',
        'description' => 'Drupal 8 is very well integrated with the latest Version of Bootstrap. There are so many responsive themes that you can use: there is not limit what you can achieve!',
      ),
      3 => array(
        'tid' => 3,
        'key' => 3,
        'name' => "Web Strategy",
        'image' => 'web-strategy.jpg',
        'caption' => 'Strategy',
        'alt' => 'Strategy',
        'href' => 'services/web-strategy',
        'description' => 'Do you need a very simple marketing brochure website or a very big robust intranet system? Plan which modules you need to install and keep it simple!',
      ),
      4 => array(
        'tid' => 4,
        'key' => 4,
        'name' => "Search Engine Optimization",
        'image' => 'search-engine-optimization.jpg',
        'caption' => 'SEO',
        'alt' => 'SEO',
        'href' => 'services/search-engine-optimization',
        'description' => 'Drupal 8 has got extremely SEO Friendly functionalities such as url alias, metatags for entities, nodes, taxonomies, page views, redirects, google analytics, etc.',
      ),
      5 => array(
        'tid' => 5,
        'key' => 1,
        'name' => "Information Architecture",
        'image' => 'businessman-in-big-data-management-concept.jpg',
        'caption' => 'Architecture',
        'alt' => 'businessman in big data management concept',
        'href' => 'services/information-architecture',
        'description' => 'Drupal 8 Architecture is very robust, fast and Object Oriented. The Drupal Core rely on many Symfony base Components that give a great entity abstraction.',
      ),
      6 => array(
        'tid' => 6,
        'key' => 2,
        'name' => "Mobile Websites",
        'image' => 'responsive-themes.jpg',
        'caption' => 'Mobile',
        'alt' => 'Mobile',
        'href' => 'services/mobile-websites',
        'description' => 'Drupal is ideal to build mobile web sites. I recently built a sub-theme of the Drupal Bootstrap base theme using the latest CDN Starterkit.',
      ),
      7 => array(
        'tid' => 7,
        'key' => 3,
        'name' => "Custom Modules",
        'image' => 'custom-modules.jpg',
        'caption' => 'Custom',
        'alt' => 'Custom',
        'href' => 'services/custom-modules',
        'description' => 'We are specialised in Custom Module Development and more recently in custom migratation modules from Drupal 7 web site into Drupal 8.',
      ),
      8 => array(
        'tid' => 8,
        'key' => 4,
        'name' => "Content Management",
        'image' => 'content-management.jpg',
        'caption' => 'Management',
        'alt' => 'Management',
        'href' => 'services/content-management',
        'description' => 'Manage your content with Drupal: create as many content types as you like, hundreds of Taxonomies, Nodes and plenty of views to disply them!',
      ),
      9 => array(
        'tid' => 9,
        'key' => 1,
        'name' => "System (CMS)",
        'image' => 'system-cms.jpg',
        'caption' => 'Drupal',
        'alt' => 'Drupal',
        'href' => 'services/system-cms',
        'description' => 'The great advantage of using Drupal is that is not just free to download, but it really helps to solve real problems and simplify the build of a complex CMS.',
      ),
      10 => array(
        'tid' => 10,
        'key' => 2,
        'name' => "Drupal consulting",
        'image' => 'drupal-consulting.jpg',
        'caption' => 'Consulting',
        'alt' => 'Consulting',
        'href' => 'services/drupal-consulting',
        'description' => 'Contact the Drupal Experts for a free consultation, get a fast and effective support. Get your custom solution, we can create the module that does the job.',
      ),
      11 => array(
        'tid' => 11,
        'key' => 3,
        'name' => "eCommerce / Online Store",
        'image' => 'ecommerce.jpg',
        'caption' => 'eCommerce',
        'alt' => 'eCommerce',
        'href' => 'services/ecommerce',
        'description' => 'Drupal 8 is very secure when it comes to secure login, manage online payments, basket, category list, products management. Recently I also used Drupal 7 in Enterprise Financial Solution such as P2P.',
      ),
      12 => array(
        'tid' => 12,
        'key' => 4,
        'name' => "Audio & Video",
        'image' => 'system-cms.jpg',
        'caption' => 'Multimedia',
        'alt' => 'Multimedia',
        'href' => 'services/audio-video',
        'description' => 'Drupal has evolved a lot in the recent years, the latest release follow the most recent and advanced web development techniques to embed Audio & Video.',
      ),
      13 => array(
        'tid' => 13,
        'key' => 1,
        'name' => "User account registration",
        'image' => 'user-account-registration.jpg',
        'caption' => 'Open Source',
        'alt' => 'Open Source',
        'href' => 'services/user-account-registration',
        'description' => 'Scalable, efficient, highly performing and SEO friendly Dupal CMS. Our Government Intranet project has got more than 50,000 registered users.',
      ),
      14 => array(
        'tid' => 14,
        'key' => 2,
        'name' => "Increase Donations",
        'image' => 'solutions.jpg',
        'caption' => 'Charity',
        'alt' => 'Charity',
        'href' => 'services/charity',
        'description' => 'The advanced features of apache web server ensure a stable and secure architecture. Open source is ideal for organisations that have little budgets.',
      ),
      15 => array(
        'tid' => 15,
        'key' => 3,
        'name' => "Increase Sales",
        'image' => 'search-engine-optimization.jpg',
        'caption' => 'Business',
        'alt' => 'Business',
        'href' => 'services/increase-sales',
        'description' => 'Drupal is free to download and the only cost for increasing your Sales is to pay for the consulting Drupal experts that can setting it up for you!',
      ),
      16 => array(
        'tid' => 16,
        'key' => 4,
        'name' => "Email Marketing",
        'image' => 'marketing.jpg',
        'caption' => 'Marketing',
        'alt' => 'Marketing',
        'href' => 'services/email-marketing',
        'description' => 'Drupal 8 has got a robust built-in and secure user registration system. Collect customer information, user preferences to make a unique user journey.',
      ),
      17 => array(
        'tid' => 17,
        'key' => 1,
        'name' => "Web Statistics & Analysis",
        'image' => 'analysis.jpg',
        'caption' => 'Analysis',
        'alt' => 'Analysis',
        'href' => 'services/web-statistics-analysis',
        'description' => 'Collect visitor statistics for your web site with Google Analytics. We offer free consultation for installing GA and Metatags modules on your Drupal web site.',
      ),
      18 => array(
        'tid' => 18,
        'key' => 2,
        'name' => "Integrated Content Strategy",
        'image' => 'analysis.jpg',
        'caption' => 'Integrated',
        'alt' => 'Integrated',
        'href' => 'services/integrated-content-strategy',
        'description' => 'Plan your content types, permissions, roles, views and taxonomies. Set up different contexts, view modes, panel variants and view.',
      ),
      19 => array(
        'tid' => 19,
        'key' => 3,
        'name' => "Accessibility",
        'image' => 'web-strategy.jpg',
        'caption' => 'Accessibility',
        'alt' => 'Accessibility',
        'href' => 'services/accessibility',
        'description' => 'Drupal 8 custom drop down menus, blocks, breadcrumbs, light box, search results offer a wide range of options on how to accesss and find your relevant content.',
      ),
      20 => array(
        'tid' => 20,
        'key' => 4,
        'name' => "Training & Consultation",
        'image' => 'training-consultation.jpg',
        'caption' => 'Support',
        'alt' => 'Support',
        'href' => 'services/training-consultation',
        'description' => 'Groups organize, meet, and work on projects based on interest or geographic location. It\'s a great way to get involved, learn more and get support.',
      ),
      21 => array(
        'tid' => 21,
        'key' => 1,
        'name' => "Database Integration",
        'image' => 'data-service-integration-using-satellite-and-cloud-storage.jpg',
        'caption' => 'Integration',
        'alt' => 'data service integration using satellite and cloud storage',
        'href' => 'services/database-integration',
        'description' => 'Drupal can be integrated with complex system that uses other databases such as PostgreSQL, MongoDB, Oracles or even other Custom MySQL Servers.',
      ),
      22 => array(
        'tid' => 22,
        'key' => 2,
        'name' => "Multi-Language Websites",
        'image' => 'multi-language.jpg',
        'caption' => 'Multilingual',
        'alt' => 'Multilingual',
        'href' => 'services/multi-language',
        'description' => 'Drupal is a very powerful tool to set up multilingual web sites: Internationalization module helps to easily translate content in different languages.',
      ),
      23 => array(
        'tid' => 23,
        'key' => 3,
        'name' => "Mobile Development",
        'image' => 'elderly-man-is-using-mobile-phone.jpg',
        'caption' => 'Development',
        'alt' => 'elderly man is using mobile phone',
        'href' => 'services/mobile-development',
        'description' => 'Drupal Bootstrap Sub Themes are ideal to implement responsive solutions for Mobile devices and tablets with all kind of screen resolutions from 320px up to 800px!',
      ),
      24 => array(
        'tid' => 24,
        'key' => 4,
        'name' => "Usability Testing",
        'image' => 'testing.jpg',
        'caption' => 'Testing',
        'alt' => 'Testing',
        'href' => 'services/usability-testing',
        'description' => 'Usability Testing is very important to identify and resolve issues and fix bugs before the web site is deployed on the live server: PHP unit Test, Behat testing.',
      ),
      25 => array(
        'tid' => 25,
        'key' => 1,
        'name' => "Investments",
        'image' => 'investing.jpg',
        'caption' => 'Financial',
        'alt' => 'Financial',
        'href' => 'services/investments',
        'description' => 'In the recent years I built very secure and scalable Drupal systems for Financial Businesses to handle their customers sensitive information.',
      ),
      26 => array(
        'tid' => 26,
        'key' => 2,
        'name' => "Stocks and Shares",
        'image' => 'stocks-and-shares.jpg',
        'caption' => 'Markets',
        'alt' => 'Markets',
        'href' => 'services/stocks-and-shares',
        'description' => 'Drupal dynamic Jquery Chart and Graph plugins for Stocks and Shares web sites: Charts and Graphs Flot, Views Charts, Poll Chart Block and a lot more.',
      ),
      27 => array(
        'tid' => 27,
        'key' => 3,
        'name' => "Saving accounts",
        'image' => 'saving-accounts.jpg',
        'caption' => 'Savings',
        'alt' => 'Savings',
        'href' => 'services/saving-accounts',
        'description' => 'Data management, risk analysis, Cash ISA, Loan Interest Calculator and other very complex finacial applications with Equifax and Cifas Direct.',
      ),
      28 => array(
        'tid' => 28,
        'key' => 4,
        'name' => "Peer to peer lending",
        'image' => 'peer-to-peer-lending.jpg',
        'caption' => 'P2P Lending',
        'alt' => 'P2P Lending',
        'href' => 'services/peer-peer-lending',
        'description' => 'In Peer to peer lending money from Investors is split in hundreds of micro loans and borrowers repayments are proportionally repaid back to Lenders.',
      ),
      29 => array(
        'tid' => 29,
        'key' => 1,
        'name' => "Property investment",
        'image' => 'property-investment.jpg',
        'caption' => 'Property',
        'alt' => 'Property',
        'href' => 'services/property-investment',
        'description' => 'Buy and sell properties online with Drupal Custom websites: The right place to meet for Sellers and buyers: Email alerts, marketing notifications and more.',
      ),
    );
    return $tags_array;
  }

  /**
   * Get the first top services details by Taxonomy IDs.
   *
   * @return array $tags_array
   *   An associative array containing tid/taxonomy details value pairs.
   */
  public static function getServicesDetailsByTid($my_tids, $limit = 4)
  {
    $more_services = self::getServicesDetails();
    $result = [];
    $key = 1;
    foreach ($my_tids as $tid) {
      if (!empty($more_services[$tid]) && $key < ($limit + 1)) {
        $more_service = $more_services[$tid];
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
   * Provides the list of all spacial services details to be used in Related Services templates.
   * @param int $exclude_nid
   * @param int $max
   *
   * @return array $special_services
   *   An associative array containing nid/service details value pairs.
   */
  public static function getSpecialServices($exclude_nid = FALSE, $max = 4)
  {
    $special_services = array(
      0 => array(
        'nid' => 33,
        'key' => 4,
        'image' => 'migrate-drupal.jpg',
        'caption' => 'Migrate',
        'alt' => 'Migrate',
        'href' => 'migrate-drupal',
        'description' => 'Migrate Drupal by follow our very simple Six Steps! Set up your YML configuration to define your custom migrate modules to import data from Drupal 7.',
      ),
      1 => array(
        'nid' => 12,
        'key' => 1,
        'image' => 'responsive-themes.jpg',
        'caption' => 'Responsive',
        'alt' => 'Responsive',
        'href' => 'responsive-design',
        'description' => 'We are specialised in building custom Drupal Bootstrap Responsive Sub Themes: we use open-source HTML / CSS framework to build responsive website.',
      ),
      2 => array(
        'nid' => 18,
        'key' => 2,
        'image' => 'website-design-and-coding-technologies.jpg',
        'caption' => 'Install Drupal 8',
        'alt' => 'website design and coding technologies',
        'href' => 'installing-drupal',
        'description' => 'Drupal 8 is an open source CMC, see how it is easy to set up on my Guide on how to install Drupal 8.',
      ),
      3 => array(
        'nid' => 14,
        'key' => 2,
        'image' => 'seo-search-engine-optimization.jpg',
        'caption' => 'XML Sitemap',
        'alt' => 'XML Sitemap',
        'href' => 'drupal-best-cms-seo#simple_sitemap',
        'description' => 'Read our article on how to use and configure Drupal to use Simple XML sitemap, ALT and titles on images.',
      ),
    );

    // Randomise the order:
    shuffle($special_services);

    if ($exclude_nid && $max < 5) {
      $new_array = [];
      $key = 5 - $max;
      foreach ($special_services as $special_service) {
        if ($special_service['nid'] != $exclude_nid && count($new_array) < $max) {
          $special_service['key'] = $key;
          $key++;
          $new_array[] = $special_service;
        } elseif (count($new_array) > $max) {
          break;
        }
      }
      $special_services = $new_array;
    }

    return $special_services;
  }

  /**
   * Return the list of Node ID of all spacial services.
   *
   * @return array $nids
   */
  public static function getSpecialServicesNIDs()
  {
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
   * Return a specific list of Special Service Nodes.
   * @param  array $nids
   *
   * @return array $result
   */
  public static function getSpecialServicesByNIDs($nids, $start_key = 1)
  {
    $result = [];
    $special_services = self::getSpecialServices();
    $key = $start_key;
    foreach ($special_services as $special_service) {
      if (!empty($special_service['nid'])) {
        $nid = $special_service['nid'];
        if (in_array($nid, $nids)) {
          $special_service['key'] = $key++;
          $result[] = $special_service;
        }
      }
    }
    return $result;
  }

  /**
   * Provides the list of 4 top main services to be used in Related Services template.
   *
   * @return array $special_services
   *   An associative array containing 4 services.
   */
  public static function getMoreServices(array $tags_array, array $special_services)
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

}
