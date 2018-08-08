#!/usr/bin/env php

<?php
/**
 * 消费邮件队列
 *
 * @author haobaif <haobaif@jumei.com>
 * @date 2018/8/8
 */

require_once(__DIR__ . '/../api/config.php');
require_once(__DIR__ . '/../api/Queue.php');
require_once(__DIR__ . '/../api/sendemail.php');

$queue = new Queue();

while (true) {
    $time = time();
    $current = $queue->getNext();

    // 队列为空或者延时时间未到
    if (empty($current) || $current['time'] + EMAIL_DELAY_TIME > time()) {
        exit(0);
    }

    $current = $queue->pop();
    email($current['id'], $current['parent'], $current['email']);
}

exit(1);