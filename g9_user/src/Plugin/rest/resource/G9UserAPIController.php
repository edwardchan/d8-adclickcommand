<?php

namespace Drupal\g9_user\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

define('SITE_URL','http://www.thrillist.com/');

/**
 * Provides a Resource you can access via API
 *
 * @RestResource(
 *   id = "g9_user_api_resource",
 *   label = @Translation("G9 User Resource"),
 *   uri_paths = {
 *     "canonical" = "/api/user/{user_id}/{action}/{version}",
 *     "https://www.drupal.org/link-relations/create" = "/api/user/{user_id}/{action}/{version}"
 *   }
 * )
 */
class G9UserAPIController extends ResourceBase {

  /**
   *
   */
  protected $g9util;

  /**
   * Responds to GET requests.
   */
  public function get($user_id = NULL, $action = 'default_action', $version = NULL, $thing, Request $request) {

    $response['message'] = 'test';
    $response['userid'] = $user_id;
    $response['action'] = $action;
    $response['version'] = $version;
    $response['request_method'] = $request->getMethod();

    return $this->handle($user_id, $action, $version, $request);
  }

  /**
   * Responds to POST requests.
   */
  public function post($user_id = NULL, $action = 'default_action', $version = NULL, $thing, Request $request) {
    return $this->handle($user_id, $action, $version, $request);
  }

  /**
   * The method set as default in the route file.
   */
  public function default_action(){
    $response['error'] = TRUE;
    $response['message'] = 'Invalid Action';
    return new JsonResponse( $response );
  }

  /**
   * All requests go throug here
  */
  public function handle($user_id, $action, $version, Request $request) {
    $response = array();
    $data = array();

    // This condition checks the `Content-type` and makes sure to
    // decode JSON string from the request body into array.
    if (0 === strpos($request->headers->get('Content-Type'), 'application/hal+json')) {
      $data = json_decode($request->getContent(), TRUE);
    }
    if (!$data) {
      $data = array();
    }
    $request_method = $request->getMethod();

    //print __FUNCTION__ . " My data=" . print_r($data, true) .PHP_EOL;
    $response = $this->routeAction($data, $user_id, $action, $request_method);

    return new JsonResponse( $response );
  }

  /**
   * Routes the request to the appropriate handler and returns the formatted response.
   *
   * @param Request $request
   *
   * @param string $method
   *
   * @return array
   *   An array containing the status bool and any data relevant to the request.
   */
  private function routeAction(array $data, $user_id, $action, $request_method) {
    $response = array();
    $result = NULL;
    // Parse the request object to determine the action desired, version and args.
    $this->logger->notice(__FUNCTION__ ."::Begin:: Data = ".print_r($data,true));
    $version = (!empty($data['version']) ? $data['version'] : NULL);

    // Here is where you might break off for changes in Version.
    $success = TRUE;
    $data['id'] = $user_id;
    //print __FUNCTION__ . " Method: {$action}. Data=".print_r($data, true).PHP_EOL;
    //die();

    if (method_exists($this, $action)) {
      try {
        $method_args = $this->_pinnacleServiceHandler($action, $data, $request_method);
        $this->logger->notice("method_args = ".print_r($method_args,true));
        //print __FUNCTION__ . " method_args = ".print_r($method_args,true).PHP_EOL;

        $result = $this->{$action}($method_args);
        // Response object built here for all responses.
        $response['status'] = $success;
        $response['data'] = $result;

      } catch (\Exception $e) {
        $this->logger->notice("Error attempting to execute requested action {$action} '{$e->getMessage()}'.");
        print __FUNCTION__ . " Error attempting to execute requested action {$action} '{$e->getMessage()}'.".PHP_EOL;
        $response['error'] = TRUE;
        $response['message'] = $e->getMessage();
      }
    }
    else {
      $response['error'] = TRUE;
      $response['data'] = "Invalid method {$action}";
    }
    // Function may return its own response(eg isAdmin, on auth fail);
    if (is_array($result)) {
      return $result;
    }
    print_r($response);
    return $response;
  }

  /**
   * Uses passed in zuul id to verify if the user is a CMS user.
   *
   * @param array $options
   *   An array of data passed in from Request.
   *
   * @throws Exception
   * @return mixed
   */
  private function isAdmin(array $options) {
    $arg_def = array(
      'id' => array(
        '#type' => 'int',
      ),
      'permission' => array(
        '#type' => 'string',
        '#optional' => TRUE,
      ),
    );
    list($args, $errors) = $this->_pinnacleServiceArgs($options, $arg_def);

    if ($errors !== NULL) {
      return $this->_pinnacleServiceArgDefError($errors);
    }

    // Load DRUPAL user based on zuul id passed.
    $user = \Drupal::service('g9.util')->getZuul()->getUserByZuulId($options['id']);

    if (!$user){
      throw new \Exception('User ' . $options['id'] . ' is not found(zuul).');
    }

    if ($user && $user->isActive()){
      return TRUE;
    }
    return FALSE;
  }

