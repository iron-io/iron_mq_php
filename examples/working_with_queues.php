<?php
/**
 * PHP client for IronMQ
 * IronMQ is a scalable, reliable, high performance message queue in the cloud.
 *
 * @link https://github.com/iron-io/iron_mq_php
 * @link http://www.iron.io/products/mq
 * @link http://dev.iron.io/
 * @license BSD License
 * @copyright Feel free to copy, steal, take credit for, or whatever you feel like doing with this code. ;)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use IronMQ\IronMQ;

$ironMQ = new IronMQ();
//$ironMQ->debug_enabled = true;
$ironMQ->ssl_verifypeer = false;

// Get a list of queues that are currently in our project
$queues = $ironMQ->getQueues();
var_dump($queues);

// Create a new queue
$queueName = md5(rand() . time());
$result = $ironMQ->createQueue($queueName, array('message_expiration' => 3600));
var_dump($result);

// Clear a queue of all it's messages, but do not delete the queue
$result = $ironMQ->clearQueue($queueName);
var_dump($result);

$result = $ironMQ->updateQueue($queueName, array('message_expiration' => 3600 * 2));
var_dump($result);

// Delete a queue
$result = $ironMQ->deleteQueue($queueName);
var_dump($result);
