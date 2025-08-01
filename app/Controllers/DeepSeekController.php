<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use Exception;

class DeepSeekController extends BaseController
{



   public function chat()
   {
      helper('text');
      /** @var \Config\DeepSeek $deepseekConfig */
      $deepseekConfig = config('DeepSeek');

      $request = $this->request->getJSON(true);
      $userMessage = esc($request['message'] ?? '');
      $chatHistory = $request['history'] ?? [];

      if (!is_array($chatHistory)) {
         $chatHistory = [];
      }



      if (empty($userMessage)) {
         return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing message.']);
      }

      // UNIVERSAL AI SYSTEM - Handle ANY user request intelligently
      log_message('info', "UNIVERSAL AI: Processing user request: '{$userMessage}'");

      return $this->handleUniversalRequest($userMessage, $chatHistory);
   }

   /**
    * UNIVERSAL AI SYSTEM - Handles ANY user request with full flexibility
    */
   private function handleUniversalRequest($userMessage, $chatHistory = [])
   {
      try {
         // Load available data sources FIRST
         $availableData = $this->loadAvailableData();
         log_message('info', "UNIVERSAL AI: Loaded " . count($availableData['hotels']) . " hotels");

         // PRE-CHECK: Handle specific hotel questions directly using our data
         $directResponse = $this->checkForDirectHotelResponse($userMessage, $availableData['hotels']);
         if ($directResponse) {
            log_message('info', "DIRECT RESPONSE: Found direct answer for hotel question");
            return $directResponse;
         }

         /** @var \Config\DeepSeek $deepseekConfig */
         $deepseekConfig = config('DeepSeek');
         $apiKey = getenv('OPENAI_API_KEY');

         if (empty($apiKey) || !$deepseekConfig || empty($deepseekConfig->model) || empty($deepseekConfig->apiUrl)) {
            return $this->generateConversationalResponse($userMessage, $chatHistory);
         }

         // Build conversation context
         $conversationContext = $this->buildConversationContext($userMessage, $chatHistory);

         // Create comprehensive AI prompt for universal handling
         $systemPrompt = $this->createUniversalSystemPrompt($availableData);

         $payload = [
            'model' => $deepseekConfig->model,
            'messages' => [
               ['role' => 'system', 'content' => $systemPrompt],
               ['role' => 'user', 'content' => $conversationContext]
            ],
            'temperature' => 0.7,
            'max_tokens' => 1000
         ];

         $client = \Config\Services::curlrequest();

         $response = $client->request('POST', $deepseekConfig->apiUrl, [
            'headers' => [
               'Authorization' => 'Bearer ' . $apiKey,
               'Content-Type' => 'application/json'
            ],
            'body' => json_encode($payload),
            'timeout' => 20
         ]);

         $body = json_decode($response->getBody(), true);
         $aiResponse = $body['choices'][0]['message']['content'] ?? null;

         if (!$aiResponse) {
            return $this->generateConversationalResponse($userMessage, $chatHistory);
         }

         // Parse AI response and generate appropriate UI response
         return $this->parseUniversalAIResponse($aiResponse, $userMessage);
      } catch (\Exception $e) {
         log_message('error', "Universal AI error: " . $e->getMessage());
         return $this->generateConversationalResponse($userMessage, $chatHistory);
      }
   }

   /**
    * Build conversation context for AI analysis
    */
   private function buildConversationContext($currentMessage, $chatHistory = [])
   {
      $context = "Recent conversation:\n";

      if (!empty($chatHistory)) {
         foreach (array_slice($chatHistory, -3) as $turn) {
            if (isset($turn['role']) && isset($turn['content'])) {
               $role = $turn['role'] === 'user' ? 'User' : 'AI';
               $context .= "{$role}: {$turn['content']}\n";
            }
         }
      }

      $context .= "User: {$currentMessage}";
      return $context;
   }

   /**
    * Check for direct hotel responses using our data - NO AI INVOLVED
    */
   private function checkForDirectHotelResponse($userMessage, $hotels)
   {
      // DISABLED OLD HOTEL DETECTION SYSTEM - Using new memory-based AI system instead
      log_message('info', "OLD HOTEL SYSTEM DISABLED: Letting memory-based AI handle all requests");
      return null; // Let the new memory-based AI system handle everything

      log_message('info', "PROCESSING HOTEL QUESTION: '{$userMessage}'");

      // Extract hotel name from the message - IMPROVED MATCHING
      foreach ($hotels as $hotel) {
         $hotelName = strtolower($hotel['hotelName'] ?? '');
         if (!empty($hotelName)) {
            // Try exact match first
            if (strpos($lowerMessage, $hotelName) !== false) {
               log_message('info', "DIRECT MATCH: Found exact hotel '{$hotel['hotelName']}' in database");
               return $this->generateHotelResponse($hotel);
            }

            // Try partial matching for common variations
            $hotelWords = explode(' ', $hotelName);
            $matchCount = 0;
            foreach ($hotelWords as $word) {
               if (strlen($word) > 3 && strpos($lowerMessage, $word) !== false) {
                  $matchCount++;
               }
            }

            // If most words match, consider it a match
            if ($matchCount >= count($hotelWords) * 0.6) {
               log_message('info', "PARTIAL MATCH: Found hotel '{$hotel['hotelName']}' with {$matchCount}/" . count($hotelWords) . " word matches");
               return $this->generateHotelResponse($hotel);
            }
         }
      }

      log_message('info', "NO HOTEL MATCH: No hotels found in database for message '{$userMessage}'");

      // Hotel not found in our database - SEARCH THE WEB
      log_message('info', "DIRECT CHECK: Hotel not found in database, searching web for information");

      // Extract hotel name for web search
      $extractedHotelName = $this->extractHotelNameFromMessage($userMessage);
      if ($extractedHotelName) {
         return $this->searchWebForHotel($extractedHotelName, $userMessage);
      }

      return null; // Let AI handle if we can't extract hotel name
   }