  /**
   * API method: Update user info.
   *
   * @param array $options
   *   An array of data passed in from Request.
   *
   * @return array
   */
  private function editUserInfo(array $options) {
    $arg_def = array(
      'default_edition' => array(
        '#type' => 'int',
        '#optional' => TRUE,
      ),
      'mobile_number' => array(
        '#type' => 'string',
        '#optional' => TRUE,
      ),
      'birthdate_month' => array(
        '#type' => 'int',
        '#optional' => TRUE,
      ),
      'birthdate_day' => array(
        '#type' => 'int',
        '#optional' => TRUE,
      ),
      'birthdate_year' => array(
        '#type' => 'int',
        '#optional' => TRUE,
      ),
      'gender' => array(
        '#type' => 'string',
        '#optional' => TRUE,
      ),
      'income' => array(
        '#type' => 'string',
        '#optional' => TRUE,
      ),
      'format' => array(
        '#type' => 'string',
        '#optional' => TRUE,
      ),
      'zip' => array(
        '#type' => 'string',
        '#optional' => TRUE,
      ),
      'first_name' => array(
        '#type' => 'string',
        '#optional' => TRUE,
      ),
      'last_name' => array(
        '#type' => 'string',
        '#optional' => TRUE,
      ),
      'email' => array(
        '#type' => 'string',
        '#optional' => TRUE,
      ),
      'name' => array(
        '#type' => 'string',
        '#optional' => TRUE,
      ),
      'password' => array(
        '#type' => 'string',
        '#optional' => TRUE,
      ),
      'hashed_password' => array(
        '#type' => 'string',
        '#optional' => TRUE,
      ),
      'fbid' => array(
        '#type' => 'string',
        '#optional' => TRUE,
      ),
      'is_21' => array(
        '#type' => 'bool',
        '#optional' => TRUE,
      ),
      'jackthreads' => array(
        '#type' => 'bool',
        '#optional' => TRUE,
      ),
      'jt_user_id' => array(
        '#type' => 'bool',
        '#optional' => TRUE,
      ),
      'public_mytl' => array(
        '#type' => 'bool',
        '#optional' => TRUE,
      ),
      'tl_fb_opt' => array(
        '#type' => 'bool',
        '#optional' => TRUE,
        '#in' => array(-1, 0, 1),
      ),
      'fb_access_token' => array(
        '#type' => 'string',
        '#optional' => TRUE,
      ),
      'get_legacy_data' => array(
        '#type' => 'bool',
        '#default' => FALSE,
      ),
      'refer' => array(
        '#type' => 'string',
        '#optional' => TRUE,
      ),
      'origin_type' => array(
        '#type' => 'string',
        '#optional' => TRUE,
      ),
      '_no_export' => array(
        '#type' => 'bool',
        '#optional' => TRUE,
      ),
    );
    list($args, $errors) = $this->_pinnacleServiceArgs($options['options'], $arg_def);
    if ($errors !== NULL) {
      $this->logger->notice('Bad parameters: ' . print_r($errors, TRUE));
      return $this->_pinnacleServiceArgDefError($errors);
    }
    /*print __FUNCTION__." Options ". PHP_EOL;
    print_r($options);
    print PHP_EOL;
    print " Args ". PHP_EOL;
    print_r($args);*/

    $account = (object) \Drupal::service('g9.util')->getZuul()->getUser($options['id']);

    // Derived from mobile_service_edit_user_info
    $update = array(
      'birthday' => array(
        'month' => $args['birthdate_month'] !== NULL ? $args['birthdate_month'] : $account->thrillist->birthdate_month,
        'day' => $args['birthdate_day'] !== NULL ? $args['birthdate_day'] : $account->thrillist->birthdate_day,
        'year' => $args['birthdate_year'] !== NULL ? $args['birthdate_year'] : $account->thrillist->birthdate_year,
      ),
      'gender' => $args['gender'] !== NULL ? $args['gender'] : $account->gender,
      'income' => $args['income'] !== NULL ? $args['income'] : $account->income,
      'mobile_number' => $args['mobile_number'] !== NULL ? $args['mobile_number'] : $account->thrillist->mobile_number,
      'zip' => $args['zip'] !== NULL ? $args['zip'] : $account->zip,
      'format' => $args['format'] === 't' ? 't' : 'h',
      'referrer' => $args['refer'] !== NULL ? $args['refer'] : $account->thrillist->refer,
      'default_edition' => $args['default_edition'] !== NULL ? $args['default_edition'] : $account->default_edition,
      'first_name' => $args['first_name'] !== NULL ? $args['first_name'] : $account->first_name,
      'last_name' => $args['last_name'] !== NULL ? $args['last_name'] : $account->last_name,
      'name' => $args['name'] !== NULL ? $args['name'] : $account->name,
    );

    if ($args['email'] !== NULL && $args['email'] != $account->email) {
      if (!\Drupal::service('email.validator')->isValid($args['email'])) {
        return $this->_pinnacleServiceError('Invalid email address');
      }
      if (($result = \Drupal::service('g9.util')->getZuul()->findByEmail($args['email'], array('id' => 1))) && $result['meta']['count']) { //zuul_find_by_email($args['email'], array('id' => 1))) && $result['meta']['count']) {
        return $this->_pinnacleServiceError(t('ERROR: That email is already registered to Thrillist or our brother company, JackThreads. Log in with your existing email & password.'));
      }
      $update['email'] = $args['email'];
    }
    if ($args['password'] !== NULL) {
      $update['password'] = $args['password'];
    }
    if (isset($args['fbid']) && $args['fbid']) {
      $update['fb_id'] = $args['fbid'];
    }
    if (isset($args['public_mytl'])) {
      $update['public_mytl'] = $args['public_mytl'] ? 1 : 0;
    }
    if ($args['is_21'] !== NULL) {
      $update['is_21'] = $args['is_21'] ? 1 : 0;
    }
    if (isset($args['tl_fb_opt'])) {
      $update['tl_fb_opt'] = $args['tl_fb_opt'];
    }
    if (isset($args['fb_access_token']) && ($account->thrillist->fb_uid || $args['fbid'])) {
      $update['access_token'] = $args['fb_access_token'];
    }
    $update['birthdate_year'] = $update['birthday']['year'];
    $update['birthdate_month'] = $update['birthday']['month'];
    $update['birthdate_day'] = $update['birthday']['day'];
    unset($update['birthday']);
    //    print "\n\n Update: \n";
    //    print_r($update);
    //    print "\n\n Account: \n";
    //    print_r($account);

    $response = \Drupal::service('g9.util')->getZuul()->updateUser($account->email, $options['id'], $update);
    $account = json_decode($response[1][0], TRUE);
    $account = $account['response'];
    $account = \Drupal::service('g9.util')->getZuul()->convertUser($account);

    //    print "\n === Acct2 \n";
    //    print "thrillist: " . print_r($account->thrillist, true);
    //    print PHP_EOL;
    //    print_r($account);

    if (isset($args['fbid']) && $args['fbid']) {
      $account->thrillist->fb_uid = $args['fbid'];
    }
    if ($args['jt_user_id'] !== NULL) {
      $account->thrillist->jt_uid = $account->id;
    }
    if (isset($args['public_mytl'])) {
      $account->thrillist->public_my_tl = $args['public_mytl'];
    }
    if ($args['is_21'] !== NULL) {
      $account->thrillist->is_21 = $args['is_21'];
    }
    if (isset($args['fbid']) && $args['fbid']) {
      $account->thrillist->fb_uid = $args['fbid'];
    }
    if (isset($args['tl_fb_opt'])) {
      $account->thrillist->tl_fb_opt = $args['tl_fb_opt'];
    }

    if ($args['get_legacy_data']) {
      $user = $account;
    }
    else {
      $user = $this->_pinnacleServiceProcessUserData($account);
    }
    return array(
      'status' => TRUE,
      'user' => $user,
    );

  }

