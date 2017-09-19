<?php
	

$apiClass = new ChargeBackAPI("12345ABC");

echo "Email:<BR>";
echo $apiClass->searchDatabase("typroducts24@gmail.com", "email");
echo "<hr>IP";
echo $apiClass->searchDatabase("127.0.1.2", "ip");
echo "<hr>Username";
echo $apiClass->searchDatabase("tyisobred", "username");
echo "<hr>PayPal Payer ID";
echo $apiClass->searchDatabase("USD4444", "pp_PayerID");
echo "<hr>";


// Future proofing for future use
define('API_METHOD_POST', 1);
define('API_METHOD_PUT', 2);
define('API_METHOD_GET', 3);
define('API_METHOD_DELETE', 4);

class ChargeBackAPI
{
	private $apiVersion = "1.0";
	private $endPointUrl;
	public $timeout = 10;
    public $debug = true;
    public $advDebug = false; // Adds Extra details, WILL break production code
    private $ApiVersion = '1.0';

    private $response;
    public $responseCode;

    
    private $apiKey;
    private $searchURL;
    private $submitURL;

    /**
     * Class constructor.
     *
     * @param array $options Array of options containing an apiKey. Just one of them.
     *                       Can be also an string if you want to use an apiKey.
     */
    public function __construct($options)
    {
        // For retro-compatibility purposes check if $options is a string,
        // so if a user passes a string we use it as the app key.
        if (is_string($options)) {
            $this->apiKey = $options;
        } elseif (is_array($options) && !empty($options['apiKey'])) {
            $this->apiKey = $options['apiKey'];
        } else {
            throw new Exception('You need to specify an API key');
        }

        $this->endPointUrl = 'https://chargebackdb.com/api/' . $this->ApiVersion . '/api.php';

        $this->searchURL = $this->endPointUrl . '?action=searchCB';
        $this->submitURL = $this->endPointUrl . '?action=submitReport';
    }




    public function searchDatabase($term, $type)
    {
    	$data = array("searchType" => $type, "searchTerm" => $this->hashInput($term));

    	$this->runAPICall($this->searchURL, $data);
    	return $this->getResponse();
    }


    /**
     * This function communicates with the ChargebackDB API.
     *
     * @param string $url
     * @param string $data Must be an array of data
     * @param int $method See constants defined at the beginning of the class
     * @return string JSON or null
     */
    private function runAPICall($url, $data = null, $method = API_METHOD_POST)
    {
    	$data['key'] = $this->apiKey;
        $headerData = array();
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // Don't print the result
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);

        
        if (empty($this->apiKey))
        {
        	throw new Exception("No API Key found");
        }

        if ($this->advDebug) {
            curl_setopt($curl, CURLOPT_HEADER, true); // Display headers
            curl_setopt($curl, CURLINFO_HEADER_OUT, true); // Display output headers
            curl_setopt($curl, CURLOPT_VERBOSE, true); // Display communication with server
        }

        if ($method == API_METHOD_POST) {
            curl_setopt($curl, CURLOPT_POST, true);
        } elseif ($method == API_METHOD_PUT) {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        } elseif ($method == API_METHOD_DELETE) {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        if (!is_null($data) && ($method == API_METHOD_POST || $method == API_METHOD_PUT)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        if (sizeof($headerData) > 0) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headerData);
        }

        try {
            $this->response = curl_exec($curl);
            $this->responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if ($this->debug || $this->advDebug) {
                $info = curl_getinfo($curl);
                echo '<pre>';
                print_r($info);
                echo '</pre>';
                if ($info['http_code'] == 0) {
                    echo '<br>cURL error num: ' . curl_errno($curl);
                    echo '<br>cURL error: ' . curl_error($curl);
                }
                echo '<br>Sent info:<br><pre>';
                print_r($data);
                echo '</pre>';
            }
        } catch (Exception $ex) {
            if ($this->debug || $this->advDebug) {
                echo '<br>cURL error num: ' . curl_errno($curl);
                echo '<br>cURL error: ' . curl_error($curl);
            }
            echo 'Error on cURL';
            $this->response = null;
        }

        curl_close($curl);

        return $this->response;
    }

    private function getResponse()
    {
    	return $this->response;
    }

    private function hashInput($input)
    {
    	$hashedVal = $input;
        for($i = 1; $i < 10; $i++)
        {
            $hashedVal = hash("sha256", "Charg" . $i . "eSalt" . $hashedVal . "BackSalt!@$");
        }
        return $hashedVal;
    }






}

?>
