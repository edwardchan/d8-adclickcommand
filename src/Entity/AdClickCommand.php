<?php

namespace Drupal\adclickcommand\Entity;

use Drupal\adclickcommand\ClickCommandClicks;
use Drupal\adclickcommand\AdClickCommandInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;


/**
 * Defines the AdClickCommand entity.
 *
 * @ingroup adclickcommand
 *
 *  .... @TODO
 *
 * id: The unique identifier of this entityType. It follows the pattern
 * 'moduleName_xyz' to avoid naming conflicts.
 *
 * label: Human readable name of the entity type.
 *
 * handlers: Handler classes are used for different tasks. You can use
 * standard handlers provided by D8 or build your own, most probably derived
 * from the standard class. In detail:
 *
 * - view_builder: we use the standard controller to view an instance. It is
 *   called when a route lists an '_entity_view' default for the entityType
 *   (see routing.yml for details. The view can be manipulated by using the
 *   standard drupal tools in the settings.
 *
 * - list_builder: We derive our own list builder class from the
 *   entityListBuilder to control the presentation.
 *   If there is a view available for this entity from the views module, it
 *   overrides the list builder. @todo: any view? naming convention?
 *
 * - form: We derive our own forms to add functionality like additional fields,
 *   redirects etc. These forms are called when the routing list an
 *   '_entity_form' default for the entityType. Depending on the suffix
 *   (.add/.edit/.delete) in the route, the correct form is called.
 *
 * - access: Our own accessController where we determine access rights based on
 *   permissions.
 *
 * More properties:
 *
 *  - base_table: Define the name of the table used to store the data. Make sure
 *    it is unique. The schema is automatically determined from the
 *    BaseFieldDefinitions below. The table is automatically created during
 *    installation.
 *
 *  - fieldable: Can additional fields be added to the entity via the GUI?
 *    Analog to content types.
 *
 *  - entity_keys: How to access the fields. Analog to 'nid' or 'uid'.
 *
 *  - links: Provide links to do standard tasks. The 'edit-form' and
 *    'delete-form' links are added to the list built by the
 *    entityListController. They will show up as action buttons in an additional
 *    column.
 *
 * There are many more properties to be used in an entity type definition. For
 * a complete overview, please refer to the '\Drupal\Core\Entity\EntityType'
 * class definition.
 *
 * The following construct is the actual definition of the entity type which
 * is read and cached. Don't forget to clear cache after changes.
 *
 * @ContentEntityType(
 *   id = "adclickcommand",
 *   label = @Translation("Ad Click Command"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\adclickcommand\Entity\Controller\AdClickCommandListBuilder",
 *     "form" = {
 *       "add" = "Drupal\adclickcommand\Form\AdClickCommandForm",
 *       "edit" = "Drupal\adclickcommand\Form\AdClickCommandForm",
 *       "delete" = "Drupal\adclickcommand\Form\AdClickCommandDeleteForm",
 *     },
 *     "access" = "Drupal\adclickcommand\AdClickCommandAccessControlHandler",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "click_command",
 *   admin_permission = "administer click command",
 *   entity_keys = {
 *     "id" = "id",
 *     "name" = "name",
 *     "url" = "url",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/adclickcommand/{adclickcommand}",
 *     "edit-form" = "/adclickcommand/{adclickcommand}/edit",
 *     "delete-form" = "/adclickcommand/{adclickcommand}/delete",
 *     "collection" = "/adclickcommand/list"
 *   },
 *   field_ui_base_route = "adclickcommand.settings",
 * )
 *
 * The 'links' above are defined by their path. For core to find the
 * corresponding route, the route name must follow the correct pattern:
 *
 * entity.<entity-name>.<link-name> (replace dashes with underscores)
 * Example: 'entity.adclickcommand.canonical'
 *
 * See routing file above for the corresponding implementation
 *
 * The Contact class defines methods and fields for the contact entity.
 *
 * Being derived from the ContentEntityBase class, we can override the methods
 * we want. In our case we want to provide access to the standard fields about
 * creation and changed time stamps.
 *
 * Our interface (see AdClickCommandInterface) also exposes the EntityOwnerInterface.
 * This allows us to provide methods for setting and providing ownership
 * information.
 *
 * The most important part is the definitions of the field properties for this
 * entity type. These are of the same type as fields added through the GUI, but
 * they can by changed in code. In the definition we can define if the user with
 * the rights privileges can influence the presentation (view, edit) of each
 * field.
 *
 * The class also uses the EntityChangedTrait trait which allows it to record
 * timestamps of save operations.
 */