  /**
   * API Method: Reset password.
   *
   * @param array $options
   *   An array of data passed in from Request.
   *
   * @return array
   *   An array containing status bool
   */
  private function passwordReset($options) {
    // Derived from mobile_service_password_reset.
    global $base_url;

    $arg_def = array(
      'email' => array(
        '#type' => 'email',
      ),
      'redirect' => array(
        '#type' => 'string',
        '#optional' => TRUE,
      ),
      'subject' => array(
        '#type' => 'string',
        '#optional' => TRUE,
      ),
      'body' => array(
        '#type' => 'string',
        '#optional' => TRUE,
      ),
      'login_url' => array(
        '#type' => 'string',
        '#optional' => TRUE,
      ),
      'edit_url' => array(
        '#type' => 'string',
        '#optional' => TRUE,
      ),
      'legacy' => array(
        '#type' => 'bool',
        '#default' => FALSE,
      ),
    );

    list($args, $errors) = $this->_pinnacleServiceArgs($options['options'], $arg_def);
    if ($errors !== NULL) {
      $this->logger->notice('Bad parameters: ' . print_r($errors, TRUE));
      return $this->_pinnacleServiceArgDefError($errors);
    }

    if (($id = \Drupal::service('g9.util')->getZuul()->getIdForUser(strtolower($args['email']))) === FALSE) {
      return array(
        'status' => FALSE,
        'message' => 'Invalid User(1):'.$id,
        'error' => 'invalid_user_id',
      );
    }
    $from = \Drupal::config('system.site')->get('site_mail_from');//, ini_get('sendmail_from'));

    if ($args['login_url'] !== NULL) {
      $login_url = $args['login_url'];
      $edit_uri = SITE_URL . 'user/' . $id . '/edit';
    }
    elseif ($args['redirect'] !== NULL) {
      $login_url = \Drupal::service('g9.util')->passwordResetUrl($id) . '?redirect=' . urlencode($args['redirect']);
      $edit_uri = 'password/reset/' . $id;
    }
    elseif (!$args['legacy']) {
      // Use Zuul password reset to lock-step with new Pinnacle password reset functionality.
      $reset_hash = \Drupal::service('g9.util')->getZuul()->getPasswordResetToken($id);

      // @TODO be less company specific
      $login_url = 'https://www.thrillist.com/password-reset/' . $reset_hash;
      $edit_uri =  SITE_URL . 'user/' . $id . '/edit';
    }
    else {
      $login_url = \Drupal::service('g9.util')->userPassResetUrl($id);
      $edit_uri = SITE_URL . 'user/' . $id . '/edit';
    }
    if ($args['edit_url'] !== NULL) {
      $edit_uri = $args['edit_url'];
    }

    $account = (object) \Drupal::service('g9.util')->getZuul()->getUser($id);

    // Mail one time login URL and instructions.
    $variables = array(
      '!username' => $account->username,
      '!site' => \Drupal::config('system.site')->get('site_name'), //, 'Drupal'),
      '!login_url' => $login_url,
      '!uri' => $base_url,
      '!uri_brief' => preg_replace('!^https?://!', '', $base_url),
      '!mailto' => $account->email,
      '!date' => format_date(time()),
      '!login_uri' => SITE_URL . 'user', //Url::fromUri('user', array('absolute' => TRUE)),
      '!edit_uri' => $edit_uri,
    );

    #print 'Got variables ' .print_r($variables,true) . PHP_EOL;

    if ($args['subject'] !== NULL) {
      $subject = strtr($args['subject'], $variables);
    }
    else {
      $subject = \Drupal::service('g9.util')->userMailText('password_reset_subject', NULL, $variables);
    }
    if ($args['body'] !== NULL) {
      $body = strtr($args['body'], $variables);
    }
    else {
      $body = \Drupal::service('g9.util')->userMailText('password_reset_body', NULL, $variables);
    }

    $module = 'pinnacle';
    $key = 'user-pass';

    if (!$cms_user = \Drupal::service('g9.util')->getZuul()->getUserByZuulId($id)){
      throw new \Exception('CMS User ' . $id . ' is not found (zuul).');
    }

    $langcode = $cms_user->getPreferredLangcode();
    $params = array('subject' => $subject, 'body' => $body);

    $site_mail = \Drupal::config('system.site')->get('mail_notification');
    // If the custom site notification email has not been set, we use the site
    // default for this.
    if (empty($site_mail)) {
      $site_mail = \Drupal::config('system.site')->get('site_mail_from');
    }
    if (empty($site_mail)) {
      $site_mail = ini_get('sendmail_from');
    }

    $message = \Drupal::service('plugin.manager.mail')->mail($module, $key, $account->email, $langcode, $params, $site_mail);

    $this->logger->notice('Message sent: '.print_r($message, true));

    if ($message['result']) {
      return array('status' => TRUE,
        'message' => 'Email Sent',
      );
    }
    else {
      return array('status' => FALSE,
        'message' => 'Email not sent',
        'error' => 'password_reset_email_not_sent',
      );
    }
  }

