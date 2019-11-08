<?php
namespace mokuyu\queue;

/**
 * 消息队列管理
 * author:zhaokeli
 * blog:https://www.zhaokeli.com
 * link:https://www.zhaokeli.com/article/8583.html
 */
class RabbitMQQueue
{
    protected $channel = null;

    //队列
    protected $conn = null;

    protected $exchange = null;

    protected $exchangeName = '';

    protected $host = '127.0.0.1';

    protected $password = 'guest';

    protected $port = '5672';

    protected $queue = null;

    //路由
    protected $queueName = '';

    //交换机名字
    protected $routeName = '';

    protected $username = 'guest';

    protected $vhost = '/';

    /**
     * 初始化配置
     * @authname [权限名字]     0
     * @DateTime 2019-09-20
     * @Author   mokuyu
     *
     * @param array  $config       连接配置信息
     * @param string $exchangeName 交换机名字(项目名字)
     */
    public function __construct($config = [], $exchangeName = 'default')
    {
        $this->setExchangeName($exchangeName);
        foreach ($config as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * 清空队列中的消息
     * @authname [权限名字]     0
     * @DateTime 2019-09-20
     * @Author   mokuyu
     *
     * @param  [type]   $queueName [description]
     * @return [type]
     */
    public function clearQueue($queueName)
    {
        $this->queue || $this->initQueue($queueName);

        return $this->queue->purge();
    }

    /**
     * 删除一个队列
     * @authname [权限名字]     0
     * @DateTime 2019-09-20
     * @Author   mokuyu
     *
     * @param  [type]   $queueName 队列名字
     * @return [type]
     */
    public function deleteQueue($queueName)
    {
        $this->queue || $this->initQueue($queueName);

        return $this->queue->delete();
    }

    /**
     * 从指定队列中取出一条消息
     * @authname [权限名字]     0
     * @DateTime 2019-09-20
     * @Author   mokuyu
     *
     * @param  [type]   $queueName [description]
     * @return [type]
     */
    public function getMessage($queueName)
    {
        $this->queue || $this->initQueue($queueName);

        return $this->queue->get(AMQP_AUTOACK)->getBody();
    }

    /**
     * 消费者阻塞接收消息
     * @authname      0
     * @DateTime 2019-09-20
     * @Author   mokuyu
     *
     * @param  [type]   $queueName          消息队列名字
     * @param  [type]   $receiveMessagefunc 消息回调函数
     * @return [type]
     */
    public function receiveMessage($queueName, $receiveMessagefunc)
    {
        $this->queue || $this->initQueue($queueName);
        //设置消息回调
        $this->queue->consume($receiveMessagefunc);
    }

    /**
     * 发送消息
     * @authname [权限名字]     0
     * @DateTime 2019-09-20
     * @Author   mokuyu
     *
     * @param  [type]   $msgType 消息类型
     * @param  [type]   $msgData 消息数据
     * @return [type]
     */
    public function sendMessage($msgType, $msgData)
    {
        $this->setRouteName($msgType);
        $this->conn || $this->initConn();
        if (!is_string($msgData)) {
            $msgData = json_encode($msgData);
        }

        return $this->exchange->publish($msgData, $this->routeName) ? true : false;
    }

    public function setExchangeName($name)
    {
        $this->exchangeName = 'exc_' . $name;
    }

    public function setQueueName($name)
    {
        $this->queueName = 'queue_' . $name;
    }

    public function setRouteName($name)
    {
        $this->routeName = 'route_' . $name;
    }

    /**
     * 初始化连接
     * @authname [权限名字]     0
     * @DateTime 2019-09-20
     * @Author   mokuyu
     *
     * @return [type]
     */
    private function initConn()
    {
        //配置信息
        $conn_args = [
            'host'     => $this->host,
            'port'     => $this->port,
            'login'    => $this->username,
            'password' => $this->password,
            'vhost'    => $this->vhost,
        ];
        //创建连接和channel
        $this->conn = new \AMQPConnection($conn_args);
        if (!$this->conn->connect()) {
            die("Cannot connect to the broker!\n");
        }
        //在连接内创建一个通道
        $this->channel = new \AMQPChannel($this->conn);
        //创建交换机
        $this->exchange = new \AMQPExchange($this->channel);
        $this->exchange->setName($this->exchangeName);
        //设置交换机类型
        //AMQP_EX_TYPE_DIRECT:直连交换机
        //AMQP_EX_TYPE_FANOUT:扇形交换机
        //AMQP_EX_TYPE_HEADERS:头交换机
        //AMQP_EX_TYPE_TOPIC:主题交换机
        $this->exchange->setType(AMQP_EX_TYPE_DIRECT);
        //设置交换机持久
        $this->exchange->setFlags(AMQP_DURABLE);
        //声明交换机并输出状态
        $this->exchange->declareExchange();
        // echo "Exchange Status:" .  . "\n";
    }

    /**
     * 初始化队列
     * @authname [权限名字]     0
     * @DateTime 2019-09-20
     * @Author   mokuyu
     *
     * @param  [type]   $queueName [description]
     * @return [type]
     */
    private function initQueue($queueName)
    {
        $this->setQueueName($queueName);
        $this->setRouteName($queueName);
        $this->conn || $this->initConn();
        //创建队列
        $this->queue = new \AMQPQueue($this->channel);
        $this->queue->setName($this->queueName);
        //设置队列持久
        $this->queue->setFlags(AMQP_DURABLE);
        //声明消息队列并输出状态
        $this->queue->declareQueue();
        //绑定交换机与队列，并指定路由键
        $this->queue->bind($this->exchangeName, $this->routeName);
    }
}
