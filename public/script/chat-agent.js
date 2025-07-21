
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

      chatHistory.push({
         role: 'system',
         content: `You are Delight, a warm, friendly, and hospitable Filipino travel companion from "Delightful Philippines", ...`
      });

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
            contentHtml = marked.parse(text);
         } catch {
            contentHtml = `<p>${text}</p>`;
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
            // 🟢 Render as Markdown after typing finishes
            try {
               contentDiv.innerHTML = marked.parse(text);
            } catch {
               contentDiv.innerText = text;
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

