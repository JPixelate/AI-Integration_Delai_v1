<?php

namespace App\Libraries;

class HotelSearchService
{
    protected $useRealAPI = false; // Switch to true when IP is whitelisted
    protected $apiUrl = 'https://travelnext.works/api/hotel-api-v6/hotel_search';
    protected $credentials = [
        'user_id' => 'brigadacomph_testAPI',
        'user_password' => 'brigadacomphTest@2025',
        'access' => 'Test',
        'ip_address' => '119.93.234.188'
    ];

    /**
     * Search for hotels using AI-powered intent detection
     */
    public function searchHotels($destination)
    {
        if ($this->useRealAPI) {
            return $this->searchFromRealAPI($destination);
        } else {
            return $this->searchFromJSONFiles($destination);
        }
    }

    /**
     * Search for hotels with specific location filtering
     */
    public function searchHotelsWithLocation($destination, $specificLocation = null)
    {
        if ($this->useRealAPI) {
            return $this->searchFromRealAPI($destination);
        } else {
            return $this->searchFromJSONFilesWithLocation($destination, $specificLocation);
        }
    }

    /**
     * Search from TravelNext-format JSON file with intelligent filtering
     */
    private function searchFromJSONFiles($searchQuery)
    {
        // Load the TravelNext-format JSON file - try multiple paths
        $possiblePaths = [
            ROOTPATH . "travelnext_hotels.json",
            FCPATH . "../travelnext_hotels.json",
            __DIR__ . "/../../travelnext_hotels.json",
            getcwd() . "/travelnext_hotels.json"
        ];

        $jsonFile = null;
        foreach ($possiblePaths as $path) {
            log_message('info', "HOTEL DATA LOADING: Checking path: {$path}");
            if (file_exists($path)) {
                $jsonFile = $path;
                log_message('info', "HOTEL DATA LOADING: Found travelnext_hotels.json at: {$path}");
                break;
            }
        }

        if (!$jsonFile) {
            log_message('error', "HotelSearchService: travelnext_hotels.json not found in any of these paths: " . implode(', ', $possiblePaths));
            return [
                'success' => false,
                'message' => "Hotel database not available."
            ];
        }

        // Debug logging
        log_message('info', "HotelSearchService: Searching for '{$searchQuery}', loading file: {$jsonFile}");

        $hotelData = json_decode(file_get_contents($jsonFile), true);
        log_message('info', "HOTEL DATA LOADED: " . count($hotelData['itineraries'] ?? []) . " hotels loaded from TravelNext JSON file");

        // STRICT CITY FILTERING: Filter ONLY by city field - NO CROSS-CONTAMINATION!
        $filteredHotels = $this->strictCityFilter($hotelData, $searchQuery);

        return [
            'success' => true,
            'data' => $filteredHotels,
            'source' => 'json_file',
            'search_query' => $searchQuery
        ];
    }

    /**
     * Search from JSON files with specific location filtering
     */
    private function searchFromJSONFilesWithLocation($city, $specificLocation = null)
    {
        // First get all hotels for the city
        $cityResults = $this->searchFromJSONFiles($city);

        if (!$cityResults['success'] || !$specificLocation) {
            // No specific location or city search failed, return city results
            return $cityResults;
        }

        // Filter hotels by specific location
        $hotelData = $cityResults['data'];
        $filteredHotels = $this->filterBySpecificLocation($hotelData, $specificLocation);

        log_message('info', "LOCATION FILTER: " . count($hotelData['itineraries'] ?? []) . " → " . count($filteredHotels['itineraries'] ?? []) . " hotels for location '{$specificLocation}'");

        return [
            'success' => true,
            'data' => $filteredHotels,
            'source' => 'json_file_with_location',
            'search_query' => $city,
            'location_filter' => $specificLocation
        ];
    }