  /**
   * Test to see if checklist entity can be accessed.
   */

  private function getAllChecklists($options) {
    $arg_def = array();
    list($args, $errors) = $this->_pinnacleServiceArgs($options['options'], $arg_def);

    $user = \Drupal::service('g9.util')->getZuul()->getUserByZuulId($options['id']);

    if ($user === NULL) {
      $this->logger->notice('Unable to authenticate.');
      return array(
        'status' => FALSE,
        'message' => 'Invalid User(2):' . $options['id'],
        'error' => 'invalid_user_id',
      );
    }

    $checklists = \Drupal::service('g9.util')->getAllChecklists();

    if ($checklists === NULL) {
      $this->logger->error('Checklists could not be accessed.');
      return array(
        'status' => FALSE,
        'message' => 'Internal error accessing checklists.',
        'error' => 'checklist_access_fail',
      );
    }

    return array(
      'status' => TRUE,
      'lists' => $checklists,
    );
  }

  /**
   * API method: create a user checklist.
   *
   * @param array $options
   *   An array of data passed in from Request.
   *
   * @return array
   *   An array containing status bool
   */
  private function createChecklist($options) {
    $arg_def = array(
      'list_title' => array(
        '#name' => 'title',
        '#type' => 'string',
      ),
      'subtitle' => array(
        '#type' => 'string',
        '#default' => '',
      ),
      'items' => array(
        '#type' => 'array',
        '#default' => array(),
      ),
    );
    list($args, $errors) = $this->_pinnacleServiceArgs($options['options'], $arg_def);

    if ($errors !== NULL) {
      $this->logger->notice('Bad parameters: ' . print_r($errors, TRUE));
      return $this->_pinnacleServiceArgDefError($errors);
    }

    $user = \Drupal::service('g9.util')->getZuul()->getUserByZuulId($options['id']);
    // CMS ID $user->id()
    // Stored Zuul id  $user->get('field_zuul_id')

    if ($user === NULL) {
      $this->logger->notice('Unable to authenticate.');
      return array(
        'status' => FALSE,
        'message' => 'Invalid User(2):' . $options['id'],
        'error' => 'invalid_user_id',
      );
    }
    $items = (object) $args['items'];
    print "B";

    $this->logger->notice( __FUNCTION__ . " List Items: \n" . print_r($items,true));

    $checklistController = \Drupal::service('g9.util')->getChecklist();
    print "C";
    if ($checklistController === NULL) {
      print "Cerr";
      $this->logger->error('Checklist could not be created.');
      return array(
        'status' => FALSE,
        'message' => 'Internal error creating checklist.',
        'error' => 'checklist_create_fail',
      );
    }
    // Make the Checklist.
    $new_args = [];
    foreach ($args as $k => $v) {
      if (array_key_exists($k, $arg_def) && !empty($arg_def[$k]['#name'])) {
        $new_args[$arg_def[$k]['#name']] = $v;
      } else {
        $new_args[$k] = $v;
      }
    }
    print "D";
    $checklist = $checklistController->createUserChecklist($user->id(), $new_args, $items);
    print "E";
    return array(
      'status' => isset($checklist),
      'message' => 'Here you go ' . $checklist->id(),
    );

    // Add to the Checklist.
    $i = 1;
    foreach ($items as $item) {
      // Hack: this now needs to support adding items in a piece to the checklist.
      $type = \Drupal::service('g9.util')->getNodeType($item);
      switch ($type) {
        case 'piece':
          $result = \Drupal::service('g9.util')->getPieceNid($item);
          foreach ($result as $row) {
            $checklist->userItemListsAddItemToList($row['nid'], $checklist->id(), $i++);
          }
          break;

        case 'venue':
        case 'item':
          $checklist->userItemListsAddItemToList($item, $checklist->id(), $i++);
          break;

        default:
          break;
      }
    }
    return array(
      'status' => TRUE,
      'list_nid' => intval($checklist->nid),
    );
  }

