<?php

class Http_Exception extends Exception{
    const NOT_MODIFIED = 304;
    const BAD_REQUEST = 400;
    const NOT_FOUND = 404;
    const NOT_ALOWED = 405;
    const CONFLICT = 409;
    const PRECONDITION_FAILED = 412;
    const INTERNAL_ERROR = 500;
}
class IronMQ_Exception extends Exception{

}

class IronMQ_Message {
    private $body;
    private $timeout;
    private $delay;
    private $expires_in;

    const max_expires_in = 2592000;

    /**
     * @param string $body
     *        The message data, as a string.
     * @param int $timeout
     *        Optional. Timeout, in seconds. After timeout, item will be placed back on queue. Defaults to 60.
     * @param int $delay
     *        Optional. The item will not be available on the queue until this many seconds have passed. Defaults to 0.
     * @param int $expires_in
     *        Optional. How long, in seconds, to keep the item on the queue before it is deleted. Defaults to 604800 (7 days). Maximum is 2592000 (30 days).
     */
    function __construct($body, $timeout = null, $delay = null, $expires_in = null) {
        $this->setBody($body);
        $this->setTimeout($timeout);
        $this->setDelay($delay);
        $this->setExpiresIn($expires_in);
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
        if(!empty($this->timeout) || $this->timeout == 0) {# 0 is considered empty, but we want people to be able to set a timeout of 0
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
        if($expires_in > max_expires_in) {
            throw new InvalidArgumentException("Expires In can't be greater than ".max_expires_in.".");
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

class IronMQ{

    //Header Constants
    const header_user_agent = "IronMQ PHP v0.1";
    const header_accept = "application/json";
    const header_accept_encoding = "gzip, deflate";
    const HTTP_OK = 200;
    const HTTP_CREATED = 201;
    const HTTP_ACEPTED = 202;

    const POST   = 'POST';
    const GET    = 'GET';
    const DELETE = 'DELETE';

    public  $debug_enabled = false;

    private $required_config_fields = array('token','protocol','host','port','api_version');

    private $url;
    private $token;
    private $api_version;
    private $version;
    private $project_id;

    /**
     * @param string|array $config_file_or_options
     *        Array of options or name of config file.
     * Fields in options array or in config:
     * Required:
     * - token
     * - protocol
     * - host
     * - port
     * - api_version
     * Optional:
     * - default_project_id
     */
    function __construct($config_file_or_options){
        $config = $this->getConfigData($config_file_or_options);
        $token              = $config['token'];
        $protocol           = $config['protocol'];
        $host               = $config['host'];
        $port               = $config['port'];
        $api_version        = $config['api_version'];
        $default_project_id = empty($config['default_project_id'])?'':$config['default_project_id'];

        $this->url          = "$protocol://$host:$port/$api_version/";
        $this->token        = $token;
        $this->api_version  = $api_version;
        $this->version      = $api_version;
        $this->project_id   = $default_project_id;
    }

    public function setProjectId($project_id) {
        if (!empty($project_id)){
          $this->project_id = $project_id;
        }
        if (empty($this->project_id)){
            throw new InvalidArgumentException("Please set project_id");
        }
    }

    public function getProjects(){
        $this->setJsonHeaders();
        $projects = json_decode($this->apiCall(self::GET, 'projects'));
        return $projects->projects;
    }

    public function getProjectDetails($project_id = ''){
        $this->setProjectId($project_id);
        $this->setJsonHeaders();
        $url =  "projects/{$this->project_id}";
        return json_decode($this->apiCall(self::GET, $url));
    }

    public function postProject($name){
        $request = array(
            'name' => $name
        );

        $this->setCommonHeaders();
        $res = $this->apiCall(self::POST, 'projects', $request);
        $responce = json_decode($res);
        return $responce->id;
    }

    public function deleteProject($project_id){
        $this->setProjectId($project_id);
        $url = "projects/{$this->project_id}";
        return $this->apiCall(self::DELETE, $url);
    }

    public function getQueues($project_id = '', $page = 0){
        $this->setProjectId($project_id);
        $url = "projects/{$this->project_id}/queues";
        if($page > 0) {
            $url .= "?page=".$page;
        }
        $this->setJsonHeaders();
        $queues = json_decode($this->apiCall(self::GET, $url));
        return $queues;
    }

    public function getQueue($project_id = '', $queue_name) {
        $this->setProjectId($project_id);
        $url = "projects/{$this->project_id}/queues/{$queue_name}";
        $this->setJsonHeaders();
        $queue = json_decode($this->apiCall(self::GET, $url));
        return $queue;
    }

    public function postMessage($project_id = '', $queue_name, $message) {
        $msg = new IronMQ_Message($message['body'], $message['timeout'], $message['delay'], $message['expires_in']);
        $req = array(
            "messages" => array($msg->asArray())
        );
        $this->setProjectId($project_id);
        $this->setCommonHeaders();
        $url = "projects/{$this->project_id}/queues/{$queue_name}/messages";
        $res = $this->apiCall(self::POST, $url, $req);
        $response = json_decode($res);
        return $response;
    }

    public function postMessages($project_id = '', $queue_name, $messages) {
        $req = array(
            "messages" => array()
        );
        foreach($messages as $message) {
            $msg = new IronMQ_Message($message['body'], $message['timeout'], $message['delay'], $message['expires_in']);
            array_push($req['messages'], $msg->asArray());
        }
        $this->setProjectId($project_id);
        $this->setCommonHeaders();
        $url = "projects/{$this->project_id}/queues/{$queue_name}/messages";
        $res = $this->apiCall(self::POST, $url, $req);
        $response = json_decode($res);
        return $response;# TODO: double-check that the API only returns the last message ID
    }

    public function getMessages($project_id = '', $queue_name, $count=1) {
        $this->setProjectId($project_id);
        $url = "projects/{$this->project_id}/queues/{$queue_name}/messages";
        if($count > 1) {
            $url .= "?count=".$count;
        }
        $this->setJsonHeaders();
        $response = $this->apiCall(self::GET, $url);
        $this->debug("Raw Response", $response);
        $messages = json_decode($response);
        return messages;
    }

    public function getMessage($project_id = '', $queue_name) {
        return $this->getMessages($project_id, $queue_name, 1);
    }

    public function deleteMessage($project_id = '', $queue_name, $message_id) {
        $this->setProjectId($project_id);
        $this->setCommonHeaders();
        $url = "projects/{$this->project_id}/queues/{$queue_name}/messages/{$message_id}";
        return $this->apiCall(self::DELETE, $url);
    }

    public function deleteTask($project_id, $task_id){
        $this->setProjectId($project_id);
        $this->setCommonHeaders();
        $this->headers['Accept'] = "text/plain";
        unset($this->headers['Content-Type']);
        $url = "projects/{$this->project_id}/tasks/$task_id";
        return $this->apiCall(self::DELETE, $url);
    }

    /* PRIVATE FUNCTIONS */

    /**
     *
     * @param string $project_id
     * @param string $name
     * @param array $options options contain:
     *   start_at OR delay — required - start_at is time of first run. Delay is number of seconds to wait before starting.
     *   run_every         — optional - Time in seconds between runs. If omitted, task will only run once.
     *   end_at            — optional - Time tasks will stop being enqueued. (Should be a Time or DateTime object.)
     *   run_times         — optional - Number of times to run task. For example, if run_times: is 5, the task will run 5 times.
     *   priority          — optional - Priority queue to run the job in (0, 1, 2). p0 is default. Run at higher priorities to reduce time jobs may spend in the queue once they come off schedule. Same as priority when queuing up a task.
     * @param array $payload
     * @return mixed
     */
    private function postSchedule($project_id, $name, $options, $payload = array()){

        $this->setProjectId($project_id);
        $url = "projects/{$this->project_id}/schedules";

        $shedule = array(
           'name' => $name,
           'code_name' => $name,
           'payload' => json_encode($payload),
        );
        $request = array(
           'schedules' => array(
               array_merge($shedule, $options)
           )
        );

        $this->setCommonHeaders();
        $res = $this->apiCall(self::POST, $url, $request);
        $shedules = json_decode($res);
        return $shedules->schedules[0]->id;
    }

    private function compiledHeaders(){

        # Set default headers if no headers set.
        if ($this->headers == null){
            $this->setCommonHeaders();
        }

        $headers = array();
        foreach ($this->headers as $k => $v){
            $headers[] = "$k: $v";
        }
        return $headers;
    }

    private function apiCall($type, $url, $params = array()){
        $url = "{$this->url}$url";

        $s = curl_init();
        if (! isset($params['oauth'])) {
          $params['oauth'] = $this->token;
        }
        switch ($type) {
            case self::DELETE:
                $fullUrl = $url . '?' . http_build_query($params);
                $this->debug('apiCall fullUrl', $fullUrl);
                curl_setopt($s, CURLOPT_URL, $fullUrl);
                curl_setopt($s, CURLOPT_CUSTOMREQUEST, self::DELETE);
                break;
            case self::POST:
                $this->debug('apiCall url', $url);
                curl_setopt($s, CURLOPT_URL,  $url);
                curl_setopt($s, CURLOPT_POST, true);
                curl_setopt($s, CURLOPT_POSTFIELDS, json_encode($params));
                break;
            case self::GET:
                $fullUrl = $url . '?' . http_build_query($params);
                $this->debug('apiCall fullUrl', $fullUrl);
                curl_setopt($s, CURLOPT_URL, $fullUrl);
                break;
        }

        curl_setopt($s, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($s, CURLOPT_HTTPHEADER, $this->compiledHeaders());
        $_out = curl_exec($s);
        $status = curl_getinfo($s, CURLINFO_HTTP_CODE);
        curl_close($s);
        switch ($status) {
            case self::HTTP_OK:
            case self::HTTP_CREATED:
            case self::HTTP_ACEPTED:
                $out = $_out;
                break;
            default:
                throw new Http_Exception("http error: {$status} | {$_out}", $status);
        }
        return $out;
    }


    /**
     * @param array|string $config_file_or_options
     * array of options or name of config file
     * @return array
     * @throws InvalidArgumentException
     */
    private function getConfigData($config_file_or_options){
        if (is_string($config_file_or_options)){
            $ini = parse_ini_file($config_file_or_options, true);
            if ($ini === false){
                throw new InvalidArgumentException("Config file $config_file_or_options not found");
            }
            if (empty($ini['iron_mq'])){
                throw new InvalidArgumentException("Config file $config_file_or_options has no section 'iron_mq'");
            }
            $config =  $ini['iron_mq'];
        }elseif(is_array($config_file_or_options)){
            $config = $config_file_or_options;
        }else{
            throw new InvalidArgumentException("Wrong parameter type");
        }
        foreach ($this->required_config_fields as $field){
            if (empty($config[$field])){
                throw new InvalidArgumentException("Required config key missing: '$field'");
            }
        }
        return $config;
    }

    private function setCommonHeaders(){
        $this->headers = array(
            'Authorization'   => "OAuth {$this->token}",
            'User-Agent'      => self::header_user_agent,
            'Content-Type'    => 'application/json',
            'Accept'          => self::header_accept,
            'Accept-Encoding' => self::header_accept_encoding
        );
    }

    private function setJsonHeaders(){
        $this->setCommonHeaders();
    }

    private function setPostHeaders(){
        $this->setCommonHeaders();
        $this->headers['Content-Type'] ='multipart/form-data';
    }

    private function debug($var_name, $variable){
        if ($this->debug_enabled){
            echo "{$var_name}: ".var_export($variable,true)."\n";
        }
    }
}