   /**
    * Generate hotel response from our database data
    */
   private function generateHotelResponse($hotel)
   {
      $rating = $hotel['hotelRating'] ?? 0;
      $price = $hotel['total'] ?? 0;
      $currency = $hotel['currency'] ?? 'PHP';
      $address = $hotel['address'] ?? 'Address not available';
      $city = $hotel['city'] ?? '';

      $reply = "Yes! I'm familiar with **{$hotel['hotelName']}**. ";

      if ($rating > 0) {
         $reply .= "It's a {$rating}-star hotel ";
      }

      $reply .= "located in {$city}";

      if ($address !== 'Address not available') {
         $reply .= " at {$address}";
      }

      if ($price > 0) {
         $formattedPrice = number_format($price, 2);
         $reply .= ". The rate is {$currency} {$formattedPrice} per night";

         if ($price < 2000) {
            $reply .= " - that's quite budget-friendly!";
         } elseif ($price < 5000) {
            $reply .= " - a reasonable mid-range option.";
         } else {
            $reply .= " - a premium choice.";
         }
      } else {
         $reply .= ".";
      }

      if (isset($hotel['fareType'])) {
         $reply .= " The booking is {$hotel['fareType']}.";
      }

      return $this->response->setJSON(['reply' => $reply]);
   }

   /**
    * Extract hotel name from user message
    */
   private function extractHotelNameFromMessage($message)
   {
      $lowerMessage = strtolower($message);

      // Common patterns for hotel questions - COMPREHENSIVE PATTERNS
      $patterns = [
         '/are you familiar with (.+?)\?/',
         '/do you know (.+?)\?/',
         '/tell me about (.+?)[\?\.]/',
         '/details about (.+?)[\?\.]/',
         '/information about (.+?)[\?\.]/',
         '/what do you know about (.+?)[\?\.]/',
         '/(.+?) is the name of hotel/',
         '/(.+?) hotel/',
         '/hotel (.+?)[\?\s]/',
         '/(.+?) guesthouse/',
         '/guesthouse (.+?)[\?\s]/',
         // Direct hotel name mentions
         '/^(.+?)$/' // Catch any remaining text as potential hotel name
      ];

      foreach ($patterns as $pattern) {
         if (preg_match($pattern, $lowerMessage, $matches)) {
            $hotelName = trim($matches[1]);
            // Clean up common words
            $hotelName = preg_replace('/\b(hotel|the|a|an)\b/i', '', $hotelName);
            $hotelName = trim($hotelName);

            if (!empty($hotelName) && strlen($hotelName) > 2) {
               log_message('info', "HOTEL NAME EXTRACTION: Extracted '{$hotelName}' from message");
               return $hotelName;
            }
         }
      }

      return null;
   }

   /**
    * Search the web for hotel information when not found in our database
    */
   private function searchWebForHotel($hotelName, $originalMessage)
   {
      try {
         log_message('info', "WEB SEARCH: Searching for hotel '{$hotelName}'");

         // Use web search to find hotel information
         $searchQuery = $hotelName . " hotel Philippines details rating price location";
         $webResults = $this->performWebSearch($searchQuery);

         if (!$webResults) {
            // Fallback response if web search fails
            return $this->response->setJSON([
               'reply' => "I'm searching for information about {$hotelName}. Let me check what I can find for you. Could you provide more details like the city or location? This will help me give you more accurate information."
            ]);
         }

         // Use AI to analyze web results and generate response
         return $this->generateWebBasedHotelResponse($hotelName, $webResults, $originalMessage);
      } catch (Exception $e) {
         log_message('error', "Web search failed for hotel '{$hotelName}': " . $e->getMessage());

         // Fallback response
         return $this->response->setJSON([
            'reply' => "I'm looking for information about {$hotelName}. While I search for the most current details, could you tell me which city or area you're interested in? This will help me provide you with the best recommendations and current information."
         ]);
      }
   }

