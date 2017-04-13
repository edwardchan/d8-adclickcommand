<?php

namespace Drupal\g9\Component\Utility;

/**
 * Class EmailValidator.
 *
 * @package Drupal\g9\Component\Utility
 */
class EmailValidator {

  /**
   * Checks if a string looks somewhat like an email address.
   *
   * Specifically, email-like strings should have an `@` symbol and at least
   * one period after the `@`.
   *
   * @param string $email
   *   A string to be tested.
   *
   * @return bool
   *   Whether the string passes the loose validity check or not.
   */
  public static function isLooselyValid($email) {
    // Require the existence of at least one period, which conveniently allows
    // IP addresses as well.
    return preg_match('/^.+@.+\..+$/', $email) > 0;
  }

  /**
   * Mostly RFC-compliant email validation.
   *
   * This should be used in place of valid_email_address() in places that
   * alter the user's email. Replace the body of this function with
   * implementation improvements instead of creating a new function.
   *
   * @param string $email
   *   A string to be tested for email validity.
   *
   * @return bool
   *   Whether the string passes the validity check or not.
   *
   * @see http://www.linuxjournal.com/article/9585?page=0,0
   */
  public static function isValid($email) {
    // First we check that there's one `@` symbol, and that the lengths are
    // right.
    if (!preg_match("/^[^@]{1,64}@[^@]{1,255}$/", $email)) {
      // Email invalid because wrong number of characters in one section or
      // wrong number of `@` symbols.
      return FALSE;
    }

    // Split it into sections to make life easier.
    $email_array = explode('@', $email);
    $local_array = explode('.', $email_array[0]);
    for ($i = 0; $i < count($local_array); $i++) {
      if (!preg_match("/^(([A-Za-z0-9!#$%&'*+\/=?^_`{|}~-][A-Za-z0-9!#$%&'*+\/=?^_`{|}~.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$/", $local_array[$i])) {
        return FALSE;
      }
    }

    // Check if domain is IP. If not, it should be valid domain name.
    if (!preg_match("/^[?[0-9.]+]?$/", $email_array[1])) {
      $domain_array = explode('.', $email_array[1]);
      if (count($domain_array) < 2) {
        // Not enough parts to be a domain.
        return FALSE;
      }

      for ($i = 0; $i < count($domain_array); $i++) {
        if (!preg_match("/^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$/", $domain_array[$i])) {
          return FALSE;
        }
      }
    }

    return TRUE;
  }

}