    /**
     * Filter hotels by specific location/area within a city
     */
    private function filterBySpecificLocation($hotelData, $specificLocation)
    {
        if (!isset($hotelData['itineraries']) || !is_array($hotelData['itineraries'])) {
            return $hotelData;
        }

        $originalCount = count($hotelData['itineraries']);
        $lowerLocation = strtolower($specificLocation);

        $filteredHotels = [];

        foreach ($hotelData['itineraries'] as $hotel) {
            $isMatch = false;

            // Check hotel name, address for the specific location
            $searchableFields = [
                'hotelName' => $hotel['hotelName'] ?? '',
                'address' => $hotel['address'] ?? '',
                'city' => $hotel['city'] ?? ''
            ];

            foreach ($searchableFields as $fieldName => $fieldValue) {
                $lowerFieldValue = strtolower($fieldValue);

                // Check if specific location is mentioned in any field
                if (strpos($lowerFieldValue, $lowerLocation) !== false) {
                    $isMatch = true;
                    log_message('info', "LOCATION MATCH: Hotel '{$hotel['hotelName']}' matches '{$specificLocation}' in {$fieldName}: '{$fieldValue}'");
                    break;
                }
            }

            if ($isMatch) {
                $filteredHotels[] = $hotel;
            }
        }

        // If no hotels match the specific location, return all city hotels
        // This prevents showing 0 results when user asks for a specific area
        if (empty($filteredHotels)) {
            log_message('info', "LOCATION FILTER: No hotels found for '{$specificLocation}', returning all city hotels");
            return $hotelData;
        }

        // Update the hotel data
        $hotelData['itineraries'] = $filteredHotels;
        $hotelData['status']['totalResults'] = count($filteredHotels);

        return $hotelData;
    }

