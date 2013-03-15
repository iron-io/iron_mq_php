<?php
/**
 * PHP client for IronMQ
 * IronMQ is a scalable, reliable, high performance message queue in the cloud.
 *
 * @link https://github.com/iron-io/iron_mq_php
 * @link http://www.iron.io/products/mq
 * @link http://dev.iron.io/
 * @version 1.4.5
 * @package IronMQPHP
 * @copyright Feel free to copy, steal, take credit for, or whatever you feel like doing with this code. ;)
 */


class IronMQ_Exception extends Exception{

}


class IronMQ_Message {
    private $body;
    private $timeout;
    private $delay;
    private $expires_in;

    const max_expires_in = 2592000;

    /**
     * Create a new message.
     *
     * @param string $message
     *        A message body
     * @param array $properties
     *        An array of message properties
     * Fields in $properties array:
     * - timeout: Timeout, in seconds. After timeout, item will be placed back on queue. Defaults to 60.
     * - delay: The item will not be available on the queue until this many seconds have passed. Defaults to 0.
     * - expires_in: How long, in seconds, to keep the item on the queue before it is deleted. Defaults to 604800 (7 days). Maximum is 2592000 (30 days).
     */
    function __construct($message, $properties = array()) {
        $this->setBody($message);

        if(array_key_exists("timeout", $properties)) {
            $this->setTimeout($properties['timeout']);
        }
        if(array_key_exists("delay", $properties)) {
            $this->setDelay($properties['delay']);
        }
        if(array_key_exists("expires_in", $properties)) {
            $this->setExpiresIn($properties['expires_in']);
        }
    }

    public function getBody() {
        return $this->body;
    }

    public function setBody($body) {
        if(empty($body)) {
            throw new InvalidArgumentException("Please specify a body");
        } else {
            $this->body = (string) $body;
        }
    }

    public function getTimeout() {
        if(!empty($this->timeout) || $this->timeout === 0) {# 0 is considered empty, but we want people to be able to set a timeout of 0
            return $this->timeout;
        } else {
            return null;
        }
    }

    public function setTimeout($timeout) {
        $this->timeout = $timeout;
    }

    public function getDelay() {
        if(!empty($this->delay) || $this->delay == 0) {# 0 is considered empty, but we want people to be able to set a delay of 0
            return $this->delay;
        } else {
            return null;
        }
    }

    public function setDelay($delay) {
        $this->delay = $delay;
    }

    public function getExpiresIn() {
        return $this->expires_in;
    }

    public function setExpiresIn($expires_in) {
        if($expires_in > self::max_expires_in) {
            throw new InvalidArgumentException("Expires In can't be greater than ".self::max_expires_in.".");
        } else {
            $this->expires_in = $expires_in;
        }
    }

    public function asArray() {
        $array = array();
        $array['body'] = $this->getBody();
        if($this->getTimeout() != null) {
            $array['timeout'] = $this->getTimeout();
        }
        if($this->getDelay() != null) {
            $array['delay'] = $this->getDelay();
        }
        if($this->getExpiresIn() != null) {
            $array['expires_in'] = $this->getExpiresIn();
        }
        return $array;
    }
}

class IronMQ extends IronCore {

    protected $client_version = '1.4.5';
    protected $client_name    = 'iron_mq_php';
    protected $product_name   = 'iron_mq';
    protected $default_values = array(
        'protocol'    => 'https',
        'host'        => 'mq-aws-us-east-1.iron.io',
        'port'        => '443',
        'api_version' => '1',
    );

    const LIST_QUEUES_PER_PAGE = 30;
    const GET_MESSAGE_TIMEOUT = 60;

    /**
     * @param string|array $config_file_or_options
     *        Array of options or name of config file.
     * Fields in options array or in config:
     *
     * Required:
     * - token
     * - project_id
     * Optional:
     * - protocol
     * - host
     * - port
     * - api_version
     */
    function __construct($config_file_or_options = null){
        $this->getConfigData($config_file_or_options);
        $this->url = "{$this->protocol}://{$this->host}:{$this->port}/{$this->api_version}/";
    }

    /**
     * Switch active project
     *
     * @param string $project_id Project ID
     * @throws InvalidArgumentException
     */
    public function setProjectId($project_id) {
        if (!empty($project_id)){
          $this->project_id = $project_id;
        }
        if (empty($this->project_id)){
            throw new InvalidArgumentException("Please set project_id");
        }
    }

    /**
     * Get list of message queues
     *
     * @param int $page
     *        Zero-indexed page to view
     * @param int $per_page
     *        Number of queues per page
     */
    public function getQueues($page = 0, $per_page = self::LIST_QUEUES_PER_PAGE) {
        $url = "projects/{$this->project_id}/queues";
        $params = array();
        if($page !== 0) {
            $params['page'] = (int) $page;
        }
        if($per_page !== self::LIST_QUEUES_PER_PAGE) {
            $params['per_page'] = (int) $per_page;
        }
        $this->setJsonHeaders();
        return self::json_decode($this->apiCall(self::GET, $url, $params));
    }

