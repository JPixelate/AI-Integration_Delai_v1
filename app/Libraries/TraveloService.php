<?php namespace App\Libraries;

use CodeIgniter\HTTP\CURLRequest;

class TraveloService
{
    protected $client;
    protected $apiBaseUrl;
    protected $username;
    protected $password;
    protected $code;

    public function __construct()
    {
        // Use the Travelopro demo API base URL
        $this->apiBaseUrl = 'https://delightful.regiment.me/';

        // Load credentials from environment variables
        $this->username = getenv('TRAVELOPRO_USERNAME');
        $this->password = getenv('TRAVELOPRO_PASSWORD');
        $this->code     = getenv('TRAVELOPRO_CODE');

        // Initialize HTTP client with Basic Authentication and JSON Accept header
        $this->client = service('curlrequest', [
            'baseURI' => $this->apiBaseUrl,
            'auth'    => [$this->username, $this->password],
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Fetch booking data from the API for a specified region.
     *
     * @param string $region
     * @return array
     * @throws \Exception
     */
    public function fetchBookingDataByRegion(string $region)
    {
        $response = $this->client->get('https://delightful.regiment.me/api/tour/search', [
            'query' => [
                'region' => 'Asia',
                'code' => $this->code,
            ],
        ]);
    
        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Failed to fetch booking data for region ' . $region);
        }
    
        $body = (string)$response->getBody();
    
        // TEMP: Log or inspect the raw response
        log_message('debug', 'API Response: ' . $body);
    
        $data = json_decode($body, true);
    
        // if (!isset($data['booking'])) {
        //     // Also include full response for debug
        //     throw new \Exception('Invalid response structure from API: ' . $body);
        // }
    
        return $data;
    }
    
}