  /**
   * API method: Add an item to a user checklist.
   *
   * @param array $options
   *   An array of data passed in from Request.
   *
   * @return array
   *   An array containing status bool
   */
  private function addToChecklist($options) {
    $arg_def = array(
      /*'access_key' => array(
        '#type' => 'string',
      ),*/
      'list_nid' => array(
        '#type' => 'int',
      ),
      'item_nid' => array(
        '#type' => 'int',
      ),
      'note' => array(
        '#type' => 'string',
        '#default' => '',
      ),
    );
    list($args, $errors) = $this->_pinnacleServiceArgs($options['options'], $arg_def);
    if ($errors !== NULL) {
      $this->logger('Bad parameters: ' . print_r($errors, TRUE));
      return $this->_pinnacleServiceArgDefError($errors);
    }
    $user = \Drupal::service('g9.util')->getZuul()->getUserByZuulId($options['id']);
    if ($user === NULL) {
      $this->logger->notice('Unable to authenticate.');
      return array(
        'status' => FALSE,
        'message' => 'Invalid User',
        'error' => 'invalid_user_id',
      );
    }
    $checklist = \Drupal::service('g9.util')->getUserChecklist($user->id());
    if ($checklist === NULL) {
      $this->logger->error('Checklist could not be accessed.');
      return array(
        'status' => FALSE,
        'message' => 'Could not access checklist.',
        'error' => 'checklist_access_fail',
      );
    }
    /*if (!$checklist->userItemListsUserOwnsList($user->id(), $args['list_nid'])) { //thrillist_item_lists_user_owns_list($user_id, $args['list_nid'])) {
      $this->logger->notice('Attempt by user \'' . $user->id() . '\' to modify checklist \'' . $args['list_nid'] . '\' which she does not own.');
      return array(
        'error' => TRUE,
        'message' => 'Forbidden access',
      );
    }*/

    // Hack: this now needs to support adding items in a piece to the checklist.
    //$type = db_result(db_query('select type from {node} where nid = %d', $args['item_nid']));
    $type_query = \Drupal::entityQuery('node')
      ->condition('nid', $args['item_nid'])
      ->execute();
    $type = current($type_query);

    switch ($type) {
      case 'piece':
        //$result = db_query('select nid from {pieces_nodes} where pid = %d', $args['item_nid']);
        $storage = \Drupal::entityTypeManager()->getStorage('node');
        $uids = \Drupal::entityQuery('user')
          ->condition('pid', $args['item_nid'])
          ->execute();
        $nodes = $storage->loadMultiple($uids);

        foreach ($nodes as $node) {
          $checklist->userItemListsAddItemToList($node['nid'], $args['list_nid'], 1, $user->id());
        }
        break;
      case 'venue':
      case 'item':
        $checklist->userItemListsAddItemToList($args['item_nid'], $args['list_nid'], 1, $user->id());
        break;
      default:
        break;
    }
    return array('status' => TRUE);
  }

