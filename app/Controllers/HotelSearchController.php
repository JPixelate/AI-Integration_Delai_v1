<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

class HotelSearchController extends BaseController
{
    private $hotelApiUrl = 'https://travelnext.works/api/hotel-api-v6/hotel_search';
    
    private $apiCredentials = [
        'user_id' => 'brigadacomph_testAPI',
        'user_password' => 'brigadacomphTest@2025',
        'access' => 'Test',
        'ip_address' => '119.93.234.188'
    ];

    public function search()
    {
        // Add CORS headers for API requests
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');

        // Only allow POST requests
        if (!$this->request->is('post')) {
            return $this->response->setStatusCode(405)->setJSON(['error' => 'Method not allowed']);
        }

        try {
            // Get request data
            $requestData = $this->request->getJSON(true);

            // If JSON parsing failed, try to get POST data
            if (!$requestData) {
                $requestData = $this->request->getPost();
            }

            // Validate required fields
            $requiredFields = ['destination', 'checkin', 'checkout'];
            foreach ($requiredFields as $field) {
                if (empty($requestData[$field])) {
                    return $this->response->setStatusCode(400)->setJSON([
                        'error' => "Missing required field: {$field}"
                    ]);
                }
            }

            // Prepare API request data in the correct format
            $adults = $requestData['adults'] ?? 2;
            $children = $requestData['children'] ?? 0;
            $rooms = $requestData['rooms'] ?? 1;

            // Build occupancy array
            $occupancy = [];
            for ($i = 1; $i <= $rooms; $i++) {
                $occupancy[] = [
                    'room_no' => $i,
                    'adult' => $adults,
                    'child' => $children,
                    'child_age' => $children > 0 ? array_fill(0, $children, 5) : [0] // Default age 5 for children
                ];
            }

            $apiData = array_merge($this->apiCredentials, [
                'checkin' => $requestData['checkin'],
                'checkout' => $requestData['checkout'],
                'city_name' => $requestData['destination'],
                'country_name' => $requestData['country'] ?? 'Philippines', // Default to Philippines
                'occupancy' => $occupancy,
                'requiredCurrency' => $requestData['currency'] ?? 'USD'
            ]);

            // Make API request with error handling
            try {
                $client = \Config\Services::curlrequest();
                $response = $client->request('POST', $this->hotelApiUrl, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ],
                    'json' => $apiData,
                    'timeout' => 30
                ]);

                $statusCode = $response->getStatusCode();
                $responseBody = $response->getBody();
                $data = json_decode($responseBody, true);

                if ($statusCode !== 200) {
                    log_message('error', 'Hotel API Error: ' . $responseBody);
                    // Return mock data for testing if API fails
                    return $this->getMockHotelData($apiData);
                }

                // Process and return the results
                return $this->response->setJSON([
                    'success' => true,
                    'data' => $data,
                    'search_params' => $apiData
                ]);

            } catch (\Exception $apiException) {
                log_message('error', 'Hotel API Connection Error: ' . $apiException->getMessage());
                // Return mock data for testing if API is unavailable
                return $this->getMockHotelData($apiData);
            }

        } catch (\Exception $e) {
            log_message('error', 'Hotel Search Exception: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'error' => 'An error occurred while searching for hotels',
                'details' => $e->getMessage()
            ]);
        }
    }

    public function searchFromChat()
    {
        // Add CORS headers for API requests
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');

        // This method will be called from the AI chat to extract hotel search parameters
        if (!$this->request->is('post')) {
            return $this->response->setStatusCode(405)->setJSON(['error' => 'Method not allowed']);
        }

        try {
            $requestData = $this->request->getJSON(true);
            $chatMessage = $requestData['message'] ?? '';

            // Extract hotel search parameters from natural language
            $searchParams = $this->extractHotelSearchParams($chatMessage);

            if (empty($searchParams)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Could not extract hotel search parameters from your message. Please provide destination, check-in and check-out dates.'
                ]);
            }

            // If we have enough parameters, perform the search
            if (isset($searchParams['destination']) && isset($searchParams['checkin']) && isset($searchParams['checkout'])) {
                // Prepare search data
                $searchData = [
                    'destination' => $searchParams['destination'],
                    'checkin' => $searchParams['checkin'],
                    'checkout' => $searchParams['checkout'],
                    'adults' => $searchParams['adults'] ?? 2,
                    'children' => $searchParams['children'] ?? 0,
                    'rooms' => $searchParams['rooms'] ?? 1,
                    'currency' => 'USD'
                ];

                // Prepare API data in correct format
                $adults = $searchParams['adults'] ?? 2;
                $children = $searchParams['children'] ?? 0;
                $rooms = $searchParams['rooms'] ?? 1;

                // Build occupancy array
                $occupancy = [];
                for ($i = 1; $i <= $rooms; $i++) {
                    $occupancy[] = [
                        'room_no' => $i,
                        'adult' => $adults,
                        'child' => $children,
                        'child_age' => $children > 0 ? array_fill(0, $children, 5) : [0]
                    ];
                }

                $searchData = array_merge($this->apiCredentials, [
                    'checkin' => $searchParams['checkin'],
                    'checkout' => $searchParams['checkout'],
                    'city_name' => $searchParams['destination'],
                    'country_name' => 'Philippines',
                    'occupancy' => $occupancy,
                    'requiredCurrency' => 'USD'
                ]);

                // Make API request directly
                try {
                    $client = \Config\Services::curlrequest();
                    $response = $client->request('POST', $this->hotelApiUrl, [
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json'
                        ],
                        'json' => $searchData,
                        'timeout' => 30
                    ]);

                    $statusCode = $response->getStatusCode();
                    $responseBody = $response->getBody();
                    $data = json_decode($responseBody, true);

                    if ($statusCode !== 200) {
                        log_message('error', 'Hotel API Error: ' . $responseBody);
                        return $this->getMockHotelData($searchData);
                    }

                    // Return successful search results
                    return $this->response->setJSON([
                        'success' => true,
                        'data' => $data,
                        'search_params' => $searchData
                    ]);

                } catch (\Exception $apiException) {
                    log_message('error', 'Hotel API Connection Error: ' . $apiException->getMessage());
                    return $this->getMockHotelData($searchData);
                }
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Please provide complete hotel search information: destination, check-in date, and check-out date.',
                'extracted_params' => $searchParams
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Hotel Search From Chat Exception: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'error' => 'An error occurred while processing your hotel search request'
            ]);
        }
    }

    private function extractHotelSearchParams($message)
    {
        $params = [];
        $message = strtolower($message);

        // Extract destination (look for common patterns)
        $destinationPatterns = [
            '/(?:hotel|accommodation|stay).*?(?:in|at|near)\s+([a-zA-Z\s,]+?)(?:\s|$|,|\.|from|for|on)/i',
            '/(?:going to|visiting|traveling to|trip to)\s+([a-zA-Z\s,]+?)(?:\s|$|,|\.|from|for|on)/i',
            '/(?:destination|location).*?(?:is|:)\s*([a-zA-Z\s,]+?)(?:\s|$|,|\.|from|for|on)/i'
        ];

        foreach ($destinationPatterns as $pattern) {
            if (preg_match($pattern, $message, $matches)) {
                $params['destination'] = trim($matches[1]);
                break;
            }
        }

        // Extract dates
        $datePatterns = [
            '/(\d{4}-\d{2}-\d{2})/',
            '/(\d{1,2}\/\d{1,2}\/\d{4})/',
            '/(\d{1,2}-\d{1,2}-\d{4})/'
        ];

        $dates = [];
        foreach ($datePatterns as $pattern) {
            if (preg_match_all($pattern, $message, $matches)) {
                $dates = array_merge($dates, $matches[1]);
            }
        }

        if (count($dates) >= 2) {
            $params['checkin'] = $dates[0];
            $params['checkout'] = $dates[1];
        }

        // Extract number of guests
        if (preg_match('/(\d+)\s*(?:adult|guest|person|people)/i', $message, $matches)) {
            $params['adults'] = (int)$matches[1];
        }

        if (preg_match('/(\d+)\s*(?:child|kid)/i', $message, $matches)) {
            $params['children'] = (int)$matches[1];
        }

        if (preg_match('/(\d+)\s*room/i', $message, $matches)) {
            $params['rooms'] = (int)$matches[1];
        }

        return $params;
    }

    private function getMockHotelData($searchParams)
    {
        // Return mock hotel data for testing when API is unavailable
        $mockHotels = [
            [
                'hotelName' => 'Grand Manila Hotel',
                'address' => '123 Makati Avenue, Makati City, Metro Manila',
                'hotelRating' => 4.5,
                'total' => 150.00,
                'currency' => 'USD',
                'id' => 'hotel_001',
                'amenities' => ['WiFi', 'Pool', 'Gym', 'Restaurant'],
                'description' => 'Luxury hotel in the heart of Makati business district'
            ],
            [
                'hotelName' => 'Seaside Resort Boracay',
                'address' => 'White Beach, Station 2, Boracay Island',
                'hotelRating' => 4.2,
                'total' => 120.00,
                'currency' => 'USD',
                'id' => 'hotel_002',
                'amenities' => ['Beach Access', 'WiFi', 'Restaurant', 'Bar'],
                'description' => 'Beautiful beachfront resort with stunning sunset views'
            ],
            [
                'hotelName' => 'City Center Inn',
                'address' => '456 Rizal Street, Cebu City',
                'hotelRating' => 3.8,
                'total' => 85.00,
                'currency' => 'USD',
                'id' => 'hotel_003',
                'amenities' => ['WiFi', 'Restaurant', 'Business Center'],
                'description' => 'Comfortable accommodation in downtown Cebu'
            ]
        ];

        return $this->response->setJSON([
            'success' => true,
            'data' => [
                'hotels' => $mockHotels,
                'total_results' => count($mockHotels),
                'search_info' => [
                    'destination' => $searchParams['city_name'] ?? 'Philippines',
                    'checkin' => $searchParams['checkin'] ?? '',
                    'checkout' => $searchParams['checkout'] ?? '',
                    'note' => 'This is mock data for testing purposes'
                ]
            ],
            'search_params' => $searchParams
        ]);
    }

    public function options()
    {
        // Add CORS headers for preflight requests
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');

        // Handle CORS preflight requests
        return $this->response->setStatusCode(200);
    }
}
