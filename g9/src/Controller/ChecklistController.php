<?php

namespace Drupal\g9\Entity;

use Drupal\Core\Controller\ControllerBase;
use Drupal\paragraphs\Entity\Paragraph;

class ChecklistController extends ControllerBase {

  function doSomething(){
    print __FUNCTION__;
    return "something";
  }
  /**
   * Get a list of checklists owned by the user.
  */
  public function getUserChecklists($user_id){
    $storage = \Drupal::entityTypeManager()->getStorage('node');
    $ids = \Drupal::entityQuery('node')
      ->condition('type', 'checklist')
      ->execute();
    if (!empty($ids)) {
      $checklists = $storage->loadMultiple($ids);
      return $checklists;
    } else {
      return NULL;
    }
  }

  public function testMe(){;
    print "\n\nOk \n\n";
    return $this;
  }
  /**
   * Internal function for creating checklists.
   */
  public function userItemListsCreate($user_id, $data = array(), $items = array()) {
    $create_data = [];

    $allowed_fields = array(
      'title',
      //'owner_id',
      //'author',
      //'summary',
      //'checklist_items',
      //'primary_vertical',
      //'is_editorial'
    );

    foreach ($allowed_fields as $allowed) {
      if (array_key_exists($allowed, $data)) {
        $create_data[$allowed] = $data[$allowed];
      }
    }

    $create_data['type'] = 'checklist';
    $user_checklist = \Drupal::entityTypeManager()->getStorage('node')->create($create_data);

    if(!$user_checklist->save()){
      $this->logger->notice(__FUNCTION__ ." Could not create checklist");
      throw new \Exception(__FUNCTION__ . " Could not create checklist");
    }

    if (!empty($items)) {
      foreach($items as $item){
        // Add the items to the checklist.
        $this->userItemListsAddItemToList($user_checklist->id(), $item['id'], $item['weight']);
      }
    }
    // Reload it with all its updated stuff.
    $user_checklist = \Drupal::entityTypeManager()->getStorage('node')->load($user_checklist->id());
    return $user_checklist;
  }

  /**
   * Add an item to a list.
   *
   * @param int $checklist_id
   *   Node ID.
   * @param int $item_id
   *   Checklist node ID.
   * @param int $weight
   *   Item weight (defaults to 1).
   */
  public function userItemListsAddItemToList($checklist_id = NULL, $item_id = NULL, $weight = 0){
    if (!$checklist_id || !$item_id) {
      return;
    }
    //$storage = \Drupal::entityTypeManager()->getStorage('node');

    if ($weight == 0) {

      $weightquery = $this->entityQuery->getAggregate('user_checklist_items');
      $maxWeight = "maxweight";
      $weightquery->aggregate('id', 'MAX', NULL, $maxWeight);
      $weightquery->condition('id', $item_id);
      $weight = $weightquery->executeAggregate();
      $weight = $weight[0] + 1;
    }

    $query = $this->entityQuery->get('user_checklist_items')
      ->condition('nid', $checklist_id)
      ->condition('node_nid', $item_id)
      ->condition('removed', 0, '<>')
      ->execute();

    if ($query === FALSE) {
      $node = node_load($item_id);
      $count_query = $this->entityQuery->get('user_checklist_items')
        ->condition('nid', $checklist_id)
        ->execute();

      if (!$count_query) {
        // Set the node_type on the checklist the first time a node is added.
        $checklist_item = \Drupal::entityTypeManager()->getStorage('user_checklist')->load($checklist_id);
        $checklist_item->node_type = $node->type;
        $checklist_item->save();
      }

      $checklist_item = \Drupal::entityTypeManager()->getStorage('user_checklist')->create(array(
          'nid' => $checklist_id,
          'node_id' => $checklist_id,
          'weight' => $weight,
        )
      );
      $checklist_item->save();
    }
    else {
      $nids = $this->entityQuery->get('user_checklist_items')
        ->condition('nid', $checklist_id)
        ->condition('node_nid', $item_id)
        ->execute();
      foreach ($nids as $nid) {
        $checklist_item = Paragraph::load($checklist_id);
        $checklist_item->node_type = time();
        $checklist_item->added = time();
        $checklist_item->removed = 0;
        $checklist_item->weight = $weight;
        $checklist_item->save();
      }
    }
    // @TODO
    // cache_clear_all(_thrillist_item_lists_distinct_editions_cid($item_list_nid), 'cache');
    // cache_clear_all(_thrillist_item_lists_distinct_prices_cid($item_list_nid), 'cache');
    $this->itemListSyncUpdate($checklist_id, 'update');
  }

  public function userItemListsUserOwnsList($user_id, $checklist_id) {
    return (\Drupal::entityTypeManager()->getStorage('user_checklist')->load($checklist_id)->getOwnerId() == $user_id);
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

  public function itemListSyncUpdate($list_nid, $op) {
    //$this->solr->pushToHyperion($list_nid, $op);
    //$this->algolia->push($list_nid, $op, NULL, ALGOLIA_PRIORITY_HIGH);
  }

  public function load($id){
    $storage = \Drupal::entityTypeManager()->getStorage('node');

    if($checklist = $storage->load($id)){
      return $checklist;
    }
    return NULL;
  }
}