  /**
   * API method: remove an item from a user checklist.
   *
   * @param array $options
   *   An array of data passed in from Request.
   *
   * @return array
   *   An array containing status bool
   */
  private function removeFromChecklists($options) {
    $arg_def = array(
      /*'access_key' => array(
        '#type' => 'string',
      ),*/
      'lists' => array(
        '#type' => 'array',
      ),
      'item_nid' => array(
        '#type' => 'int',
      ),
    );
    list($args, $errors) = $this->_pinnacleServiceArgs($options['options'], $arg_def);
    if ($errors !== NULL) {
      $this->logger('Bad parameters: ' . print_r($errors, TRUE));
      return $this->_pinnacleServiceArgDefError($errors);
    }
    $user = \Drupal::service('g9.util')->getZuul()->getUserByZuulId($options['id']);
    if ($user === NULL) {
      $this->logger->notice('Unable to authenticate.');
      return array(
        'status' => FALSE,
        'message' => 'Invalid User',
        'error' => 'invalid_user_id',
      );
    }

    $forbidden_lists = array();
    foreach ($args['lists'] as $list_nid) {
      $checklist = \Drupal::service('g9.util')->getUserChecklist($user->id());
      if(!$checklist) {
        continue;
      }
      if (!$checklist->userItemListsUserOwnsList($user->id(), $list_nid)) {
        $forbidden_lists[] = $list_nid;
        continue;
      }
      $checklist->userItemListsRemoveItemFromList($args['item_nid'], $list_nid);
    }
    if (!empty($forbidden_lists)) {
      $this->logger->notice('Attempt by user \'' . $user->id() . '\' to modify checklist(s) \'' . implode(', ', $$forbidden_lists) . '\' which she does not own.');
      return $this->_pinnacleServiceError('Forbidden Access');
    }
    return array('status' => TRUE);
  }


  /**
   * Re-implementation of services request handler.
   */
  private function _pinnacleServiceHandler($method_name, $data, $request_method) {
    $methods = $this->_pinnacleServiceMethods();
    $args = array();

    foreach ($methods as $method) {
      if ($method['method'] == $method_name) {
        $args = array();
        foreach($method['args'] as $arg) {
          //print __FUNCTION__ . " Looking for method-arg {$arg['name']} in ".print_r($data, true).PHP_EOL;
          if (isset($data[$arg['name']])) {
            // print __FUNCTION__ ." Found {$arg['name']} ".PHP_EOL;
            $args[$arg['name']] = $data[$arg['name']];
          }
          elseif ($arg['optional'] == 0) {
            $this->logger->notice(__FUNCTION__ ."  " . $arg['name'] ." not received");
            throw new \Exception("Argument ". $arg['name'] ." not received");
          }
          /* @TODO
           * elseif (!empty($arg['allowed']) && !in_array($request_method, $arg['allowed'])) {
            print "\n  Method {$request_method} not Allowed on {$method_name}".PHP_EOL;
            throw new \Exception("Method {$request_method} not Allowed on {$method_name}");
          }*/
          else {
            $this->logger->notice( __FUNCTION__ . " NULL {$arg['name']} ");
            $args[$arg['name']] = NULL;
          }
        }

        return $args;
      }
    }

  }

