<?php
namespace ank\queue;

use Enqueue\Redis\RedisConnectionFactory;

/**
 * 消息队列管理
 * author:zhaokeli
 * blog:https://www.zhaokeli.com
 * link:https://www.zhaokeli.com/article/8583.html
 */
class RedisQueue
{
    protected $host         = '127.0.0.1';
    protected $port         = '5672';
    protected $exchangeName = ''; //项目名字
    protected $conn         = null;
    /**
     * 初始化配置
     * @authname [权限名字]     0
     * @Author   mokuyu
     * @DateTime 2019-09-20
     * @param    array      $config       连接配置信息
     * @param    string     $exchangeName 交换机名字(项目名字)
     */
    public function __construct($config = [], $exchangeName = 'default')
    {
        $this->exchangeName = $exchangeName;
        foreach ($config as $key => $value) {
            $this->$key = $value;
        }
    }
    /**
     * 初始化连接
     * @authname [权限名字]     0
     * @Author   mokuyu
     * @DateTime 2019-09-20
     * @return   [type]
     */
    private function initConn()
    {
        $factory = new RedisConnectionFactory([
            'host'              => $this->host,
            'port'              => $this->port,
            'scheme_extensions' => ['phpredis'],
        ]);
        $this->conn = $factory->createContext();
    }
    /**
     * 发送消息
     * @authname [权限名字]     0
     * @Author   mokuyu
     * @DateTime 2019-09-20
     * @param    [type]     $msgType 消息类型
     * @param    [type]     $msgData 消息数据
     * @return   [type]
     */
    public function sendMessage($msgType, $msgData)
    {
        // $this->setRouteName($msgType);
        $this->conn || $this->initConn();
        if (!is_string($msgData)) {
            $msgData = json_encode($msgData);
        }
        $fooQueue = $this->conn->createQueue($this->exchangeName . ':' . $msgType);
        $message  = $this->conn->createMessage($msgData);
        return $this->conn->createProducer()->send($fooQueue, $message);
    }
    /**
     * 从指定队列中取出一条消息
     * @authname [权限名字]     0
     * @Author   mokuyu
     * @DateTime 2019-09-20
     * @param    [type]     $queueName [description]
     * @return   [type]
     */
    public function getMessage($queueName)
    {
        $this->conn || $this->initConn();
        $fooQueue = $this->conn->createQueue($this->exchangeName . ':' . $queueName);
        $consumer = $this->conn->createConsumer($fooQueue);
        $message  = $consumer->receive(1000);
        if ($message) {
            $body = $message->getBody();
            // echo $body;
            //...业务处理
            // 确认消费并删除消息，如果不执行下面则此条消息会被标识为已经接收,但并不会删除
            $consumer->acknowledge($message);
            //$consumer->reject($message);
            return $body;
        } else {
            return '';
        }
    }
    /**
     * 消费者阻塞接收消息
     * @authname      0
     * @Author   mokuyu
     * @DateTime 2019-09-20
     * @param    [type]     $queueName          消息队列名字
     * @param    [type]     $receiveMessagefunc 消息回调函数
     * @return   [type]
     */
    public function receiveMessage($queueName, $receiveMessagefunc)
    {
        // $this->queue || $this->initQueue($queueName);
        // //设置消息回调
        // $this->queue->consume($receiveMessagefunc);
        $this->conn || $this->initConn();
        $fooQueue = $this->conn->createQueue($this->exchangeName . ':' . $queueName);
        $consumer = $this->conn->createConsumer($fooQueue);
        while (true) {
            $message = $consumer->receive();
            if ($message) {
                $receiveMessagefunc($message, $consumer);
                // $body = $message->getBody();
                // echo $body;
                //...业务处理
                // 确认消费并删除消息，如果不执行下面则此条消息会被标识为已经接收,但并不会删除
                // $consumer->acknowledge($message);
                //$consumer->reject($message);
            }
        }
    }
    /**
     * 删除一个队列
     * @authname [权限名字]     0
     * @Author   mokuyu
     * @DateTime 2019-09-20
     * @param    [type]     $queueName 队列名字
     * @return   [type]
     */
    public function deleteQueue($queueName)
    {
        return $this->conn->deleteQueue($this->exchangeName . ':' . $queueName);
    }
    /**
     * 清空队列中的消息
     * @authname [权限名字]     0
     * @Author   mokuyu
     * @DateTime 2019-09-20
     * @param    [type]     $queueName [description]
     * @return   [type]
     */
    public function clearQueue($queueName)
    {
        return $this->conn->deleteQueue($this->exchangeName . ':' . $queueName);
    }
}
