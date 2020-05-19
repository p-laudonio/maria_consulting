<?php

namespace Drupal\maria_custom;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Render\Markup;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\UserData;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;

class MariaCustomService
{
  /**
   * The menu link manager interface.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The Database Connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Date Formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messengerService;

  /**
   * Session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  protected $usersSession;

  /**
   * User Data.
   *
   * @var \Drupal\user\UserData
   */
  protected $userData;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Node Storage manager.
   *
   * @var EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * Term Storage manager.
   *
   * @var EntityStorageInterface
   */
  protected $termStorage;

  /**
   * File Storage manager.
   *
   * @var EntityStorageInterface
   */
  protected $fileStorage;

  /**
   * Image Style Storage manager.
   *
   * @var EntityStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * RiskRegisterService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager service.
   * @param \Drupal\Core\Database\Connection $db_connection
   *   The database connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Symfony\Component\HttpFoundation\Session\Session $session
   *   The users session.
   * @param \Drupal\user\UserData $user_data
   *   User Data service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    Connection $db_connection,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger,
    DateFormatter $date_formatter,
    MessengerInterface $messenger,
    Session $session,
    UserData $user_data,
    AccountInterface $account)
  {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->database = $db_connection;
    $this->configFactory = $config_factory;
    $this->logger = $logger;
    $this->dateFormatter = $date_formatter;
    $this->messengerService = $messenger;
    $this->usersSession = $session;
    $this->userData = $user_data;
    $this->currentUser = $account;
    try {
      $this->nodeStorage = $this->entityTypeManager->getStorage('node');
      $this->termStorage = $this->entityTypeManager->getStorage('taxonomy_term');
      $this->fileStorage = $this->entityTypeManager->getStorage('file');
      $this->imageStyleStorage = $this->entityTypeManager->getStorage('image_style');
    } catch (PluginNotFoundException $exception) {
      throw new NotFoundHttpException("Plugin Not Found Exception");
    } catch (InvalidPluginDefinitionException $exception) {
      throw new NotFoundHttpException("Invalid Plugin Definition Exception");
    }
  }

  /**
   * Get the Taxonomy Details by Taxonomy ID.
   * @param int $tid
   *
   * @return array $term_item
   *   An associative array containing tid/taxonomy details value pairs.
   */
  public function getServiceDetails($tid)
  {
    $term_item = [];
    $term = $this->termStorage->load($tid);
    if ($term instanceof ContentEntityInterface) {
      $image_data = $this->getImageData($term);
      $term_id = $term->id();
      $term_url = $term->toUrl()->toString();

      if ($term->hasField('field_term_teaser_title')) {
        $caption = $term->field_term_teaser_title->value;
      }
      if (empty($caption)) {
        $title_parts = explode(' ', $image_data['title']);
        $caption = $title_parts[0];
      }

      if ($term->hasField('field_term_teaser')) {
        $description = strip_tags($term->field_term_teaser->value);
      }
      if (empty($description)) {
        $description = $this->getFirstWords(strip_tags($term->description->value), 137);
      }

      $term_item = array(
        "tid" => $term_id,
        'key' => 1,
        "name" => $term->label(),
        "image" => $image_data['url'],
        'caption' => $caption,
        'alt' => $image_data['alt'],
        'title' => $image_data['title'],
        'href' => $term_url,
        'description' => $description,
      );
    }

    return $term_item;
  }

  /**
   * Return info on the Image to use on the Preview Mode.
   * @param  ContentEntityInterface $contentEntity
   *
   * @return array $image_info
   */
  private function getFieldImageInfo($contentEntity)
  {
    $image_info = [];
    if ($contentEntity->hasField('field_service_image')) {
      $image_info = ['field_service_image', 'medium'];
    }
    elseif ($contentEntity->hasField('field_header_image')) {
      $image_info = ['field_header_image', 'thumbnail'];
    }
    elseif ($contentEntity->hasField('field_image')) {
      $image_info = ['field_image', 'medium'];
    }
    return $image_info;
  }

