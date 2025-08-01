
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

      // 🟢 STEP 1: Pre-format the complete response with proper HTML structure
      let formattedHTML;
      try {
         // Clean and process the text first
         let processedText = text;

         // Remove markdown headers and replace with clean text
         processedText = processedText.replace(/^#{1,6}\s+(.+)$/gm, '$1');

         // AGGRESSIVELY remove ALL dashes, bullets, and symbols from line starts
         processedText = processedText.replace(/^[-•*]\s+(.+)$/gm, '$1');
         processedText = processedText.replace(/^[\s]*[-•*]\s+(.+)$/gm, '$1');
         processedText = processedText.replace(/^[\s]*-\s+(.+)$/gm, '$1');

         // Convert newlines to proper line breaks
         const textWithBreaks = processedText.replace(/\n/g, '<br>');
         formattedHTML = marked.parse(textWithBreaks);

         // Create a temporary div to process the HTML
         const tempDiv = document.createElement('div');
         tempDiv.innerHTML = formattedHTML;

         // Remove list markers and clean up formatting
         const lists = tempDiv.querySelectorAll('ul, ol');
         lists.forEach(list => {
            list.style.listStyleType = 'none';
            list.style.paddingLeft = '0';
         });

         // AGGRESSIVELY remove any remaining dashes from list items and paragraphs
         const allTextElements = tempDiv.querySelectorAll('li, p, div');
         allTextElements.forEach(element => {
            if (element.textContent) {
               // Remove dashes at the start of text content
               element.innerHTML = element.innerHTML.replace(/^[\s]*-\s+/, '');
               element.innerHTML = element.innerHTML.replace(/^[\s]*•\s+/, '');
               element.innerHTML = element.innerHTML.replace(/^[\s]*\*\s+/, '');
            }
         });

         // Remove any remaining ### headers
         const headings = tempDiv.querySelectorAll('h1, h2, h3, h4, h5, h6');
         headings.forEach(heading => {
            const p = document.createElement('p');
            p.innerHTML = heading.innerHTML;
            p.style.fontWeight = '600';
            p.style.margin = '12px 0 8px 0';
            heading.parentNode.replaceChild(p, heading);
         });

         // Apply prose styling wrapper
         const proseWrapper = document.createElement('div');
         proseWrapper.className = 'prose-chat prose relative break-words';
         proseWrapper.innerHTML = tempDiv.innerHTML;

         // Enhance hotel names with icons
         enhanceHotelNamesWithIcons(proseWrapper);

         formattedHTML = proseWrapper.outerHTML;

      } catch (error) {
         console.error('Error formatting response:', error);
         // Fallback: convert newlines to <br> tags
         const textWithBreaks = text.replace(/\n/g, '<br>');
         formattedHTML = `<div class="prose-chat prose relative break-words"><p>${textWithBreaks}</p></div>`;
      }

      // 🟢 STEP 2: Set up the formatted HTML immediately with click handlers
      contentDiv.innerHTML = formattedHTML;

      // Add click handlers to enhanced hotel elements
      const hotelElements = contentDiv.querySelectorAll('.hotel-pill[data-hotel-id]');
      hotelElements.forEach(element => {
         element.addEventListener('click', function(e) {
            e.preventDefault();
            const hotelId = this.getAttribute('data-hotel-id');
            const lat = parseFloat(this.getAttribute('data-lat'));
            const lng = parseFloat(this.getAttribute('data-lng'));

            if (!isNaN(lat) && !isNaN(lng)) {
               focusMapOnHotelWithAnimation(hotelId, lat, lng);
            } else {
               console.warn('Invalid coordinates for hotel:', hotelId);
            }
         });
      });

      // Add click handlers to draggable place elements
      const placeElements = contentDiv.querySelectorAll('.draggable-place');
      placeElements.forEach(element => {
         element.addEventListener('click', function(e) {
            e.preventDefault();
            const placeName = this.textContent.trim();
            console.log('Place clicked:', placeName);
            // You can add place-specific functionality here
         });
      });

      // 🟢 STEP 3: Apply line-by-line reveal effect to the formatted content
      applyLineByLineReveal(contentDiv);
   }

   // Function to apply line-by-line reveal effect to formatted content
   function applyLineByLineReveal(contentDiv) {
      // Hide the content initially
      contentDiv.style.opacity = '0';

      // Get all text nodes and elements to reveal
      const elementsToReveal = [];

      // Split content by lines (paragraphs, list items, etc.)
      const children = contentDiv.querySelectorAll('p, li, h1, h2, h3, h4, h5, h6, div');

      if (children.length === 0) {
         // If no structured elements, split by line breaks
         const textContent = contentDiv.innerHTML;
         const lines = textContent.split('<br>');

         contentDiv.innerHTML = '';
         lines.forEach((line) => {
            if (line.trim()) {
               const lineDiv = document.createElement('div');
               lineDiv.innerHTML = line;
               lineDiv.style.opacity = '0';
               contentDiv.appendChild(lineDiv);
               elementsToReveal.push(lineDiv);
            }
         });
      } else {
         // Hide all structured elements initially
         children.forEach(element => {
            element.style.opacity = '0';
            elementsToReveal.push(element);
         });
      }

      // Show the container
      contentDiv.style.opacity = '1';

      // Reveal elements one by one with typing-like timing
      let currentIndex = 0;
      const revealSpeed = 300; // ms between each line reveal

      function revealNextElement() {
         if (currentIndex < elementsToReveal.length) {
            const element = elementsToReveal[currentIndex];

            // Fade in the element
            element.style.transition = 'opacity 0.2s ease-in';
            element.style.opacity = '1';

            // Scroll to keep the content in view
            chatBody.scrollTop = chatBody.scrollHeight;

            currentIndex++;
            setTimeout(revealNextElement, revealSpeed);
         }
      }

      // Start the reveal process
      setTimeout(revealNextElement, 100);
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
   const fullStars = Math.floor(rating);
   const filledStars = '★'.repeat(fullStars);
   const emptyStars = '☆'.repeat(5 - fullStars);
   const stars = filledStars + emptyStars;

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

// Function to focus map on specific hotel when hotel name is clicked
function focusMapOnHotel(hotelId, latitude, longitude) {
   console.log('Focusing map on hotel:', hotelId, latitude, longitude);

   // Show hotels tab if not already shown
   showHotelsTab();

   // Get the hotel map instance
   if (window.hotelMap && window.hotelMap.map) {
      const lat = parseFloat(latitude);
      const lng = parseFloat(longitude);

      if (!isNaN(lat) && !isNaN(lng)) {
         // Center map on the hotel
         window.hotelMap.map.setCenter({ lat, lng });
         window.hotelMap.map.setZoom(16);

         // Find and trigger click on the corresponding marker
         const marker = window.hotelMap.markers.find(m => {
            const pos = m.getPosition();
            return Math.abs(pos.lat() - lat) < 0.0001 && Math.abs(pos.lng() - lng) < 0.0001;
         });

         if (marker) {
            // Trigger marker click to show info window
            google.maps.event.trigger(marker, 'click');
         }
      }
   } else {
      console.warn('Hotel map not available');
   }
}



// Function to enhance hotel names with icons after AI response is rendered
function enhanceHotelNamesWithIcons(contentDiv) {
   // Hotel bed icon SVG (black and white)
   const hotelIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="hotel-icon"><path d="M5.5 11.188h13.875a1.5 1.5 0 0 1 1.5 1.5v3.562H4v-3.563a1.5 1.5 0 0 1 1.5-1.5ZM4 16.25v2.25M20.875 16.25v2.25"></path><path d="M19.188 11.188V6.125A1.125 1.125 0 0 0 18.063 5H6.813a1.125 1.125 0 0 0-1.125 1.125v5.063"></path><path d="M9.813 8.375h5.25a.75.75 0 0 1 .75.75v2.063h-6.75V9.124a.75.75 0 0 1 .75-.75Z"></path></svg>';

   // Find all hotel name links and enhance them with icons
   const hotelLinks = contentDiv.querySelectorAll('.hotel-name-link');
   hotelLinks.forEach(link => {
      // Wrap the hotel name with icon and styling
      const hotelName = link.textContent;
      const hotelId = link.getAttribute('data-hotel-id');
      const lat = link.getAttribute('data-lat');
      const lng = link.getAttribute('data-lng');

      // Create enhanced hotel element with white background and icon
      const enhancedHotel = document.createElement('span');
      enhancedHotel.className = 'hotel-pill';
      enhancedHotel.setAttribute('data-hotel-id', hotelId);
      enhancedHotel.setAttribute('data-lat', lat);
      enhancedHotel.setAttribute('data-lng', lng);

      enhancedHotel.innerHTML = `${hotelIcon}${hotelName}`;

      // Replace the original link with the enhanced version
      link.parentNode.replaceChild(enhancedHotel, link);
   });
}

// Function to focus map on specific hotel with smooth animation
function focusMapOnHotelWithAnimation(hotelId, latitude, longitude) {
   console.log('Focusing map on hotel with animation:', hotelId, latitude, longitude);

   // Show hotels tab if not already shown
   showHotelsTab();

   // Get the hotel map instance
   if (window.hotelMap && window.hotelMap.map) {
      const lat = parseFloat(latitude);
      const lng = parseFloat(longitude);

      if (!isNaN(lat) && !isNaN(lng)) {
         const map = window.hotelMap.map;

         // Get current zoom
         const currentZoom = map.getZoom();
         const targetZoom = 16;

         // Smooth pan to location (Google Maps handles the animation automatically)
         map.panTo({ lat, lng });

         // Animate zoom if needed
         if (currentZoom < targetZoom) {
            setTimeout(() => {
               map.setZoom(targetZoom);
            }, 800); // Wait for pan animation to mostly complete
         }

         // Find and trigger click on the corresponding marker after animation
         setTimeout(() => {
            const marker = window.hotelMap.markers.find(m => {
               const pos = m.getPosition();
               return Math.abs(pos.lat() - lat) < 0.0001 && Math.abs(pos.lng() - lng) < 0.0001;
            });

            if (marker) {
               // Add bounce animation to marker
               marker.setAnimation(google.maps.Animation.BOUNCE);

               // Stop bouncing after 2 seconds and show info window
               setTimeout(() => {
                  marker.setAnimation(null);
                  google.maps.event.trigger(marker, 'click');
               }, 2000);
            }
         }, 1500); // Wait for both pan and zoom animations
      }
   } else {
      console.warn('Hotel map not available');
   }
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



