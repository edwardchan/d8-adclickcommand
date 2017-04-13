<?php

/**
 * @file
 * Contains \Drupal\g9\Service\G9Util.
 */

namespace Drupal\g9\Service;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\g9\Entity\Zuul;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * G9 Utility Class.
 * Gives access to Zuul, Checklist and does a few useful things.
 */

class G9Util {

  protected $zuul;
  protected $checklist;

  protected $entityQuery;

  public function __construct(QueryFactory $entityQuery) {
    $this->entityQuery = $entityQuery;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  /**
   * Return a zuuluser object.
   * @todo.
   *
   */
  public function getZuul() {
    if (!$this->zuul){
      $this->zuul = new Zuul(new LoggerChannelFactory());
    }
    return $this->zuul;
  }

  /**
   * @todo.
   * @group Plain Old Utilities.
   */
  public function passwordResetUrl($account) {
    $timestamp = time();
    return implode('/', array(
      'password/reset/login',
      $account->uid,
      $timestamp,
      $this->userPassRehash($account->pass, $timestamp, $account->login)));
  }

  /**
   * @todo.
   * @group Plain Old Utilities.
   */
  public function userPassResetUrl($account) {
    $timestamp = time();
    return "user/reset/" . $account->id . "/" . $timestamp . "/" . $this->userPassRehash($account->pass, $timestamp, $account->login);
  }

  /**
   * @todo.
   * @group Plain Old Utilities.
   */
  public function userPassRehash($password, $timestamp, $login) {
    return md5($timestamp . $password . $login);
  }

  /**
   * @todo.
   * @group Plain Old Utilities.
   */
  public function userMailText($key, $langcode = NULL, $variables = array()) {
    $langcode = (!empty($langcode) ? $langcode : array());
    if ($admin_setting = \Drupal::config('system.site')->get('user_mail_'. $key, FALSE)) {
      // An admin setting overrides the default string.
      return strtr($admin_setting, $variables);
    }
    else {
      // No override, return default string.
      switch ($key) {
        case 'register_no_approval_required_subject':
          return t('Account details for !username at !site', $variables, $langcode);
        case 'register_no_approval_required_body':
          return t("!username,\n\nThank you for registering at !site. You may now log in to !login_uri using the following username and password:\n\nusername: !username\npassword: !password\n\nYou may also log in by clicking on this link or copying and pasting it in your browser:\n\n!login_url\n\nThis is a one-time login, so it can be used only once.\n\nAfter logging in, you will be redirected to !edit_uri so you can change your password.\n\n\n--  !site team", $variables, $langcode);
        case 'register_admin_created_subject':
          return t('An administrator created an account for you at !site', $variables, $langcode);
        case 'register_admin_created_body':
          return t("!username,\n\nA site administrator at !site has created an account for you. You may now log in to !login_uri using the following username and password:\n\nusername: !username\npassword: !password\n\nYou may also log in by clicking on this link or copying and pasting it in your browser:\n\n!login_url\n\nThis is a one-time login, so it can be used only once.\n\nAfter logging in, you will be redirected to !edit_uri so you can change your password.\n\n\n--  !site team", $variables, $langcode);
        case 'register_pending_approval_subject':
        case 'register_pending_approval_admin_subject':
          return t('Account details for !username at !site (pending admin approval)', $variables, $langcode);
        case 'register_pending_approval_body':
          return t("!username,\n\nThank you for registering at !site. Your application for an account is currently pending approval. Once it has been approved, you will receive another e-mail containing information about how to log in, set your password, and other details.\n\n\n--  !site team", $variables, $langcode);
        case 'register_pending_approval_admin_body':
          return t("!username has applied for an account.\n\n!edit_uri", $variables, $langcode);
        case 'password_reset_subject':
          return t('Replacement login information for !username at !site', $variables, $langcode);
        case 'password_reset_body':
          return t("!username,\n\nA request to reset the password for your account has been made at !site.\n\nYou may now log in to !uri_brief by clicking on this link or copying and pasting it in your browser:\n\n!login_url\n\nThis is a one-time login, so it can be used only once. It expires after one day and nothing will happen if it's not used.\n\nAfter logging in, you will be redirected to !edit_uri so you can change your password.", $variables, $langcode);
        case 'status_activated_subject':
          return t('Account details for !username at !site (approved)', $variables, $langcode);
        case 'status_activated_body':
          return t("!username,\n\nYour account at !site has been activated.\n\nYou may now log in by clicking on this link or copying and pasting it in your browser:\n\n!login_url\n\nThis is a one-time login, so it can be used only once.\n\nAfter logging in, you will be redirected to !edit_uri so you can change your password.\n\nOnce you have set your own password, you will be able to log in to !login_uri in the future using:\n\nusername: !username\n", $variables, $langcode);
        case 'status_blocked_subject':
          return t('Account details for !username at !site (blocked)', $variables, $langcode);
        case 'status_blocked_body':
          return t("!username,\n\nYour account on !site has been blocked.", $variables, $langcode);
        case 'status_deleted_subject':
          return t('Account details for !username at !site (deleted)', $variables, $langcode);
        case 'status_deleted_body':
          return t("!username,\n\nYour account on !site has been deleted.", $variables, $langcode);
      }
    }
  }

  /**
   * @todo.
   */
  public function getChecklist($checklist_id = NULL) {
    print "1";
    if (!$this->checklist){
      print "2";
      if ($checklist_id == NULL){
        print "3";
        $this->checklist =  \Drupal::service('g9.checklist')->doSomething();
        print $this->checklist;
      } else {
        $this->checklist =  \Drupal::service('g9.checklist')->getChecklist($checklist_id);
      }
     }
    print "4";

    return $this->checklist;
  }

  /**
   * @todo.
   */
  /*public function createUserChecklist($user_id, $data = array()) {
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
  }*/

  /**
   * @todo.
   */
  public function getUserChecklist($user_id) {
    $ids = \Drupal::entityQuery('node')
      ->condition('type', 'checklist')
      ->condition('owner_uid', $user_id)
      ->execute();
    if (!empty($ids)) {
      $this->checklist = \Drupal\g9\Entity\Checklist::load(current($ids));
      return $this->checklist;
    } else {
      return NULL;
    }
  }

  /**
   * @Test.
  */
  public function getAllChecklists() {
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

  public function getNodeType($nid){
    $query = $this->entityQuery->get('node');
    $query->condition('nid', $nid);
    $types = $query->execute();
    foreach ($types as $type) {
      if (!empty($type['type'])) {
        return $type['type'];
      }
    }
  }

  public function getPieceNid($nid){
    $query = $this->entityQuery->get('pieces_nodes');
    $query->condition('pid', $nid);
    $pieces = $query->execute();
    foreach ($pieces as $piece) {
      if (!empty($piece['nid'])) {
        return $piece['nid'];
      }
    }
    return NULL;
  }

}