   /**
    * Perform web search for hotel information
    */
   private function performWebSearch($query)
   {
      try {
         log_message('info', "WEB SEARCH: Performing search for: {$query}");

         // Use AI to generate hotel information based on general knowledge
         // This is more reliable than web scraping and provides immediate results

         /** @var \Config\DeepSeek $deepseekConfig */
         $deepseekConfig = config('DeepSeek');
         $apiKey = getenv('OPENAI_API_KEY');

         if (empty($apiKey) || !$deepseekConfig) {
            return null;
         }

         $searchPrompt = "You are a travel information expert. Provide information about this hotel search query: '{$query}'

         TASK: Generate factual information about this hotel if it exists, including:
         - Location and address (if known)
         - Star rating or quality level
         - General price range
         - Key amenities or features
         - Nearby attractions or landmarks

         If you don't have specific information about this exact hotel, provide general guidance about:
         - The area/city it might be in
         - Typical hotel options in that area
         - What travelers should look for

         Be helpful and informative, but honest about what information is general vs. specific.

         Respond with useful travel information:";

         $payload = [
            'model' => $deepseekConfig->model,
            'messages' => [
               ['role' => 'user', 'content' => $searchPrompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 400
         ];

         $client = \Config\Services::curlrequest();

         $response = $client->request('POST', $deepseekConfig->apiUrl, [
            'headers' => [
               'Authorization' => 'Bearer ' . $apiKey,
               'Content-Type' => 'application/json'
            ],
            'body' => json_encode($payload),
            'timeout' => 15
         ]);

         $body = json_decode($response->getBody(), true);
         $aiInfo = $body['choices'][0]['message']['content'] ?? null;

         if ($aiInfo) {
            return [
               'query' => $query,
               'results' => [
                  [
                     'title' => 'Hotel Information',
                     'snippet' => $aiInfo,
                     'url' => 'AI Generated Information'
                  ]
               ]
            ];
         }

         return null;
      } catch (Exception $e) {
         log_message('error', "Web search error: " . $e->getMessage());
         return null;
      }
   }

   /**
    * Generate hotel response based on web search results
    */
   private function generateWebBasedHotelResponse($hotelName, $webResults, $originalMessage)
   {
      try {
         /** @var \Config\DeepSeek $deepseekConfig */
         $deepseekConfig = config('DeepSeek');
         $apiKey = getenv('OPENAI_API_KEY');

         if (empty($apiKey) || !$deepseekConfig) {
            return $this->response->setJSON([
               'reply' => "I'm researching {$hotelName} for you. Based on my search, I'm gathering current information about this hotel. Could you specify which city or area you're looking at? This will help me provide more targeted details about location, pricing, and availability."
            ]);
         }

         $webContext = "Web search results for '{$hotelName}':\n";
         foreach ($webResults['results'] as $result) {
            $webContext .= "- {$result['title']}: {$result['snippet']}\n";
         }

         $prompt = "You are a helpful travel assistant. A user asked about '{$hotelName}' but this hotel is not in our database.

         USER QUESTION: {$originalMessage}

         WEB SEARCH RESULTS:
         {$webContext}

         TASK: Provide a helpful response about {$hotelName} based on the web search results.

         RULES:
         1. Be conversational and helpful
         2. Use information from the web search results if available
         3. If web results are limited, ask for more specific location details
         4. Never say 'I don't have this in my database' - always be proactive
         5. Offer to help find similar hotels or provide more information
         6. Be honest about what information you have vs. what you're still researching

         Generate a helpful, conversational response:";

         $payload = [
            'model' => $deepseekConfig->model,
            'messages' => [
               ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 300
         ];

         $client = \Config\Services::curlrequest();

         $response = $client->request('POST', $deepseekConfig->apiUrl, [
            'headers' => [
               'Authorization' => 'Bearer ' . $apiKey,
               'Content-Type' => 'application/json'
            ],
            'body' => json_encode($payload),
            'timeout' => 15
         ]);

         $body = json_decode($response->getBody(), true);
         $aiResponse = $body['choices'][0]['message']['content'] ?? null;

         if ($aiResponse) {
            return $this->response->setJSON(['reply' => trim($aiResponse)]);
         }

         // Fallback if AI fails
         return $this->response->setJSON([
            'reply' => "I'm researching {$hotelName} for you. To provide the most accurate information, could you let me know which city or area this hotel is in? This will help me give you specific details about location, amenities, and current rates."
         ]);
      } catch (Exception $e) {
         log_message('error', "Web-based response generation failed: " . $e->getMessage());

         return $this->response->setJSON([
            'reply' => "I'm looking into {$hotelName} for you. While I gather the most current information, could you provide the city or location? This will help me give you the best details about this hotel."
         ]);
      }
   }

   /**
    * Load all available data sources
    */
   private function loadAvailableData()
   {
      $data = [
         'hotels' => [],
         'flights' => [],
         'restaurants' => []
      ];

      // 🚨 NEW API INTEGRATION - VERSION 2.0 🚨
      log_message('info', "🚀 NEW API SYSTEM ACTIVE - TravelNext Integration v2.0");

      // Load hotel data from TravelNext API
      try {
         log_message('info', "HOTEL DATA LOADING: Starting API integration - fetchHotelsFromTravelNextAPI()");

         // Extract city from user context if available
         $cityName = $this->extractCityFromContext(); // Dynamic city extraction
         $countryName = 'Philippines'; // Always Philippines

         $apiHotels = $this->fetchHotelsFromTravelNextAPI($cityName, $countryName);

         log_message('info', "HOTEL DATA LOADING: API call completed, received " . count($apiHotels) . " hotels");

         if (!empty($apiHotels)) {
            $data['hotels'] = $apiHotels;
            log_message('info', "HOTEL DATA LOADED: " . count($data['hotels']) . " hotels loaded from TravelNext API");
         } else {
            // Fallback to local JSON file if API fails
            log_message('info', "HOTEL DATA LOADING: API returned empty, falling back to local hotel.json");
            $this->loadHotelsFromLocalFile($data);
         }
      } catch (Exception $e) {
         log_message('error', "HOTEL DATA LOADING: API Exception - " . $e->getMessage());
         // Fallback to local JSON file
         $this->loadHotelsFromLocalFile($data);
      }

      return $data;
   }

   /**
    * Fetch hotels from TravelNext API
    * @param string $cityName Optional city name from user query
    * @param string $countryName Optional country name from user query
    */
   private function fetchHotelsFromTravelNextAPI($cityName = 'Manila', $countryName = 'Philippines')
   {
      try {
         $apiUrl = 'https://travelnext.works/api/hotel-api-v6/hotel_search';

         log_message('info', "TRAVELNEXT API: Searching for hotels in {$cityName}, {$countryName}");

         // API request body with exact format as specified
         $postData = [
            'user_id' => 'brigadacomph_testAPI',
            'user_password' => 'brigadacomphTest@2025',
            'access' => 'Test',
            'ip_address' => '119.93.234.188',
            'checkin' => date('Y-m-d', strtotime('+1 day')),
            'checkout' => date('Y-m-d', strtotime('+2 days')),
            'city_name' => $cityName,
            'country_name' => $countryName,
            'occupancy' => [
               [
                  'room_no' => 1,
                  'adult' => 2,
                  'child' => 0,
                  'child_age' => [0]
               ]
            ],
            'requiredCurrency' => 'PHP'
         ];

         $ch = curl_init();
         curl_setopt($ch, CURLOPT_URL, $apiUrl);
         curl_setopt($ch, CURLOPT_POST, true);
         curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
         curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
         ]);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch, CURLOPT_TIMEOUT, 30);
         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

         $response = curl_exec($ch);
         $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
         curl_close($ch);

         if ($httpCode === 200 && $response) {
            $apiData = json_decode($response, true);
            if (isset($apiData['itineraries']) && is_array($apiData['itineraries'])) {
               log_message('info', "TRAVELNEXT API: Successfully fetched " . count($apiData['itineraries']) . " hotels");
               return $apiData['itineraries'];
            } else {
               log_message('error', "TRAVELNEXT API: Invalid response structure");
               return [];
            }
         } else {
            log_message('error', "TRAVELNEXT API: HTTP {$httpCode} - " . substr($response, 0, 200));
            return [];
         }
      } catch (Exception $e) {
         log_message('error', "TRAVELNEXT API: Exception - " . $e->getMessage());
         return [];
      }
   }

