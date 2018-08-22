<?php
/**
 * 获取最近热门 Thread
 * 暂时设置为 30 天，5 条
 *
 * @author   fooleap <fooleap@gmail.com>
 * @version  2018-05-31 15:49:13
 * @link     https://github.com/fooleap/disqus-php-api
 *
 */
require_once('init.php');

$fields = (object) array(
    'limit' => 5,
    'forum' => DISQUS_SHORTNAME,
    'interval' => '30d'
);
$fields2 = (object) array();
$curl_url = '/api/3.0/threads/listPopular.json?';
$data = curl_get($curl_url, $fields);

$posts = array();
$thread = array();
foreach ( $data -> response as $key => $post ) {
    $posts[$key] = (object) array( 
        'id'=> $post -> id,
        'link'=> $post-> link,
        'title'=> $post -> clean_title,
        'postsInInterval'=> $post -> postsInInterval,
        'posts'=> $post -> posts,
    );
    $thread[] = $post -> id;
}
$fields2 -> thread = $thread;
$curl_url2 = '/api/3.0/discovery/listTopPost.json?';
$data2 = curl_get($curl_url2, $fields2);
foreach ( $data2 -> response as $k => $p ) {
	foreach ( $posts as $i => $post) {
		$a = $p -> thread;
		$b = $post -> id;
        if ($a == $b) {
            $post -> author = $p -> author;
            $post -> message = $p -> raw_message;
        }
    }
}


$output = $data -> code == 0 ? array(
    'code' => $data -> code,
    'response' => $posts
) : $data;

print_r(json_encode($output));