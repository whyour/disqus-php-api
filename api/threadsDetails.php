<?php
/**
 * 获取文章详情
 *
 * @param thread   Thread Id
 *
 * @author   fooleap <fooleap@gmail.com>
 * @version  2018-09-21 18:59:57
 * @link     https://github.com/fooleap/disqus-php-api
 *
 */
require_once('init.php');

$forum = $cache -> get('forum');
$thread = 'ident:'.$_GET['ident'];
$fields = (object) array(
    'forum' => DISQUS_SHORTNAME,
    'thread' => $thread
);

$curl_url = '/api/3.0/threads/details.json?';
$data = curl_get($curl_url, $fields);
if( $data -> code == 2 ){
    $thread = 'link:'.$website.$_GET['link'];
    $fields -> thread = $thread;
    $data = curl_get($curl_url, $fields);
}
$fields = (object) array(
    'thread' => $data -> response -> id
);
$curl_url = '/api/3.0/threads/listUsersVotedThread.json?';
$userdata = curl_get($curl_url, $fields);
if( !$data -> response -> ipAddress){
    adminLogin();
}

$output = $data -> code == 0 ? (object) array(
    'code' => 0,
    'response' => thread_format($data -> response),
    'forum' => $forum,
    'votedusers' => $userdata -> response
) : $data;

print_r(json_encode($output));
