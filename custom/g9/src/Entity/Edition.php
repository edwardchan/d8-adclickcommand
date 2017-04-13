<?php

namespace Drupal\g9;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\State\StateInterface;
use Drupal\g9\Entity\G9EntityInterface;

/**
 * A Class to wrap the Edition table.
 */
class Edition implements G9EntityInterface {

  /**
   * The table name.
   */
  protected $tablename = 'edition';

  /**
   * The current database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs the service.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The active database connection.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(Connection $database, StateInterface $state) {
    $this->database = $database;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function read(array $entities, $entity_type, $accurate = TRUE) {
    $options = $accurate ? array() : array('target' => 'replica');
    /*$stats = $this->database->select('comment_entity_statistics', 'ces', $options)
      ->fields('ces')
      ->condition('ces.entity_id', array_keys($entities), 'IN')
      ->condition('ces.entity_type', $entity_type)
      ->execute();*/

    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function delete(EntityInterface $entity) {
    $this->database->delete($this->tablename)
      ->condition('id', $entity->id())
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function create(FieldableEntityInterface $entity, array $fields) {
    $query = $this->database->insert($this->tablename)
      ->fields(array(
        'ad',
        'ccid',
        'clicks',
        'unique_clicks',
      ));
    /*foreach ($fields as $field_name => $detail) {
      // Skip fields that entity does not have.
      if (!$entity->hasField($field_name)) {
        continue;
      }
      $query->values(array(
        'ccid' => $entity->id(),
        'ad' => $entity->ad(),
        'clicks' => $field_name,
        'unique_clicks' => 0,
      ));
    }*/
    $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function update() {
    $query = $this->database->select($this->tablename, 'c');
  }

  /**
   * {@inheritdoc}
   */
  public function get($id) {
    print __FUNCTION__ .PHP_EOL;
    $query = $this->database->select($this->tablename, 'id')
      ->fields('id')
      ->fields('name')
      ->fields('label')
      ->fields('edition_tid')
      ->fields('relevance_tid')
      ->fields('campaign_id')
      ->condition('id', $id);
    if (isset($id)) {
      $query->condition('nid', $id);
    }
    $result = $query->execute();
    // If $id was passed, return the value.
    if (isset($id)) {
      $return = array();
      foreach ($result as $record) {
        $return[$record->ccid] = ($record->serialized ? unserialize($record->value) : $record->value);
      }
      return $return;
    }
    else {
      $return = array();
      foreach ($result as $record) {
        $return[$record->id][$record->cid] = ($record->serialized ? unserialize($record->value) : $record->value);
      }
      return $return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function set($ad, $id, $clicks, $unique_clicks) {
    print __FUNCTION__ .PHP_EOL;
    /*$serialized = 0;
        if (!is_scalar($value)) {
          $value = serialize($value);
          $serialized = 1;
        }
        $this->database->merge('click_command_clicks')
          ->keys(array(
            'ad' => $ad,
            'ccid' => $id,
            'clicks' => $clicks,
            'unique_clicks' => $unique_clicks,
          ))
          ->fields(array(
            'value' => $value,
            'serialized' => $serialized,
          ))
          ->execute();*/
  }

  public function count($eids){
    print __FUNCTION__ . "\n";
    $result = $this->database->select($this->tablename, 'id')
      ->fields('name')
      ->condition('id', $eids)->execute();
     print "Result \n";
     print_r($result);
     return $result;
  }
  public function editionSubscribe($user_id, $editions, $bool) {
    print __FUNCTION__ .PHP_EOL;

  }

  public function editionIsRewardsMarket($edition) {
    print __FUNCTION__ .PHP_EOL;

  }

  public function editionUnsubscribe($user_id){
    print __FUNCTION__ .PHP_EOL;

  }

  // gets the editions from the database based on id
  public function getEditionName($id) {
    //return tldb_quick_select('edition', 'id', $eid, 'name');
    print __FUNCTION__ .PHP_EOL;
    $result = $this->database->select($this->tablename, 'id')
      ->fields('name')
      ->condition('id', $id)->execute();
    return $result;
  }

  /**
   * Get the edition data for the reward or invite corresponding to an edition.
   *
   * @param $edition_name string
   *   Name of the edition.
   *
   * @return array
   *   Associative array containing:
   */
  public function rewardOrInviteForEdition($edition_name) {
    print __FUNCTION__ .PHP_EOL;
    $this->database->query("SELECT e2.*
      FROM {edition_prerequisites} AS ep, {edition} AS e1, {edition} AS e2
      WHERE e1.name = '" . $edition_name . "'
        AND ep.parent_eid = e1.id
        AND e2.id = ep.eid
        AND (e2.edition_type = 'invites' OR e2.edition_type = 'rewards')
      ORDER BY e2.edition_type DESC
      LIMIT 1")->fetchField();


    /*$table1 = db_select('edition_prerequisites', 'ep')
      ->fields('ep', array('column3', 'column4'));

    $table2 = db_select($this->tablename, 'e1')
        ->fields('e1', array('column1', 'column2'));

    $table3 = db_select($this->tablename, 'e2')
      ->fields('e2', array('column3', 'column4'));

    $query = $this->database->select($table1->union($table2,$table3), 'id')
      ->fields('e2.*')
      ->condition('name', $edition_name)
      ->condition('ep.parent_eid', 'e1.id' )
      ->condition('ep.id', 'ep.eid' )
      ->condition('e2.edition_type', 'invites' OR 'e2.edition_type', 'rewards')
      ->orderBy('e2.edition_type','DESC')*/

    return NULL;
  }

  public function byCompany($edition_ids){
    $thrillist_editions   = $this->database->query('SELECT id FROM {edition} WHERE edition_type IN (\'daily\', \'weekly\', \'seasonal\')', NULL, 'id');
    $rewards_editions     = $this->database->query('SELECT id FROM {edition} WHERE edition_type = \'rewards\'', NULL, 'id');
    $jackthreads_editions = $this->database->query('SELECT id FROM {edition} WHERE edition_type = \'jackthreads\'', NULL, 'id');
    $crosbypress_editions = $this->database->query('SELECT id FROM {edition} WHERE edition_type = \'crosbypress\'', NULL, 'id');
    $edition_data = array(
      'thrillist'   => array_values(array_intersect($edition_ids, $thrillist_editions)),
      'rewards'     => array_values(array_intersect($edition_ids, $rewards_editions)),
      'jackthreads' => array_values(array_intersect($edition_ids, $jackthreads_editions)),
      'crosbypress' => array_values(array_intersect($edition_ids, $crosbypress_editions)),
    );
    return $edition_data;
  }

}
