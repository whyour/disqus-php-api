<?php
/**
 * 发送电子邮件
 */

date_default_timezone_set("Asia/Shanghai");
require_once('init.php');
require_once('PHPMailer/class.phpmailer.php');
require_once('PHPMailer/class.smtp.php');

use PHPMailer;

function send($parentEmail, $post, $parentPost, $postUrl){
    global $cache;

    $date = date('Y-m-d H:i:s');
    $title = $cache -> get('forum') -> name;
    $url = $cache -> get('forum') -> url;

    $avatar  = getImgUrl($post['avatar']);
    $name    = $post['name'];
    $img     = getImgUrl($post['media'][0]);
    $message = empty($post['media']) ? $post['message'] : "<img src='{$img}' style='max-height: 80px;'>";
    $parentAvatar  = getImgUrl($parentPost['avatar']);
    $parentName    = $parentPost['name'];
    $parentImg     = getImgUrl($parentPost['media'][0]);
    $parentMessage = empty($parentPost['media']) ? $parentPost['message'] : "<img src='{$parentImg}' style='max-height: 80px;'>";;

    // 内容
    $content = file_get_contents(__DIR__.'/PHPMailer/template.html');
    $fields = array('avatar', 'parentAvatar', 'name', 'parentName', 'message', 'parentMessage', 'postUrl', 'url', 'title', 'date');
    $param = array();
    foreach ($fields as $field) {
        $param[$field] = $$field;
        $content = str_replace('{{'.$field.'}}', $$field, $content);
    }
    if (empty($content)) {
        return false;
    }

    // 发送邮件
    $mail = new PHPMailer();
    $mail->CharSet = "UTF-8";
    $mail->IsSMTP();
    $mail->SMTPAuth   = true;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Host       = SMTP_HOST;
    $mail->Port       = SMTP_PORT;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->Subject = '您在「' . $title . '」的评论有了新回复';
    $mail->MsgHTML($content);
    $mail->AddAddress($parentEmail, $parentName);
    $from = defined('SMTP_FROM') ? SMTP_FROM : SMTP_USERNAME;
    $from_name = defined('SMTP_FROMNAME') ? SMTP_FROMNAME : $title;
    $mail->SetFrom($from, $from_name);

    $reuslt = $mail->Send();
    // 日志
    $msg = sprintf("%s %s|param=%s|msg=%s", date('Y-m-d H:i:s'), '%s', json_encode($param), '%s');
    if (!$reuslt) {
        $msg = sprintf($msg, 'failed', $mail->ErrorInfo);
    } else {
        $msg = sprintf($msg, 'success', '');
    }
    @file_put_contents(__DIR__.'/logs/email.log', $msg);

    return $reuslt;
}


function email($postId, $parentPostId, $parentEmail){
    // 获取被回复信息
    $curl_url = '/api/3.0/posts/details.json?';
    $fields = (object) array(
        'post' => $parentPostId,
        'related' => 'thread'
    );
    $data = curl_get($curl_url, $fields);
    if ($data -> code != 0) {
        return false;
    }
    $parentPost = post_format($data->response);
    $postUrl = $data -> response-> thread -> link;
    $parentPost['name'] = 'Your';

    // 获取回复信息
    $fields = (object) array(
        'post' => $postId
    );
    $data = curl_get($curl_url, $fields);
    if ($data -> code != 0) {
        return false;
    }
    $post = post_format($data->response);

    return send($parentEmail, $post, $parentPost, $postUrl);
}