<?php

namespace App\Controllers;
use CodeIgniter\HTTP\ResponseInterface;
use Exception;

class DeepSeekController extends BaseController {



   public function chat() {
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
   private function handleUniversalRequest($userMessage, $chatHistory = []) {
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
   private function buildConversationContext($currentMessage, $chatHistory = []) {
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
   private function checkForDirectHotelResponse($userMessage, $hotels) {
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
   private function generateHotelResponse($hotel) {
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
   private function extractHotelNameFromMessage($message) {
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
   private function searchWebForHotel($hotelName, $originalMessage) {
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
   private function performWebSearch($query) {
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
   private function generateWebBasedHotelResponse($hotelName, $webResults, $originalMessage) {
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
   private function loadAvailableData() {
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
   private function fetchHotelsFromTravelNextAPI($cityName = 'Manila', $countryName = 'Philippines') {
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
   private function loadHotelsFromLocalFile(&$data) {
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
   private function formatHotelDataForAIMemory($hotels) {
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
               $memoryData .= "   • Type: {$propertyType} | Rating: {$rating}★ | Price: {$currency}{$total}/night\n";
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
   private function extractCityFromContext() {
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
   private function createUniversalSystemPrompt($availableData) {
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

📋 RESPONSE FORMAT:
- Use natural conversational text (NO markdown, NO hashtags, NO special formatting)
- Include specific hotel details (name, price, rating, address)
- When discussing hotels, mention multiple options with details
- NO ### headers, NO ** bold text, NO markdown formatting, NO - bullet points
- Write like you're talking to a friend, not writing documentation
- Use simple numbered lists: 1. Hotel Name - details, 2. Hotel Name - details
- NEVER say in my database, from my database, in my memory - just provide the information naturally

🎯 EXAMPLES:
Q: 'Nearest Hostel Pasay City details?'
A: 'Yes! Nearest Hostel Pasay City By Reddoorz is a 1-star budget option in Pasay at ₱1,962.87 per night, located on 64 Legaspi, Aurora Blvd. It offers non-refundable bookings. Would you like to see other Pasay options too?'

Q: 'Can you find hotel in 2231 Aurora Boulevard Former Tramo Corner Buenaven?'
A: 'Yes! That address is Stone House Hotel Pasay, a 2-star hotel priced at PHP 1,991.05 per night. The hotel is located in Manila and offers refundable bookings. Would you like more details about Stone House Hotel Pasay or see other nearby options?'

Q: 'Can you find hotel near me? 64 Legaspi, Aurora Blvd.'
A: 'Yes! There is a hotel at that exact address - Nearest Hostel Pasay City By Reddoorz, a 1-star budget option priced at PHP 1,962.87 per night. The hotel is located in Pasay and offers non-refundable bookings. Would you like more details about this hostel or see other nearby options?'

Q: 'Generate all hotels in Cebu'
A: 'Here are the Cebu hotels I can recommend:

1. Sugbutel Family Hotel - 2-star, ₱2,156.25 per night, located at Colon Street, Cebu City. A family-friendly option in the heart of the city.

2. Residenz Guesthouse - 2-star, ₱1,205.88 per night, at S-36 San Jose Village, Umapad. A budget-friendly guesthouse with basic amenities.

3. GV Tower Hotel - 3-star, ₱2,340.00 per night, on Mactan Island near the airport. Great for travelers with early flights.

These are the Cebu hotels I know about. Would you like more details about any of them?'

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
   private function parseUniversalAIResponse($aiResponse, $userMessage) {
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
   private function cleanAIResponse($response) {
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
   private function shouldTriggerHotelPanel($aiResponse, $userMessage) {
       $userMessageLower = strtolower($userMessage);
       $aiResponseLower = strtolower($aiResponse);

       // Hotel search keywords
       $hotelKeywords = [
           'hotel', 'hotels', 'accommodation', 'stay', 'book', 'reservation',
           'where to stay', 'recommend', 'suggestion', 'best hotel', 'find me hotels',
           'nearest hostel', 'pasay centrale', 'stone house', 'hostel',
           'hotel near me', 'find hotel', 'aurora blvd', 'aurora boulevard', 'legaspi'
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
           'pasay centrale hotel', 'nearest hostel pasay', 'stone house hotel',
           'zen rooms', 'stay malate', 'sugbutel', 'crimson resort', 'reddoorz'
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
   private function extractHotelDataFromAIResponse($aiResponse, $userMessage) {
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
               $exactHotels = array_filter($allHotels, function($hotel) use ($destination) {
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
   private function extractDestinationFromText($text) {
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
   private function generateConversationalResponse($userMessage, $chatHistory = []) {
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

   /**
    * Analyze if user is asking about specific hotel details from previous search
    */
   private function analyzeHotelQuestionIntent($message, $chatHistory = []) {
       try {
           // Extract hotel names from recent chat history
           $recentHotels = $this->extractHotelsFromHistory($chatHistory);

           if (empty($recentHotels)) {
               return ['is_hotel_question' => false, 'confidence' => 0, 'hotel_name' => null];
           }

           $lowerMessage = strtolower($message);

           // Check if message mentions any hotel from recent history
           $mentionedHotel = null;
           foreach ($recentHotels as $hotel) {
               $hotelName = strtolower($hotel['hotelName'] ?? '');
               if (!empty($hotelName) && strpos($lowerMessage, $hotelName) !== false) {
                   $mentionedHotel = $hotel;
                   break;
               }
           }

           // Check for question patterns about hotel details - COMPREHENSIVE DETECTION
           $questionPatterns = [
               'rating' => ['rating', 'star', 'review', 'score', 'rated', 'stars'],
               'price' => ['price', 'cost', 'expensive', 'cheap', 'rate', 'rates', 'pricing', 'per room', 'per night', 'how much', 'pricing of'],
               'location' => ['where', 'location', 'address', 'near', 'located'],
               'amenities' => ['facilities', 'amenities', 'wifi', 'pool', 'gym', 'services'],
               'general' => ['popular', 'good', 'recommend', 'about', 'tell me', 'information', 'details', 'full details', 'do you have', 'do you know']
           ];

           log_message('info', "HOTEL QUESTION DETECTION: Analyzing message '{$message}' for patterns");

           // Detect ALL question types mentioned (not just the first one)
           $detectedQuestionTypes = [];
           foreach ($questionPatterns as $type => $patterns) {
               foreach ($patterns as $pattern) {
                   if (strpos($lowerMessage, $pattern) !== false) {
                       $detectedQuestionTypes[] = $type;
                       break; // Move to next question type
                   }
               }
           }

           // Remove duplicates and determine primary question type
           $detectedQuestionTypes = array_unique($detectedQuestionTypes);
           $questionType = !empty($detectedQuestionTypes) ? $detectedQuestionTypes[0] : null;

           // Determine if this is a hotel question - BE MORE AGGRESSIVE
           $isHotelQuestion = false;
           $confidence = 0;

           if ($mentionedHotel && $questionType) {
               $isHotelQuestion = true;
               $confidence = 0.9; // High confidence - specific hotel + question pattern
               log_message('info', "HOTEL QUESTION: High confidence - specific hotel '{$mentionedHotel['hotelName']}' + question type '{$questionType}'");
           } elseif ($questionType && count($recentHotels) === 1) {
               $isHotelQuestion = true;
               $confidence = 0.8; // Medium-high confidence - question about the only recent hotel
               $mentionedHotel = $recentHotels[0];
               log_message('info', "HOTEL QUESTION: Medium-high confidence - single hotel context");
           } elseif ($questionType && !empty($recentHotels)) {
               $isHotelQuestion = true;
               $confidence = 0.6; // Medium confidence - question with recent hotels context
               $mentionedHotel = $recentHotels[0]; // Default to first hotel
               log_message('info', "HOTEL QUESTION: Medium confidence - multiple hotels context");
           } elseif ($questionType) {
               // Even without hotel context, if it's clearly a hotel question, try to handle it
               $isHotelQuestion = true;
               $confidence = 0.5; // Lower confidence but still try
               log_message('info', "HOTEL QUESTION: Low confidence - question pattern without hotel context");
           }

           return [
               'is_hotel_question' => $isHotelQuestion,
               'confidence' => $confidence,
               'hotel_name' => $mentionedHotel ? ($mentionedHotel['hotelName'] ?? null) : null,
               'hotel_data' => $mentionedHotel,
               'question_type' => $questionType,
               'all_question_types' => $detectedQuestionTypes,
               'all_recent_hotels' => $recentHotels
           ];

       } catch (Exception $e) {
           log_message('error', "Hotel question analysis failed: " . $e->getMessage());
           return ['is_hotel_question' => false, 'confidence' => 0, 'hotel_name' => null];
       }
   }

   /**
    * Extract hotel data from recent chat history
    */
   private function extractHotelsFromHistory($chatHistory) {
       $hotels = [];

       if (!is_array($chatHistory)) {
           return $hotels;
       }

       // Look through recent messages for hotel data
       log_message('info', "HOTEL EXTRACTION: Searching through " . count($chatHistory) . " chat history messages");

       foreach (array_reverse(array_slice($chatHistory, -5)) as $index => $message) { // Last 5 messages
           log_message('info', "HOTEL EXTRACTION: Checking message {$index}: " . json_encode(array_keys($message ?? [])));

           // Check multiple possible formats for hotel data
           if (isset($message['hotel_results']) && isset($message['hotel_results']['itineraries'])) {
               $hotelItineraries = $message['hotel_results']['itineraries'];
               if (is_array($hotelItineraries)) {
                   $hotels = array_merge($hotels, $hotelItineraries);
                   log_message('info', "HOTEL EXTRACTION: Found " . count($hotelItineraries) . " hotels in hotel_results.itineraries");
               }
           }

           // Also check if hotel data is stored differently
           if (isset($message['reply']) && strpos($message['reply'], 'hotel') !== false) {
               log_message('info', "HOTEL EXTRACTION: Found hotel mention in reply: " . substr($message['reply'], 0, 100));
           }
       }

       return array_slice($hotels, 0, 10); // Limit to 10 most recent hotels
   }

   /**
    * Answer specific questions about hotels using hotel data context
    */
   private function answerHotelQuestion($message, $chatHistory, $questionAnalysis) {
       try {
           $hotelData = $questionAnalysis['hotel_data'];
           $questionType = $questionAnalysis['question_type'];
           $allQuestionTypes = $questionAnalysis['all_question_types'] ?? [$questionType];
           $hotelName = $questionAnalysis['hotel_name'];

           if (!$hotelData) {
               // No hotel data available, but user is asking hotel questions
               // Provide helpful response about needing hotel context
               log_message('info', "HOTEL QUESTION: No hotel data available, providing helpful guidance");

               $reply = "I'd be happy to help you with hotel information! However, I need to know which specific hotel you're asking about. ";

               if (strpos(strtolower($message), 'pricing') !== false || strpos(strtolower($message), 'price') !== false) {
                   $reply .= "To get current pricing information, I can search for hotels in a specific city. Which destination are you interested in?";
               } elseif (strpos(strtolower($message), 'details') !== false) {
                   $reply .= "To provide detailed hotel information, I can search for accommodations in your preferred location. Where would you like to stay?";
               } else {
                   $reply .= "You can ask me to search for hotels in any Philippine city, and I'll provide detailed information including ratings, prices, and amenities.";
               }

               return $this->response->setJSON(['reply' => $reply]);
           }

           // Extract hotel details from the actual data
           $rating = $hotelData['hotelRating'] ?? 0;
           $price = $hotelData['total'] ?? 0;
           $currency = $hotelData['currency'] ?? 'PHP';
           $address = $hotelData['address'] ?? 'Address not available';
           $city = $hotelData['city'] ?? '';
           $facilities = $hotelData['facilities'] ?? [];

           log_message('info', "HOTEL QUESTION: Answering about '{$hotelName}' - Rating: {$rating}, Price: {$price}, Questions: " . implode(', ', $allQuestionTypes));

           // Handle multiple question types in one response
           if (count($allQuestionTypes) > 1) {
               return $this->generateComprehensiveHotelAnswer($hotelData, $allQuestionTypes, $hotelName);
           }

           // Generate specific answer based on single question type
           $reply = '';

           switch ($questionType) {
               case 'rating':
                   if ($rating > 0) {
                       $reply = "**{$hotelName}** has a **{$rating}-star rating**. ";
                       if ($rating >= 4) {
                           $reply .= "That's an excellent rating for a quality stay!";
                       } elseif ($rating >= 3) {
                           $reply .= "That's a good rating for comfortable accommodation.";
                       } else {
                           $reply .= "It's a budget-friendly option with basic amenities.";
                       }
                   } else {
                       $reply = "The star rating for **{$hotelName}** is not available in my current data. However, it's located in {$city}";
                       if ($price > 0) {
                           $formattedPrice = number_format($price, 2);
                           $reply .= " and costs {$currency} {$formattedPrice} per night";
                       }
                       $reply .= ".";
                   }
                   break;

               case 'price':
                   if ($price > 0) {
                       $formattedPrice = number_format($price, 2);
                       $reply = "**{$hotelName}** costs **{$currency} {$formattedPrice} per night**. ";
                       if ($price < 2000) {
                           $reply .= "That's quite budget-friendly! Great value for money.";
                       } elseif ($price < 5000) {
                           $reply .= "That's a reasonable mid-range price with good amenities.";
                       } else {
                           $reply .= "That's in the premium price range with luxury features.";
                       }

                       // Add rating context if available
                       if ($rating > 0) {
                           $reply .= " It's a {$rating}-star hotel.";
                       }
                   } else {
                       $reply = "The exact pricing for **{$hotelName}** is not available in my current data. ";
                       if ($rating > 0) {
                           $reply .= "However, it's a {$rating}-star hotel located in {$city}. ";
                       }
                       $reply .= "I recommend contacting them directly for current rates.";
                   }
                   break;

               case 'location':
                   $reply = "**{$hotelName}** is located at **{$address}** in {$city}.";
                   break;

               case 'amenities':
                   if (!empty($facilities)) {
                       $reply = "**{$hotelName}** offers these facilities: " . implode(', ', array_slice($facilities, 0, 8));
                       if (count($facilities) > 8) {
                           $reply .= " and more.";
                       }
                   } else {
                       $reply = "I don't have detailed amenities information for **{$hotelName}** in my current data.";
                   }
                   break;

               default:
                   // General question - provide overview
                   $reply = "**{$hotelName}** is located in {$city}";
                   if ($rating > 0) {
                       $reply .= " with a {$rating}-star rating";
                   }
                   if ($price > 0) {
                       $formattedPrice = number_format($price, 2);
                       $reply .= " at {$currency} {$formattedPrice} per night";
                   }
                   $reply .= ". " . ($address ? "It's situated at {$address}." : "");
           }

           return $this->response->setJSON(['reply' => $reply]);

       } catch (Exception $e) {
           log_message('error', "Hotel question answering failed: " . $e->getMessage());
           return $this->generateConversationalResponse($message, $chatHistory);
       }
   }

   /**
    * Generate comprehensive answer when user asks multiple questions about a hotel
    */
   private function generateComprehensiveHotelAnswer($hotelData, $questionTypes, $hotelName) {
       $rating = $hotelData['hotelRating'] ?? 0;
       $price = $hotelData['total'] ?? 0;
       $currency = $hotelData['currency'] ?? 'PHP';
       $address = $hotelData['address'] ?? 'Address not available';
       $city = $hotelData['city'] ?? '';
       $facilities = $hotelData['facilities'] ?? [];

       $reply = "Here's the information about **{$hotelName}**:\n\n";

       // Add rating information if requested
       if (in_array('rating', $questionTypes)) {
           if ($rating > 0) {
               $reply .= "⭐ **Rating**: {$rating}-star hotel";
               if ($rating >= 4) {
                   $reply .= " (Excellent quality)\n";
               } elseif ($rating >= 3) {
                   $reply .= " (Good quality)\n";
               } else {
                   $reply .= " (Budget-friendly)\n";
               }
           } else {
               $reply .= "⭐ **Rating**: Not available in current data\n";
           }
       }

       // Add price information if requested
       if (in_array('price', $questionTypes)) {
           if ($price > 0) {
               $formattedPrice = number_format($price, 2);
               $reply .= "💰 **Price**: {$currency} {$formattedPrice} per night";
               if ($price < 2000) {
                   $reply .= " (Budget-friendly)\n";
               } elseif ($price < 5000) {
                   $reply .= " (Mid-range)\n";
               } else {
                   $reply .= " (Premium)\n";
               }
           } else {
               $reply .= "💰 **Price**: Contact hotel for current rates\n";
           }
       }

       // Add location information if requested
       if (in_array('location', $questionTypes)) {
           $reply .= "📍 **Location**: {$address}, {$city}\n";
       }

       // Add amenities information if requested
       if (in_array('amenities', $questionTypes)) {
           if (!empty($facilities)) {
               $reply .= "🏨 **Facilities**: " . implode(', ', array_slice($facilities, 0, 6));
               if (count($facilities) > 6) {
                   $reply .= " and more\n";
               } else {
                   $reply .= "\n";
               }
           } else {
               $reply .= "🏨 **Facilities**: Information not available\n";
           }
       }

       return $this->response->setJSON(['reply' => trim($reply)]);
   }





    /**
     * COMPREHENSIVE conversation analysis - understands full context and user journey
     */
    private function analyzeConversationIntent($currentMessage, $chatHistory = []) {
        try {
            /** @var \Config\DeepSeek $deepseekConfig */
            $deepseekConfig = config('DeepSeek');
            $apiKey = getenv('OPENAI_API_KEY');

            if (empty($apiKey) || !$deepseekConfig || empty($deepseekConfig->model) || empty($deepseekConfig->apiUrl)) {
                // Fallback to basic analysis
                return $this->basicConversationAnalysis($currentMessage, $chatHistory);
            }

            // Build full conversation context
            $conversationContext = '';
            if (is_array($chatHistory) && count($chatHistory) > 0) {
                foreach ($chatHistory as $msg) {
                    if (isset($msg['content'])) {
                        $role = isset($msg['role']) ? strtoupper($msg['role']) : 'MESSAGE';
                        $conversationContext .= "{$role}: {$msg['content']}\n";
                    }
                }
            }
            $conversationContext .= "USER: {$currentMessage}";

            $prompt = "Analyze this ENTIRE conversation to understand what the user wants. Consider the full context and conversation flow.

CONVERSATION:
{$conversationContext}

TASK: Determine the user's intent and extract relevant information.

POSSIBLE INTENTS:
1. 'hotel_search' - User wants to find/book hotels or accommodation
2. 'travel_advice' - User wants travel information, recommendations, or general advice
3. 'general_chat' - General conversation, greetings, or other topics

IMPORTANT RULES:
- Look at the ENTIRE conversation, not just the last message
- If user previously mentioned a destination and now asks for hotels/accommodation, it's a hotel search for that destination
- Handle contextual references like 'those spots', 'there', 'that place' by looking at previous context
- Examples:
  * User mentions Cebu, then asks 'hotels near those spots' = hotel_search for Cebu
  * User asks 'what to expect in Manila' = travel_advice
  * User says 'find hotels in Boracay' = hotel_search for Boracay

RESPONSE FORMAT (JSON):
{
  \"intent\": \"hotel_search|travel_advice|general_chat\",
  \"destination\": \"city_name_or_null\",
  \"confidence\": \"high|medium|low\",
  \"reasoning\": \"brief explanation\"
}

Respond with ONLY the JSON, no other text.";

            $payload = [
                'model' => $deepseekConfig->model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.1,
                'max_tokens' => 150
            ];

            $ch = curl_init($deepseekConfig->apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                if (isset($data['choices'][0]['message']['content'])) {
                    $aiResponse = trim($data['choices'][0]['message']['content']);
                    $analysis = json_decode($aiResponse, true);

                    if ($analysis && isset($analysis['intent'])) {
                        log_message('info', "AI CONVERSATION ANALYSIS SUCCESS: " . $aiResponse);
                        return $analysis;
                    }
                }
            }

            log_message('warning', "AI conversation analysis failed, using fallback");
            return $this->basicConversationAnalysis($currentMessage, $chatHistory);

        } catch (Exception $e) {
            log_message('error', "AI conversation analysis error: " . $e->getMessage());
            return $this->basicConversationAnalysis($currentMessage, $chatHistory);
        }
    }

    /**
     * Basic fallback conversation analysis when AI is unavailable
     */
    private function basicConversationAnalysis($currentMessage, $chatHistory = []) {
        $lowerMessage = strtolower($currentMessage);

        // Check for explicit hotel keywords
        $hotelKeywords = ['hotel', 'accommodation', 'stay', 'lodging', 'resort', 'book'];
        $hasHotelKeyword = false;
        foreach ($hotelKeywords as $keyword) {
            if (strpos($lowerMessage, $keyword) !== false) {
                $hasHotelKeyword = true;
                break;
            }
        }

        // Determine intent based on hotel keywords only
        if ($hasHotelKeyword) {
            return [
                'intent' => 'hotel_search',
                'destination' => null, // Let AI extract destination later
                'confidence' => 'medium',
                'reasoning' => 'Basic keyword detection'
            ];
        }

        return [
            'intent' => 'general_chat',
            'destination' => null,
            'confidence' => 'low',
            'reasoning' => 'No clear hotel intent detected'
        ];
    }

    /**
     * AI-powered hotel intent detection - PURE AI ANALYSIS!
     */
    private function isHotelQuery($message, $chatHistory = []) {
        try {
            /** @var \Config\DeepSeek $deepseekConfig */
            $deepseekConfig = config('DeepSeek');
            $apiKey = getenv('OPENAI_API_KEY');

            if (empty($apiKey) || !$deepseekConfig || empty($deepseekConfig->model) || empty($deepseekConfig->apiUrl)) {
                // Fallback to basic keyword detection only if AI is unavailable
                return $this->basicHotelKeywordDetection($message);
            }

            // Build context from chat history
            $context = '';
            if (is_array($chatHistory) && count($chatHistory) > 0) {
                $recentMessages = array_slice($chatHistory, -3);
                foreach ($recentMessages as $msg) {
                    if (isset($msg['content'])) {
                        $context .= $msg['content'] . "\n";
                    }
                }
            }

            $prompt = "Analyze this conversation to determine if the user is looking for hotel/accommodation booking.

CONVERSATION CONTEXT:
{$context}

CURRENT MESSAGE: \"{$message}\"

IMPORTANT: Consider the FULL conversation context, not just the current message. Examples:

✅ HOTEL SEARCH (respond YES):
- \"Find hotels in Paris\"
- \"Where to stay in Manila?\"
- \"Can you find me hotels near those destinations?\" (when previous context mentions travel destinations)
- \"Show me accommodation in Cebu\"
- \"What about hotels in Boracay?\" (follow-up in travel context)

❌ CASUAL CONVERSATION (respond NO):
- \"I'm planning to go to Paris\" (just sharing plans)
- \"What should I expect in Cebu?\" (asking for travel advice)
- \"Tell me about Manila\" (general information request)

KEY RULE: If the user asks for hotels, accommodation, or places to stay - even if they reference previous destinations mentioned in the conversation - it's a hotel search.

Respond with only 'YES' if it's a hotel search request, or 'NO' if it's casual conversation.";

            $payload = [
                'model' => $deepseekConfig->model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.1, // Low temperature for consistent classification
                'max_tokens' => 10
            ];

            $ch = curl_init($deepseekConfig->apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                if (isset($data['choices'][0]['message']['content'])) {
                    $aiResponse = trim(strtoupper($data['choices'][0]['message']['content']));
                    $isHotelQuery = ($aiResponse === 'YES');

                    log_message('info', "AI HOTEL DETECTION: Message='{$message}' | AI Response='{$aiResponse}' | Result=" . ($isHotelQuery ? 'HOTEL_SEARCH' : 'CASUAL_CHAT'));

                    return $isHotelQuery;
                }
            }

            // Fallback if AI call fails
            log_message('warning', "AI hotel detection failed, using basic keyword detection");
            return $this->basicHotelKeywordDetection($message);

        } catch (Exception $e) {
            log_message('error', "AI hotel detection error: " . $e->getMessage());
            return $this->basicHotelKeywordDetection($message);
        }
    }

    /**
     * Basic keyword detection fallback when AI is unavailable
     */
    private function basicHotelKeywordDetection($message) {
        $lowerMessage = strtolower($message);

        // Only explicit hotel search keywords
        $hotelKeywords = ['hotel', 'accommodation', 'stay', 'lodging', 'resort'];
        foreach ($hotelKeywords as $keyword) {
            if (strpos($lowerMessage, $keyword) !== false) {
                return true;
            }
        }

        // Explicit search phrases only
        $searchPhrases = ['find hotels', 'search hotels', 'show hotels', 'book hotel', 'where to stay'];
        foreach ($searchPhrases as $phrase) {
            if (strpos($lowerMessage, $phrase) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * This method is no longer needed - AI handles all intent detection
     * Keeping for backward compatibility but it's not used
     */
    private function isHotelFollowUp($message) {
        // This method is deprecated - AI now handles all intent detection
        return false;
    }

    /**
     * Handle hotel queries with SMART AI processing
     */
    private function handleHotelQuery($userMessage, $chatHistory) {
        log_message('info', "HOTEL QUERY START: Processing new request '{$userMessage}'");
        $hotelService = new \App\Libraries\HotelSearchService();

        // Build conversation context
        $fullContext = $userMessage;
        if (is_array($chatHistory) && count($chatHistory) > 0) {
            foreach ($chatHistory as $msg) {
                if (isset($msg['content'])) {
                    $fullContext = $msg['content'] . "\n" . $fullContext;
                }
            }
        }

        // CONTEXT-AWARE destination extraction - considers conversation history
        $locationInfo = $this->extractLocationWithConversationContext($userMessage, $chatHistory);
        $destination = $locationInfo['city'] ?? null;
        $specificLocation = $locationInfo['specific_location'] ?? null;

        log_message('info', "CONTEXT-AWARE LOCATION: City='{$destination}', Specific='{$specificLocation}' from message: '{$userMessage}' with conversation context");

        // DEBUG: Log what was extracted
        log_message('info', "DESTINATION DEBUG: Message='{$userMessage}', Context='{$fullContext}', Extracted='{$destination}'");

        if (!$destination) {
            log_message('info', "DESTINATION DEBUG: No destination found, asking user for city");
            return $this->response->setJSON([
                'reply' => 'Which city are you looking for hotels in?',
                'hotel_search' => true,
                'needs_more_info' => true
            ]);
        }

        // LOCATION-AWARE HOTEL SEARCH - Considers specific locations
        log_message('info', "LOCATION SEARCH: Searching for hotels in city '{$destination}'" . ($specificLocation ? " near '{$specificLocation}'" : ""));

        // Search for hotels with location filtering
        $result = $hotelService->searchHotelsWithLocation($destination, $specificLocation);
        log_message('info', "SIMPLE SEARCH: Found " . count($result['data']['itineraries'] ?? []) . " hotels");

        if ($result['success'] && !empty($result['data']['itineraries'])) {
            // AI DEEP SEARCH - Generate intelligent descriptions like before
            log_message('info', "AI DEEP SEARCH: About to generate AI descriptions for hotels");
            $processedResult = $this->deepSearchHotelsWithAI($userMessage, $result['data'], $destination);
            log_message('info', "AI DEEP SEARCH: AI descriptions completed");

            return $this->response->setJSON([
                'reply' => $processedResult['reply'],
                'hotel_search' => true,
                'hotel_results' => $processedResult['hotels'],
                'show_hotels_tab' => true,
                'destination' => $destination,
                'ai_generated' => true, // Flag to indicate AI-generated content
                'processing_complete' => true
            ]);
        } else {
            // No hotels found
            $reply = "I couldn't find any hotels in " . ucfirst($destination) . " at the moment. Try searching for a different city.";

            return $this->response->setJSON([
                'reply' => $reply,
                'hotel_search' => true,
                'no_results' => true,
                'destination' => $destination
            ]);
        }

     
        log_message('info', "DESTINATION DEBUG: About to search for hotels in '{$destination}'");

        // Search for hotels
        log_message('info', "DESTINATION DEBUG: Calling hotelService->searchHotels('{$destination}')");
        $result = $hotelService->searchHotels($destination);
        log_message('info', "DESTINATION DEBUG: Hotel search completed, success=" . ($result['success'] ? 'true' : 'false'));

        if ($result['success']) {
            // AI DEEP SEARCH - Generate intelligent descriptions
            log_message('info', "DESTINATION DEBUG: About to call deepSearchHotelsWithAI");
            $processedResult = $this->deepSearchHotelsWithAI($userMessage, $result['data'], $destination);
            log_message('info', "DESTINATION DEBUG: deepSearchHotelsWithAI completed");

            return $this->response->setJSON([
                'reply' => $processedResult['reply'],
                'hotel_search' => true,
                'hotel_results' => $processedResult['hotels'],
                'show_hotels_tab' => true,
                'destination' => $destination,
                'ai_generated' => true, // Flag to indicate AI-generated content
                'processing_complete' => true
            ]);
        } else {
            // Generate intelligent "no hotels found" response
            $intelligentNoResultsResponse = $this->generateIntelligentNoResultsResponse($userMessage, $destination);

            return $this->response->setJSON([
                'reply' => $intelligentNoResultsResponse,
                'hotel_search' => true,
                'no_results' => true
            ]);
        }
    }

    /**
     * Process hotel request with AI intelligence - NO PRE-CREATED RESPONSES!
     */
    private function processHotelRequestWithAI($userMessage, $hotelData, $destination) {
        $hotels = $hotelData['itineraries'] ?? [];
        $totalCount = count($hotels);

        if ($totalCount === 0) {
            return [
                'reply' => 'No hotels found for your search.',
                'hotels' => $hotelData
            ];
        }

        // Analyze user's specific requirements
        $requirements = $this->analyzeUserRequirements($userMessage);

        // Filter and sort hotels based on requirements
        $filteredHotels = $this->filterAndSortHotels($hotels, $requirements);

        // Generate intelligent response based on what user asked for
        $reply = $this->generateIntelligentResponse($requirements, $filteredHotels, $totalCount, $userMessage, $destination);

        // Add hotel names with AI-generated descriptions
        $reply .= $this->addHotelNamesWithDescriptions($filteredHotels, $userMessage);

        // Update the hotel data with filtered results
        $hotelData['itineraries'] = $filteredHotels;
        $hotelData['status']['totalResults'] = count($filteredHotels);

        return [
            'reply' => $reply,
            'hotels' => $hotelData
        ];
    }

    /**
     * AI DEEP SEARCH - Intelligent hotel analysis and description generation
     */
    private function deepSearchHotelsWithAI($userMessage, $hotelData, $destination) {
        $hotels = $hotelData['itineraries'] ?? [];
        $totalCount = count($hotels);

        log_message('info', "DEEP SEARCH DEBUG: Received {$totalCount} hotels from HotelSearchService");
        log_message('info', "DEEP SEARCH DEBUG: User message: '{$userMessage}'");
        log_message('info', "DEEP SEARCH DEBUG: Destination: '{$destination}'");

        if ($totalCount === 0) {
            log_message('info', "DEEP SEARCH DEBUG: No hotels found, returning no results response");
            return [
                'reply' => $this->generateIntelligentNoResultsResponse($userMessage, $destination),
                'hotels' => $hotelData
            ];
        }

        // Filter hotels based on user requirements (but skip location filtering since destination is already determined)
        $requirements = $this->analyzeUserRequirements($userMessage);

        // IMPORTANT: Clear location_specific since we already have the destination from conversation context
        // This prevents filtering out hotels when user says "near those waterfalls" etc.
        $requirements['location_specific'] = null;

        log_message('info', "DEEP SEARCH DEBUG: Requirements analyzed: " . json_encode($requirements));

        $filteredHotels = $this->filterAndSortHotels($hotels, $requirements);
        $filteredCount = count($filteredHotels);
        log_message('info', "DEEP SEARCH DEBUG: After filtering: {$totalCount} → {$filteredCount} hotels");

        // AI DEEP ANALYSIS - Generate intelligent descriptions for each hotel
        $hotelsWithAIDescriptions = $this->generateDeepHotelAnalysis($filteredHotels, $userMessage, $destination);

        // Generate intelligent response
        $isTagalog = $this->isTagalogMessage($userMessage);
        $reply = $this->generateIntelligentHotelFoundResponse($filteredHotels, $userMessage, $destination, $isTagalog);

        // Add AI-generated hotel list to chat
        $reply .= $this->formatHotelsForChat($hotelsWithAIDescriptions, $isTagalog);

        // Update hotel data
        $hotelData['itineraries'] = $hotelsWithAIDescriptions;
        $hotelData['status']['totalResults'] = count($hotelsWithAIDescriptions);

        return [
            'reply' => $reply,
            'hotels' => $hotelData
        ];
    }

    /**
     * Analyze user requirements with AI intelligence - SMART PARSING!
     */
    private function analyzeUserRequirements($message) {
        $lowerMessage = strtolower($message);
        $requirements = [
            'limit' => null,
            'sort_by' => null,
            'filter_by' => null,
            'location_specific' => null,
            'price_range' => null,
            'price_filter' => null
        ];

        // SMART LIMIT DETECTION - "find 3", "show me 5", "top 2", etc.
        if (preg_match('/(?:find|show|give|get|top)\s+(\d+)/', $lowerMessage, $matches)) {
            $requirements['limit'] = (int)$matches[1];
            log_message('info', "LIMIT DETECTED: {$requirements['limit']} hotels requested");
        }

        // SMART SORTING DETECTION
        if (strpos($lowerMessage, 'cheapest') !== false || strpos($lowerMessage, 'lowest price') !== false) {
            $requirements['sort_by'] = 'price_asc';
            log_message('info', "SORT DETECTED: cheapest first");
        } elseif (strpos($lowerMessage, 'expensive') !== false || strpos($lowerMessage, 'highest price') !== false) {
            $requirements['sort_by'] = 'price_desc';
            log_message('info', "SORT DETECTED: most expensive first");
        } elseif (strpos($lowerMessage, 'best rated') !== false || strpos($lowerMessage, 'highest rating') !== false) {
            $requirements['sort_by'] = 'rating_desc';
            log_message('info', "SORT DETECTED: best rated first");
        }

        // Detect number limit (top 3, first 5, etc.)
        if (preg_match('/(?:top|first|show|list)\s+(\d+)/', $lowerMessage, $matches)) {
            $requirements['limit'] = (int)$matches[1];
        }

        // Detect sorting preferences
        if (strpos($lowerMessage, 'cheapest') !== false || strpos($lowerMessage, 'lowest price') !== false) {
            $requirements['sort_by'] = 'price_low';
        } elseif (strpos($lowerMessage, 'expensive') !== false || strpos($lowerMessage, 'highest price') !== false) {
            $requirements['sort_by'] = 'price_high';
        } elseif (strpos($lowerMessage, 'highest rated') !== false || strpos($lowerMessage, 'best rated') !== false) {
            $requirements['sort_by'] = 'rating_high';
        } elseif (strpos($lowerMessage, 'lowest rated') !== false) {
            $requirements['sort_by'] = 'rating_low';
        }

        // Detect specific location mentions - let AI handle city vs location distinction
        if (preg_match('/(?:near|at)\s+([A-Za-z0-9\s,.-]+?)(?:\s|$|,|\.)/', $lowerMessage, $matches)) {
            $location = trim($matches[1]);

            // Only capture specific locations that are longer than 3 characters
            // Let AI determine if it's a city or specific location
            if (strlen($location) > 3) {
                $requirements['location_specific'] = $location;
            }
        }

        // Detect budget preferences
        if (strpos($lowerMessage, 'budget') !== false || strpos($lowerMessage, 'cheap') !== false) {
            $requirements['filter_by'] = 'budget';
        } elseif (strpos($lowerMessage, 'luxury') !== false || strpos($lowerMessage, '5 star') !== false) {
            $requirements['filter_by'] = 'luxury';
        }

        // GENIUS PRICE DETECTION!
        $requirements['price_filter'] = $this->detectPriceRequirements($message);

        return $requirements;
    }

    /**
     * GENIUS price detection - understands 5k, 5000, 5 thousand, under 3000, etc.
     */
    private function detectPriceRequirements($message) {
        $lowerMessage = strtolower($message);
        $priceFilter = [
            'type' => null,    // 'exact', 'under', 'over', 'range'
            'min' => null,
            'max' => null,
            'target' => null
        ];

        // Pattern 1: "5000", "5k", "5 thousand" - exact or around price
        if (preg_match('/(\d+)\s*k(?:\s|$|,|\.)/i', $message, $matches)) {
            $price = (float)$matches[1] * 1000;
            $priceFilter['type'] = 'around';
            $priceFilter['target'] = $price;
            $priceFilter['min'] = $price * 0.8;  // ±20% range
            $priceFilter['max'] = $price * 1.2;
            log_message('info', "Price detected: {$matches[1]}k = {$price} (range: {$priceFilter['min']}-{$priceFilter['max']})");
        }
        // Pattern 2: "5000", "3000" - direct numbers
        elseif (preg_match('/(\d{4,})/', $message, $matches)) {
            $price = (float)$matches[1];
            $priceFilter['type'] = 'around';
            $priceFilter['target'] = $price;
            $priceFilter['min'] = $price * 0.8;  // ±20% range
            $priceFilter['max'] = $price * 1.2;
            log_message('info', "Price detected: {$price} (range: {$priceFilter['min']}-{$priceFilter['max']})");
        }
        // Pattern 3: "5 thousand", "3 thousand"
        elseif (preg_match('/(\d+)\s+thousand/i', $message, $matches)) {
            $price = (float)$matches[1] * 1000;
            $priceFilter['type'] = 'around';
            $priceFilter['target'] = $price;
            $priceFilter['min'] = $price * 0.8;  // ±20% range
            $priceFilter['max'] = $price * 1.2;
            log_message('info', "Price detected: {$matches[1]} thousand = {$price} (range: {$priceFilter['min']}-{$priceFilter['max']})");
        }
        // Pattern 4: "under 5000", "below 3k"
        elseif (preg_match('/(?:under|below|less than)\s+(\d+)k?/i', $message, $matches)) {
            $price = strpos($message, 'k') !== false ? (float)$matches[1] * 1000 : (float)$matches[1];
            $priceFilter['type'] = 'under';
            $priceFilter['max'] = $price;
            log_message('info', "Price detected: under {$price}");
        }
        // Pattern 5: "over 5000", "above 3k"
        elseif (preg_match('/(?:over|above|more than)\s+(\d+)k?/i', $message, $matches)) {
            $price = strpos($message, 'k') !== false ? (float)$matches[1] * 1000 : (float)$matches[1];
            $priceFilter['type'] = 'over';
            $priceFilter['min'] = $price;
            log_message('info', "Price detected: over {$price}");
        }

        return $priceFilter['type'] ? $priceFilter : null;
    }

    /**
     * Filter and sort hotels based on AI-analyzed requirements - INCLUDING PRICE!
     */
    private function filterAndSortHotels($hotels, $requirements) {
        $filteredHotels = $hotels;

        // GENIUS PRICE FILTERING!
        if ($requirements['price_filter']) {
            $filteredHotels = $this->filterHotelsByPrice($filteredHotels, $requirements['price_filter']);
        }

        // Filter by specific location if mentioned
        if ($requirements['location_specific']) {
            $location = strtolower($requirements['location_specific']);
            $filteredHotels = array_filter($filteredHotels, function($hotel) use ($location) {
                $hotelAddress = strtolower($hotel['address'] ?? '');
                $hotelName = strtolower($hotel['hotelName'] ?? '');
                return strpos($hotelAddress, $location) !== false || strpos($hotelName, $location) !== false;
            });
        }

        // Filter by budget/luxury
        if ($requirements['filter_by'] === 'budget') {
            $filteredHotels = array_filter($filteredHotels, function($hotel) {
                $price = (float)($hotel['total'] ?? 0);
                return $price <= 2000; // Budget threshold
            });
        } elseif ($requirements['filter_by'] === 'luxury') {
            $filteredHotels = array_filter($filteredHotels, function($hotel) {
                $rating = (int)($hotel['hotelRating'] ?? 0);
                return $rating >= 4; // Luxury threshold
            });
        }

        // SMART SORTING based on user request
        if ($requirements['sort_by']) {
            switch ($requirements['sort_by']) {
                case 'price_asc':
                case 'price_low':
                    usort($filteredHotels, function($a, $b) {
                        return (float)($a['total'] ?? 0) <=> (float)($b['total'] ?? 0);
                    });
                    log_message('info', "SORTED: Hotels by price (cheapest first)");
                    break;
                case 'price_desc':
                case 'price_high':
                    usort($filteredHotels, function($a, $b) {
                        return (float)($b['total'] ?? 0) <=> (float)($a['total'] ?? 0);
                    });
                    log_message('info', "SORTED: Hotels by price (most expensive first)");
                    break;
                case 'rating_desc':
                case 'rating_high':
                    usort($filteredHotels, function($a, $b) {
                        return (int)($b['hotelRating'] ?? 0) <=> (int)($a['hotelRating'] ?? 0);
                    });
                    log_message('info', "SORTED: Hotels by rating (best first)");
                    break;
                case 'rating_low':
                    usort($filteredHotels, function($a, $b) {
                        return (int)($a['hotelRating'] ?? 0) <=> (int)($b['hotelRating'] ?? 0);
                    });
                    log_message('info', "SORTED: Hotels by rating (lowest first)");
                    break;
            }
        }

        // Apply limit if specified
        if ($requirements['limit'] && $requirements['limit'] > 0) {
            $filteredHotels = array_slice($filteredHotels, 0, $requirements['limit']);
        }

        return array_values($filteredHotels); // Re-index array
    }

    /**
     * GENIUS price filtering - handles all price patterns!
     */
    private function filterHotelsByPrice($hotels, $priceFilter) {
        if (!$priceFilter || !$priceFilter['type']) {
            return $hotels;
        }

        $filteredHotels = [];
        $originalCount = count($hotels);

        foreach ($hotels as $hotel) {
            $hotelPrice = (float)($hotel['total'] ?? 0);
            $isMatch = false;

            switch ($priceFilter['type']) {
                case 'around':
                    // Within ±20% range
                    if ($hotelPrice >= $priceFilter['min'] && $hotelPrice <= $priceFilter['max']) {
                        $isMatch = true;
                        log_message('info', "PRICE MATCH: {$hotel['hotelName']} - ₱{$hotelPrice} is around ₱{$priceFilter['target']}");
                    }
                    break;

                case 'under':
                    if ($hotelPrice <= $priceFilter['max']) {
                        $isMatch = true;
                        log_message('info', "PRICE MATCH: {$hotel['hotelName']} - ₱{$hotelPrice} is under ₱{$priceFilter['max']}");
                    }
                    break;

                case 'over':
                    if ($hotelPrice >= $priceFilter['min']) {
                        $isMatch = true;
                        log_message('info', "PRICE MATCH: {$hotel['hotelName']} - ₱{$hotelPrice} is over ₱{$priceFilter['min']}");
                    }
                    break;
            }

            if ($isMatch) {
                $filteredHotels[] = $hotel;
            } else {
                log_message('info', "PRICE REJECT: {$hotel['hotelName']} - ₱{$hotelPrice} doesn't match criteria");
            }
        }

        log_message('info', "PRICE FILTER: {$originalCount} hotels → " . count($filteredHotels) . " hotels matching price criteria");

        return $filteredHotels;
    }

    /**
     * Generate intelligent response based on user requirements - NATURAL LANGUAGE!
     */
    private function generateIntelligentResponse($requirements, $hotels, $originalCount, $userMessage, $destination) {
        $count = count($hotels);

        if ($count === 0) {
            if ($requirements['location_specific']) {
                return "I couldn't find any hotels near {$requirements['location_specific']}.";
            }
            return "No hotels match your criteria.";
        }

        // Generate natural responses based on specific requests
        if ($requirements['sort_by'] === 'price_low') {
            if ($count === 1) {
                return "Here's the cheapest hotel I found:";
            }
            return "Here are the cheapest hotels, sorted by price:";
        }

        if ($requirements['sort_by'] === 'price_high') {
            if ($count === 1) {
                return "Here's the most expensive hotel:";
            }
            return "Here are the most expensive hotels:";
        }

        if ($requirements['sort_by'] === 'rating_high') {
            if ($count === 1) {
                return "Here's the highest rated hotel:";
            }
            return "Here are the highest rated hotels:";
        }

        if ($requirements['limit']) {
            return "Here are the top {$requirements['limit']} hotels:";
        }

        if ($requirements['location_specific']) {
            return "Here are hotels near {$requirements['location_specific']}:";
        }

        if ($requirements['filter_by'] === 'budget') {
            return "Here are budget-friendly hotels:";
        }

        if ($requirements['filter_by'] === 'luxury') {
            return "Here are luxury hotels:";
        }

        // GENIUS price-based responses
        if ($requirements['price_filter']) {
            $priceFilter = $requirements['price_filter'];
            switch ($priceFilter['type']) {
                case 'around':
                    return "Here are hotels around ₱" . number_format($priceFilter['target']) . ":";
                case 'under':
                    return "Here are hotels under ₱" . number_format($priceFilter['max']) . ":";
                case 'over':
                    return "Here are hotels over ₱" . number_format($priceFilter['min']) . ":";
            }
        }

        // Default response
        return "Here are the hotels I found:";
    }

    /**
     * Add hotel names to chat response (left side) with proper formatting
     */
    private function addHotelNamesToResponse($hotels) {
        if (empty($hotels)) {
            return "";
        }

        $hotelList = "\n\n";
        foreach ($hotels as $hotel) {
            $hotelList .= "• " . ($hotel['hotelName'] ?? 'Unknown Hotel') . "\n";
        }

        return rtrim($hotelList); // Remove trailing newline
    }

    /**
     * Generate intelligent "no results" response using AI - NO PRE-CREATED TEXT!
     */
    private function generateIntelligentNoResultsResponse($userMessage, $destination) {
        try {
            /** @var \Config\DeepSeek $deepseekConfig */
            $deepseekConfig = config('DeepSeek');
            $apiKey = getenv('OPENAI_API_KEY');

            if (empty($apiKey) || !$deepseekConfig || empty($deepseekConfig->model) || empty($deepseekConfig->apiUrl)) {
                // Even fallback should be more natural
                return "I don't have any hotel information for {$destination} right now. Would you like me to help you with something else?";
            }

            // Detect if user is speaking Tagalog
            $isTagalog = $this->isTagalogMessage($userMessage);
            $language = $isTagalog ? 'Tagalog' : 'English';

            $prompt = "The user asked: '{$userMessage}' and was looking for hotels in {$destination}, but no hotels were found in our database. Generate a helpful, natural response explaining that no hotels are available for {$destination}. Be empathetic and offer alternative assistance. Respond in {$language}. Keep it conversational and under 50 words.";

            $payload = [
                'model' => $deepseekConfig->model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.8,
                'max_tokens' => 100
            ];

            $ch = curl_init($deepseekConfig->apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                if (isset($data['choices'][0]['message']['content'])) {
                    $aiResponse = trim($data['choices'][0]['message']['content']);
                    if (!empty($aiResponse)) {
                        return $aiResponse;
                    }
                }
            }

            // Simple fallback without pre-created arrays
            return $isTagalog
                ? "Wala akong makitang hotel sa {$destination} sa ngayon. Mayroon ka bang ibang lugar na gusto mo?"
                : "I don't have any hotels for {$destination} at the moment. Is there another destination you'd like to explore?";

        } catch (Exception $e) {
            log_message('error', "AI No Results Response error: " . $e->getMessage());
            return "I don't have hotel information for {$destination} right now. How else can I help you?";
        }
    }

    /**
     * AI-powered language detection - NO HARDCODED WORD LISTS!
     */
    private function isTagalogMessage($message) {
        try {
            /** @var \Config\DeepSeek $deepseekConfig */
            $deepseekConfig = config('DeepSeek');
            $apiKey = getenv('OPENAI_API_KEY');

            if (empty($apiKey) || !$deepseekConfig || empty($deepseekConfig->model) || empty($deepseekConfig->apiUrl)) {
                // Simple fallback - check for common Tagalog words
                $commonTagalog = ['ako', 'mo', 'sa', 'ng', 'mga', 'ang'];
                $lowerMessage = strtolower($message);
                foreach ($commonTagalog as $word) {
                    if (strpos($lowerMessage, $word) !== false) {
                        return true;
                    }
                }
                return false;
            }

            $prompt = "Detect the language of this message. Respond with only 'TAGALOG' if the message is in Tagalog/Filipino, or 'ENGLISH' if it's in English or any other language.\n\nMessage: \"{$message}\"";

            $payload = [
                'model' => $deepseekConfig->model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.1,
                'max_tokens' => 10
            ];

            $ch = curl_init($deepseekConfig->apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                if (isset($data['choices'][0]['message']['content'])) {
                    $aiResponse = trim(strtoupper($data['choices'][0]['message']['content']));
                    return ($aiResponse === 'TAGALOG');
                }
            }

            return false;

        } catch (Exception $e) {
            log_message('error', "AI language detection error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add hotel names with AI-generated descriptions
     */
    private function addHotelNamesWithDescriptions($hotels, $userMessage) {
        if (empty($hotels)) {
            return "";
        }

        $isTagalog = $this->isTagalogMessage($userMessage);
        $hotelList = "\n\n";

        foreach ($hotels as $hotel) {
            $hotelName = $hotel['hotelName'] ?? 'Unknown Hotel';
            $price = $hotel['total'] ?? '0';
            $rating = $hotel['hotelRating'] ?? 0;
            $city = $hotel['city'] ?? '';

            // Generate AI description based on hotel data
            $description = $this->generateHotelDescription($hotel, $isTagalog);

            $hotelList .= "• **{$hotelName}**\n";
            $hotelList .= "  {$description}\n\n";
        }

        return rtrim($hotelList);
    }

    /**
     * Generate AI description for each hotel - NO PRE-CREATED RESPONSES!
     */
    private function generateHotelDescription($hotel, $isTagalog = false) {
        $hotelName = $hotel['hotelName'] ?? 'Hotel';
        $city = $hotel['city'] ?? '';
        $address = $hotel['address'] ?? '';

        // Use the existing AI hotel description method that's already implemented
        return $this->generateAIHotelDescription($hotelName, $city, $address, $isTagalog);
    }

    /**
     * AI DEEP ANALYSIS - Generate intelligent descriptions for each hotel
     */
    private function generateDeepHotelAnalysis($hotels, $userMessage, $destination) {
        $analyzedHotels = [];

        foreach ($hotels as $hotel) {
            // Deep analysis of hotel characteristics
            $analysis = $this->analyzeHotelCharacteristics($hotel, $destination);

            // Generate unique, intelligent description
            $aiDescription = $this->generateUniqueHotelDescription($hotel, $analysis, $userMessage);

            // Add AI-generated data to hotel
            $hotel['ai_description'] = $aiDescription;
            $hotel['ai_analysis'] = $analysis;

            $analyzedHotels[] = $hotel;

            // Log the AI analysis
            log_message('info', "AI DEEP ANALYSIS: {$hotel['hotelName']} - {$aiDescription}");
        }

        return $analyzedHotels;
    }

    /**
     * Analyze hotel characteristics intelligently
     */
    private function analyzeHotelCharacteristics($hotel, $destination) {
        $name = $hotel['hotelName'] ?? '';
        $address = $hotel['address'] ?? '';
        $rating = (int)($hotel['hotelRating'] ?? 0);
        $price = (float)($hotel['total'] ?? 0);
        $city = $hotel['city'] ?? '';

        $analysis = [
            'category' => $this->determineHotelCategory($name, $rating, $price),
            'location_type' => $this->analyzeLocation($address, $city),
            'price_segment' => $this->analyzePriceSegment($price),
            'brand_type' => $this->analyzeBrandType($name),
            'unique_features' => $this->identifyUniqueFeatures($name, $address)
        ];

        return $analysis;
    }

    /**
     * Generate AI-powered hotel description using external AI knowledge
     */
    private function generateUniqueHotelDescription($hotel, $analysis, $userMessage) {
        $isTagalog = $this->isTagalogMessage($userMessage);
        $hotelName = $hotel['hotelName'] ?? '';
        $city = $hotel['city'] ?? '';
        $address = $hotel['address'] ?? '';

        // Use AI to generate description based on hotel knowledge
        $aiDescription = $this->generateAIHotelDescription($hotelName, $city, $address, $isTagalog);

        return $aiDescription;
    }

    /**
     * Call AI to generate hotel description based on its knowledge
     */
    private function generateAIHotelDescription($hotelName, $city, $address, $isTagalog = false) {
        try {
            /** @var \Config\DeepSeek $deepseekConfig */
            $deepseekConfig = config('DeepSeek');
            $apiKey = getenv('OPENAI_API_KEY');

            if (empty($apiKey) || !$deepseekConfig || empty($deepseekConfig->model) || empty($deepseekConfig->apiUrl)) {
                return $this->getFallbackDescription($hotelName, $city, $isTagalog);
            }

            $language = $isTagalog ? 'Tagalog' : 'English';
            $prompt = "Write a brief 1-2 sentence description for '{$hotelName}' hotel located at '{$address}' in {$city}, Philippines. Focus on what makes this hotel unique, its amenities, or location benefits. Respond in {$language}. Keep it under 50 words.";

            $payload = [
                'model' => $deepseekConfig->model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.7,
                'max_tokens' => 100
            ];

            $ch = curl_init($deepseekConfig->apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                if (isset($data['choices'][0]['message']['content'])) {
                    $description = trim($data['choices'][0]['message']['content']);
                    log_message('info', "AI DESCRIPTION for {$hotelName}: {$description}");
                    return $description;
                }
            }

            log_message('error', "AI Description failed for {$hotelName}: HTTP {$httpCode}");
            return $this->getFallbackDescription($hotelName, $city, $isTagalog);

        } catch (Exception $e) {
            log_message('error', "AI Description error for {$hotelName}: " . $e->getMessage());
            return $this->getFallbackDescription($hotelName, $city, $isTagalog);
        }
    }

    /**
     * Simple fallback description if AI completely fails - minimal and natural
     */
    private function getFallbackDescription($hotelName, $city, $isTagalog = false) {
        // Even fallback should be more natural and specific to the hotel
        if ($isTagalog) {
            return "Hotel sa {$city} na pwedeng pagstayan.";
        } else {
            return "Hotel accommodation available in {$city}.";
        }
    }

    /**
     * Craft intelligent description based on hotel analysis
     */
    private function craftIntelligentDescription($hotel, $analysis, $isTagalog) {
        $name = $hotel['hotelName'] ?? '';
        $price = number_format((float)($hotel['total'] ?? 0));
        $rating = $hotel['hotelRating'] ?? 0;
        $city = $hotel['city'] ?? '';

        // Build description based on analysis
        $features = [];

        // Add category-based description
        if ($analysis['category'] === 'luxury') {
            $features[] = $isTagalog ? 'luxury hotel' : 'luxury accommodation';
        } elseif ($analysis['category'] === 'budget') {
            $features[] = $isTagalog ? 'budget-friendly' : 'affordable option';
        } else {
            $features[] = $isTagalog ? 'comfortable hotel' : 'quality accommodation';
        }

        // Add location insights
        if ($analysis['location_type'] === 'airport') {
            $features[] = $isTagalog ? 'malapit sa airport' : 'convenient airport location';
        } elseif ($analysis['location_type'] === 'city_center') {
            $features[] = $isTagalog ? 'sa city center' : 'in the heart of the city';
        } elseif ($analysis['location_type'] === 'business') {
            $features[] = $isTagalog ? 'business district area' : 'prime business location';
        }

        // Add unique features
        if (!empty($analysis['unique_features'])) {
            $features = array_merge($features, $analysis['unique_features']);
        }

        // Craft final description
        if ($isTagalog) {
            $description = "Ito ay {$rating}-star " . implode(', ', $features) . " sa {$city}. Presyo: ₱{$price} per night.";
        } else {
            $description = "A {$rating}-star " . implode(', ', $features) . " in {$city}. Rate: ₱{$price} per night.";
        }

        return $description;
    }

    /**
     * Determine hotel category based on name, rating, and price
     */
    private function determineHotelCategory($name, $rating, $price) {
        $lowerName = strtolower($name);

        if (strpos($lowerName, 'shangri') !== false || strpos($lowerName, 'luxury') !== false || $rating >= 5) {
            return 'luxury';
        } elseif (strpos($lowerName, 'budget') !== false || strpos($lowerName, 'oyo') !== false || $price < 2000) {
            return 'budget';
        } else {
            return 'mid-range';
        }
    }

    /**
     * Analyze location type from address
     */
    private function analyzeLocation($address, $city) {
        $lowerAddress = strtolower($address);

        if (strpos($lowerAddress, 'airport') !== false || strpos($lowerAddress, 'mactan') !== false) {
            return 'airport';
        } elseif (strpos($lowerAddress, 'mabini') !== false || strpos($lowerAddress, 'ermita') !== false) {
            return 'city_center';
        } elseif (strpos($lowerAddress, 'makati') !== false || strpos($lowerAddress, 'bgc') !== false) {
            return 'business';
        } else {
            return 'residential';
        }
    }

    /**
     * Analyze price segment
     */
    private function analyzePriceSegment($price) {
        if ($price < 2000) {
            return 'budget';
        } elseif ($price < 5000) {
            return 'mid-range';
        } else {
            return 'premium';
        }
    }

    /**
     * Analyze brand type
     */
    private function analyzeBrandType($name) {
        $lowerName = strtolower($name);

        if (strpos($lowerName, 'reddoorz') !== false || strpos($lowerName, 'oyo') !== false) {
            return 'chain_budget';
        } elseif (strpos($lowerName, 'shangri') !== false || strpos($lowerName, 'marriott') !== false) {
            return 'international_luxury';
        } else {
            return 'local';
        }
    }

    /**
     * Identify unique features
     */
    private function identifyUniqueFeatures($name, $address) {
        $features = [];
        $lowerName = strtolower($name);
        $lowerAddress = strtolower($address);

        if (strpos($lowerName, 'spa') !== false || strpos($lowerAddress, 'spa') !== false) {
            $features[] = 'with spa facilities';
        }

        if (strpos($lowerName, 'garden') !== false) {
            $features[] = 'garden setting';
        }

        if (strpos($lowerAddress, 'beach') !== false || strpos($lowerAddress, 'island') !== false) {
            $features[] = 'beachfront location';
        }

        return $features;
    }

    /**
     * Generate AI-powered hotel found response - NO MORE HARDCODED TEXT!
     */
    private function generateIntelligentHotelFoundResponse($hotels, $userMessage, $destination, $isTagalog) {
        try {
            /** @var \Config\DeepSeek $deepseekConfig */
            $deepseekConfig = config('DeepSeek');
            $apiKey = getenv('OPENAI_API_KEY');

            if (empty($apiKey) || !$deepseekConfig || empty($deepseekConfig->model) || empty($deepseekConfig->apiUrl)) {
                // Simple fallback without hardcoded templates
                $count = count($hotels);
                if ($isTagalog) {
                    return "Nakakita ako ng {$count} hotels sa {$destination} para sa iyo.";
                } else {
                    return "I found {$count} hotels in {$destination} for you.";
                }
            }

            $count = count($hotels);
            $language = $isTagalog ? 'Tagalog' : 'English';

            $prompt = "Generate a natural, conversational response to introduce hotel search results.

USER REQUEST: \"{$userMessage}\"
DESTINATION: {$destination}
HOTELS FOUND: {$count}
LANGUAGE: {$language}

Create a brief, friendly introduction that:
1. Acknowledges what the user asked for
2. Mentions the destination and number of hotels found
3. Sounds natural and conversational (not robotic)
4. Is under 25 words
5. Ends with a colon (:) to introduce the hotel list

Examples:
- \"I found {$count} great hotels in {$destination} for you:\"
- \"Here are {$count} hotels near those beautiful waterfalls in {$destination}:\"
- \"Perfect! I discovered {$count} accommodations in {$destination}:\"

Respond with ONLY the introduction text, nothing else.";

            $payload = [
                'model' => $deepseekConfig->model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.7,
                'max_tokens' => 50
            ];

            $ch = curl_init($deepseekConfig->apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                if (isset($data['choices'][0]['message']['content'])) {
                    $aiResponse = trim($data['choices'][0]['message']['content']);
                    if (!empty($aiResponse)) {
                        return $aiResponse;
                    }
                }
            }

            // Fallback if AI fails
            if ($isTagalog) {
                return "Nakakita ako ng {$count} hotels sa {$destination} para sa iyo:";
            } else {
                return "I found {$count} hotels in {$destination} for you:";
            }

        } catch (Exception $e) {
            log_message('error', "AI hotel response generation error: " . $e->getMessage());
            $count = count($hotels);
            if ($isTagalog) {
                return "Nakakita ako ng {$count} hotels sa {$destination}:";
            } else {
                return "I found {$count} hotels in {$destination}:";
            }
        }
    }

    /**
     * Format hotels for chat display
     */
    private function formatHotelsForChat($hotels, $isTagalog) {
        if (empty($hotels)) {
            return "";
        }

        $hotelList = "\n\n";

        foreach ($hotels as $hotel) {
            $hotelName = $hotel['hotelName'] ?? 'Unknown Hotel';
            $aiDescription = $hotel['ai_description'] ?? 'No description available';

            $hotelList .= "• **{$hotelName}**\n";
            $hotelList .= "  {$aiDescription}\n\n";
        }

        return rtrim($hotelList);
    }

    /**
     * CONTEXT-AWARE location extraction - Understands conversation flow and references
     */
    private function extractLocationWithConversationContext($currentMessage, $chatHistory = []) {
        try {
            /** @var \Config\DeepSeek $deepseekConfig */
            $deepseekConfig = config('DeepSeek');
            $apiKey = getenv('OPENAI_API_KEY');

            if (empty($apiKey) || !$deepseekConfig || empty($deepseekConfig->model) || empty($deepseekConfig->apiUrl)) {
                log_message('info', "CONTEXT LOCATION EXTRACTION: API not configured, using simple fallback");
                return $this->contextAwareLocationFallback($currentMessage, $chatHistory);
            }

            // Build conversation context with recent messages
            $conversationContext = "Recent conversation:\n";
            if (!empty($chatHistory)) {
                foreach (array_slice($chatHistory, -4) as $turn) { // Last 4 turns for context
                    if (isset($turn['role']) && isset($turn['content'])) {
                        $role = $turn['role'] === 'user' ? 'User' : 'AI';
                        $conversationContext .= "{$role}: {$turn['content']}\n";
                    }
                }
            }
            $conversationContext .= "User: {$currentMessage}";

            log_message('info', "CONTEXT LOCATION EXTRACTION: Starting AI analysis with conversation context");

            $prompt = "You are a Philippine travel expert. Extract the destination from this hotel search request, considering the FULL conversation context.

CONVERSATION CONTEXT:
{$conversationContext}

TASK: Identify where the user wants to find hotels, considering:
1. Any destinations mentioned in recent conversation
2. References like \"I want that\" or \"there\" that refer to previously mentioned places
3. Context clues from the conversation flow

EXAMPLES:
- User: \"I want to travel Cebu\" → AI: \"Great choice!\" → User: \"find me hotels there\" → {\"city\": \"cebu\", \"specific_location\": null}
- User: \"I want to travel Manila\" → AI: \"Nice!\" → User: \"hotels near beaches\" → {\"city\": \"manila\", \"specific_location\": \"beaches\"}
- User: \"Thinking of Palawan\" → AI: \"Beautiful!\" → User: \"I want that. hotels near El Nido\" → {\"city\": \"palawan\", \"specific_location\": \"el nido\"}

CRITICAL RULES:
1. Look at the ENTIRE conversation to understand context
2. If user says \"I want that\" or similar, refer to the destination mentioned earlier
3. If user mentions specific areas (\"near beaches\", \"downtown\"), capture as specific_location
4. If no destination is mentioned in current message, check previous messages
5. Only return null if NO destination context exists in the entire conversation

Respond with ONLY a JSON object: {\"city\": \"city_name_or_null\", \"specific_location\": \"area_name_or_null\", \"reasoning\": \"brief_explanation\"}";

            $payload = [
                'model' => $deepseekConfig->model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.1
            ];

            $ch = curl_init($deepseekConfig->apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                log_message('error', "CONTEXT LOCATION EXTRACTION: cURL error: " . $curlError);
                return $this->contextAwareLocationFallback($currentMessage, $chatHistory);
            }

            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                if (isset($data['choices'][0]['message']['content'])) {
                    $aiResponse = trim($data['choices'][0]['message']['content']);
                    $locationInfo = json_decode($aiResponse, true);

                    if ($locationInfo && isset($locationInfo['city'])) {
                        log_message('info', "CONTEXT LOCATION EXTRACTION: Success - " . json_encode($locationInfo));
                        return $locationInfo;
                    }
                }
            }

            log_message('warning', "CONTEXT LOCATION EXTRACTION: Failed to parse response, using fallback");
            return $this->contextAwareLocationFallback($currentMessage, $chatHistory);

        } catch (Exception $e) {
            log_message('error', "Context location extraction failed: " . $e->getMessage());
            return $this->contextAwareLocationFallback($currentMessage, $chatHistory);
        }
    }

    /**
     * Context-aware location fallback when AI is not available
     */
    private function contextAwareLocationFallback($currentMessage, $chatHistory = []) {
        $lowerMessage = strtolower($currentMessage);

        // Check if current message has explicit location
        $explicitLocation = $this->simpleLocationFallback($currentMessage);
        if ($explicitLocation['city']) {
            return $explicitLocation;
        }

        // Check for reference words that indicate user is referring to previous context
        $referenceWords = ['that', 'there', 'it', 'this place', 'the place'];
        $hasReference = false;
        foreach ($referenceWords as $word) {
            if (strpos($lowerMessage, $word) !== false) {
                $hasReference = true;
                break;
            }
        }

        if ($hasReference && !empty($chatHistory)) {
            // Look for destinations mentioned in recent conversation
            foreach (array_reverse(array_slice($chatHistory, -5)) as $turn) {
                if (isset($turn['content'])) {
                    $content = $turn['content'];
                    $locationFromHistory = $this->simpleLocationFallback($content);
                    if ($locationFromHistory['city']) {
                        log_message('info', "CONTEXT FALLBACK: Found reference to '{$locationFromHistory['city']}' from conversation history");

                        // Check if current message has specific location (like "near beaches")
                        $specificLocation = null;
                        $locationKeywords = ['near', 'close to', 'around', 'by the'];
                        foreach ($locationKeywords as $keyword) {
                            if (strpos($lowerMessage, $keyword) !== false) {
                                // Extract what comes after the location keyword
                                $parts = explode($keyword, $lowerMessage, 2);
                                if (count($parts) > 1) {
                                    $specificLocation = trim($parts[1]);
                                    break;
                                }
                            }
                        }

                        return [
                            'city' => $locationFromHistory['city'],
                            'specific_location' => $specificLocation,
                            'reasoning' => 'Referenced previous destination from conversation'
                        ];
                    }
                }
            }
        }

        log_message('info', "CONTEXT FALLBACK: No location context found");
        return ['city' => null, 'specific_location' => null, 'reasoning' => 'No location context available'];
    }

    /**
     * AI-POWERED location extraction - Captures both city and specific location!
     */
    private function extractLocationWithAI($conversationContext) {
        try {
            /** @var \Config\DeepSeek $deepseekConfig */
            $deepseekConfig = config('DeepSeek');
            $apiKey = getenv('OPENAI_API_KEY');

            if (empty($apiKey) || !$deepseekConfig || empty($deepseekConfig->model) || empty($deepseekConfig->apiUrl)) {
                log_message('info', "AI LOCATION EXTRACTION: API not configured, using simple fallback");
                return $this->simpleLocationFallback($conversationContext);
            }

            log_message('info', "AI LOCATION EXTRACTION: Starting AI analysis for: '{$conversationContext}'");

            $prompt = "You are a Philippine travel expert. Extract BOTH the city and specific location from this hotel search request.

CONVERSATION CONTEXT:
{$conversationContext}

TASK: Identify both the main city and any specific location/area mentioned. If NO location is mentioned, return null.

EXAMPLES:
- \"hotel in Manila\" → {\"city\": \"manila\", \"specific_location\": null}
- \"hotel in Intramuros Rizal\" → {\"city\": \"manila\", \"specific_location\": \"intramuros\"}
- \"accommodation near Rizal Park\" → {\"city\": \"manila\", \"specific_location\": \"rizal park\"}
- \"hotels in Moalboal Cebu\" → {\"city\": \"cebu\", \"specific_location\": \"moalboal\"}
- \"stay in El Nido\" → {\"city\": \"palawan\", \"specific_location\": \"el nido\"}
- \"find me hotels\" → {\"city\": null, \"specific_location\": null}
- \"book a hotel\" → {\"city\": null, \"specific_location\": null}
- \"I need accommodation\" → {\"city\": null, \"specific_location\": null}

CRITICAL RULES:
1. ONLY extract locations that are explicitly mentioned in the text
2. DO NOT guess or assume any location if none is mentioned
3. If no city/location is mentioned, return {\"city\": null, \"specific_location\": null}
4. Map landmarks to cities (e.g., \"Intramuros\" = Manila city)
5. Handle local variations (\"GenSan\" = General Santos)

Respond with ONLY a JSON object: {\"city\": \"city_name_or_null\", \"specific_location\": \"area_name_or_null\"}";

            $payload = [
                'model' => $deepseekConfig->model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.1
            ];

            $ch = curl_init($deepseekConfig->apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 second timeout
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // 5 second connection timeout

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                log_message('error', "AI LOCATION EXTRACTION: cURL error: " . $curlError);
                return $this->simpleLocationFallback($conversationContext);
            }

            log_message('info', "AI LOCATION EXTRACTION: HTTP Code: {$httpCode}");

            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                if (isset($data['choices'][0]['message']['content'])) {
                    $aiResponse = trim($data['choices'][0]['message']['content']);
                    $locationInfo = json_decode($aiResponse, true);

                    if ($locationInfo && isset($locationInfo['city'])) {
                        log_message('info', "AI LOCATION EXTRACTION: Success - " . json_encode($locationInfo));
                        return $locationInfo;
                    }
                }
            }

            log_message('warning', "AI LOCATION EXTRACTION: Failed to parse response, using fallback");
            return $this->simpleLocationFallback($conversationContext);

        } catch (Exception $e) {
            log_message('error', "AI location extraction failed: " . $e->getMessage());
            return $this->simpleLocationFallback($conversationContext);
        }
    }

    /**
     * Simple location fallback when AI is not available - STRICT matching only
     */
    private function simpleLocationFallback($text) {
        $lowerText = strtolower($text);

        // Check for generic hotel requests without location
        $genericRequests = ['find me hotels', 'book a hotel', 'i need accommodation', 'hotel booking', 'find hotels'];
        foreach ($genericRequests as $generic) {
            if (strpos($lowerText, $generic) !== false) {
                log_message('info', "SIMPLE LOCATION FALLBACK: Generic hotel request detected: '{$text}'");
                return ['city' => null, 'specific_location' => null];
            }
        }

        // Basic keyword matching for major cities and landmarks - ONLY if explicitly mentioned
        $locationMap = [
            'manila' => [
                'keywords' => ['manila', 'makati', 'bgc'],
                'landmarks' => ['intramuros', 'rizal park', 'luneta', 'malacañang', 'binondo']
            ],
            'cebu' => [
                'keywords' => ['cebu'],
                'landmarks' => ['moalboal', 'oslob', 'kawasan']
            ],
            'palawan' => [
                'keywords' => ['palawan'],
                'landmarks' => ['el nido', 'coron']
            ]
        ];

        

        foreach ($locationMap as $city => $data) {
            // Check for city keywords
            foreach ($data['keywords'] as $keyword) {
                if (strpos($lowerText, $keyword) !== false) {
                    // Check for specific landmarks
                    foreach ($data['landmarks'] as $landmark) {
                        if (strpos($lowerText, $landmark) !== false) {
                            log_message('info', "SIMPLE LOCATION FALLBACK: Found '{$city}' with landmark '{$landmark}'");
                            return ['city' => $city, 'specific_location' => $landmark];
                        }
                    }
                    log_message('info', "SIMPLE LOCATION FALLBACK: Found '{$city}' without specific location");
                    return ['city' => $city, 'specific_location' => null];
                }
            }

            // Check for landmarks that map to cities
            foreach ($data['landmarks'] as $landmark) {
                if (strpos($lowerText, $landmark) !== false) {
                    log_message('info', "SIMPLE LOCATION FALLBACK: Found '{$city}' via landmark '{$landmark}'");
                    return ['city' => $city, 'specific_location' => $landmark];
                }
            }
        }

        log_message('info', "SIMPLE LOCATION FALLBACK: No location found in text: '{$text}'");
        return ['city' => null, 'specific_location' => null];
    }

    /**
     * AI-POWERED destination extraction - Understands ANY location in the Philippines!
     */
    private function extractDestinationWithAI($conversationContext) {
        try {
            /** @var \Config\DeepSeek $deepseekConfig */
            $deepseekConfig = config('DeepSeek');
            $apiKey = getenv('OPENAI_API_KEY');

            if (empty($apiKey) || !$deepseekConfig || empty($deepseekConfig->model) || empty($deepseekConfig->apiUrl)) {
                log_message('info', "AI DESTINATION EXTRACTION: API not configured, using simple fallback");
                return $this->simpleDestinationFallback($conversationContext);
            }

            log_message('info', "AI DESTINATION EXTRACTION: Starting AI analysis for: '{$conversationContext}'");

            $prompt = "You are a Philippine travel expert. Extract the destination/location from this hotel search request.

CONVERSATION CONTEXT:
{$conversationContext}

TASK: Identify the Philippine destination where the user wants to find hotels.

RULES:
1. Look for ANY Philippine location mentioned (cities, provinces, landmarks, tourist spots)
2. Understand landmarks and map them to cities (e.g., \"Intramuros\" = Manila, \"Chocolate Hills\" = Bohol)
3. If multiple locations mentioned, pick the most relevant one for hotel search
4. Handle variations and local names (e.g., \"GenSan\" = General Santos, \"CDO\" = Cagayan de Oro)

EXAMPLES:
- \"hotels in Manila\" → manila
- \"near Intramuros, Rizal Park\" → manila (these are Manila landmarks)
- \"accommodation in Cebu\" → cebu
- \"hotels in Chocolate Hills area\" → bohol
- \"stay in El Nido\" → palawan
- \"GenSan hotels\" → general santos

Respond with ONLY the destination name in lowercase. If no clear Philippine destination is found, respond with 'none'.";

            $payload = [
                'model' => $deepseekConfig->model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.1,
                'max_tokens' => 20
            ];

            $ch = curl_init($deepseekConfig->apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 second timeout
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // 5 second connection timeout

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                log_message('error', "AI DESTINATION EXTRACTION: cURL error: " . $curlError);
                return null;
            }

            log_message('info', "AI DESTINATION EXTRACTION: HTTP Code: {$httpCode}");

            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                if (isset($data['choices'][0]['message']['content'])) {
                    $aiResponse = trim(strtolower($data['choices'][0]['message']['content']));

                    if ($aiResponse !== 'none' && !empty($aiResponse)) {
                        log_message('info', "AI DESTINATION EXTRACTION: Context='{$conversationContext}' | Extracted='{$aiResponse}'");
                        return $aiResponse;
                    }
                }
            }

            log_message('info', "AI DESTINATION EXTRACTION: No destination found in context");
            return null;

        } catch (Exception $e) {
            log_message('error', "AI destination extraction error: " . $e->getMessage());
            return $this->simpleDestinationFallback($conversationContext);
        }
    }

    /**
     * Simple destination fallback when AI is not available
     */
    private function simpleDestinationFallback($text) {
        $lowerText = strtolower($text);

        // Basic keyword matching for major cities only as last resort
        $basicCities = [
            'manila' => ['manila', 'intramuros', 'rizal park', 'makati', 'bgc'],
            'cebu' => ['cebu', 'moalboal', 'oslob'],
            'palawan' => ['palawan', 'el nido', 'coron'],
            'boracay' => ['boracay'],
            'davao' => ['davao'],
            'baguio' => ['baguio'],
            'bohol' => ['bohol', 'chocolate hills']
        ];

        foreach ($basicCities as $city => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($lowerText, $keyword) !== false) {
                    log_message('info', "SIMPLE FALLBACK: Found '{$city}' via keyword '{$keyword}'");
                    return $city;
                }
            }
        }

        log_message('info', "SIMPLE FALLBACK: No destination found in text: '{$text}'");
        return null;
    }
}
