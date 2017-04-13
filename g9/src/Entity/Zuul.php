<?php

namespace Drupal\g9\Entity;

use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\g9\ThrillistHttp;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;

define('ZUUL_SHARED_SECRET', '9e71aad7686608b896cb8d467ff848cc');
define('ZUUL_HOST', 'stage-users-01.thrillist.com');
define('ZUUL_SERVER', 'http://' . ZUUL_HOST . ':8888/v1');
define('ZUUL_SECURE_SERVER', 'https://' . ZUUL_HOST . '/v1');
define('ZUUL_USE_HTTPS', FALSE);

   /**
 * A Class to wrap the zuul functions.
 */
class Zuul {

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
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger
   *   The logger.
   */
  public function __construct(LoggerChannelFactory $logger) {
    $this->logger = $logger->get('zuul');
  }

  public static function create(ContainerInterface $container){
    return new static(
      $container->get('logger.factory')
    );
  }
  /**
   * Get Drupal user data.
   *
   * @param int $id
   *  User ID.
   * @param bool $reset
   *
   * @return array
   */
  public function getUser($id, $reset = TRUE) {
    $account = NULL;
    if (!$id) {
      return NULL;
    }
    $cache = NULL;
    $cid = 'zuul.user.' . $id;
    if (!$reset) {
      // @TODO How to reset cache?
      //$cache = cache_get($cid, 'cache');
    }
    if ($reset || empty($cache->data)) {
      list($success, $r) = $this->_callApi('/users/' . $id, array(), 'get', ZUUL_USE_HTTPS);
      if (!$success) {
        return NULL;
      }
      $result = json_decode($r[0], TRUE);
      $account = $result['response'];
      //$accounts[$id] = $account;
      //cache_set($cid, $account, 'cache');
    }
    else {
      //$account = $cache->data;
    }
    //print "\n\n\nGot Email ".print_r($account['email'],true);
    return $account;
  }

  /**
   * Get Drupal user data.
   *
   * @param int $zuul_id
   *  Zuul User ID.
   *
   * @return object
   */
  public function getUserByZuulId($zuul_id) {
    // query the drupal db on the zuul_id_field column
    $user_id = \Drupal::entityQuery('user')
      ->condition('field_zuul_user_id', $zuul_id)
      ->execute();
    $user = User::load(current($user_id));
    return $user;
  }

  /**
   * @param string $path
   *  The path of the API endpoint.
   *
   * @param array $data
   *  An array of data.
   *
   * @param string $method
   *  Which request method to use. Defaults to POST
   *
   * @param boolean $use_https
   *  Whether to use HTTPS or not. Defaults to FALSE.
   *
   * @param int $expected_response
   *  The expected HTTP Code response.
   *
   * @return array
   *  The API response.
   */
  private function _callApi($path, $data = array(), $method = 'post', $use_https = FALSE, $expected_response = 200) {
    $http = new ThrillistHttp('application/json');
    $headers = array(
      'Content-Type: application/json; charset=utf-8',
      'secret: ' . ZUUL_SHARED_SECRET,
      'zuulcompany: thrillist',
    );
    if (!empty($data)) {
      $data = json_encode($data);
    }

    print __METHOD__ . " Path: $path ".PHP_EOL;

    $r = $http->$method(($use_https ? ZUUL_SECURE_SERVER : ZUUL_SERVER) . $path, $data, $headers, 30);
    if ($r[1]['http_code'] != $expected_response) {
      $data = preg_replace('/"password":".*?"/', '"password":"ewok"', $data);
      if ($r[1]['http_code'] == 500 || $r[1]['http_code'] == 403) {
        //print  __FUNCTION__ . "  ". $r[1]['http_code'] . " // " . $method . "//" . $path . "//".print_r($data,true);
        $this->logger->notice('zuul.call_api ' . "Zuul error: [!http_code] \n\t(\n\t\tmethod='!method', \n\t\tpath='!path', \n\t\tdata='!data'\n\t)",
          array(
            '!http_code' => $r[1]['http_code'],
            '!method' => $method,
            '!path' => $path,
            '!data' => $data ? $data : '{}',
          ) );
      }
      else {
        $this->logger->notice('zuul.call_api ' . "Zuul error: [!http_code] \n\t(\n\t\tmethod='!method', \n\t\tpath='!path', \n\t\tdata='!data', \n\t\tresp='!resp'\n\t)",
          array(
            '!http_code' => $r[1]['http_code'],
            '!method' => $method,
            '!path' => $path,
            '!data' => $data ? $data : '{}',
            '!resp' => $r[0],
          ));
      }
      return array(FALSE, $r);
    }

    if (\Drupal::config('system')->get('zuul_log_calls') && function_exists('module_implements')) {
      $data = preg_replace('/"password":".*?"/', '"password":"ewok"', $data);
      $this->logger->notice('zuul.call_api ' . "Zuul call: [!http_code] \n\t(\n\t\tmethod='!method', \n\t\tpath='!path', \n\t\tdata='!data', \n\t\tresp='!resp'\n\t)",
        array(
          '!http_code' => $r[1]['http_code'],
          '!method' => $method,
          '!path' => $path,
          '!data' => $data ? $data : '{}',
          '!resp' => $r[0],
        ));
    }

    return array(TRUE, $r);
  }