  /**
   * This basically replaces the Services type checking.
   *
   * @param array $options
   *   An array of data passed in from Request.
   *
   * @param array $arg_def
   *   An array of arguments required.
   * @return array
   */
  private function _pinnacleServiceArgs($options, $arg_def) {
    $args = array();
    $missing = array();
    $bad_types = array();
    $bad_values = array();
    if (empty($options)) {
      $options = array();
    }
    $this->logger->notice( __FUNCTION__ ."\n" .  print_r($options,true) . "\n ========== Arg def \n" . print_r($arg_def,true));

    if (!is_array($options)) {
      $this->logger->notice('Array expected for first argument (got \'' . print_r($options, TRUE) . '\' instead); backtrace: ' . print_r(debug_backtrace(FALSE) )) ;
    }
    foreach ($arg_def as $arg => $def) {
      //print __FUNCTION__. " (arg_def)Arg: {$arg} => ".print_r($def,true) .PHP_EOL;
      if (!array_key_exists('#type', $def)) {
        $this->logger->notice('#type missing');
        continue;
      }
      // Test for missing parameters.
      if (!array_key_exists($arg, $options)) {
        // print "$arg Doesnt exist in options arr ".print_r(array_keys($options),true).PHP_EOL;
        if (array_key_exists('#default', $def)) {
          $options[$arg] = $def['#default'];
        }
        elseif (array_key_exists('#optional', $def) && $def['#optional']) {
          $args[$arg] = NULL;
          continue;
        }
        else {
          print __FUNCTION__." Missing. {$arg} not in ".print_r($options,true).PHP_EOL;
          $missing[] = $arg;
          continue;
        }
      }
      // Test for correct types.
      if (!$this->_pinnacleServiceTestType($options[$arg], $def['#type'])) {
        $bad_types[] = $arg;
        continue;
      }
      // Test enumerations.
      if (array_key_exists('#in', $def) && !in_array($options[$arg], $def['#in'])) {
        $bad_values[] = $arg;
        continue;
      }

      // Success!
      $args[$arg] = $options[$arg];
      //print "!!OK! Setting {$arg} to ".$options[$arg] . PHP_EOL;
    }
    if (!(empty($missing) && empty($bad_types) && empty($bad_values))) {
      $errors = array($missing, $bad_types, $bad_values);
    }
    else {
      $errors = NULL;
    }
    return array($args, $errors);
  }

  /**
   * Available methods. Compatible with services method definition.
   */
  private function _pinnacleServiceMethods() {
    static $methods = NULL;

    if ($methods === NULL) {
      $methods = array(
        array(
          'method' => 'getAllChecklists',
          'allowed' => array(
            'POST',
          ),
          'args' => array(
            array(
              'name'         => 'id',
              'type'         => 'int',
              'optional'     => FALSE,
              'description'  => t('The zuul id'),
            ),
            array(
              'name'         => 'options',
              'type'         => 'array',
              'optional'     => TRUE,
              'description'  => t('The request related values'),
            ),
          ),
        ),
        array(
          'method' => 'passwordReset',
          'allowed' => array(
            'POST',
            ),
          'args' => array(
            array(
              'name'         => 'email',
              'type'         => 'email',
              'optional'     => FALSE,
              'description'  => t('The users email'),
            ),
            array(
              'name'         => 'login_url',
              'type'         => 'string',
              'optional'     => TRUE,
              'description'  => t('The login url'),
            ),
            array(
              'name'         => 'options',
              'type'         => 'array',
              'optional'     => TRUE,
              'description'  => t('The request related values'),
            ),
          ),
        ),
        array(
          'method' => 'isAdmin',
          'allowed' => array(
            'POST',
            'GET',
          ),
          'args' => array(
            array(
              'name'         => 'id',
              'type'         => 'int',
              'optional'     => FALSE,
              'description'  => t('The zuul id'),
            ),
            array(
              'name'         => 'options',
              'type'         => 'array',
              'optional'     => TRUE,
              'description'  => t('The request related values'),
            ),
          ),
        ),
        array(
          'method' => 'editUserInfo',
          'allowed' => array(
            'POST',
          ),
          'args' => array(
            array(
              'name'         => 'id',
              'type'         => 'int',
              'optional'     => FALSE,
              'description'  => t('The zuul id'),
            ),
            array(
              'name'         => 'options',
              'type'         => 'array',
              'optional'     => TRUE,
              'description'  => t('The request related values'),
            ),
          ),
        ),
        array(
          'method' => 'createChecklist',
          'allowed' => array(
            'POST',
          ),
          'args' => array(
            array(
              'name'         => 'id',
              'type'         => 'int',
              'optional'     => FALSE,
              'description'  => t('The zuul id'),
            ),
            array(
              'name'         => 'options',
              'type'         => 'array',
              'optional'     => TRUE,
              'description'  => t('The request related values'),
            ),
          ),
        ),
        array(
          'method' => 'addToChecklist',
          'allowed' => array(
            'POST',
          ),
          'args' => array(
            array(
              'name'         => 'options',
              'type'         => 'array',
              'optional'     => TRUE,

            ),
          ),
        ),
        array(
          'method' => 'removeFromChecklists',
          'allowed' => array(
            'POST',
          ),
          'args' => array(
            array(
              'name'         => 'options',
              'type'         => 'array',
              'optional'     => TRUE,
              'description'  => t('The request related values'),
            ),
          ),
        ),
      );
    }
    return $methods;
  }

