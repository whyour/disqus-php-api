<?php
/**
 * 发表评论
 *
 * @param thread  thread ID
 * @param parent  父评论 ID，可为空
 * @param message 评论内容
 * @param name    访客名字
 * @param email   访客邮箱
 * @param url     访客网址，可为空
 *
 * @author   fooleap <fooleap@gmail.com>
 * @version  2018-06-13 21:52:53
 * @link     https://github.com/fooleap/disqus-php-api
 *
 */
require_once('init.php');
require_once('sendemail.php');

$author_name = $_POST['name'];
$author_email = $_POST['email'];
$author_url = $_POST['url'] == '' || $_POST['url'] == 'null' ? null : $_POST['url'];
$thread = $_POST['thread'];
$parent = $_POST['parent'];
$identifier = $_POST['identifier'];

$curl_url = '/api/3.0/posts/create.json';
$post_message = $emoji->toUnicode($_POST['message']);

// 已登录
if( isset($access_token) ){
    $post_data = (object) array(
        'thread' => $thread,
        'parent' => $parent,
        'message' => $post_message,
        'ip_address' => $_SERVER['REMOTE_ADDR']
    );
} else {
    $post_data = (object) array(
        'thread' => $thread,
        'parent' => $parent,
        'message' => $post_message,
        'author_name' => $author_name,
        'author_email' => $author_email,
        'author_url' => $author_url
    );
}

$data = curl_post($curl_url, $post_data);

if( $data -> code == 0 ){
    $output = array(
        'code' => $data -> code,
        'thread' => $thread,
        'response' => post_format($data -> response)
    );
} else {
    $output = $data;
}

print_r(json_encode($output));
fastcgi_finish_request();

// 发送邮件通知
if( $data -> code == 0 ){
    $id = $data -> response -> id;
    $createdAt = $data -> response ->createdAt;
    $posts = $cache -> get('posts');

    // 邮件通知父评,目前只能做到匿名评论,无法通过其他方式获取邮件地址
    if( isset($posts -> $parent) && SMTP_ENABLE ){
        email($id, $parent, $posts -> $parent -> email);
    }

    // 匿名用户暂存邮箱号
    if( !isset($access_token) ){
        /*foreach ( $posts as $key => $post ){
            if(strtotime('-1 month') > strtotime($post -> createdAt)){
                unset($posts -> $key);
            }
        }*/
        $posts -> $id = (object) array(
            'email' => $author_email,
            'createdAt' => $createdAt
        );
        $cache -> update($posts, 'posts');
    }
}

updateThreadData("ident:$identifier");