class AdClickCommand extends ContentEntityBase implements AdClickCommandInterface {

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     *
     * When a new entity instance is added, set the user_id entity reference to
     * the current user as the creator of the instance.
     */
    public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
        parent::preCreate($storage_controller, $values);
        $values += array(
            'user_id' => \Drupal::currentUser()->id(),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatedTime() {
        return $this->get('created')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getChangedTime() {
        return $this->get('changed')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getOwner() {
        return $this->get('user_id')->entity;
    }

    /**
     * {@inheritdoc}
     */
    public function getOwnerId() {
        return $this->get('user_id')->target_id;
    }

    /**
     * {@inheritdoc}
     */
    public function generateURL($id) {
        return 'http://ad.thrillist.com/ad/0/click/' . $id;
    }

    /**
     * Get command clicks.
     *
     * @param int $ccid
     *   An id of a click command.
     *
     * @return int
     *   An int.
     */
    public function getClicks($id) {
        return  \Drupal::service('adclickcommand.clicks_command_clicks')->getClickCount($id);
            //$this->entityQuery('click_command_clicks')->condition('ccid', $id)->execute()->fetchAll();
    }

    /**
     * {@inheritdoc}
     */
    public function setOwnerId($uid) {
        $this->set('user_id', $uid);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOwner(UserInterface $account) {
        $this->set('user_id', $account->id());
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * Define the field properties here.
     *
     * Field name, type and size determine the table structure.
     *
     * In addition, we can define how the field and its content can be manipulated
     * in the GUI. The behaviour of the widgets used can be determined here.
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

        $fields = parent::baseFieldDefinitions($entity_type);

        // Standard field, used as unique if primary index.
        $fields['id'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('CCID'))
            ->setDescription(t('The CCID of the AdClickCommand.'))
            ->setReadOnly(TRUE);

        // Name field for the contact.
        // We set display options for the view as well as the form.
        // Users with correct privileges can change the view and edit configuration.
        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Name'))
            ->setDescription(t('The name of the AdClickCommand.'))
            ->setSettings(array(
                'default_value' => '',
                'max_length' => 255,
                'text_processing' => 0,
            ))
            ->setDisplayOptions('view', array(
                'label' => 'above',
                'type' => 'string',
                'weight' => -6,
            ))
            ->setDisplayOptions('form', array(
                'type' => 'string_textfield',
                'weight' => -6,
            ))
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['url'] = BaseFieldDefinition::create('string')
            ->setLabel(t('URL'))
            ->setDescription(t('The URL of the AdClickCommand.'))
            ->setSettings(array(
                'default_value' => '',
                'max_length' => 255,
                'text_processing' => 0,
            ))
            ->setDisplayOptions('view', array(
                'label' => 'above',
                'type' => 'string',
                'weight' => -5,
            ))
            ->setDisplayOptions('form', array(
                'type' => 'string_textfield',
                'weight' => -5,
            ))
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['uuid'] = BaseFieldDefinition::create('uuid')
            ->setLabel(t('UUID'))
            ->setDescription(t('Universally Unique ID'))
            ->setReadOnly(TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Created'))
            ->setDescription(t('The time that the user was created.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Changed'))
            ->setDescription(t('The time that the user was last edited.'))
            ->setTranslatable(TRUE);

        return $fields;
    }

    public function preSave(EntityStorageInterface $storage) {
        parent::preSave($storage);

        // Update the {click_command_clicks} table prior to executing the hook.
        // \Drupal::service('click_command_clicks')->update($this);
    }

    public static function postDelete(EntityStorageInterface $storage, array $entities) {
        parent::postDelete($storage, $entities);

        /*foreach ($entities as $id => $entity) {
            \Drupal::service('click_command_clicks')->update($entity);
        }*/
    }
}
