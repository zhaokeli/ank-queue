<?php
use mokuyu\queue\RedisQueue;
//包含自动加载类
$loader = require __DIR__ . '/vendor/autoload.php';

$queue = new RedisQueue([
    'host' => '127.0.0.1',
    'port' => 6379,
], 'default' /*项目名字*/);
// 发送一个消息
// $queue->sendMessage('order', 'a message');
// // 取出一条信息
// $message = $queue->getMessage('order');
// var_dump($message);
// // 阻塞获取消息
// $queue->receiveMessage('order', function ($message, $obj) {
//     $body = $message->getBody();
//     echo $body;
//     //...业务处理
//     // 确认消费并删除消息，如果不执行下面则此条消息会被标识为已经接收,但并不会删除
//     $obj->acknowledge($message);
//     //$obj->reject($message);
// });
