<?php

namespace Drupal\adclickcommand\Tests;

use Drupal\adclickcommand\Entity\Contact;
use Drupal\Tests\examples\Functional\ExamplesBrowserTestBase;

/**
 * Tests the basic functions of the AdClickCommand module.
 *
 * @package Drupal\adclickcommand\Tests
 *
 * @ingroup adclickcommand
 *
 * @group adclickcommand
 * @group examples
 */
class AdClickCommandTest extends ExamplesBrowserTestBase {

    public static $modules = array('adclickcommand', 'block', 'field_ui');

    /**
     * Basic tests for AdClickCommand.
     */
    public function testAdClickCommand() {
        $assert = $this->assertSession();

        $web_user = $this->drupalCreateUser(array(
            'add click command',
            'edit click command',
            'view click command',
            'delete click command',
            'administer click command',
            'administer click command display',
            'administer click command fields',
            'administer click command form display',
        ));

        // Anonymous User should not see the link to the listing.
        $assert->pageTextNotContains('Click Command: Listing');

        $this->drupalLogin($web_user);

        // Web_user user has the right to view listing.
        $assert->linkExists('Click Command: Listing');

        $this->clickLink('Click Command: Listing');

        // WebUser can add entity content.
        $assert->linkExists('Add click command');

        $this->clickLink(t('Add click command'));

        $assert->fieldValueEquals('name[0][value]', '');
        $assert->fieldValueEquals('name[0][value]', '');
        $assert->fieldValueEquals('name[0][value]', '');

        $user_ref = $web_user->name->value . ' (' . $web_user->id() . ')';
        $assert->fieldValueEquals('user_id[0][target_id]', $user_ref);

        // Post content, save an instance. Go back to list after saving.
        $edit = array(
            'name[0][value]' => 'test name',
            'url[0][value]' => 'http://test.url',
        );
        $this->drupalPostForm(NULL, $edit, t('Save'));

        // Entity listed.
        $assert->linkExists('Edit');
        $assert->linkExists('Delete');

        $this->clickLink('test name');

        // Entity shown.
        $assert->pageTextContains('test name');
        $assert->pageTextContains('http://test.url');
        $assert->linkExists('Add click command');
        $assert->linkExists('Edit');
        $assert->linkExists('Delete');

        // Delete the entity.
        $this->clickLink('Delete');

        // Confirm deletion.
        $assert->linkExists('Cancel');
        $this->drupalPostForm(NULL, array(), 'Delete');

        // Back to list, must be empty.
        $assert->pageTextNotContains('test name');

        // Settings page.
        $this->drupalGet('admin/structure/adclickcommand_settings');
        $assert->pageTextContains('Contact Settings');

        // Make sure the field manipulation links are available.
        $assert->linkExists('Settings');
        $assert->linkExists('Manage fields');
        $assert->linkExists('Manage form display');
        $assert->linkExists('Manage display');
    }

    /**
     * Test all paths exposed by the module, by permission.
     */
    public function testPaths() {
        $assert = $this->assertSession();

        // Generate a contact so that we can test the paths against it.
        $contact = AdClickCommand::create(
            array(
                'name' => 'somename',
                'url' => 'http://www.testurl.org',
            )
        );
        $contact->save();

        // Gather the test data.
        $data = $this->providerTestPaths($contact->id());

        // Run the tests.
        foreach ($data as $datum) {
            // drupalCreateUser() doesn't know what to do with an empty permission
            // array, so we help it out.
            if ($datum[2]) {
                $user = $this->drupalCreateUser(array($datum[2]));
                $this->drupalLogin($user);
            }
            else {
                $user = $this->drupalCreateUser();
                $this->drupalLogin($user);
            }
            $this->drupalGet($datum[1]);
            $assert->statusCodeEquals($datum[0]);
        }
    }

    /**
     * Data provider for testPaths.
     *
     * @param int $contact_id
     *   The id of an existing adclickcommand.
     *
     * @return array
     *   Nested array of testing data. Arranged like this:
     *   - Expected response code.
     *   - Path to request.
     *   - Permission for the user.
     */
    protected function providerTestPaths($_id) {
        return array(
            array(
                200,
                '/adclickcommand/' . $_id,
                'view adclickcommand',
            ),
            array(
                403,
                '/adclickcommand/' . $_id,
                '',
            ),
            array(
                200,
                '/adclickcommand/list',
                'view adclickcommand',
            ),
            array(
                403,
                '/adclickcommand/list',
                '',
            ),
            array(
                200,
                '/adclickcommand/add',
                'add adclickcommand',
            ),
            array(
                403,
                '/adclickcommand/add',
                '',
            ),
            array(
                200,
                '/adclickcommand/' . $_id . '/edit',
                'edit adclickcommand',
            ),
            array(
                403,
                '/adclickcommand/' . $_id . '/edit',
                '',
            ),
            array(
                200,
                '/adclickcommand/' . $_id . '/delete',
                'delete adclickcommand',
            ),
            array(
                403,
                '/adclickcommand/' . $_id . '/delete',
                '',
            ),
            array(
                200,
                'admin/structure/adclickcommand_settings',
                'administer adclickcommand',
            ),
            array(
                403,
                'admin/structure/adclickcommand_settings',
                '',
            ),
        );
    }

    /**
     * Test add new fields to the adclickcommand.
     */
    public function testAddFields() {
        $web_user = $this->drupalCreateUser(array(
            'administer adclickcommand',
            'administer adclickcommand display',
            'administer adclickcommand fields',
            'administer adclickcommand form display',
        ));

        $this->drupalLogin($web_user);
        $entity_name = 'adclickcommand';
        $add_field_url = 'admin/structure/' . $entity_name . '_settings/fields/add-field';
        $this->drupalGet($add_field_url);
        $field_name = 'test_name';
        $edit = array(
            'new_storage_type' => 'list_string',
            'label' => 'test name',
            'field_name' => $field_name,
        );

        $this->drupalPostForm(NULL, $edit, t('Save and continue'));
        $expected_path = $this->buildUrl('admin/structure/' . $entity_name . '_settings/fields/' . $entity_name . '.' . $entity_name . '.field_' . $field_name . '/storage');

        // Fetch url without query parameters.
        $current_path = strtok($this->getUrl(), '?');
        $this->assertEquals($expected_path, $current_path);
    }

}