  /**
   * API parameter conversion.
   *
   * @param string $var
   *   The value you are looking at.
   * @param string $type
   *   The type you are requiring.
   *
   * @return array
   */
  private function _pinnacleServiceTestType($var, $type) {
    switch ($type) {
      case 'string':
        $type_func = 'is_string';
        break;

      case 'int':
        $type_func = 'is_numeric';
        break;

      case 'numeric':
        $type_func = 'is_numeric';
        break;

      case 'bool':
        // Hack.
        $var = $var ? TRUE : FALSE;
        $type_func = 'is_bool';
        break;

      case 'array':
        $type_func = 'is_array';
        break;

      case 'object':
        $type_func = 'is_object';
        break;

      case 'email':
        $type_func = 'is_string'; //'thrillist_really_valid_email_address';
        break;

      default:
        $this->logger->notice('Invalid type: ' . $type);
        return NULL;
    }
    return call_user_func($type_func, $var) ? TRUE : FALSE;
  }

  /**
   * Raise an argument definition error.
   *
   * @param array $errors
   *   An array of error strings generated.
   *
   * @return array
   */
  private function _pinnacleServiceArgDefError(&$errors) {
    list($missing, $bad_types, $bad_values) = $errors;
    $error_str = '';
    if (!empty($missing)) {
      $error_str .= 'Missing: ' . implode(', ', $missing) . '. ';
    }
    if (!empty($bad_types)) {
      $error_str .= 'Bad type(s): ' . implode(', ', $bad_types) . '. ';
    }
    if (!empty($bad_values)) {
      $error_str .= 'Bad value(s): ' . implode(', ', $bad_values) . '. ';
    }
    return $this->_pinnacleServiceError($error_str);
  }

  /**
   * Announce error during API processing.
   *
   * @param string $message
   *
   * @return array
   */
  private function _pinnacleServiceError($message) {
    return array("error" => TRUE, "message" => $message);
  }

  /**
   * Process user data.
   *
   * @param object $user
   *  A G9 User object
   *
   * @return array
   */
  private function _pinnacleServiceProcessUserData($user) {

    return array(
      'uid' => intval($user->uid),
      'name' => $user->name,
      'mail' => $user->email,
      'login' => intval($user->login),
      'access' => intval($user->access),
      'subscribed' => isset($user->thrillist->subscribed) ? intval($user->thrillist->subscribed) : NULL,
      'unsubscribed' => isset($user->thrillist->unsubscribed) ? intval($user->thrillist->unsubscribed) : NULL,
      'created' => intval($user->created),
      'first_name' => $user->first_name,
      'last_name' => $user->last_name,
      'format' => $user->format,
      'default_edition' => intval($user->default_edition),
      'subscriptions' => is_null($user->subscriptions) ? array() : array_map('intval', array_keys($user->subscriptions)),
      'editions' => is_null($user->editions) ? array() : array_map('intval', array_values($user->editions)),
      'invites' => is_null($user->invites) ? array() : array_map('intval', array_keys($user->invites)),
      'rewards' => !isset($user->rewards) ? array() : array_map('intval', array_keys($user->rewards)),
      'fbid' => isset($user->fb_uid) ? $user->fb_uid : $user->thrillist->fb_uid,
      'tl_fb_opt' => isset($user->thrillist->tl_fb_opt) ? (int) $user->thrillist->tl_fb_opt : -1,
      'public_mytl' => $user->thrillist->public_mytl ? TRUE : FALSE,
      'jt_uid' => intval($user->thrillist->jt_uid),
      'birthdate_day' => intval($user->thrillist->birthdate_day),
      'birthdate_month' => intval($user->thrillist->birthdate_month),
      'birthdate_year' => intval($user->thrillist->birthdate_year),
      'gender' => $user->gender,
      'income' => $user->income,
      'join_from' => $user->thrillist->join_from,
      'refer' => $user->thrillist->refer,
      'zip' => $user->thrillist->zip,
      'mobile_number' => $user->thrillist->mobile_number,
      'is_21' => $user->thrillist->is_21 == -1 ? NULL : ($user->thrillist->is_21 ? TRUE : FALSE),
    );
  }

}