  /**
   * Return a Special Service Node by Node ID.
   * @param  int $nid
   *
   * @return array $special_service
   */
  public function getSpecialService($nid)
  {
    $service_item = [];
    $node = $this->nodeStorage->load($nid);
    if ($node instanceof ContentEntityInterface) {
      $image_data = $this->getImageData($node);

      $nid = $node->id();
      $node_url = $node->toUrl()->toString();

      // Caption is the first word in the image title.
      $title_parts = explode(' ', $image_data['title']);
      $caption = $title_parts[0];

      $description = $this->getTeaserDescription($node);

      $service_item = array(
        "nid" => $nid,
        'key' => 1,
        "name" => $node->label(),
        "image" => $image_data['url'],
        'caption' => $caption,
        'alt' => $image_data['alt'],
        'title' => $image_data['title'],
        'href' => $node_url,
        'description' => $description,
      );
    }

    return $service_item;
  }

  /**
   * Return the teaser description.
   * @param ContentEntityInterface $contentEntity
   *
   * @return string $description
   */
  public function getTeaserDescription(ContentEntityInterface $contentEntity)
  {
    $description = '';
    $type_id = $contentEntity->getEntityTypeId();
    if ($type_id == 'node') {
      if ($contentEntity->bundle() == 'service' && $contentEntity->hasField('field_image_text_preview')) {
        $description = $contentEntity->field_image_text_preview->value;
      }

      if (empty($description) && $contentEntity->hasField('field_teaser')) {
        $description = $this->getFirstWords(strip_tags($contentEntity->field_teaser->value), 137);
      }

      if (empty($description) && $contentEntity->hasField('body')) {
        $description = $this->getFirstWords(strip_tags($contentEntity->body->value), 137);
      }
    }

    return $description;
  }

  /**
   * Return the data from a field Image.
   * @param ContentEntityInterface $contentEntity
   * @param array $image_info_default
   *
   * @return array|bool $image_data
   */
  public function getImageData(ContentEntityInterface $contentEntity, $image_info_default = [])
  {
    $image_data = [
      'url' => '/themes/maria_consulting/img/image-blank.png',
      'alt' => '',
      'title' => '',
      'found' => false,
    ];

    if (count($image_info_default) < 2) {
      $image_info = $this->getFieldImageInfo($contentEntity);
    }
    else {
      $image_info = $image_info_default;
    }

    if (count($image_info) == 2) {
      list($field_name, $image_style) = $image_info;
    }
    else {
      return $image_data;
    }

    $field_image = FALSE;
    if ($contentEntity->hasField($field_name)) {
      /** @var \Drupal\field\Entity\FieldConfig $def */
      $def = $contentEntity->getFieldDefinition($field_name);
      if ($def->getType() == 'image') {
        /** @var \Drupal\Core\Field\FieldItemListInterface $field_image */
        $field_image = $contentEntity->get($field_name);
      }
    }

    if ($field_image && !$field_image->isEmpty()) {
      $values = $field_image->getValue();

      if (!empty($values[0])) {
        $value = $values[0];
        $image_data['alt'] = $value['alt'];
        $image_data['title'] = $value['title'];

        /** @var File $file */
        $file = $this->fileStorage->load($value['target_id']);

        $image_uri = $file->getFileUri();

        if ($image_style == 'original') {
          $image_url = file_create_url($image_uri);
        }
        else {
          /** @var ImageStyle $image_style */
          $image_style = $this->imageStyleStorage->load($image_style);
          $image_url = $image_style->buildUrl($image_uri);
        }

        $image_data['url'] = $image_url;
        $image_data['found'] = true;
      }

    }

    if (empty($image_data['title'])) {
      $image_data['title'] = $contentEntity->label();
    }

    return $image_data;
  }

  /**
   * Return the created date.
   * @param ContentEntityInterface $contentEntity
   *
   * @return string $created_date
   */
  public function getDateCreated(ContentEntityInterface $contentEntity)
  {
    $created_date = '';
    $type_id = $contentEntity->getEntityTypeId();
    if ($type_id == 'node') {
      $time = $contentEntity->getCreatedTime();
      $created_date = date('Y-m-d', $time);
    }

    return $created_date;
  }

  /**
   * Return the created date.
   * @param ContentEntityInterface $contentEntity
   *
   * @return string $modified_date
   */
  public function getDateModified(ContentEntityInterface $contentEntity)
  {
    $modified_date = '';
    $type_id = $contentEntity->getEntityTypeId();
    if ($type_id == 'node') {
      $time = $contentEntity->getChangedTime();
      $modified_date = date('Y-m-d', $time);
    }

    return $modified_date;
  }

