<?php

namespace Drupal\g9\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\node\Entity\Node;

/**
 * Plugin implementation of the 'Thrillist Highlight' field type.
 *
 * @FieldType (
 *   id = "thrillist_highlight",
 *   label = @Translation("Thrillist Highlight"),
 *   description = @Translation("Stores a URL path and a weight."),
 *   category = @Translation("Thrillist"),
 *   default_widget = "thrillist_highlight",
 *   default_formatter = "thrillist_highlight"
 * )
 */
class ThrillistHighlight extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'url' => [
          'type' => 'varchar',
          'length' => 2048,
        ],
        'weight' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    parent::setValue($values);
    // If the 'url' is set, add this entity to a highlight.
    if (isset($values['url'])) {
      $weight = isset($values['weight']) ? $values['weight'] : 0;
      $this->addHighlight($values['url'], $weight);
    }
  }

  /**
   * Add entity and values to highlight.
   */
  protected function addHighlight($url, $weight) {
    $entity = $this->getEntity();
    // If the entity is null or it doesn't have an ID, return.
    if (!$entity || !$entity->id()) {
      return;
    }

    // Make a query to check for any 'highlight_item' that already reference
    // the current entity.
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'highlight_item')
      ->condition('field_node_reference', $entity->id())
      ->execute();

    $highlight_item_node = NULL;
    // If a 'highlight_item' already exists for the entity, load it.
    if ($nids) {
      $existing_highlight_item_nid = reset($nids);
      $highlight_item_node = Node::load($existing_highlight_item_nid);
    }
    // Otherwise, create a new 'highlight_item' and add the entity as the ref.
    else {
      // Create a 'highlight_item' node that references the current node.
      $highlight_item_node = Node::create([
        'type' => 'highlight_item',
        'uid' => $entity->getOwnerId(),
        'title' => '"' . $entity->getTitle() . '" Highlight Item',
        'status' => 1,
      ]);
      $highlight_item_node->set('field_node_reference', ['target_id' => $entity->id()]);
      $highlight_item_node->save();
    }

    // If the 'highlight_item' node could not be loaded or created, return.
    if (!$highlight_item_node) {
      return;
    }

    // Check for an existing 'highlight' node that already contains the url.
    // TypedData does not support dependency injection yet, so get the entity
    // query service statically - https://www.drupal.org/node/2053415.
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'highlight')
      ->condition('field_url_path', $url)
      ->execute();
    // If an existing 'highlight' node already exists with the url path,
    // append the highlight item to that node.
    if ($nids) {
      $highlight_nodes = Node::loadMultiple($nids);
      foreach ($highlight_nodes as $highlight_node) {
        $referenced_entities = array_column($highlight_node->field_highlight_items->getValue(), 'target_id');
        // Adjust the weights so weight 0 is slot 1, weight 1 is slot 2, etc.
        if ($weight > 0) {
          $weight -= 1;
        }
        // Only add it to the referenced entities if it does not already exist.
        if (!in_array($highlight_item_node->id(), $referenced_entities)) {
          array_splice($referenced_entities, $weight, 0, [$highlight_item_node->id()]);
          $highlight_node->field_highlight_items->setValue($referenced_entities);
        }
        $highlight_node->save();
      }
    }
    // Otherwise, create a new 'highlight' node with the url path and append
    // the highlight item to it.
    else {
      $highlight_node = Node::create([
        'type' => 'highlight',
        'uid' => $entity->getOwnerId(),
        'title' => '"' . $entity->getTitle() . '" Highlight',
        'status' => 1,
      ]);
      $highlight_node->set('field_url_path', $url);
      $highlight_node->field_highlight_items->appendItem($highlight_item_node);
      $highlight_node->save();
    }

  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value1 = $this->get('url')->getValue();
    $value2 = $this->get('weight')->getValue();
    return empty($value1) && empty($value2);
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Add our properties.
    $properties['url'] = DataDefinition::create('string')
      ->setLabel(t('URL'))
      ->setDescription(t('URL path'));

    $properties['weight'] = DataDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('Weight'));

    return $properties;
  }

}
