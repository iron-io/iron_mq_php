<?php
/**
 * PHP client for IronMQ
 * IronMQ is a scalable, reliable, high performance message queue in the cloud.
 *
 * @link https://github.com/iron-io/iron_mq_php
 * @link http://www.iron.io/products/mq
 * @link http://dev.iron.io/
 * @version 1.1.1
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
     * @param array|string $message
     *        An array of message properties or a string of the message body.
     * Fields in message array:
     * Required:
     * - body: The message data, as a string.
     * Optional:
     * - timeout: Timeout, in seconds. After timeout, item will be placed back on queue. Defaults to 60.
     * - delay: The item will not be available on the queue until this many seconds have passed. Defaults to 0.
     * - expires_in: How long, in seconds, to keep the item on the queue before it is deleted. Defaults to 604800 (7 days). Maximum is 2592000 (30 days).
     */
    function __construct($message) {
        if(is_string($message)) {
            $this->setBody($message);
        } elseif(is_array($message)) {
            $this->setBody($message['body']);
            if(array_key_exists("timeout", $message)) {
                $this->setTimeout($message['timeout']);
            }
            if(array_key_exists("delay", $message)) {
                $this->setDelay($message['delay']);
            }
            if(array_key_exists("expires_in", $message)) {
                $this->setExpiresIn($message['expires_in']);
            }
        }
    }

    public function getBody() {
        return $this->body;
    }

    public function setBody($body) {
        if(empty($body)) {
            throw new InvalidArgumentException("Please specify a body");
        } else {
            $this->body = $body;
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

class IronMQ extends IronCore{

    protected $client_version = '1.1.1';
    protected $client_name    = 'iron_mq_php';
    protected $product_name   = 'iron_mq';
    protected $default_values = array(
        'protocol'    => 'https',
        'host'        => 'mq-aws-us-east-1.iron.io',
        'port'        => '443',
        'api_version' => '1',
    );

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

    public function getQueues($page = 0){
        $url = "projects/{$this->project_id}/queues";
        $params = array();
        if($page > 0) {
            $params['page'] = $page;
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
     * Push a message on the queue
     *
     * Examples:
     * <code>
     * $ironmq->postMessage("test_queue", "Hello world");
     * </code>
     * <code>
     * $ironmq->postMessage("test_queue", array(
     *   "body" => "Test Message"
     *   "timeout" => 120,
     *   'delay' => 2,
     *   'expires_in' => 2*24*3600 # 2 days
     * ));
     * </code>
     *
     * @param string $queue_name Name of the queue.
     * @param array|string $message
     * @return mixed
     */
    public function postMessage($queue_name, $message) {
        $msg = new IronMQ_Message($message);
        $req = array(
            "messages" => array($msg->asArray())
        );
        $this->setCommonHeaders();
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue/messages";
        $res = $this->apiCall(self::POST, $url, $req);
        return self::json_decode($res);
    }

    /**
     * Push multiple messages on the queue
     *
     * @param string $queue_name Name of the queue.
     * @param array $messages array of messages, each message same as for postMessage() method
     * @return mixed
     */
    public function postMessages($queue_name, $messages) {
        $req = array(
            "messages" => array()
        );
        foreach($messages as $message) {
            $msg = new IronMQ_Message($message);
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
     * @return array|null array of messages or null
     */
    public function getMessages($queue_name, $count=1) {
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue/messages";
        $params = array();
        if($count > 1) {
            $params['n'] = $count;
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
     * @return mixed|null single message or null
     */
    public function getMessage($queue_name) {
        $messages = $this->getMessages($queue_name, 1);
        if ($messages){
            return $messages[0];
        }else{
            return null;
        }
    }

    public function deleteMessage($queue_name, $message_id) {
        $this->setCommonHeaders();
        $queue = rawurlencode($queue_name);
        $url = "projects/{$this->project_id}/queues/$queue/messages/{$message_id}";
        return $this->apiCall(self::DELETE, $url);
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
