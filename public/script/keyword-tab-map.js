function autoExtendKeywordMap(map) {
   const extendedMap = { ...map };

   for (const keyword in map) {
      const value = map[keyword];

      // Add plural if it doesn't exist
      if (!keyword.endsWith('s')) {
         const plural = keyword + 's';
         if (!extendedMap[plural]) {
            extendedMap[plural] = value;
         }
      }

      // Optionally add singular if needed (rare)
      if (keyword.endsWith('s')) {
         const singular = keyword.slice(0, -1);
         if (!extendedMap[singular]) {
            extendedMap[singular] = value;
         }
      }
   }

   return extendedMap;
}


// ✅ Apply auto-plural logic to your original map
const keywordTabMap = autoExtendKeywordMap({
   hotel: 'hotels-tab',
   hotels: 'hotels-tab',
   flight: 'flights-tab',
   flights: 'flights-tab',
   airfare: 'flights-tab',
   airport: 'flights-tab',
   car: 'cars-tab',
   cars: 'cars-tab',
   rental: 'cars-tab',
   rentals: 'cars-tab',
   "car rental": 'cars-tab',
   "vehicle hire": 'cars-tab',
   attraction: 'guide-tab',
   attractions: 'guide-tab',
   tour: 'guide-tab',
   tours: 'guide-tab',
   landmark: 'guide-tab',
   sightseeing: 'guide-tab',
   "things to do": 'guide-tab',
   concierge: 'concierge-tab',
   help: 'concierge-tab',
   assistant: 'concierge-tab',
   support: 'concierge-tab',
   booking: 'concierge-tab',
   rebook: 'concierge-tab',
   document: 'concierge-tab',
   visa: 'concierge-tab',
   insurance: 'concierge-tab',
   emergency: 'concierge-tab',
   contact: 'concierge-tab',
   currency: 'concierge-tab',
   money: 'concierge-tab',
   destination: 'destinations-tab',
   destinations: 'destinations-tab',
   itinerary: 'itinerary-tab',
   plan: 'itinerary-tab',
   package: 'packages-tab',
   packages: 'packages-tab',
   vacation: 'packages-tab',
   transport: 'transportation-tab',
   transportation: 'transportation-tab',
   guide: 'guide-tab',
   weather: 'weather-tab',
   summary: 'summary-tab',
   bookings: 'summary-tab'
});

const allTabIds = [...new Set(Object.values(keywordTabMap))];


// ========== FUNCTION: Show Tab(s) by Keyword (SUPPORTS MULTIPLE MATCHES) ==========
// ========== INTENT CLASSIFICATION ==========: Detect the user’s intent from natural language input

function showTabsByKeyword(text) {
   const lowerInput = text.toLowerCase();
   const matchedTabIds = new Set();

   for (const keyword in keywordTabMap) {
      const pattern = new RegExp(`\\b${keyword.replace(/\s+/g, '\\s+')}\\b`, 'i');
      if (pattern.test(lowerInput)) {
         matchedTabIds.add(keywordTabMap[keyword]);
         console.log(`[MATCH] Keyword "${keyword}" ➜ Tab "${keywordTabMap[keyword]}"`);
      }
   }

   allTabIds.forEach(id => {
      const tabElement = document.getElementById(id);
      if (!tabElement) return;

      const shouldShow = matchedTabIds.has(id);
      const alreadyVisible = tabElement.style.display === 'block';

      if (shouldShow && !alreadyVisible) {
         tabElement.style.display = 'block';
         initializeTabContent(id, tabElement);
      } else if (!shouldShow && alreadyVisible) {
         tabElement.style.display = 'none';
      }
   });
}


