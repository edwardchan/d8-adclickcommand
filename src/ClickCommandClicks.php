<?php

namespace Drupal\ad_click_command;


use Drupal\ad_click_command\Entity\AdClickCommand;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;

class ClickCommandClicks implements ClickCommandClicksInterface {

    /**
     * The current database connection.
     *
     * @var \Drupal\Core\Database\Connection
     */
    protected $database;

    /**
     * The current logged in user.
     *
     * @var \Drupal\Core\Session\AccountInterface
     */
    //protected $currentUser;

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
     * Constructs the CommentStatistics service.
     *
     * @param \Drupal\Core\Database\Connection $database
     *   The active database connection.
     * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
     *   The entity manager service.
     * @param \Drupal\Core\State\StateInterface $state
     *   The state service.
     */
    public function __construct(Connection $database, EntityManagerInterface $entity_manager, StateInterface $state) {
        $this->database = $database;
        //$this->currentUser = $current_user;
        $this->entityManager = $entity_manager;
        $this->state = $state;
    }

    /**
     * {@inheritdoc}
     */
    public function read($entities, $entity_type, $accurate = TRUE) {
        $options = $accurate ? array() : array('target' => 'replica');
        $stats = $this->database->select('comment_entity_statistics', 'ces', $options)
            ->fields('ces')
            ->condition('ces.entity_id', array_keys($entities), 'IN')
            ->condition('ces.entity_type', $entity_type)
            ->execute();

        $statistics_records = array();
        while ($entry = $stats->fetchObject()) {
            $statistics_records[] = $entry;
        }
        return $statistics_records;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(EntityInterface $entity) {
        $this->database->delete('click_command_clicks')
            ->condition('ccid', $entity->id())
            ->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function create(FieldableEntityInterface $entity, $fields) {
        $query = $this->database->insert('click_command_clicks')
            ->fields(array(
                'ad',
                'ccid',
                'clicks',
                'unique_clicks',
            ));
        foreach ($fields as $field_name => $detail) {
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
        }
        $query->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function getClickCount($ccid) {
        return $this->database->query('SELECT unique_clicks FROM {click_command_clicks} WHERE ccid = :id', array(':id' => $ccid))->fetchField();
    }


    /**
     * {@inheritdoc}
     */
    public function update(AdClickCommand $adClickCommand) {

        $query = $this->database->select('click_command_clicks', 'c');
        $query->addExpression('COUNT(ccid)');
        $count = $query->condition('c.ccid', $adClickCommand->id())
            ->execute()
            ->fetchField();

        if ($count > 0) {

        }
        else {

        }

        // Reset the cache of the commented entity so that when the entity is loaded
        // the next time, the statistics will be loaded again.
        // $this->entityManager->getStorage($comment->getCommentedEntityTypeId())->resetCache(array($comment->getCommentedEntityId()));
    }

    /**
     * {@inheritdoc}
     */
    public function get($ccid) {
        $query = $this->database->select('click_command_clicks', 'ud')
            ->fields('ud')
            ->condition('ccid', $ccid);
        if (isset($ccid)) {
            $query->condition('ccid', $ccid);
        }
        $result = $query->execute();
        // If $id was passed, return the value.
        if (isset($ccid)) {
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
    public function set($ad, $ccid, $clicks, $unique_clicks) {
        $serialized = 0;
        if (!is_scalar($value)) {
            $value = serialize($value);
            $serialized = 1;
        }
        $this->database->merge('click_command_clicks')
            ->keys(array(
                'ad' => $ad,
                'ccid' => $ccid,
                'clicks' => $clicks,
                'unique_clicks' => $unique_clicks,
            ))
            ->fields(array(
                'value' => $value,
                'serialized' => $serialized,
            ))
            ->execute();
    }

}