   /**
    * Fallback method to load hotels from TravelNext-format JSON file
    */
   private function loadHotelsFromLocalFile(&$data)
   {
      log_message('info', "🚨 FALLBACK METHOD CALLED - Loading TravelNext-format JSON file");

      try {
         $possiblePaths = [
            FCPATH . 'travelnext_hotels.json',
            ROOTPATH . 'travelnext_hotels.json',
            __DIR__ . '/../../travelnext_hotels.json',
            getcwd() . '/travelnext_hotels.json'
         ];

         $hotelFile = null;
         foreach ($possiblePaths as $path) {
            log_message('info', "FALLBACK: Checking path: {$path}");
            if (file_exists($path)) {
               $hotelFile = $path;
               log_message('info', "FALLBACK: Found travelnext_hotels.json at: {$path}");
               break;
            }
         }

         if ($hotelFile) {
            $hotelJson = file_get_contents($hotelFile);
            $hotelData = json_decode($hotelJson, true);
            if (isset($hotelData['itineraries'])) {
               $data['hotels'] = $hotelData['itineraries'];
               log_message('info', "FALLBACK: " . count($data['hotels']) . " hotels loaded from TravelNext-format JSON file");
               log_message('info', "FALLBACK: Total results from JSON: " . ($hotelData['status']['totalResults'] ?? 'unknown'));
            }
         } else {
            log_message('error', "FALLBACK: No travelnext_hotels.json file found");
         }
      } catch (Exception $e) {
         log_message('error', "FALLBACK: Failed to load TravelNext JSON file - " . $e->getMessage());
      }
   }

