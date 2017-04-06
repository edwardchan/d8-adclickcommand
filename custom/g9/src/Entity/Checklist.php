<?php

namespace Drupal\g9;

use Drupal\algolia\Algolia;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\solr\Solr;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ChecklistController extends ControllerBase
{
  /**
   * Drupal\Core\Entity\Query\QueryFactory definition.
   *
   */
  protected $entityQuery;
  protected $algolia;
  protected $solr;

  public function __construct(QueryFactory $entityQuery, Algolia $algolia, Solr $solr) {
    $this->entityQuery = $entityQuery;
    $this->algolia = $algolia;
    $this->solr = $solr;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('algolia'),
      $solr->get('solr')
    );
  }

  public function someFunction($id) {
    //
    $query = $this->entityQuery->get('checklist');
    $query->condition('field', $id);
    $entity_ids = $query->execute();
    //
  }

  /**
   * Internal function for creating checklists.
   */
  public function itemListsCreate($title,
                                  $subtitle = '',
                                  $items = array(),
                                  $owner_uid = 0,
                                  $is_allied = FALSE,
                                  $is_hq = FALSE,
                                  $redirect = TRUE,
                                  $is_editorial = FALSE,
                                  $show_price_filter = FALSE,
                                  $status = 1,
                                  $publish_date = 0,
                                  $allied_endpoint = NULL,
                                  $watermark_style = NULL,
                                  $node_type = 'item') {
    $checklist = Checklist::create(array(
      'type' => 'checklist',
      'title' => $title,
      'subtitle' => $subtitle,
      'items' => $items,
      'owner_uid' => $owner_uid,
      'is_allied' => $is_allied,
      'is_hq' => $is_hq,
      'is_editorial' => $is_editorial,
      'show_price_filter' => $show_price_filter,
      'status' => $status,
      'publish_date' => $publish_date,
      'allied_endpoint' => $allied_endpoint,
      'watermark_style' => $watermark_style
      )
    );
    /*if (!$redirect) {
      global $user;
      $user->no_checklist_redirect = TRUE;
    }*/

    $checklist->save();

    return $checklist;

  }

  /**
   * Add an item to a list.
   *
   * @param int $node_nid
   *   Node ID.
   * @param int $item_list_nid
   *   Checklist node ID.
   * @param int $weight
   *   Item weight (defaults to 1).
   * @param int $user_id
   *   User ID.
   */
  public function itemListsAddItemToList($node_nid, $item_list_nid, $weight = 1, $user_id = NULL){
    if (!$node_nid || !$item_list_nid) {
      return;
    }
    if ($user_id === NULL) {
      $user_id = 1;
    }

    if ($weight == 1) {
      //  $query = 'SELECT max(weight) as weight FROM checklist_items WHERE nid = %d';
      //  $weight = tldb_linear_query($query, $item_list_nid, 'weight');
      $weightquery = $this->entityQuery->getAggregate('checklist_items');
      $maxWeight = "maxweight";
      $weightquery->aggregate('nid', 'MAX', NULL, $maxWeight);
      $weightquery->condition('nid', $node_nid);
      $weight = $weightquery->executeAggregate();
      $weight = $weight[0] + 1;
    }

    // $query = 'SELECT nid FROM {checklist_items}
    // WHERE nid = %d
    // AND node_nid = %d
    // AND removed != 0';
    $query = $this->entityQuery->get('checklist_items')
      ->condition('nid', $item_list_nid)
      ->condition('node_nid', $node_nid)
      ->condition('removed', 0, '<>')
      ->execute();


    if ($query === FALSE) {
      $node = node_load($node_nid);
      //$count_query = 'SELECT count(*) FROM {checklist_items} WHERE nid = %d';
      $count_query = $this->entityQuery->get('checklist_items')
        ->condition('nid', $item_list_nid)
        ->execute();
      if (!$count_query) {
        // Set the node_type on the checklist the first time a node is added.
        // $nt_query = 'UPDATE checklist SET node_type = \'%s\' WHERE nid = %d';
        // db_query($nt_query, array($node->type, $item_list_nid));
        $checklist_item = ChecklistItems::load($item_list_nid);
        $checklist_item->node_type = $node->type;
        $checklist_item->save();
      }
      // $query = "INSERT INTO {checklist_items} (nid, node_nid, node_type, weight, added, user_id)
      // VALUES (%d, %d, '%s', %d, %d, %d)";
      // db_query($query, $item_list_nid, $node_nid, $node->type, $weight, time(), intval($user_id));
      $checklist_item = Checklist_Items::create(array(
          'nid' => $item_list_nid,
          'node_id' => $node_nid,
          'node_type' => $node->type,
          'weight' => $weight,
          'added' => time(),
          'user_id' => intval($user_id)
        )
      );
      $checklist_item->save();

    }
    else {
      // $query = "UPDATE {checklist_items} SET added = %d, removed = 0, weight = %d, user_id = %d
      // WHERE nid = %d AND node_nid = %d";
      // db_query($query, time(), $weight, intval($user->uid), $item_list_nid, $node_nid);
      $nids = $this->entityQuery->get('checklist_items')
        ->condition('nid', $item_list_nid)
        ->condition('node_nid', $node_nid)
        ->execute();
      foreach ($nids as $nid) {
        $checklist_item = Paragraph::load($item_list_nid);
        $checklist_item->node_type = time();
        $checklist_item->added = time();
        $checklist_item->removed = 0;
        $checklist_item->weight = $weight,
        $checklist_item->user_id = intval($user_id);
        $checklist_item->save();
      }
    }
    // @TODO
    // cache_clear_all(_thrillist_item_lists_distinct_editions_cid($item_list_nid), 'cache');
    // cache_clear_all(_thrillist_item_lists_distinct_prices_cid($item_list_nid), 'cache');
    $this->itemListSyncUpdate($item_list_nid, 'update');
  }

  /**
   * Sync checklist updates to external systems.
   *
   * Push updates to Solr and Algolia.
   *
   * @param int $list_nid
   *   Checklist node ID.
   * @param string $op
   *   Operation; one of "insert", "update", and "delete".
   */
  function itemListSyncUpdate($list_nid, $op) {
    $this->solr->pushToHyperion($list_nid, $op);
    $this->algolia->push($list_nid, $op, NULL, ALGOLIA_PRIORITY_HIGH);
  }

}