  /**
   * Helper function to return the first characters of a string.
   *
   * @return string $result
   */
  public function getFirstWords($s, $limit)
  {
    $result = '';
    if (strlen($s) > $limit) {
      $words = explode(' ', $s);
      foreach ($words as $word) {
        if (strlen($result) < $limit) {
          $result .= ' ' . $word;
        } else {
          break;
        }
      }
      $result .= '..';
    } else {
      $result = $s;
    }
    return $result;
  }

  /**
   * Helper function to extract company information from a string.
   * @param ContentEntityInterface $node
   *
   * @return array $company_details
   */
  public function getCompanydetails(ContentEntityInterface $node)
  {
    if ($node->hasField('field_company_details')) {
      $values = explode(',', $node->field_company_details->value);
    }
    else {
      $values = [];
    }

    if ($node->hasField('field_job_title')) {
      $job_title = $node->field_job_title->value;
    }
    else {
      $job_title = false;
    }

    $periods = $node->hasField('field_period') ? $node->field_period->getValue() : [];
    if (!empty($periods)) {
      $start = $periods[0]['value'];
      $end = $periods[0]['end_value'];
    }
    else {
      $start = false;
      $end = false;
    }

    // In all our Nodes the field_company_details always contains: "company,address,city,post code".
    if (count($values) > 3) {
      list($company, $address, $city, $post_code) = $values;
      $company_details = [
        'company_url' => $node->toUrl()->toString(),
        'company' => $company,
        'address' => $address,
        'city' => $city,
        'post_code' => $post_code,
        'job_title' => $job_title,
        'start' => $start,
        'end' => $end,
      ];
    }
    else {
      $company_details = [
        'company_url' => '',
        'company' => '',
        'address' => '',
        'city' => '',
        'post_code' => '',
        'job_title' => $job_title,
        'start' => $start,
        'end' => $end,
      ];
    }

    return $company_details;
  }

  /**
   * Helper function to determine to which RDF schema Type the Entity Belongs to.
   * @param ContentEntityInterface $contentEntity
   *
   * @return string $rdf_type
   */
  public function getRdfType(ContentEntityInterface $contentEntity)
  {
    $rdf_type = "Article";

    if($accordion = $this->getFirstReferencedEntity($contentEntity, 'field_accordion')) {
      $panel_title = $accordion->hasField('field_panel_title') ? $accordion->field_panel_title->value : '';
      if (substr( $panel_title, 0, 3) === "1. ") {
        $rdf_type = "HowTo";
      }
    }
    elseif (in_array($contentEntity->bundle(), ['service', 'project'])) {
      $rdf_type = "WebPage";
    }
    elseif ($contentEntity->getEntityTypeId() == 'taxonomy_term') {
      $rdf_type = "WebPage";
    }
    elseif ($contentEntity->bundle() == 'user') {
      $rdf_type = "Person";
    }

    return $rdf_type;
  }

  /**
   * Helper function to get the first referenced Entity from an entity reference field.
   * @param ContentEntityInterface $contentEntity
   * @param string $field_name
   *
   * @return ContentEntityInterface $ref_entity
   */
  public function getFirstReferencedEntity(ContentEntityInterface $contentEntity, $field_name)
  {
    $ref_entity = false;

    if ($contentEntity->hasField($field_name) &&
      $contentEntity->{$field_name} instanceof EntityReferenceFieldItemListInterface) {
      $ref_entities = $contentEntity->{$field_name}->referencedEntities();
      if (!empty($ref_entities)) {
        $ref_entity = reset($ref_entities);
      }
    }

    return $ref_entity;
  }

  /**
   * Get the taxonomy terms for a given Taxonomy Term Vocabulary to use in a select element.
   *
   * @return array
   *   An array of terms.
   */
  public function getAllTerms($vocabularyId = 'tags')
  {
    $options = [];

    $terms = $this->termStorage->loadTree($vocabularyId);
    if (!empty($terms)) {
      foreach ($terms as $term) {
        $options[$term->tid] = $term->name;
      }
    }

    return $options;
  }

