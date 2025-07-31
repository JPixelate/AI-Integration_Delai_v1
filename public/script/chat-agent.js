
// ========== MAIN SCRIPT ==========
document.addEventListener('DOMContentLoaded', () => {

   const openChatBtn = document.getElementById('openChatBtn');
   const closeChatBtn = document.getElementById('closeChatBtn');
   const initialScreen = document.getElementById('initialPromptScreen');
   const chatScreen = document.querySelector('.aspect-ratio-wrapper');

   if (openChatBtn) {
      openChatBtn.addEventListener('click', () => {
         initialScreen.style.display = 'none';
         chatScreen.style.display = 'flex';
         chatScreen.classList.remove('fade-down');
         chatScreen.classList.add('fade-up');
      });
   }

   if (closeChatBtn) {
      closeChatBtn.addEventListener('click', () => {
         chatScreen.classList.remove('fade-up');
         chatScreen.classList.add('fade-down');

         // Wait for animation to complete before hiding
         setTimeout(() => {
            chatScreen.style.display = 'none';
            initialScreen.style.display = 'flex';
         }, 300); // match fadeDown duration
      });
   }


   // ========== EVENT LISTENER FOR NEW CHAT BUTTON ==========
   document.querySelector('.chat-toggle').addEventListener('click', resetChat);
   // ========== UI ELEMENTS ==========
   const inputField = document.querySelector('.chat-input input');
   const sendBtn = document.querySelector('.send-btn');
   const chatBody = document.querySelector('.chat-body');

   function resetChat() {
      // Check if there's existing conversation worth preserving
      const existingChats = document.querySelectorAll('.chat-bubble').length;

      if (existingChats > 1) { // More than just the initial message
         if (!confirm('Are you sure you want to start a new chat? Your current conversation will be cleared.')) {
            return; // User canceled
         }
      }

      // Proceed with reset
      const chatBody = document.querySelector('.chat-body');
      chatBody.innerHTML = '';

      const inputField = document.querySelector('.chat-input input');
      inputField.value = '';

      allTabIds.forEach(id => {
         const tab = document.getElementById(id);
         if (tab) tab.style.display = 'none';
      });

      addMessage('assistant', "Welcome! I'm here to help you plan your dream trip around the Philippines. Whether you're looking for the best destinations, flights, hotels, or local tips — just type your question and let's start exploring!");

      chatHistory.length = 0; // Clear history

      // No system prompt - let the AI be intelligent naturally

   }


   // ========== FUNCTION: Call DeepSeek AI ==========
   async function callDeepSeekAI(userInput) {
      try {
         const apiBase = window.APP_CONFIG.API_BASE;
         const response = await fetch(`${apiBase}/deepseek/chat`, {
            
            method: 'POST',
            headers: {
               'Content-Type': 'application/json',
               'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ message: userInput, history: chatHistory })

         });

         if (!response.ok) throw new Error('API request failed');

         const data = await response.json();

         // Handle hotel search results with AI processing
         if (data.hotel_search && data.show_hotels_tab && data.hotel_results) {
            showHotelsTab();

            // Always show processing state first - right panel waits for AI
            showHotelProcessingState();

            // Simulate AI thinking time based on number of hotels
            const hotelCount = data.hotel_results?.itineraries?.length || 0;
            const processingTime = Math.max(2000, hotelCount * 800); // 800ms per hotel, minimum 2 seconds

            // Display results after AI "finishes" generating descriptions
            setTimeout(() => {
               displayHotelResults(data.hotel_results, data.destination);
            }, processingTime);
         }

         return data.reply || "No answer available right now. Sorry.";

      }
      catch (err) {
         console.error("DeepSeek backend error:", err);
         return "Sorry — the AI service is unavailable right now.";
      }
   }



   // ========== FUNCTION: Add Message Bubble ==========
   function addMessage(role, text) {
      const bubble = document.createElement('div');
      bubble.className = (role === 'user') ? 'chat-bubble user' : 'chat-bubble assistant';

      let contentHtml;
      if (role === 'assistant') {
         try {
            // Convert newlines to proper line breaks for hotel lists
            const textWithBreaks = text.replace(/\n/g, '<br>');
            contentHtml = marked.parse(textWithBreaks);
         } catch {
            // Fallback: convert newlines to <br> tags
            const textWithBreaks = text.replace(/\n/g, '<br>');
            contentHtml = `<p>${textWithBreaks}</p>`;
         }
      } else {
         contentHtml = `<p>${text}</p>`;
      }

      bubble.innerHTML = `<div class="chat-bubble-content">${contentHtml}</div>`;
      chatBody.appendChild(bubble);
      chatBody.scrollTop = chatBody.scrollHeight;
   }



   // ========== FUNCTION: Hallucination Filter ==========
   function isHallucinated(text) {
      const riskyPatterns = [
         /as an ai language model/i,
         /i (do not|don't) have (access|information)/i,
         /this may not be accurate/i,
         /i am not sure/i,
         /i cannot guarantee/i,
         /hallucination/i,
         /fictional/i,
         /made up/i,
         /not factual/i,
         /i might be wrong/i
      ];

      return riskyPatterns.some(pattern => pattern.test(text));
   }


   function typeReply(text) {
      const bubble = document.createElement('div');
      bubble.className = 'chat-bubble assistant';

      const contentDiv = document.createElement('div');
      contentDiv.className = 'chat-bubble-content';
      bubble.appendChild(contentDiv);

      chatBody.appendChild(bubble);
      chatBody.scrollTop = chatBody.scrollHeight;

      let index = 0;
      const speed = 4; // Typing speed per character
      let plainText = '';

      function typeChar() {
         if (index < text.length) {
            plainText += text.charAt(index);
            contentDiv.innerText = plainText; // Type as plain text
            index++;
            chatBody.scrollTop = chatBody.scrollHeight;
            setTimeout(typeChar, speed);
         } else {
            // 🟢 Render as Markdown after typing finishes - PRESERVE LINE BREAKS!
            try {
               // Convert newlines to proper line breaks for hotel lists
               const textWithBreaks = text.replace(/\n/g, '<br>');
               contentDiv.innerHTML = marked.parse(textWithBreaks);
            } catch {
               // Fallback: convert newlines to <br> tags
               const textWithBreaks = text.replace(/\n/g, '<br>');
               contentDiv.innerHTML = textWithBreaks;
            }
         }
      }

      typeChar();
   }

   // Spam protection variables
   let messageTimestamps = [];
   const SPAM_LIMIT = 3; // Max messages
   const SPAM_WINDOW = 5000; // ms (5 seconds)
   const SPAM_COOLDOWN = 7000; // ms (7 seconds)
   let spamBlocked = false;

   function showSpamWarning() {
      let modal = document.getElementById('spamModal');

      if (!modal) {
         modal = document.createElement('div');
         modal.id = 'spamModal';
         modal.innerHTML = `
            <div class="spam-modal-content">
               <div class="spam-container">
                  <h3>Whoa, slow down!</h3>
                  <p>You're sending messages too quickly.</p>
                  <p>Please wait <span id="spamCountdown">...</span> second(s) before sending more.</p>
               </div>
            </div>
         `;
         document.body.appendChild(modal);
      }

      modal.style.display = 'flex';

      const countdownElement = modal.querySelector('#spamCountdown');
      let secondsLeft = Math.floor(SPAM_COOLDOWN / 1000);

      const chatInput = document.querySelector('.chat-input input');
      if (chatInput) {
         chatInput.disabled = true;
      }

      // Hide .delight-ai-input
      const delightInput = document.querySelector('.delight-ai-input');
      if (delightInput) {
         delightInput.style.display = 'none';
      }

      countdownElement.textContent = secondsLeft;

      if (modal._intervalId) clearInterval(modal._intervalId);

      modal._intervalId = setInterval(() => {
         secondsLeft--;
         if (secondsLeft <= 0) {
            clearInterval(modal._intervalId);
         } else {
            countdownElement.textContent = secondsLeft;
         }
      }, 1000);

      setTimeout(() => {
         modal.style.display = 'none';
         clearInterval(modal._intervalId);
         modal._intervalId = null;

         if (chatInput) {
            chatInput.disabled = false;
         }

         // Re-show .delight-ai-input
         if (delightInput) {
            delightInput.style.display = '';
         }
      }, SPAM_COOLDOWN);
   }




   // showSpamWarning(); // Remove this After Designing

   // ========== FUNCTION: Handle Send ==========
   async function handleSend() {

      if (spamBlocked) {
         showSpamWarning();
         return;
      }

      const now = Date.now();
      messageTimestamps = messageTimestamps.filter(ts => now - ts < SPAM_WINDOW);
      messageTimestamps.push(now);

      if (messageTimestamps.length > SPAM_LIMIT) {
         spamBlocked = true;
         showSpamWarning();
         // Disable input
         inputField.disabled = true;
         sendBtn.disabled = true;
         setTimeout(() => {
            spamBlocked = false;
            inputField.disabled = false;
            sendBtn.disabled = false;
            messageTimestamps = [];
         }, SPAM_COOLDOWN);
         return;
      }

      const userInput = inputField.value.trim();
      chatHistory.push({ role: 'user', content: userInput });

      if (!userInput) return;

      // Step 1: Add user message
      addMessage('user', userInput);
      inputField.value = '';
      inputField.focus();

      // Step 2: Add assistant's "Typing..." message
      addMessage('assistant', 'Thinking...');

      // Step 3: Call AI and wait for the response
      const aiReply = await callDeepSeekAI(userInput);
      chatHistory.push({ role: 'assistant', content: aiReply });

      // Step 4: Remove the "Typing..." bubble
      const typingBubble = chatBody.querySelector('.chat-bubble.assistant:last-child');
      if (typingBubble && typingBubble.textContent.trim() === 'Thinking...') {
         typingBubble.remove();
      }

      // ✅ STEP 3 FIX: Check for empty or null response BEFORE doing anything else
      if (!aiReply || aiReply.trim() === '') {
         addMessage('assistant', 'Sorry — there is no valid answer at the moment.');
         return;
      }

      // Step 5: Add AI reply with hallucination filter
      if (isHallucinated(aiReply)) {
         addMessage('assistant', 'Sorry — the response might be unreliable or incorrect. Please try again.');
      } else {
         typeReply(aiReply);
      }

      const combinedText = `${userInput} ${aiReply}`;

      // Check if this is a new hotel request
      if (isNewHotelRequest(userInput)) {
         // Clear existing hotel content before showing new tabs
         clearHotelContent();
      }

      showTabsByKeyword(combinedText);

   }

   // ========== EVENTS ==========
   sendBtn.addEventListener('click', handleSend);
   inputField.addEventListener('keypress', e => {
      if (e.key === 'Enter') {
         e.preventDefault();
         handleSend();
      }
   });

   // ========== QUICK ACTION BUTTONS ==========
   document.querySelectorAll('.action-btn').forEach(button => {
      button.addEventListener('click', () => {
         const actionText = button.innerText.trim();
         inputField.value = actionText;
         handleSend(); // Auto-send on click
      });
   });
});

// ========== HOTEL DISPLAY FUNCTIONS ==========

function showHotelsTab() {
   const hotelsTab = document.getElementById('hotels-tab');
   if (hotelsTab) {
      hotelsTab.style.display = 'block';

      // Initialize tab if not already done
      if (hotelsTab.getAttribute('data-initialized') !== 'true') {
         initializeTabContent('hotels-tab', hotelsTab);
      }
   }
}

// Show AI processing state in hotel panel
function showHotelProcessingState() {
   const hotelsList = document.getElementById('hotelsList');
   if (!hotelsList) return;

   hotelsList.innerHTML = `
      <div class="processing-state" style="text-align: center; padding: 40px 20px; color: #666;">
         <div class="processing-spinner" style="margin-bottom: 15px;">
            <div style="display: inline-block; width: 20px; height: 20px; border: 3px solid #f3f3f3; border-top: 3px solid #007bff; border-radius: 50%; animation: spin 1s linear infinite;"></div>
         </div>
         <p style="margin: 0; font-size: 14px; font-weight: 500;">🤖 AI is generating hotel descriptions...</p>
         <p style="margin: 8px 0 0 0; font-size: 12px; color: #999;">Creating personalized descriptions for each hotel</p>
         <p style="margin: 5px 0 0 0; font-size: 11px; color: #bbb;">This may take a few moments</p>
      </div>
      <style>
         @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
         }
      </style>
   `;
}

function displayHotelResults(hotelData, destination) {
   console.log('displayHotelResults called with:', hotelData, 'for destination:', destination);

   // Hide loading and no results
   const loadingState = document.getElementById('hotelLoadingState');
   const noResults = document.getElementById('noHotelResults');
   const mapContainer = document.getElementById('hotelMapContainer');
   const detailPanel = document.getElementById('hotelDetailPanel');

   if (loadingState) loadingState.style.display = 'none';
   if (noResults) noResults.style.display = 'none';
   if (detailPanel) detailPanel.style.display = 'none';

   // Get hotels from the data
   const hotels = hotelData.itineraries || [];
   console.log('Hotels found:', hotels.length);

   if (hotels.length === 0) {
      console.log('No hotels found, showing no results');
      if (noResults) noResults.style.display = 'block';
      return;
   }

   // Show ONLY the map container with pins
   if (mapContainer) {
      mapContainer.style.display = 'block';
      console.log('Map container shown');
   }

   // Add hotels to map
   console.log('Adding hotels to map:', hotels);
   addHotelsToMapFromChat(hotels);
}

function createHotelCard(hotel) {
   const card = document.createElement('div');
   card.className = 'hotel-card';

   const rating = hotel.hotelRating || 0;
   const stars = '★'.repeat(rating) + '☆'.repeat(5 - rating);

   card.innerHTML = `
      <div class="hotel-header">
         <h3 class="hotel-name">${hotel.hotelName || 'Hotel Name Not Available'}</h3>
         <div class="hotel-rating">${stars} ${rating}/5</div>
      </div>
      <div class="hotel-address">
         <i class="fas fa-map-marker-alt"></i> ${hotel.address || 'Address not available'}
      </div>
      ${hotel.ai_description ? `<div class="hotel-ai-description" style="margin: 10px 0; padding: 8px; background: #f8f9fa; border-radius: 4px; font-size: 14px; color: #555; font-style: italic;">
         <i class="fas fa-robot" style="color: #007bff; margin-right: 5px;"></i>
         ${hotel.ai_description}
      </div>` : ''}
      <div class="hotel-price">${hotel.currency || 'PHP'} ${hotel.total || '0'} per night</div>
      <div class="hotel-fare-type">
         <span class="fare-badge ${hotel.fareType === 'Refundable' ? 'refundable' : 'non-refundable'}">
            ${hotel.fareType || 'Standard'}
         </span>
      </div>
      ${hotel.thumbNailUrl ? `<img src="${hotel.thumbNailUrl}" alt="${hotel.hotelName}" class="hotel-image" style="width: 100%; height: 200px; object-fit: cover; border-radius: 4px; margin: 10px 0;">` : ''}
      <button class="book-btn" onclick="bookHotel('${hotel.hotelId}', '${hotel.hotelName}')">
         <i class="fas fa-calendar-check"></i> Book Now
      </button>
   `;

   return card;
}

function bookHotel(hotelId, hotelName) {
   alert(`Booking ${hotelName} (ID: ${hotelId}). Booking functionality will be implemented soon!`);
}

// Add hotels to map from chat
async function addHotelsToMapFromChat(hotels) {
   if (!window.hotelMap) {
      console.error('Hotel map instance not found');
      return;
   }

   // Retry mechanism for map initialization
   let retries = 3;
   while (retries > 0) {
      try {
         await window.hotelMap.addHotelMarkers(hotels);
         return; // Success, exit
      } catch (error) {
         console.warn(`Failed to add hotels to map (attempt ${4 - retries}):`, error);
         retries--;

         if (retries > 0) {
            // Wait a bit before retrying
            await new Promise(resolve => setTimeout(resolve, 1000));
         } else {
            console.error('Failed to add hotels to map after all retries');
         }
      }
   }
}

// Check if user input is a new hotel request
function isNewHotelRequest(userInput) {
   const lowerInput = userInput.toLowerCase();
   const hotelKeywords = ['hotel', 'hotels', 'accommodation', 'stay', 'lodge', 'resort', 'inn'];
   const locationKeywords = ['in', 'at', 'near', 'around', 'palawan', 'davao', 'cebu', 'manila', 'boracay', 'baguio'];

   const hasHotelKeyword = hotelKeywords.some(keyword => lowerInput.includes(keyword));
   const hasLocationKeyword = locationKeywords.some(keyword => lowerInput.includes(keyword));

   return hasHotelKeyword || (hasLocationKeyword && (lowerInput.includes('find') || lowerInput.includes('show') || lowerInput.includes('search')));
}

// Clear existing hotel content
function clearHotelContent() {
   const mapContainer = document.getElementById('hotelMapContainer');
   const detailPanel = document.getElementById('hotelDetailPanel');
   const loadingState = document.getElementById('hotelLoadingState');
   const noResults = document.getElementById('noHotelResults');

   // Hide all hotel content
   if (mapContainer) mapContainer.style.display = 'none';
   if (detailPanel) detailPanel.style.display = 'none';
   if (loadingState) loadingState.style.display = 'none';
   if (noResults) noResults.style.display = 'none';

   // Clear map markers if map exists
   if (window.hotelMap && window.hotelMap.clearMarkers) {
      window.hotelMap.clearMarkers();
   }

   console.log('Hotel content cleared for new request');
}