    /**
     * Get information about queue.
     * Also returns queue size.
     *
     * @param string $queue_name
     * @return mixed
     */
    public function getQueue($queue_name) {
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue";
        $this->setJsonHeaders();
        return self::json_decode($this->apiCall(self::GET, $url));
    }

    /**
     * Clear all messages from queue.
     *
     * @param string $queue_name
     * @return mixed
     */
    public function clearQueue($queue_name) {
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue/clear";
        $this->setJsonHeaders();
        return self::json_decode($this->apiCall(self::POST, $url));
    }

    /**
     * Push a message on the queue
     *
     * Examples:
     * <code>
     * $ironmq->postMessage("test_queue", "Hello world");
     * </code>
     * <code>
     * $ironmq->postMessage("test_queue", "Test Message", array(
     *   'timeout' => 120,
     *   'delay' => 2,
     *   'expires_in' => 2*24*3600 # 2 days
     * ));
     * </code>
     *
     * @param string $queue_name Name of the queue.
     * @param string $message
     * @param array $properties
     * @return mixed
     */
    public function postMessage($queue_name, $message, $properties = array()) {
        $msg = new IronMQ_Message($message, $properties);
        $req = array(
            "messages" => array($msg->asArray())
        );
        $this->setCommonHeaders();
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue/messages";
        $res = $this->apiCall(self::POST, $url, $req);
        $decoded = self::json_decode($res);
        $decoded->id = $decoded->ids[0];
        return $decoded;
    }

    /**
     * Push multiple messages on the queue
     *
     * Example:
     * <code>
     * $ironmq->postMessages("test_queue", array("Lorem", "Ipsum"), array(
     *   'timeout' => 120,
     *   'delay' => 2,
     *   'expires_in' => 2*24*3600 # 2 days
     * ));
     * </code>
     *
     * @param string $queue_name Name of the queue.
     * @param array $messages array of messages, each message same as for postMessage() method
     * @param array $properties array of message properties, applied to each message in $messages
     * @return mixed
     */
    public function postMessages($queue_name, $messages, $properties = array()) {
        $req = array(
            "messages" => array()
        );
        foreach($messages as $message) {
            $msg = new IronMQ_Message($message, $properties);
            array_push($req['messages'], $msg->asArray());
        }
        $this->setCommonHeaders();
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue/messages";
        $res = $this->apiCall(self::POST, $url, $req);
        return self::json_decode($res);
    }

    /**
     * Get multiplie messages from queue
     *
     * @param string $queue_name Queue name
     * @param int $count
     * @param int $timeout
     * @return array|null array of messages or null
     */
    public function getMessages($queue_name, $count = 1, $timeout = self::GET_MESSAGE_TIMEOUT) {
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue/messages";
        $params = array();
        if($count !== 1) {
            $params['n'] = (int) $count;
        }
        if($timeout !== self::GET_MESSAGE_TIMEOUT) {
            $params['timeout'] = (int) $timeout;
        }
        $this->setJsonHeaders();
        $response = $this->apiCall(self::GET, $url, $params);
        $result = self::json_decode($response);
        if(count($result->messages) < 1) {
            return null;
        } else {
            return $result->messages;
        }
    }

    /**
     * Get single message from queue
     *
     * @param string $queue_name Queue name
     * @param int $timeout
     * @return mixed|null single message or null
     */
    public function getMessage($queue_name, $timeout = self::GET_MESSAGE_TIMEOUT) {
        $messages = $this->getMessages($queue_name, 1, $timeout);
        if ($messages){
            return $messages[0];
        }else{
            return null;
        }
    }

    /**
     * Delete a Message from a Queue
     * This call will delete the message. Be sure you call this after you’re done with a message or it will be placed back on the queue.
     *
     * @param $queue_name
     * @param $message_id
     * @return mixed
     */
    public function deleteMessage($queue_name, $message_id) {
        $this->setCommonHeaders();
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue/messages/{$message_id}";
        return $this->apiCall(self::DELETE, $url);
    }

    /**
     * Peek Messages on a Queue
     * Peeking at a queue returns the next messages on the queue, but it does not reserve them.
     *
     * @param string $queue_name
     * @return object|null  message or null if queue is empty
     */
    public function peekMessage($queue_name) {
        $messages = $this->peekMessages($queue_name, 1);
        if ($messages == null) {
            return null;
        } else {
            return $messages[0];
        }
    }

    /**
     * Peek Messages on a Queue
     * Peeking at a queue returns the next messages on the queue, but it does not reserve them.
     *
     * @param string $queue_name
     * @param int $count The maximum number of messages to peek. Maximum is 100.
     * @return array|null array of messages or null if queue is empty
     */
    public function peekMessages($queue_name, $count) {
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue/messages/peek";
        $params = array();
        if($count !== 1) {
            $params['n'] = (int) $count;
        }
        $this->setJsonHeaders();
        $response = self::json_decode($this->apiCall(self::GET, $url, $params));
        return $response->messages;
    }

