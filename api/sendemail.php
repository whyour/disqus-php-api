<?php
/**
 * 发送电子邮件
 *
 * @param thread  thread 信息
 * @param parent  父评论信息
 * @param post    当前评论信息
 *
 * @author   fooleap <fooleap@gmail.com>
 * @version  2018-09-01 14:11:50
 * @link     https://github.com/fooleap/disqus-php-api
 *
 */
date_default_timezone_set("Asia/Shanghai");
require_once('init.php');
require_once('PHPMailer/class.phpmailer.php');
require_once('PHPMailer/class.smtp.php');

use PHPMailer;

$authors = $cache -> get('authors');
$code = $_POST['code'];
if(!empty($code)){
    if(isset($authors -> $code)){
        $pEmail = $authors -> $code;
        $thread = json_decode($_POST['thread']);
        $pPost = json_decode($_POST['parent']);
        $rPost = json_decode($_POST['post']);
        sendEmail($thread, $pPost, $rPost, $pEmail);
    }
}

$debug = '';
function sendEmail($thread, $pPost, $rPost, $pEmail){
    global $cache;

    $date = date('Y-m-d H:i:s');
    $forum = $cache -> get('forum');
    $forumName = $forum -> name;
    $forumUrl = $forum -> url;

    $threadTitle = $thread -> title;
    $threadLink = $thread -> link;

    $pId = $pPost -> id;
    $pName = $pPost -> name;
    $rName = $rPost -> name;
    $rAvatar  = getImgUrl($rPost['avatar']);
    $rImg     = getImgUrl($rPost['media'][0]);
    $pAvatar  = getImgUrl($pPost['avatar']);
    $pImg     = getImgUrl($pPost['media'][0]);
    $pMessage = empty($pPost['media']) ? $pPost['message'] : "<img src='{$pImg}' style='max-height: 80px;'>";;
    $rMessage = empty($rPost['media']) ? $rPost['message'] : "<img src='{$rImg}' style='max-height: 80px;'>";

    // 内容
    $content = file_get_contents(__DIR__.'/PHPMailer/template.html');
    $fields = array('rAvatar', 'pAvatar', 'rName', 'pName', 'rMessage', 'pMessage', 'threadLink', 'forumUrl', 'forumName', 'date');
    foreach ($fields as $field) {
        $content = str_replace('{{'.$field.'}}', $$field, $content);
    }
    if (empty($content)) {
        return false;
    }


    // $content = '<p>' . $pName . '，您在<a target="_blank" href="'.$forumUrl.'">「'. $forumName .'」</a>的评论：</p>';
    // $content .= $pMessage;
    // $content .= '<p>' . $rName . ' 的回复如下：</p>';
    // $content .= $rMessage;
    // $content .= '<p>查看详情及回复请点击：<a target="_blank" href="'.$threadLink.'?#comment-'.$pId.'">'. $threadTitle . '</a></p>';

    $mail          = new PHPMailer();
    $mail->CharSet = "UTF-8"; 
    $mail->IsSMTP();
    $mail->SMTPAuth   = true;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Host       = gethostbyname(SMTP_HOST);
    $mail->Port       = SMTP_PORT;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    if(!extension_loaded('openssl')){
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
    } else {
        $mail->SMTPOptions = array(
            'ssl' => array (
                'verify_peer' => true,
                'verify_depth' => 3,
                'allow_self_signed' => true,
                'peer_name' => SMTP_HOST,
                'cafile' => './cacert.pem',
             )
        );
    }
    $mail->Subject = '您在「' . $forumName . '」的评论有了新回复';
    $mail->MsgHTML($content);
    $mail->AddAddress($pEmail, $pName);
    $from = defined('SMTP_FROM') ? SMTP_FROM : SMTP_USERNAME;
    $fromName = defined('SMTP_FROMNAME') ? SMTP_FROMNAME : $forumName;
    $mail->SetFrom($from, $fromName);
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = function($str, $level) {
        $GLOBALS['debug'] .= "$level: $str\n";
    };
    if(!$mail->Send()) {
        file_put_contents(__DIR__.'/cache/phpmailer_error.log', $GLOBALS['debug']);
    }
}
