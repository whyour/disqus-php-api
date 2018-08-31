<?php
/**
 * Class Queue.
 * redis队列
 *
 * @author haobaif <haobaif@jumei.com>
 */

/**
 * Class Queue.
 */
class Queue
{
    /**
     * Redis
     */
    public $redisInstance;

    /**
     * 队列key
     */
    public $key = 'disqus_queue';

    public function __construct()
    {
        $this->redisInstance = new Redis();
        if (!$this->redisInstance->connect(REDIS_HOST)) {
            new Exception('redis connect error.');
        }

        $this->redisInstance->auth(REDIS_AUTH);
    }

    /**
     * 获取即将出队的元素,类似于pop,但是元素不会从队列中消失
     *
     * @return mixed|null
     */
    public function getNext()
    {
        $msg = $this->redisInstance->lRange($this->key, 0, 0);
        if ($msg) {
            return json_decode($msg[0], true);
        }

        return null;
    }

    /**
     * 出队
     *
     * @return mixed|null
     */
    public function pop()
    {
        $msg = $this->redisInstance->lPop($this->key);
        if ($msg) {
            return json_decode($msg, true);
        }

        return null;
    }

    /**
     * 入队
     *
     * @param array $data
     *
     * @return bool
     */
    public function push(array $data)
    {
        return (boolean)$this->redisInstance->rPush($this->key, json_encode($data));
    }

    /**
     * 队列是否为空
     *
     * @return bool
     */
    public function isEmpty()
    {
        return (boolean)$this->redisInstance->lLen($this->key);
    }
}