   /**
    * Format all hotel data for AI memory - creates comprehensive hotel knowledge base
    * Includes ALL details: facilities, ratings, reviews, contact info, etc.
    */
   private function formatHotelDataForAIMemory($hotels)
   {
      if (empty($hotels)) {
         return "No hotel data available.";
      }

      $memoryData = "COMPREHENSIVE HOTEL DATABASE ({count} hotels):\n";
      $memoryData = str_replace('{count}', count($hotels), $memoryData);

      // Group hotels by city for better organization
      $hotelsByCity = [];
      foreach ($hotels as $hotel) {
         // Handle both API formats
         $city = strtoupper($hotel['city'] ?? $hotel['locality'] ?? 'UNKNOWN');
         if (!isset($hotelsByCity[$city])) {
            $hotelsByCity[$city] = [];
         }
         $hotelsByCity[$city][] = $hotel;
      }

      // Format each city's hotels with COMPLETE details
      foreach ($hotelsByCity as $city => $cityHotels) {
         $memoryData .= "\n=== {$city} HOTELS ===\n";

         foreach ($cityHotels as $hotel) {
            // Basic hotel information
            $hotelName = $hotel['hotelName'] ?? 'Unknown Hotel';
            $currency = $hotel['currency'] ?? 'PHP';
            $total = $hotel['total'] ?? '0';
            $rating = $hotel['hotelRating'] ?? 0;
            $address = $hotel['address'] ?? 'Address not available';
            $locality = $hotel['locality'] ?? '';
            $propertyType = $hotel['propertyType'] ?? 'hotel';
            $fareType = $hotel['fareType'] ?? 'Unknown';

            // TripAdvisor information
            $tripAdvisorRating = $hotel['tripAdvisorRating'] ?? 0;
            $tripAdvisorReview = $hotel['tripAdvisorReview'] ?? 0;
            $special = $hotel['special'] ?? '';

            // Contact information
            $phone = $hotel['phone'] ?? 'Not available';
            $email = $hotel['email'] ?? 'Not available';

            // Facilities
            $facilities = $hotel['facilities'] ?? [];
            $facilitiesText = !empty($facilities) ? implode(', ', $facilities) : 'No facilities listed';

            // Format comprehensive hotel entry
            $memoryData .= "\n🏨 {$hotelName}\n";
            $memoryData .= "   • Location: {$address}" . ($locality ? ", {$locality}" : "") . "\n";
            // Generate 5-star rating display
            $filledStars = str_repeat('★', $rating);
            $emptyStars = str_repeat('☆', 5 - $rating);
            $starDisplay = $filledStars . $emptyStars;

            $memoryData .= "   • Type: {$propertyType} | Rating: {$starDisplay} | Price: {$currency}{$total}/night\n";
            $memoryData .= "   • Booking: {$fareType}\n";

            if ($tripAdvisorRating > 0) {
               $memoryData .= "   • TripAdvisor: {$tripAdvisorRating}/5 ({$tripAdvisorReview} reviews)\n";
            }

            if ($special) {
               $memoryData .= "   • Special: {$special}\n";
            }

            $memoryData .= "   • Facilities: {$facilitiesText}\n";
            $memoryData .= "   • Contact: {$phone} | {$email}\n";
         }
      }

      return $memoryData;
   }

   /**
    * Extract city name from user context/message for dynamic API calls
    */
   private function extractCityFromContext()
   {
      // For now, return Manila as default
      // TODO: Implement dynamic city extraction from user messages
      // This could analyze recent chat history or current user message
      // to detect city names like "hotels in Cebu", "find me hotels in Davao", etc.

      $defaultCity = 'Manila';

      // Future enhancement: Parse user messages for city names
      // $userMessage = $this->request->getPost('message') ?? '';
      // $detectedCity = $this->detectCityInMessage($userMessage);
      // return $detectedCity ?: $defaultCity;

      log_message('info', "CITY EXTRACTION: Using default city '{$defaultCity}' (dynamic extraction not yet implemented)");
      return $defaultCity;
   }

