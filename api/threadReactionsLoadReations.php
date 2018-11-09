<?php
/**
 * 获取 Thread 相关 Reactions
 *
 * @param thread   thread ID
 *
 * @author   fooleap <fooleap@gmail.com>
 * @version  2018-09-22 23:21:08
 * @link     https://github.com/fooleap/disqus-php-api
 *
 */
require_once('init.php');
$fields = (object) array(
    'thread' => $_GET['thread']
);
$curl_url = '/api/3.0/threadReactions/loadReactions?';
$data = curl_get($curl_url, $fields);

print_r(json_encode($data));
