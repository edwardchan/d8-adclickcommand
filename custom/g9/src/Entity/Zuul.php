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
 * A Class to wrap the click_command_clicks table.
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
    print __FUNCTION__ . " IN Path: $path ".PHP_EOL;

    $r = $http->$method(($use_https ? ZUUL_SECURE_SERVER : ZUUL_SERVER) . $path, $data, $headers, 30);
    if ($r[1]['http_code'] != $expected_response) {
//    print "Zuul Response : ";
//    print_r($r);

      $data = preg_replace('/"password":".*?"/', '"password":"ewok"', $data);
      if ($r[1]['http_code'] == 500 || $r[1]['http_code'] == 403) {
        print  __FUNCTION__ . "  ". $r[1]['http_code'] . " // " . $method . "//" . $path . "//".print_r($data,true);
        $this->logger->notice('zuul.call_api ' . "Zuul error: [!http_code] \n\t(\n\t\tmethod='!method', \n\t\tpath='!path', \n\t\tdata='!data'\n\t)",
          array(
            '!http_code' => $r[1]['http_code'],
            '!method' => $method,
            '!path' => $path,
            '!data' => $data ? $data : '{}',
          ) );
      }
      else {
        print "==B== ";
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
    //print_r($r[0]);

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
      return NULL;
    }

    list($success, $r) = $this->_callApi('/users/email/' . $email, array(), 'get', ZUUL_USE_HTTPS);
    if (!$success) {
      print __FUNCTION__ . " Users/Email Call Fail. ".PHP_EOL;
      print __FUNCTION__ . "R = ".print_r($r);
      return NULL;
    }

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

}