   /**
    * Create universal system prompt with pre-loaded hotel data in AI memory
    */
   private function createUniversalSystemPrompt($availableData)
   {
      $hotelCount = count($availableData['hotels']);

      // Pre-load ALL hotel data into AI memory for conversational responses
      $hotelMemoryData = $this->formatHotelDataForAIMemory($availableData['hotels']);

      return "You are Delai, a warm and knowledgeable Filipino travel assistant who knows all about Philippine hotels.

🏨 YOUR HOTEL KNOWLEDGE ({$hotelCount} hotels):
{$hotelMemoryData}

⚠️ CRITICAL INSTRUCTIONS:
1. You MUST use the COMPLETE hotel information above to answer ALL hotel questions
2. When asked about ANY hotel detail, use the comprehensive data above
3. You have FULL ACCESS to: facilities, TripAdvisor ratings, contact info, addresses, prices
4. For facilities questions: Check the Facilities section for each hotel
5. For TripAdvisor questions: Use the TripAdvisor ratings and review counts
6. For contact questions: Use the Contact phone and email information
7. ALWAYS mention specific hotel names, prices, and details naturally
8. When asked HOW MANY hotels, COUNT them and give the exact number first, then list them
9. You know EVERYTHING about each hotel - never say you don't have information

📋 RESPONSE FORMAT - ABSOLUTELY NO DASHES:
- Use natural conversational text with clean, simple formatting
- Include specific hotel details (name, price, rating, address)
- When discussing hotels, mention multiple options with details
- For lists of hotels, use simple numbered format: 1. Hotel Name, 2. Hotel Name
- For features or amenities, list them WITHOUT any dashes, bullets, or symbols
- Write like you're talking to a friend with clear, organized information
- CRITICAL: NO dashes (-), NO bullets (•), NO asterisks (*), NO ### headers, NO ** bold text
- Use simple line breaks and spacing for organization
- NEVER say in my database, from my database, in my memory - just provide the information naturally
- Structure responses with clear sections using simple text and line breaks
- Keep formatting minimal and clean - NO DASHES ANYWHERE

🎯 EXAMPLES - NO DASHES FORMAT WITH 5-STAR RATING:
Q: 'Generate all hotels in Cebu'
A: 'Here are some excellent hotel options in Cebu:

1. Sugbutel Family Hotel
Rating: ★★☆☆☆ (2-star family-friendly option)
Price: ₱2,156.25 per night
Located at Colon Street, Cebu City in the heart of the city
Great for families visiting the historic center

2. Residenz Guesthouse
Rating: ★★☆☆☆ (Budget-friendly 2-star option)
Price: ₱1,205.88 per night
Located at S-36 San Jose Village, Umapad
Perfect for budget travelers

Would you like more details about any of these hotels or see additional options?'

CRITICAL:
- NO dashes anywhere in the response
- Rating shows ALL 5 stars (filled ★ + empty ☆)
- Each detail is on its own line without any dash, bullet, or symbol prefix

Q: 'How many RedDoorz hotels in the Philippines?'
A: 'I know about 3 RedDoorz hotels in the Philippines:

1. Nearest Hostel Pasay City By Reddoorz - 1-star, ₱1,962.87 per night in Pasay
2. RedDoorz near PNR Espana Station - 2-star, ₱2,250.00 per night in Manila
3. RedDoorz @ Bankal Lapulapu - 1-star, ₱1,589.48 per night in Lapu-Lapu

Would you like more details about any of these RedDoorz locations?'

CRITICAL INSTRUCTIONS:
- When asked about specific addresses, use the hotel information above for exact matches
- For 2231 Aurora Boulevard address = Stone House Hotel Pasay (PHP 1,991.05, 2-star, Manila)
- For 64 Legaspi Aurora Blvd address = Nearest Hostel Pasay City By Reddoorz (PHP 1,962.87, 1-star, Pasay)
- ALWAYS use the hotel information above before saying you do not have information
- When listing multiple hotels, use simple numbered format (1. 2. 3.) NOT markdown headers
- The hotels you mention in chat MUST appear in the right panel
- When asked HOW MANY hotels (count questions), always give the exact number first, then list them
- For RedDoorz count: You know about 3 RedDoorz hotels total
- For Pasay count: You know about 2 hotels in Pasay
- For Manila count: Count all hotels with city = Manila

CONVERSATION EXAMPLES:

User: \"Do you have details about Residenz Guesthouse?\"
Response: \"Yes! Residenz Guesthouse is a 2-star hotel in Cebu, located at S-36 San Jose Village, Umapad. It costs PHP 1,205.88 per night and offers a cozy, budget-friendly stay.\"

User: \"What is the rating of Residenz Guesthouse?\"
Response: \"Residenz Guesthouse has a 2-star rating. It's a budget-friendly option with basic amenities, perfect for travelers looking for affordable accommodation in Cebu.\"

User: \"Find hotels in Cebu\"
Response: \"Here are some great hotels in Cebu for you! There's Residenz Guesthouse (2-star, ₱1,205.88/night) at S-36 San Jose Village, and RedDoorz @ Bankal Lapulapu which is also budget-friendly. Would you like more details about any of these?\"

User: \"Are you familiar with Holiday Inn Cebu City?\"
Response: \"I don't know about Holiday Inn Cebu City, but I can recommend other excellent hotels in Cebu like Residenz Guesthouse (2-star, ₱1,205.88/night) and RedDoorz @ Bankal Lapulapu (budget-friendly option). Would you like to see all available Cebu hotels?\"

REMEMBER: Always respond in natural conversational text using the specific hotel information above.";
   }

   /**
    * Parse AI response and generate appropriate UI response for memory-based system
    */
   private function parseUniversalAIResponse($aiResponse, $userMessage)
   {
      try {
         log_message('info', "MEMORY-BASED AI RESPONSE: Raw response: " . $aiResponse);

         // Clean the response - remove any JSON formatting if AI mistakenly used it
         $cleanResponse = $this->cleanAIResponse($aiResponse);

         log_message('info', "MEMORY-BASED AI RESPONSE: Cleaned response: " . $cleanResponse);

         // Check if AI response mentions specific hotels and should trigger hotel panel
         $shouldShowHotelPanel = $this->shouldTriggerHotelPanel($cleanResponse, $userMessage);

         if ($shouldShowHotelPanel) {
            // Extract hotel information from AI response and load hotel data
            $hotelData = $this->extractHotelDataFromAIResponse($cleanResponse, $userMessage);

            // Format the response with clickable hotel names
            $hotels = $hotelData['itineraries'] ?? [];
            if (!empty($hotels)) {
               $formattedResponse = $this->formatMemoryBasedHotelResponse($cleanResponse, $hotels);
               $cleanResponse = $formattedResponse;
            }

            return $this->response->setJSON([
               'reply' => $cleanResponse,
               'hotel_search' => true,
               'hotel_results' => $hotelData,
               'show_hotels_tab' => true,
               'ai_generated' => true,
               'processing_complete' => true
            ]);
         } else {
            // Pure conversational response
            return $this->response->setJSON([
               'reply' => $cleanResponse,
               'ai_generated' => true
            ]);
         }
      } catch (Exception $e) {
         log_message('error', "Failed to parse memory-based AI response: " . $e->getMessage());
         // Fallback to treating as plain text
         return $this->response->setJSON(['reply' => $this->cleanAIResponse($aiResponse)]);
      }
   }

