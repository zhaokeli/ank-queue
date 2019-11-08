<?php
use mokuyu\queue\RabbitMQQueue;
//包含自动加载类
$loader = require __DIR__ . '/vendor/autoload.php';

$conf = [
    'host'     => '127.0.0.1',
    'port'     => '5672',
    'login'    => 'guest',
    'password' => 'guest',
    'vhost'    => '/',
];
$obj = new RabbitMQQueue($conf, 'default' /*项目名字*/);

//发送消息到order队列
echo $obj->sendMessage('order', time());

//从队列取出一个消息
$msg = $obj->getMessage('order');
echo $msg;

//删除队列
$obj->deleteQueue('order');
//清除队列中的消息
$obj->clearQueue('order');

// 阻塞回调消息处理
// //接收消息并进行处理的回调方法，回调示例
// public function processMessage($envelope, $queue)
// {
//     //取消息内容
//     $msg = $envelope->getBody();
//     echo $msg . "\n"; //处理消息
//     //显式确认，队列收到消费者显式确认后，会删除该消息
//     $queue->ack($envelope->getDeliveryTag());
// }
//这里直接传啦匿名函数
$obj->receiveMessage('order', function ($envelope, $queue) {
    //取消息内容
    $msg = $envelope->getBody();
    echo $msg . "\n"; //处理消息
                      //显式确认，队列收到消费者显式确认后，会删除该消息
    $queue->ack($envelope->getDeliveryTag());
});
