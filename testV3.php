<?php

#require("phar://iron_mq.phar");
require("../iron_core_php/IronCore.class.php");
require("IronMQ.class.php");

$ironmq = new IronMQ(array('host' => 'localhost', 'port' => 8080, 'protocol' => 'http'));
$ironmq->debug_enabled = true;
$ironmq->ssl_verifypeer = false;

$res = $ironmq->postMessage("test_queue", "Test Message 1");
var_dump($res);

$res = $ironmq->clearQueue("test_queue");
var_dump($res);

$ironmq->postMessage("test_queue", "Test Message 2");
$ironmq->postMessage("test_queue", "Test Message 3");
$res = $ironmq->getMessage("test_queue");
var_dump($res);
$res = $ironmq->getMessage("test_queue");
var_dump($res);

$ironmq->postMessage("test_queue", "Test Message 4");
$ironmq->postMessage("test_queue", "Test Message 5");
$ironmq->postMessage("test_queue", "Test Message 6");
$res = $ironmq->reserveMessages("test_queue", 2);
var_dump($res);

$ironmq->clearQueue("test_queue");

$ironmq->postMessage("test_queue", "Test Message 5");
$ironmq->postMessage("test_queue", "Test Message 6");
$res = $ironmq->peekMessages("test_queue", 2);
var_dump($res);
$res = $ironmq->peekMessages("test_queue", 2);
var_dump($res);

$ironmq->clearQueue("test_queue");

$ironmq->postMessage("test_queue", "Test Message 7");
$message = $ironmq->reserveMessage("test_queue");
var_dump($message);
$res = $ironmq->deleteMessage("test_queue", $message->id, $message->reservation_id);
var_dump($res);

$ironmq->clearQueue("test_queue");

$ironmq->postMessage("test_queue", "Test Message 0");
$message = $ironmq->peekMessage("test_queue");
var_dump($message);
$res = $ironmq->deleteMessage("test_queue", $message->id);
var_dump($res);

$ironmq->clearQueue("test_queue");

$ironmq->postMessage("test_queue", "Test Message 8");
$ironmq->postMessage("test_queue", "Test Message 9");
$messages = $ironmq->reserveMessages("test_queue", 2);
var_dump($messages);

$res = $ironmq->deleteMessages("test_queue", $messages);
# or
# m1 = array('id' => $messages[0]->id, 'reservation_id' => $messages[0]->reservation_id);
# m2 = array('id' => $messages[1]->id, 'reservation_id' => $messages[1]->reservation_id);
# $res = $ironmq->deleteMessages("test_queue", array(m1, m2));
# or
# for non-reserved messages
# $res = $ironmq->deleteMessages("test_queue", array($messages[0]->id, $messages[1]->id));
var_dump($res);

$ironmq->clearQueue("test_queue");

$id = $ironmq->postMessage("test_queue", "Test Message 0")->ids[0];
$message = $ironmq->getMessageById("test_queue", $id);
var_dump($message);

$queues = $ironmq->getQueues('n', 25);
var_dump($queues);

$ironmq->clearQueue("test_queue");

$ironmq->postMessage("test_queue", "Test Message 1");
$ironmq->postMessage("test_queue", "Test Message 2");
$message = $ironmq->reserveMessage("test_queue");
$res = $ironmq->touchMessage("test_queue", $message->id, $message->reservation_id);
var_dump($res);

$ironmq->clearQueue("test_queue");

$ironmq->postMessage("test_queue", "Test Message 1");
$ironmq->postMessage("test_queue", "Test Message 2");
$message = $ironmq->reserveMessage("test_queue");
$res = $ironmq->releaseMessage("test_queue", $message->id, 0, $message->reservation_id);
var_dump($res);

$ironmq->clearQueue("test_queue");

$res = $ironmq->getQueue("test_queue");
var_dump($res);

$ironmq->postMessage("test_queue_aler", "Test Message 1");
$res = $ironmq->getQueue("test_queue_aler");
var_dump($res);
$res = $ironmq->addAlerts("test_queue_aler", array(array('type' => 'progressive', 'direction' => 'asc', 'trigger' =>  200, 'queue' => 'ddd')));
var_dump($res);
$res = $ironmq->getQueue("test_queue_aler");
var_dump($res);

$ironmq->postMessage("test_queue_upd", "Test Message 2");
$res = $ironmq->getQueue("test_queue_upd");
$res = $ironmq->updateQueue("test_queue_upd", array('queue' => array('message_expiration' => 600777)));
var_dump($res);

$res = $ironmq->createQueue("test_queue_c", array('queue' => array('message_expiration' => 600333)));
var_dump($res);

