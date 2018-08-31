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
 * @version  2018-08-30 07:47:42
 * @link     https://github.com/fooleap/disqus-php-api
 *
 */
require_once('init.php');
require_once('queue.php');

$authorName = $_POST['name'];
$authorEmail = $_POST['email'];
$authorUrl = $_POST['url'] == '' || $_POST['url'] == 'null' ? null : $_POST['url'];
$threadId = $_POST['thread'];
$parent = $_POST['parent'];
$authors = $cache -> get('authors');

// 存在父评，即回复
if(!empty($parent)){
    $fields = (object) array(
        'post' => $parent,
        'related' => 'thread'
    );
    $curl_url = '/api/3.0/posts/details.json?';
    $data = curl_get($curl_url, $fields);
    $pAuthor = $data->response->author;
    $pUid = md5($pAuthor->name.$pAuthor->email);
    if( $pAuthor->isAnonymous == false ){
        // 防止重复发邮件
        $approved = null;
    }
    
    $thread = thread_format($data->response->thread); // 文章信息
    $pUid = md5($pAuthor->name.$pAuthor->email);
    $pEmail = $authors -> $pUid; // 被回复邮箱
    $pPost = post_format($data->response);
}

$curl_url = '/api/3.0/posts/create.json';
$postMessage = $emoji->toUnicode($_POST['message']);

// 已登录
if( isset($access_token) ){
    $post_data = (object) array(
        'thread' => $threadId,
        'parent' => $parent,
        'message' => $postMessage,
        'ip_address' => $_SERVER['REMOTE_ADDR']
    );
} else {
    $post_data = (object) array(
        'thread' => $threadId,
        'parent' => $parent,
        'message' => $postMessage,
        'author_name' => $authorName,
        'author_email' => $authorEmail,
        'author_url' => $authorUrl
    );

    if(!!$cache -> get('cookie')){
        $post_data -> state = $approved;
    }
}

$data = curl_post($curl_url, $post_data);

if( $data -> code == 0 ){
    $rPost = post_format($data->response);

    $output = array(
        'code' => $data -> code,
        'thread' => $thread,
        'parent' => $pPost,
        'response' => $rPost
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
        // 放入队列
        try {
            $queue = new Queue();
            $queue->push(array('id' => $id, 'parent' => $parent, 'email' => $posts -> $parent -> email, 'time' => time()));
        } catch (\Exception $e) {

        }
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
