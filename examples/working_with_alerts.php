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

/**
 * For more information about alerts, please visit http://dev.iron.io/mq/reference/queue_alerts/
 */

require_once __DIR__ . '/../vendor/autoload.php';

use IronMQ\IronMQ;

$ironMQ = new IronMQ();
//$ironMQ->debug_enabled = true;
$ironMQ->ssl_verifypeer = false;

// Generating a random queue so we don't collide with any existing queues. Feel free to use a proper name
$queueName = md5(rand() . time());
$ironMQ->createQueue($queueName, array('message_expiration' => 3600));

// Generate a queue to hold the alerts that will be generated
$alertQueueName = $queueName . '_alerts';
$ironMQ->createQueue($alertQueueName, array('message_expiration' => 3600));

// Every 200 messages, generate an alert
$alert = array(
    'type'      => 'progressive',
    'direction' => 'asc',
    'trigger'   => 200,
    'queue'     => $alertQueueName,
);
$addResponse = $ironMQ->addAlerts($queueName, array($alert));
var_dump($addResponse);

// Our Alert queue should be empty
$messages = $ironMQ->peekMessage($alertQueueName);
var_dump($messages);

// Let's generate a bunch of messages to trigger an alert
for ($i = 0; $i < 250; $i++) {
    $ironMQ->postMessage($queueName, 'Test Message' . $i);
}

// We should now have one
$messages = $ironMQ->peekMessage($alertQueueName);
var_dump($messages);

// To get the existing alerts on a queue, use getQueue()
$queueData = $ironMQ->getQueue($queueName);
$alerts = $queueData->alerts;

// Update the alerts on a queue
$alerts[0]->trigger = 500;
$updateResponse = $ironMQ->updateAlerts($queueName, $alerts);
var_dump($updateResponse);

// Clean up our tests
$ironMQ->deleteQueue($queueName);
$ironMQ->deleteQueue($alertQueueName);
