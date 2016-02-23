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

// Generating a random queue so we don't collide with any existing queues. Feel free to use a proper name
$queueName = md5(rand() . time());
$ironMQ->createQueue($queueName, array('message_expiration' => 3600));

// Post a message to a queue
$singleMessageResponse = $ironMQ->postMessage($queueName, 'This is a sample message');
var_dump($singleMessageResponse);
// Post multiple messages to a single queue
$multiMessageResponse = $ironMQ->postMessages($queueName, array('Message 1', 'Message 2'));
var_dump($multiMessageResponse);

// Delete these messages one at a time
$ironMQ->deleteMessage($queueName, $singleMessageResponse->id);
foreach ($multiMessageResponse->ids as $messageID) {
    $ironMQ->deleteMessage($queueName, $messageID);
}

// Put a new message in the queue for us to work with
$ironMQ->postMessage($queueName, 'This is a sample message');

// Retrieve a message from a queue
$message = $ironMQ->reserveMessage($queueName);
var_dump($message);

// Touch a message to keep it around longer, in case you are nearing the timeout
echo 'Touching a message';
$reservationID = $ironMQ->touchMessage($queueName, $message->id, $message->reservation_id, IronMQ::GET_MESSAGE_TIMEOUT);
var_dump($reservationID);
// The reservation ID changes after touching, so make sure to update the message
$message->reservation_id = $reservationID->reservation_id;

// Delete a message when you are finished. Reserved messages MUST pass the reservation ID
$deleteResult = $ironMQ->deleteMessage($queueName, $message->id, $message->reservation_id);
var_dump($deleteResult);

// Put some new messages in the queue for us to work with
$ironMQ->postMessage($queueName, 'This is a sample message');
$ironMQ->postMessage($queueName, 'This is a sample message2');
$ironMQ->postMessage($queueName, 'This is a sample message3');
$ironMQ->postMessage($queueName, 'This is a sample message4');

$messages = $ironMQ->reserveMessages($queueName, 4);
var_dump($messages);

// Delete all of the messages. You can also delete messages individually if you'd like
$ironMQ->deleteMessages($queueName, $messages);

// Put some new messages in the queue for us to work with
$ironMQ->postMessage($queueName, 'This is a sample message');
$ironMQ->postMessage($queueName, 'This is a sample message2');

// See what messages are there without reserving them
$peekResult = $ironMQ->peekMessage($queueName);
var_dump($peekResult);
$peekResult = $ironMQ->peekMessages($queueName, 2);
var_dump($peekResult);

// Delete the sample queue we created
$ironMQ->deleteQueue($queueName);
