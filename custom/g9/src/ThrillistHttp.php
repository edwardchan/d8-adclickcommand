<?php

namespace Drupal\g9;

class ThrillistHttp {

private static $instance = array();
protected $ch = NULL;

public $content_type = 'text/html';
public $arg_separator;

public function __construct($content_type) {
$this->content_type = $content_type;
}

/**
* Retrieve the singleton instance.
*
* @param $content_type string
*   Requested content-type (default: "text/html").
* @param $namespace string
*   Singleton namespace, to allow multiple singletons based on context (default: "tl").
*/
public static function instance($content_type = NULL, $namespace = 'tl') {
if (!isset(self::$instance[$namespace])) {
self::$instance[$namespace] = new self($content_type);
}
return self::$instance[$namespace];
}

public function initHandle($timeout) {
$this->ch = curl_init();
curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($this->ch, CURLOPT_TIMEOUT, $timeout);
curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, FALSE);
curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, TRUE);
curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Accept: ' . $this->content_type));
}

/**
* Make an HTTP request.
*
* @param string $uri
*   URI to request.
* @param string $method
*  HTTP method to use (GET, POST, PUT, etc.)
* @param mixed $parms
*  Query parameters or request body. May be a string (raw request body) or
*  an array (GET query parameters or POST form fields).
* @param bool $close
*   If TRUE, close the connection after the request completes.
* @param bool $get_info
*   If TRUE, retrieve cURL information.
* @param int $timeout
*   HTTP timeout in seconds.
* @param array $headers
*   And array of HTTP headers to add to the request.
* @return mixed
*   If $get_info is TRUE, return a 2-tuple where the first element is the
*   response and the second is the cURL information.
*   Otherwise return only the response.
*/
public function request($uri, $method = 'GET', $parms = NULL, $close = TRUE, $get_info = FALSE, $timeout = 10, $headers = array()) {
if ($this->ch === NULL) {
$this->initHandle($timeout);
}
curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $method);
curl_setopt($this->ch, CURLINFO_HEADER_OUT, 1);
switch ($method) {
case 'GET':
if ($parms) {
// Assumes $uri does not already contain a query string!
$uri .= '?' . $this->buildQuery($parms);
}
break;
case 'POST':
curl_setopt($this->ch, CURLOPT_POST, TRUE);
if ($parms) {
curl_setopt($this->ch, CURLOPT_POSTFIELDS, $parms);
}
break;
case 'PUT':
case 'DELETE':
if ($parms) {
curl_setopt($this->ch, CURLOPT_POSTFIELDS, $parms);
}
break;
}
curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($this->ch, CURLOPT_URL, $uri);
$response = curl_exec($this->ch);
if ($get_info) {
$info = curl_getinfo($this->ch);
}
if ($close) {
curl_close($this->ch);
$this->ch = NULL;
}
if ($get_info) {
return array($response, $info);
}
return $response;
}

public function get($uri, $parms = array(), $headers = array(), $timeout = 10) {
return $this->request($uri, 'GET', $parms, TRUE, TRUE, $timeout, $headers);
}

public function post($uri, $body = array(), $headers = array(), $timeout = 10) {
return $this->request($uri, 'POST', $body, TRUE, TRUE, $timeout, $headers);
}

public function delete($uri, $body = array(), $headers = array(), $timeout = 10) {
return $this->request($uri, 'DELETE', $body, TRUE, TRUE, $timeout, $headers);
}

public function put($uri, $body = array(), $headers = array(), $timeout = 10) {
return $this->request($uri, 'PUT', $body, TRUE, TRUE, $timeout, $headers);
}

/**
* Build the query string.
*
* Override this if the target service doesn't like PHP-style query building.
*
* @param type $params
*/
protected function buildQuery($params) {
return http_build_query($params);
}
}
