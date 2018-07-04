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

$author_name = $_POST['name'];
$author_email = $_POST['email'];
$author_url = $_POST['url'] == '' || $_POST['url'] == 'null' ? null : $_POST['url'];
$thread = $_POST['thread'];
$parent = $_POST['parent'];

// 存在父评，即回复
if(!empty($parent)){

    $fields = (object) array(
        'post' => $parent
    );
    $curl_url = '/api/3.0/posts/details.json?';
    $data = curl_get($curl_url, $fields);
    $isAnonParent = $data->response->author->isAnonymous;
    if( $isAnonParent == false ){
        // 防止重复发邮件
        $approved = null;
    }
}

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

    if(!!$cache -> get('cookie')){
        $post_data -> state = $approved;
    }
}

$data = curl_post($curl_url, $post_data);

if( $data -> code == 0 ){

    $output = array(
        'code' => $data -> code,
        'thread' => $thread,
        'response' => post_format($data -> response)
    );

    $id = $data -> response -> id;
    $createdAt = $data -> response ->createdAt;
    $posts = $cache -> get('posts');
    $parentPost = $posts -> $parent;

    // 父评邮箱号存在
    if( isset($parentPost) && SMTP_ENABLE ){

        $fields = (object) array(
            'parent' => $parent,
            'parentEmail' => $parentPost -> email,
            'id' => $id
        );

        $fields_string = fields_format($fields);

        $ch = curl_init();
        $options = array(
            CURLOPT_URL => getCurrentDir().'/sendemail.php',
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_POST => count($fields),
            CURLOPT_POSTFIELDS => $fields_string,
            CURLOPT_TIMEOUT => 1
        );
        curl_setopt_array($ch, $options);
        curl_exec($ch);
        $errno = curl_errno($ch);
        if ($errno == 60 || $errno == 77) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
            curl_exec($ch);
        }
        curl_close($ch);
    }

    // 匿名用户暂存邮箱号
    if( !isset($access_token) ){
        foreach ( $posts as $key => $post ){
            if(strtotime('-1 month') > strtotime($post -> createdAt)){
                unset($posts -> $key);
            }
        }
        $posts -> $id = (object) array(
            'email' => $author_email,
            'createdAt' => $createdAt
        );
        $cache -> update($posts, 'posts');
    }

} else {

    $output = $data;

}

print_r(json_encode($output));