  /**
   * Get the ID for a given user.
   *
   * @param string User email address.
   * @return int Zuul user ID.
   */
  public function getIdForUser($email) {
    if ($email !== '' && !\Drupal::service('email.validator')->isValid($email)) {
      print __METHOD__ . " !! Invalid email $email. ".PHP_EOL;
      return NULL;
    }

    list($success, $r) = $this->_callApi('/users/email/' . $email, array(), 'get', ZUUL_USE_HTTPS);
    if (!$success) {
      print __FUNCTION__ . " Users/Email Call Fail. ".PHP_EOL;
      print __FUNCTION__ . "R = ".print_r($r);
      return NULL;
    }
    print __FUNCTION__ . " Got some success ".PHP_EOL;

    $result = json_decode($r[0], TRUE);
    return $result['response'];
  }

  /**
  * ($args['email'], array('id' => 1)))
  */
  public function findByEmail($email, $fields = array()){
    $uid = $this->getIdForUser($email);
    return $this->getUser($uid);
  }

  /**
   * Get a password reset token.
   *
   * @param int $id User ID.
   * @return string  Password reset token.
   */
  public function getPasswordResetToken($id) {
    list($success, $r) = $this->_callApi('/users/' . $id . '/password/reset', array(), 'get', ZUUL_USE_HTTPS, 200);
    if (!$success) {
      return NULL;
    }
    $result = json_decode($r[0], TRUE);
    return $result['response']['hash'];
  }

  /**
   * Update a user in Zuul.
   *
   * @param int $zuul_id
   *  Email address
   *
   * @param array $data
   *
   * @return array
   *   FALSE + response object on any failure, and TRUE
   *   + user data on success.
   */
  function updateUser($email, $user_id, $data = array()) {
    $update_data = array();
    $allowed_fields = array(
      'gender',
      'income',
      'mobile_number',
      'zip',
      'format',
      'referrer',
      'default_edition',
      'first_name',
      'last_name',
      'name',
      'birthdate_year',
      'birthdate_month',
      'birthdate_day',
      'password',
    );

    foreach ($allowed_fields as $allowed) {
      if (array_key_exists($allowed, $data)) {
        $update_data[$allowed] = $data[$allowed];
      }
    }
    $update_data['email'] = $email;

    $update_data = array(
      'email' => $email,
      'password' => $data['password'],
      'username' => ($data['username'] ? $data['username'] : sha1($email . time())),
      //'subscriptions' => $subscriptions,
    );

    list($success, $r) = $this->_callApi('/users/' . $user_id, $update_data, 'post', ZUUL_USE_HTTPS, 201);
    if (!$success) {
      return array(FALSE, $r);
    }
    $result = json_decode($r[0], TRUE);
    return $result['response'];
  }


  /**
   * Get user editions.
   *
   * @param mixed $user
   *
   * @return array
   *  List of editions on success, NULL on failure.
   */
  public function getEditionsForUser($user_id) {
    print __FUNCTION__ .PHP_EOL;
    $user = NULL;
    if (is_numeric($user_id)) {
      if (($user = $this->getUserByZuulId($user_id)) === NULL) {
        return NULL;
      }
    }
    $editions = array();
    foreach ($user['subscriptions'] as $sub) {
      $editions[] = $sub['list_id'];
    }
    return $editions;
  }


  /**
   * Edit Zuul subscriptions for the given user.
   *
   * @param int $id
   *  User ID.
   *
   * @param array $list_ids
   *  List of list IDs.
   *
   * @param string $company
   *  Company name.
   *
   * @param bool $get_user
   *  If TRUE, return user data (and clear the cache in the process).
   *
   * @return mixed
   *  True on success, unless get_user is true, in which case we return a Zuul object.
   */
  function editSubscriptions($id, $list_ids, $company, $source = NULL, $get_user = TRUE) {
    $data = array('subscriptions' => array_map('intval', $list_ids));
    // TODO: support oncoming changes to the API
    if ($source === NULL) {
      $source = array(
        'partner' => 'organic',
        'origin_type' => 'default',
        'origin_id' => 'thrillist',
      );
    }
    $data['source'] = $source;
    list($success, $r) = $this->_call_api('/users/' . $id . '/subscriptions/' . $company, $data, 'post', ZUUL_USE_HTTPS);
    if (!$success) {
      return $get_user ? NULL : FALSE;
    }
    if ($get_user) {
      return $this->getUser($id, TRUE);
    }
    return TRUE;
  }