  /**
   * @param $field_name
   * @param $bundle
   * @param $entity_type
   *
   * @return string
   */
  public function getBetterDescriptionForField($field_name, $bundle, $entity_type)
  {
    // Get better descriptions.
    $better_field_descriptions = $this->configFactory->get('better_field_descriptions.settings')
      ->get('better_field_descriptions');

    if (isset($better_field_descriptions) && !empty($better_field_descriptions[$entity_type][$bundle][$field_name])) {
      $data = $better_field_descriptions[$entity_type][$bundle][$field_name];
      // Stop processing if this is just defaults.
      return $data;
    }
    return '';
  }

  /**
   * Utility: find Entity ID by alias.
   *
   * @param string $alias
   *  The alias of the entity
   *
   * @return ContentEntityInterface|NULL
   *  ContentEntityInterface or FALSE if none.
   */
  public function getEntityByAlias($alias)
  {
    $entity = FALSE;
    $url = Url::fromUri('internal:' . $alias);
    // Check exist alias.
    if ($url->isRouted()) {
      $params = $url->getRouteParameters();
      $entity_type = key($params);
      /** @var ContentEntityInterface $entity */
      $entity = $this->entityTypeManager->getStorage($entity_type)->load($params[$entity_type]);
    }

    return $entity;
  }

  /**
   * Utility: find an URL inside an HTML string.
   *
   * @param string $html_link
   *  The HTML string that contains something like:
   *  <a href="alias" >Text</a>
   *
   * @return string
   *  The alias or FALSE if it does not find it.
   */
  public function findURLfromHTML($html_link)
  {
    $alias = "";

    if (preg_match('/<a href="(.+)" (.+)>/', $html_link, $match)) {
      if (isset($match[1])) {
        $alias = $match[1];
      }
    }

    return $alias;
  }

  /**
   * Utility: find term by name and vid.
   *
   * @param null $name
   *  Term name
   * @param null $vid
   *  Term vid
   *
   * @return int
   *  Term id or 0 if none.
   */
  public function getTidByName($name = NULL, $vid = NULL)
  {
    $properties = [];
    if (!empty($name)) {
      $properties['name'] = $name;
    }
    if (!empty($vid)) {
      $properties['vid'] = $vid;
    }
    try {
      $terms = $this->termStorage->loadByProperties($properties);
      $term = reset($terms);
    } catch (InvalidPluginDefinitionException $e) {
      $this->logger->get('maria_custom')->error($e->getMessage());
    }

    return !empty($term) ? $term->id() : 0;
  }

  /**
   * Display a message to the user.
   *
   * @param $message
   *   A message string.
   */
  public function addMessage($message)
  {
    $output = Markup::create($message);
    $this->messengerService->addMessage($output);
  }

  /**
   * Return the entire menu trail from the current menu item.
   * @param ContentEntityInterface $contentEntity
   *
   * @return bool|MenuLinkContent $content_link
   */
  public function getMenuLinkContent(ContentEntityInterface $contentEntity) {
    $this->menuLinkManager = \Drupal::service('plugin.manager.menu.link');
    $menu_name = 'main';
    $content_link = false;
    $url = $contentEntity->toUrl();
    $route_links = $this->menuLinkManager->loadLinksByRoute($url->getRouteName(), $url->getRouteParameters(), $menu_name);
    if (!empty($route_links)) {
      /** @var MenuLinkContent $content_link */
      $content_link = reset($route_links);
    }
    return $content_link;
  }

  /**
   * Return the entire menu trail from the current menu item.
   * @param MenuLinkContent $content_link
   *
   * @return array $links
   */
  public function getMenuLinkTrail(MenuLinkContent $content_link) {
    $this->menuLinkManager = \Drupal::service('plugin.manager.menu.link');
    $links = [];

    $link_plugin_id = $content_link->getPluginId();
    $menuTrail = $this->menuLinkManager->getParentIds($link_plugin_id);

    // Generate basic breadcrumb trail from active trail.
    // Keep same link ordering as Menu Breadcrumb (so also reverses menu trail)
    foreach (array_reverse($menuTrail) as $id) {
      $plugin = $this->menuLinkManager->createInstance($id);

      // Skip items that have an empty URL if the option is set.
      if (empty($plugin->getUrlObject()->toString())) {
        continue;
      }

      $link = Link::fromTextAndUrl($plugin->getTitle(), $plugin->getUrlObject());
      $links[] = $link;

      // Stop items when the first url matching occurs.
      if ($plugin->getUrlObject()->toString() == Url::fromRoute('<current>')->toString()) {
        break;
      }

    }
    return $links;
  }

}
