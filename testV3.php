<?php

#require("phar://iron_mq.phar");
require("../iron_core_php/IronCore.class.php");
require("IronMQ.class.php");

$ironmq = new IronMQ();
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

#for ($i = 0; $i < 10; $i++) {
#    echo "Post message:\n";
#    $res = $ironmq->postMessage("test_queue", "Test Message $i");
#    var_dump($res);
#
#    echo "Post messages:\n";
#    $res = $ironmq->postMessages("test-queue-multi", array("Test Message $i", "Test Message $i-2"));
#    var_dump($res);
#
#    echo "Get message..\n";
#    $message = $ironmq->getMessage("test_queue");
#    print_r($message);
#
#    echo "Touch message..\n";
#    $res = $ironmq->touchMessage("test_queue", $message->id);
#    print_r($res);
#
#    echo "Release message..\n";
#    $res = $ironmq->releaseMessage("test_queue", $message->id);
#    print_r($res);
#
#    echo "Peek message..\n";
#    $res = $ironmq->peekMessage("test_queue");
#    print_r($res);
#
#    echo "Delete message..\n";
#    $message = $ironmq->deleteMessage("test_queue", $message->id);
#    print_r($message);
#
#    $message = $ironmq->getMessage("test_queue");
#    print_r($message);
#
#    echo "Getting multiple messages..\n";
#    $messageIds = array();
#    $messages = $ironmq->getMessages("test-queue-multi", 2);
#    foreach($messages as $message){
#        array_push($messageIds, $message->id);
#    }
#    echo "Deleting messages with ids..\n";
#    print_r($messageIds);
#    $res = $ironmq->deleteMessages("test-queue-multi", $messageIds);
#    print_r($res);
#
#    echo "Adding alerts..\n";
#    $res = $ironmq->postMessage("test_alert_queue", "Test Message 1");
#    $first_alert = array(
#        'type' => 'fixed',
#        'direction' => 'desc',
#        'trigger' => 1001,
#        'snooze' => 10,
#        'queue' => 'test_alert_queue');
#    $second_alert = array(
#        'type' => 'fixed',
#        'direction' => 'asc',
#        'trigger' => 1000,
#        'snooze' => 5,
#        'queue' => 'test_alert_queue',);
#
#    $res = $ironmq->addAlerts("test_alert_queue", array($first_alert, $second_alert));
#    print_r($res);
#
#    echo "Deleting alerts with ids..\n";
#    $message = $ironmq->getQueue("test_alert_queue");
#    $alert_ids = array();
#    $alerts = $message-> alerts;
#    foreach($alerts as $alert) {
#        array_push($alert_ids, array('id'=>$alert->id));
#    }
#    print_r($alert_ids);
#    $res = $ironmq->deleteAlerts("test_alert_queue", $alert_ids);
#    print_r($res);
#
#    echo "\n------$i-------\n";
#}
#
#
#echo "\n done";