    /**
     * AI-powered city detection - NO REGEX KEYWORDS!
     */
    private function detectCityWithAI($text)
    {
        // This is where we'd call the real AI API
        // For now, simple but intelligent detection
        
        $text = strtolower($text);
        
        // Available cities (expand as you add more JSON files)
        $availableCities = [
            'manila' => ['manila', 'makati', 'quezon', 'pasay', 'taguig', 'bgc', 'ortigas'],
            'cebu' => ['cebu', 'lapu-lapu', 'mactan', 'talisay']
        ];

        foreach ($availableCities as $city => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    return $city;
                }
            }
        }

        return null;
    }

    /**
     * INTELLIGENT hotel filtering - matches ANY field (city, name, address, etc.)
     */
    private function intelligentHotelFilter($hotelData, $searchQuery)
    {
        if (!isset($hotelData['itineraries']) || !is_array($hotelData['itineraries'])) {
            return $hotelData;
        }

        $originalCount = count($hotelData['itineraries']);
        $searchQuery = strtolower(trim($searchQuery));

        // If no search query, return all hotels
        if (empty($searchQuery)) {
            return $hotelData;
        }

        $filteredHotels = [];

        foreach ($hotelData['itineraries'] as $hotel) {
            $isMatch = false;

            // Check all searchable fields
            $searchableFields = [
                'city' => $hotel['city'] ?? '',
                'hotelName' => $hotel['hotelName'] ?? '',
                'address' => $hotel['address'] ?? ''
            ];

            foreach ($searchableFields as $fieldName => $fieldValue) {
                $fieldValue = strtolower($fieldValue);

                // Direct match or contains match
                if (strpos($fieldValue, $searchQuery) !== false) {
                    $isMatch = true;
                    log_message('info', "MATCH: Hotel '{$hotel['hotelName']}' matched on {$fieldName}: '{$fieldValue}' contains '{$searchQuery}'");
                    break;
                }
            }

            // Special handling for city aliases
            $cityAliases = [
                'manila' => ['manila', 'pasay', 'caloocan', 'makati', 'quezon', 'taguig', 'ermita', 'malate'],
                'cebu' => ['cebu', 'lapu-lapu', 'lapu-lapu city', 'mactan', 'mactan island'],
                'boracay' => ['boracay', 'boracay island', 'malay', 'aklan'],
                'siargao' => ['siargao', 'siargao island', 'general luna'],
                'gensan' => ['general santos', 'general santos city', 'gensan'],
                'davao' => ['davao', 'davao city']
            ];

            foreach ($cityAliases as $mainCity => $aliases) {
                if (in_array($searchQuery, $aliases)) {
                    $hotelCity = strtolower($hotel['city'] ?? '');
                    foreach ($aliases as $alias) {
                        if (strpos($hotelCity, $alias) !== false) {
                            $isMatch = true;
                            log_message('info', "ALIAS MATCH: Hotel '{$hotel['hotelName']}' in '{$hotelCity}' matched alias '{$alias}' for search '{$searchQuery}'");
                            break 2;
                        }
                    }
                }
            }

            if ($isMatch) {
                $filteredHotels[] = $hotel;
            }
        }

        log_message('info', "INTELLIGENT FILTER: {$originalCount} hotels → " . count($filteredHotels) . " hotels for '{$searchQuery}'");

        // Update the hotel data
        $hotelData['itineraries'] = $filteredHotels;
        $hotelData['status']['totalResults'] = count($filteredHotels);

        return $hotelData;
    }

    /**
     * STRICT city filtering - NO CROSS-CONTAMINATION!
     */
    private function strictCityFilter($hotelData, $requestedCity)
    {
        if (!isset($hotelData['itineraries']) || !is_array($hotelData['itineraries'])) {
            return $hotelData;
        }

        $originalCount = count($hotelData['itineraries']);

        // Define city matching rules - ONLY for cities that exist in database
        $cityMatches = [
            'cebu' => ['cebu', 'lapu-lapu', 'lapu-lapu city', 'mactan', 'cebu airport'],
            'manila' => ['manila', 'pasay', 'caloocan', 'makati', 'quezon', 'taguig'],
            'boracay' => ['boracay', 'boracay island'],
            'siargao' => ['siargao', 'siargao island'],
            // Cities NOT in database - will return 0 results
            'davao' => ['davao', 'davao city'],
            'gensan' => ['gensan', 'general santos'],
            'baguio' => ['baguio'],
            'palawan' => ['palawan', 'el nido', 'coron'],
            'bohol' => ['bohol']
        ];

        log_message('info', "STRICT FILTER: Searching for '{$requestedCity}' in database");

        $allowedCities = $cityMatches[$requestedCity] ?? [$requestedCity];

        // Filter hotels strictly by city
        $filteredHotels = [];
        foreach ($hotelData['itineraries'] as $hotel) {
            $hotelCity = strtolower($hotel['city'] ?? '');

            $isMatch = false;
            foreach ($allowedCities as $allowedCity) {
                if (strpos($hotelCity, strtolower($allowedCity)) !== false) {
                    $isMatch = true;
                    break;
                }
            }

            if ($isMatch) {
                $filteredHotels[] = $hotel;
            } else {
                // Log rejected hotels for debugging
                log_message('info', "REJECTED: Hotel '{$hotel['hotelName']}' in city '{$hotelCity}' does not match requested city '{$requestedCity}'");
            }
        }

        log_message('info', "STRICT FILTER: {$originalCount} hotels → " . count($filteredHotels) . " hotels for '{$requestedCity}'");

        // Update the hotel data
        $hotelData['itineraries'] = $filteredHotels;
        $hotelData['status']['totalResults'] = count($filteredHotels);

        return $hotelData;
    }

    /**
     * Search from real API (when IP is whitelisted)
     */
    private function searchFromRealAPI($destination)
    {
        try {
            $apiData = array_merge($this->credentials, [
                'checkin' => date('Y-m-d', strtotime('+1 day')),
                'checkout' => date('Y-m-d', strtotime('+2 days')),
                'city_name' => $destination,
                'country_name' => $this->getCountryForCity($destination),
                'occupancy' => [
                    [
                        'room_no' => 1,
                        'adult' => 2,
                        'child' => 0,
                        'child_age' => [0]
                    ]
                ],
                'requiredCurrency' => $this->getCurrencyForCountry($this->getCountryForCity($destination))
            ]);

            $client = service('curlrequest');
            $response = $client->request('POST', $this->apiUrl, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'json' => $apiData,
                'timeout' => 30
            ]);

            $data = json_decode($response->getBody(), true);
            
            return [
                'success' => true,
                'data' => $data,
                'source' => 'real_api'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get country for city
     */
    private function getCountryForCity($city)
    {
        $cityCountryMap = [
            'manila' => 'Philippines',
            'cebu' => 'Philippines',
            'davao' => 'Philippines',
            'boracay' => 'Philippines',
            'bangalore' => 'India',
            'mumbai' => 'India',
            'delhi' => 'India'
        ];

        return $cityCountryMap[strtolower($city)] ?? 'Philippines';
    }

    /**
     * Get currency for country
     */
    private function getCurrencyForCountry($country)
    {
        $currencyMap = [
            'Philippines' => 'PHP',
            'India' => 'INR',
            'Thailand' => 'THB',
            'Singapore' => 'SGD'
        ];

        return $currencyMap[$country] ?? 'USD';
    }
}