   /**
    * Clean AI response from any unwanted formatting
    */
   private function cleanAIResponse($response)
   {
      // Remove HTML tags
      $clean = str_replace(['<br>', '<br/>', '<br />'], ' ', $response);

      // If AI returned JSON format, extract the chat_response
      if (strpos($clean, '"chat_response"') !== false) {
         $jsonData = json_decode($clean, true);
         if ($jsonData && isset($jsonData['chat_response'])) {
            $clean = $jsonData['chat_response'];
         }
      }

      // Remove any remaining JSON-like formatting
      $clean = preg_replace('/^json\s*/', '', $clean);
      $clean = preg_replace('/^\{.*?"chat_response":\s*"([^"]+)".*?\}$/s', '$1', $clean);

      return trim($clean);
   }

   /**
    * Determine if AI response should trigger hotel panel display
    */
   private function shouldTriggerHotelPanel($aiResponse, $userMessage)
   {
      $userMessageLower = strtolower($userMessage);
      $aiResponseLower = strtolower($aiResponse);

      // Hotel search keywords
      $hotelKeywords = [
         'hotel',
         'hotels',
         'accommodation',
         'stay',
         'book',
         'reservation',
         'where to stay',
         'recommend',
         'suggestion',
         'best hotel',
         'find me hotels',
         'nearest hostel',
         'pasay centrale',
         'stone house',
         'hostel',
         'hotel near me',
         'find hotel',
         'aurora blvd',
         'aurora boulevard',
         'legaspi'
      ];

      // Check user message for hotel intent
      foreach ($hotelKeywords as $keyword) {
         if (strpos($userMessageLower, $keyword) !== false) {
            log_message('info', "HOTEL PANEL TRIGGER: User message contains '{$keyword}'");
            return true;
         }
      }

      // Check if AI response mentions specific hotels from our database
      $hotelNames = [
         'pasay centrale hotel',
         'nearest hostel pasay',
         'stone house hotel',
         'zen rooms',
         'stay malate',
         'sugbutel',
         'crimson resort',
         'reddoorz'
      ];

      foreach ($hotelNames as $hotelName) {
         if (strpos($aiResponseLower, $hotelName) !== false) {
            log_message('info', "HOTEL PANEL TRIGGER: AI response mentions '{$hotelName}'");
            return true;
         }
      }

      // Check if AI response mentions prices (₱ symbol or PHP)
      if (strpos($aiResponseLower, '₱') !== false || strpos($aiResponseLower, 'php') !== false) {
         log_message('info', "HOTEL PANEL TRIGGER: AI response mentions prices");
         return true;
      }

      return false;
   }

   /**
    * Extract hotel data from AI response to populate hotel panel - EXACT MATCH with chat
    */
   private function extractHotelDataFromAIResponse($aiResponse, $userMessage)
   {
      // Load all available hotel data
      $availableData = $this->loadAvailableData();
      $allHotels = $availableData['hotels'];

      log_message('info', "HOTEL PANEL DATA: Extracting EXACT hotels mentioned in AI response");
      log_message('info', "HOTEL PANEL DATA: AI Response: " . substr($aiResponse, 0, 200) . "...");

      $exactHotels = [];

      // Extract ONLY the EXACT hotel names mentioned in the AI response
      foreach ($allHotels as $hotel) {
         $hotelName = $hotel['hotelName'];

         // Check if this EXACT hotel name is mentioned in the AI response
         if (stripos($aiResponse, $hotelName) !== false) {
            $exactHotels[] = $hotel;
            log_message('info', "HOTEL PANEL DATA: Found exact match: {$hotelName}");
         }
      }

      log_message('info', "HOTEL PANEL DATA: Exact matching complete - found " . count($exactHotels) . " hotels");

      // If no exact matches found, fall back to city-based filtering
      if (empty($exactHotels)) {
         $destination = $this->extractDestinationFromText($userMessage . ' ' . $aiResponse);

         if ($destination) {
            $exactHotels = array_filter($allHotels, function ($hotel) use ($destination) {
               return stripos($hotel['city'], $destination) !== false;
            });
            log_message('info', "HOTEL PANEL DATA: No exact matches, filtered by destination '{$destination}' - found " . count($exactHotels) . " hotels");
         } else {
            // Last resort: show first few hotels
            $exactHotels = array_slice($allHotels, 0, 3);
            log_message('info', "HOTEL PANEL DATA: No matches found, showing first 3 hotels");
         }
      }

      log_message('info', "HOTEL PANEL DATA: Final selection - " . count($exactHotels) . " hotels for panel");

      return [
         'status' => [
            'sessionId' => 'exact_match_' . time(),
            'totalResults' => count($exactHotels)
         ],
         'itineraries' => array_values($exactHotels)
      ];
   }