    /**
     * Touch a Message on a Queue
     * Touching a reserved message extends its timeout by the duration specified when the message was created, which is 60 seconds by default.
     *
     * @param string $queue_name
     * @param string $message_id
     * @return mixed
     */
    public function touchMessage($queue_name, $message_id) {
        $this->setJsonHeaders();
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue/messages/{$message_id}/touch";
        return self::json_decode($this->apiCall(self::POST, $url));
    }

    /**
     * Release a Message on a Queue
     * Releasing a reserved message unreserves the message and puts it back on the queue as if the message had timed out.
     *
     * @param string $queue_name
     * @param string $message_id
     * @param int $delay The item will not be available on the queue until this many seconds have passed. Default is 0 seconds. Maximum is 604,800 seconds (7 days).
     * @return mixed
     */
    public function releaseMessage($queue_name, $message_id, $delay = 0) {
        $this->setJsonHeaders();
        $queue = rawurlencode($queue_name);
        $params = array();
        if ($delay !== 0) {
            $params['delay'] = (int) $delay;
        }
        $url = "projects/{$this->project_id}/queues/$queue/messages/{$message_id}/release";
        return self::json_decode($this->apiCall(self::POST, $url, $params));
    }

    /**
     * Delete a Message Queue
     * This call deletes a message queue and all its messages.
     *
     * @param string $queue_name
     * @return mixed
     */
    public function deleteQueue($queue_name) {
        $this->setJsonHeaders();
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue";
        return self::json_decode($this->apiCall(self::DELETE, $url));
    }

    /**
     * Updates the queue object
     *
     * @param string $queue_name
     * @param array $options Parameters to change. keys:
     * - "subscribers" url's to subscribe to
     * - "push_type" multicast (default) or unicast.
     * - "retries" Number of retries. 3 by default
     * - "retries_delay" Delay between retries. 60 (seconds) by default
     */
    public function updateQueue($queue_name, $options) {
        $this->setJsonHeaders();
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue";
        return self::json_decode($this->apiCall(self::POST, $url, $options));
    }

    /**
     * Add Subscriber to a Queue
     *
     * Example:
     * <code>
     * $ironmq->addSubscriber("test_queue", array("url" => "http://example.com"));
     * </code>
     *
     * @param string $queue_name
     * @param array $subscriber_hash Subscriber. keys:
     * - "url" Subscriber url
     * @return mixed
     */
    public function addSubscriber($queue_name, $subscriber_hash) {
        $this->setJsonHeaders();
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue/subscribers";
        $options = array(
            'subscribers' => array($subscriber_hash)
        );
        return self::json_decode($this->apiCall(self::POST, $url, $options));
    }

    /**
     * Remove Subscriber from a Queue
     *
     * Example:
     * <code>
     * $ironmq->removeSubscriber("test_queue", array("url" => "http://example.com"));
     * </code>
     *
     * @param string $queue_name
     * @param array $subscriber_hash Subscriber. keys:
     * - "url" Subscriber url
     * @return mixed
     */
    public function removeSubscriber($queue_name, $subscriber_hash) {
        $this->setJsonHeaders();
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue/subscribers";
        $options = array(
            'subscribers' => array($subscriber_hash)
        );
        return self::json_decode($this->apiCall(self::DELETE, $url, $options));
    }

    /**
     * Get Message's Push Statuses (for Push Queues only)
     *
     * Example:
     * <code>
     * statuses = $ironmq->getMessagePushStatuses("test_queue", $message_id)
     * </code>
     *
     * @param string $queue_name
     * @param string $message_id
     * @return array
     */
    public function getMessagePushStatuses($queue_name, $message_id) {
        $this->setJsonHeaders();
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue/messages/{$message_id}/subscribers";
        $response = self::json_decode($this->apiCall(self::GET, $url));
        return $response->subscribers;
    }

    /**
     * Delete Message's Push Status (for Push Queues only)
     *
     * Example:
     * <code>
     * $ironmq->deleteMessagePushStatus("test_queue", $message_id, $subscription_id)
     * </code>
     *
     * @param string $queue_name
     * @param string $message_id
     * @param string $subscription_id
     * @return mixed
     */
    public function deleteMessagePushStatus($queue_name, $message_id, $subscription_id) {
        $this->setJsonHeaders();
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue/messages/{$message_id}/subscribers/{$subscription_id}";
        return self::json_decode($this->apiCall(self::DELETE, $url));
    }


    /* PRIVATE FUNCTIONS */

    private function setJsonHeaders(){
        $this->setCommonHeaders();
    }

    private function setPostHeaders(){
        $this->setCommonHeaders();
        $this->headers['Content-Type'] ='multipart/form-data';
    }

}