  /**
   * Convert a Zuul user response to an object that mimics the old user system.
   */
  public function convertUser($response) {
    $bd_year = '';
    $bd_month = '';
    $bd_day = '';
    if (isset($response['birthdate'])) {
      list($bd_year, $bd_month, $bd_day) = explode('-', $response['birthdate']);
    }

    // hostname, cache, session, roles, persistent_login will be populated externally
    $user_id = isset($response['id']) ? $response['id'] : $response['_id'];
    $thrillist =& $response['metadatas']['thrillist'];
    if (!empty($response['sources'])) {
      $refer = $response['sources'][0]['partner'];
      $join_from = $response['sources'][0]['origin_id'];
    }
    if (isset($response['facebooks']['thrillist']) && !empty($response['facebooks']['thrillist'])) {
      $facebook =& $response['facebooks']['thrillist'][0];
    }
    else {
      $facebook = NULL;
    }
    $subscriptions = NULL;//_zuul_get_subscriptions($response);
    return (object) array(
      'uid' => $user_id,
      'name' => $response['email'],
      'pass' => '',
      'mail' => $response['email'],
      'email' => $response['email'],
      'picture' => '',
      'mode' => '0',
      'sort' => '0',
      'threshold' => '0',
      'theme' => '',
      'signature' => '',
      'signature_format' => '0',
      'created' => strtotime($response['created_at']),
      'access' => isset($thrillist['access']) ? $thrillist['access'] : $response['last_logged_in'],
      'login' => $response['last_logged_in'] !== NULL ? strtotime($response['last_logged_in']) : 0,
      'status' => 1,
      'timezone' => NULL,
      'language' => '',
      'init' => $response['initial_email'],
      'data' => '',
      'timezone_name' => '',
      'first_name' => $response['first_name'],
      'last_name' => $response['last_name'],
      'default_edition' => isset($thrillist['default_edition']) ? $thrillist['default_edition'] : 1, // TEMP
      'format' => isset($thrillist['format']) ? $thrillist['format'] : '',
      'subscriptions' => $subscriptions,
      'invites' => NULL, //_zuul_get_invites($response),
      'income' => $thrillist['income'],
      'gender' => $response['gender'],
      'birthday' => array(
        'month' => $bd_month,
        'day' => $bd_day,
        'year' => $bd_year,
      ),

      'birthdate_day' => $bd_day,
      'birthdate_month' => $bd_month,
      'birthdate_year' => $bd_year,
      'is_21' => (int)$response['is_21'] > 0,
      'birthdate' => $response['birthdate'],
      'zip' => $response['zip'],

      'mobile_device_id' => !empty( $thrillist['mobile_device_id'] ) ? $thrillist['mobile_device_id'] : NULL,
      'sources' => $response['sources'],
      'editions' => array_keys($subscriptions), // Deprecated in favor of subscriptions
      'thrillist' => (object)array(
        'uid' => isset($response['id']) ? $response['id'] : $response['_id'],
        'last_login' => !empty( $thrillist['last_logged_in'] ) ? $thrillist['last_logged_in'] : NULL,
        'default_edition' => isset($thrillist['default_edition']) ? $thrillist['default_edition'] : 1, // TEMP
        'gender' => $response['gender'],
        'income' => $thrillist['income'],
        'birthdate_month' => $bd_month,
        'birthdate_day' => $bd_day,
        'birthdate_year' => $bd_year,
        'mobile_number' => !empty( $thrillist['mobile_number'] ) ? $thrillist['mobile_number'] : NULL,
        'zip' => $response['zip'],
        'format' => isset($thrillist['format']) ? $thrillist['format'] : 'h',
        'refer' => $refer, // Deprecated in favor of sources
        'join_from' => $join_from, // Deprecated in favor of sources
        'ssid' => '0',

        'first_name' => $response['first_name'],
        'last_name' => $response['last_name'],
        'mobile_device_id' => !empty( $thrillist['mobile_device_id'] ) ? $thrillist['mobile_device_id'] : NULL,
        'jt_uid' => NULL, //zuul_is_jackthreads_user($response) ? $response['id'] : NULL,

        'fb_uid' => isset($facebook['fb_id']) ? $facebook['fb_id'] : $response['fb_id'],

        'is_21' => $response['is_21'],

        'uid_hash' => $thrillist['uid_hash'],
        'public_mytl' => isset($thrillist['public_mytl']) ? (int)$thrillist['public_mytl'] : NULL,
        'tl_fb_opt' => isset($thrillist['tl_fb_opt']) ? $thrillist['tl_fb_opt'] : -1,
        'fb_access_token' => isset($facebook['access_token']) ? $facebook['access_token'] : NULL,
      ),
    );
  }


}
