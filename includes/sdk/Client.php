<?php

/**
 * Class API
 * @package Yopify\WooCommerce
 */
class Yopify_Yo_Client
{
    /**
     * @var string SDK version
     */
    private $version = '1.0.0';

    /**
     * @var string Yo Auth token
     */
    public $authToken;

    /**
     * @var $basicAuthUsername
     */
    private $basicAuthUsername;

    /**
     * @var $basicAuthPassword
     */
    private $basicAuthPassword;

    /**
     * @var int Yo App id
     */
    public $appId = 0;

    /**
     * @var string Yo server endpoint
     */
    private $endpoint = YOPIFY_YO_API_BASE_URL;

    /**
     * @var array
     */
    public $defaults = [
        'CHARSET'     => 'UTF-8',
        'METHOD'      => 'GET',
        'URL'         => '/',
        'HEADERS'     => array(),
        'DATA'        => array(),
        'FAILONERROR' => false,
        'RETURNARRAY' => false,
        'ALLDATA'     => true
    ];

    /**
     * Checks for presence of setup $data array and loads
     *
     * @param bool $data
     */
    public function __construct()
    {

    }

    /**
     * Set basic auth
     *
     * @param $username
     * @param $password
     */
    public function setBasicAuth($username, $password)
    {
        $this->basicAuthUsername = $username;
        $this->basicAuthPassword = $password;
    }


    /**
     * Executes the actual cURL call based on $userData
     *
     * @param array $userData
     *
     * @return mixed
     * @throws \Exception
     */
    public function call($userData = array())
    {
        if (is_string($userData)) {
            $userData = ['URL' => $userData];
        }

        $request = array_merge($this->defaults, $userData);


        // Send & accept JSON data
        $defaultHeaders = array();
        $defaultHeaders[] = 'Content-Type: application/json; charset=' . $request['CHARSET'];
        $defaultHeaders[] = 'Accept: application/json';

        if ($this->authToken) {
            $defaultHeaders[] = 'Authorization: ' . $this->authToken;
        }else {
            if ($this->basicAuthUsername && $this->basicAuthPassword) {
                $defaultHeaders[] = 'Authorization: Basic ' . base64_encode($this->basicAuthUsername . ':' . $this->basicAuthPassword);
            }
        }

        $headers = array_merge($defaultHeaders, $request['HEADERS']);

        $url = $request['URL'];

        // cURL setup
        $ch = curl_init();
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL            => $this->endpoint . $url,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CUSTOMREQUEST  => strtoupper($request['METHOD']),
            CURLOPT_ENCODING       => '',
            CURLOPT_USERAGENT      => 'Yopify/Yo/PHP/' . $this->version,
            CURLOPT_FAILONERROR    => $request['FAILONERROR'],
            CURLOPT_VERBOSE        => $request['ALLDATA'],
            CURLOPT_HEADER         => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        );

        // Checks if DATA is being sent
        if ( ! empty($request['DATA'])) {
            if (is_array($request['DATA'])) {
                $options[CURLOPT_POSTFIELDS] = json_encode($request['DATA']);
            }else {
                // Detect if already a JSON object
                json_decode($request['DATA']);
                if (json_last_error() == JSON_ERROR_NONE) {
                    $options[CURLOPT_POSTFIELDS] = $request['DATA'];
                }else {
                    throw new \Exception('DATA malformed.');
                }
            }
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        // Data returned
        $result = json_decode(substr($response, $headerSize), $request['RETURNARRAY']);

        // Headers
        $info = array_filter(array_map('trim', explode("\n", substr($response, 0, $headerSize))));

        foreach ($info as $k => $header) {
            if (strpos($header, 'HTTP/') > -1) {
                $_INFO['HTTP_CODE'] = $header;
                continue;
            }

            list($key, $val) = explode(':', $header);
            $_INFO[trim($key)] = trim($val);
        }


        // cURL Errors
        $_ERROR = array('NUMBER' => curl_errno($ch), 'MESSAGE' => curl_error($ch));

        curl_close($ch);

        if ($_ERROR['NUMBER']) {
            throw new \Exception('ERROR #' . $_ERROR['NUMBER'] . ': ' . $_ERROR['MESSAGE']);
        }


        // Send back in format that user requested
        if ($request['ALLDATA']) {
            if ($request['RETURNARRAY']) {
                $result['_ERROR'] = $_ERROR;
            }else {
                $result->_ERROR = $_ERROR;
            }

            return $result;
        }else {
            return $result;
        }
    }

    /**
     * Ping to check auth
     *
     * @param null $appId
     *
     * @return mixed
     */
    public function ping($appId = null, $allData = false)
    {
        try{
            $appId = $appId ? $appId : $this->appId;

            $data = $this->call([
                'CHARSET'     => 'UTF-8',
                'METHOD'      => 'GET',
                'URL'         => '/ping/' . $appId,
                'HEADERS'     => array(),
                'DATA'        => array(),
                'FAILONERROR' => false,
                'RETURNARRAY' => false,
                'ALLDATA'     => true
            ]);


            if (isset($data->status) && $data->status == 1) {
                return $allData ? $data : $data->time;
            }
        }catch (\Exception $e){

        }

        return false;
    }

    /**
     * Get event
     *
     * @param      $id int Event ID
     *
     * @param null $appId
     *
     * @return mixed $data
     */
    public function getEvent($id, $appId = null)
    {
        $appId = $appId ? $appId : $this->appId;

        return $this->call('/events/' . $appId . '/' . $id);
    }


    /**
     * Get events
     *
     * @param int  $limit Page limit, default = 30
     * @param int  $page Page number, default = 1
     *
     * @param null $appId
     *
     * @return mixed $data
     */
    public function getEvents($limit = 30, $page = 1, $appId = null)
    {
        $appId = $appId ? $appId : $this->appId;

        $data = $this->call(
            [
                'URL'    => '/events/' . $appId,
                'METHOD' => 'GET',
                'DATA'   => [
                    'limit' => $limit,
                    'page'  => $page
                ]
            ]);

        return $data;
    }

    /**
     * Create event
     *
     * @param      $event
     *
     * @param null $appId
     *
     * @return mixed $data
     */
    public function createEvent($event, $appId = null)
    {
        $appId = $appId ? $appId : $this->appId;

        if ( ! is_array($event)) {
            $event = (array)$event;
        }

        return $this->call([
                'URL'    => '/events/' . $appId,
                'METHOD' => 'POST',
                'DATA'   => $event
            ]
        );
    }

    /**
     * Update event
     *
     * @param $event
     *
     * @return $data
     */
    public function updateEvent($event, $appId = null)
    {
        $appId = $appId ? $appId : $this->appId;

        if ( ! is_array($event)) {
            $event = (array)$event;
        }

        return $this->call([
                'URL'    => '/events/' . $appId . '/' . $event['id'],
                'METHOD' => 'PATCH',
                'DATA'   => $event
            ]
        );
    }

    /**
     * Delete event
     *
     * @param      $id int Event ID
     *
     * @param null $appId
     *
     * @return mixed $data
     */
    public function deleteEvent($id, $appId = null)
    {
        $appId = $appId ? $appId : $this->appId;

        return $this->call(
            [
                'URL'    => '/events/' . $appId . '/' . $id,
                'METHOD' => 'DELETE',
            ]);
    }
}