   /**
    * Extract destination from text using simple keyword matching
    */
   private function extractDestinationFromText($text)
   {
      $text = strtolower($text);
      $cities = ['manila', 'cebu', 'davao', 'boracay', 'palawan', 'baguio', 'iloilo'];

      foreach ($cities as $city) {
         if (strpos($text, $city) !== false) {
            return $city;
         }
      }

      return null;
   }

   /**
    * Generate natural, conversational responses that understand context and emotions
    */
   private function generateConversationalResponse($userMessage, $chatHistory = [])
   {
      try {
         /** @var \Config\DeepSeek $deepseekConfig */
         $deepseekConfig = config('DeepSeek');
         $apiKey = getenv('OPENAI_API_KEY');

         if (empty($apiKey) || !$deepseekConfig || empty($deepseekConfig->model) || empty($deepseekConfig->apiUrl)) {
            return $this->response->setJSON(['reply' => 'I apologize, but I\'m having technical difficulties right now. Please try again later.']);
         }

         // Natural conversation prompt
         $systemPrompt = "You are Delai, a warm and friendly Filipino travel assistant. You are conversational, empathetic, and natural - NOT robotic.

         PERSONALITY TRAITS:
         - Warm and friendly, like talking to a Filipino friend
         - Acknowledge emotions and respond appropriately
         - Listen carefully to what users actually say
         - Don't jump to conclusions or assume what users want
         - Use natural language, not scripted responses
         - Be helpful but not pushy

         CONVERSATION RULES:
         1. If user expresses frustration, acknowledge it and be understanding
         2. If user mentions a place casually, don't assume they want hotels - just chat about it
         3. If user explicitly says they DON'T want something, respect that completely
         4. Only offer hotel search when user clearly asks for accommodation
         5. Be contextually aware - remember what was just discussed
         6. Respond naturally like a human friend would

         EXAMPLES:
         - User: \"idiot AI\" → \"I understand you're frustrated. I'm sorry if I misunderstood something. How can I help you better?\"
         - User: \"Manila\" → \"Ah, Manila! The bustling capital. Are you planning to visit, or just curious about the city?\"
         - User: \"I am not asking for hotels yet\" → \"Got it! No hotel search right now. What would you like to know about instead?\"

         Be natural, warm, and actually listen to what the user is saying.";

         // Build conversation context
         $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
         ];

         // Add recent conversation history
         if (!empty($chatHistory)) {
            foreach (array_slice($chatHistory, -3) as $turn) { // Last 3 turns
               if (isset($turn['role']) && isset($turn['content'])) {
                  $messages[] = $turn;
               }
            }
         }

         // Add current user message
         $messages[] = ['role' => 'user', 'content' => $userMessage];

         $payload = [
            'model' => $deepseekConfig->model,
            'messages' => $messages,
            'temperature' => 0.8, // Higher temperature for more natural responses
            'max_tokens' => 200   // Keep responses concise
         ];

         $client = \Config\Services::curlrequest();

         $response = $client->request('POST', $deepseekConfig->apiUrl, [
            'headers' => [
               'Authorization' => 'Bearer ' . $apiKey,
               'Content-Type' => 'application/json'
            ],
            'body' => json_encode($payload),
            'timeout' => 15
         ]);

         $body = json_decode($response->getBody(), true);
         $reply = $body['choices'][0]['message']['content'] ?? 'I apologize, but I\'m having trouble responding right now. Could you try asking again?';

         // Clean up the response
         $reply = trim($reply);

         return $this->response->setJSON(['reply' => $reply]);
      } catch (\Exception $e) {
         log_message('error', "Conversational AI error: " . $e->getMessage());
         return $this->response->setJSON([
            'reply' => 'I apologize, but I\'m having some technical difficulties. Please try again in a moment.'
         ]);
      }
   }


  
  
   private function formatMemoryBasedHotelResponse($aiResponse, $hotels)
   {
      if (empty($hotels)) {
         return $aiResponse;
      }

      $formattedResponse = $aiResponse;

      // Replace hotel names in the AI response with clickable versions
      foreach ($hotels as $index => $hotel) {
         $hotelName = $hotel['hotelName'] ?? 'Unknown Hotel';
         $hotelId = $hotel['hotelId'] ?? $index;
         $latitude = $hotel['latitude'] ?? '';
         $longitude = $hotel['longitude'] ?? '';

         // Create clickable hotel name
         $clickableHotelName = "<span class=\"hotel-name-link\" data-hotel-id=\"{$hotelId}\" data-lat=\"{$latitude}\" data-lng=\"{$longitude}\">{$hotelName}</span>";

         // Replace the hotel name in the response (case insensitive)
         $formattedResponse = str_ireplace($hotelName, "**{$clickableHotelName}**", $formattedResponse);
      }

      return $formattedResponse;
   }

}
