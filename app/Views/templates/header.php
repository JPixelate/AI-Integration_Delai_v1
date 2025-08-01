<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>AI Travel Companion - DeepSeek Integration</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

  <!-- Google Maps API will be loaded dynamically when needed -->

  <link rel="stylesheet" href="<?= base_url('css/header.css') ?>">
  <link rel="stylesheet" href="<?= base_url('css/home.css') ?>">
  <link rel="stylesheet" href="<?= base_url('css/hotel-map.css') ?>">
</head>
<body>




<header>
   <div class="small-container header-container">
      <div class="header-item">
         <img src="<?= base_url('icon/delightful_ph_logo-01.png')?>" alt="Delightful Philippines" width="120">
      </div>
      <div class="header-item">
         <ul>
            <li><a href="#">Home</a></li>
            <li><a href="#">About us</a></li>
            <li><a href="#">Travel News</a></li>
            <li><a href="#">Trip Planner</a></li>
            <li><a href="#">Destinations</a></li>
            <li><a href="#">Contact us</a></li>
            <li><a href="#">Gallery</a></li>
         </ul>
      </div>
      <div class="header-item">
         <button class="quick-menu">
            <span>Quick Menu</span>
            <img src="<?= base_url('icon/quick-menu.png')?>" alt="Quick Menu" width="20">
         </button>
      </div>
    </div>
</header>


<div class="aspect-ratio-wrapper" style="display:none;">

   <button id="closeChatBtn" class="close-chat-btn">
      <img src="<?=base_url('icon/exit.png')?>" alt="Close Panel" width="12">
   </button>

   <div class="split-screen">
      <!-- LEFT: Chat -->
      <div class="left-panel">
         <div class="chat-container">
            <div class="chat-body">
               <div class="chat-bubble">
                  <p>Welcome! I'm here to help you plan your dream trip around the Philippines. Whether you're looking for the best destinations, flights, hotels, or local tips — just type your question and let's start exploring!</p>
               </div>
            </div>

            <div class="chat-input-area">
               <div class="chat-actions">
                  <button class="action-btn"><i class="fas fa-pencil-alt"></i> Modify trip</button>
                  <button class="action-btn"><i class="fas fa-tag"></i> Make it cheaper</button>
                  <button class="action-btn"><i class="fas fa-plane"></i> Find me flights</button>
                  <button class="action-btn hotel-search"><i class="fas fa-hotel"></i> Find me hotels</button>
               </div>

               <div class="chat-input">
                  <input type="text" placeholder="Ask anything, the more you share the better I can help...">
                  <button class="send-btn"><i class="fas fa-paper-plane"></i></button>
               </div>

               <div class="chat-buttons">
                  <button class="chat-toggle"><i class="fas fa-comment"></i>New Chat</button>
               </div>
            </div>
         </div>
      </div>

      <!-- RIGHT: Trip Details -->
      <div class="right-panel">
         <div class="container">

            <div id="destinations-tab" class="tab-content" style="display:none;" data-tab="destinations" data-initialized="false"></div>
            <div id="itinerary-tab" class="tab-content" style="display:none;" data-tab="itinerary" data-initialized="false"></div>
            <div id="packages-tab" class="tab-content" style="display:none;" data-tab="packages" data-initialized="false"></div>
            <div id="hotels-tab" class="tab-content" style="display:none;" data-tab="hotels" data-initialized="false"></div>
            <div id="flights-tab" class="tab-content" style="display:none;" data-tab="flights" data-initialized="false"></div>
            <div id="transportation-tab" class="tab-content" style="display:none;" data-tab="transportation" data-initialized="false"></div>
            <div id="guide-tab" class="tab-content" style="display:none;" data-tab="guide" data-initialized="false"></div>
            <div id="visa-tab" class="tab-content" style="display:none;" data-tab="visa" data-initialized="false"></div>
            <div id="insurance-tab" class="tab-content" style="display:none;" data-tab="insurance" data-initialized="false"></div>
            <div id="currency-tab" class="tab-content" style="display:none;" data-tab="currency" data-initialized="false"></div>
            <div id="weather-tab" class="tab-content" style="display:none;" data-tab="weather" data-initialized="false"></div>
            <div id="emergency-tab" class="tab-content" style="display:none;" data-tab="emergency" data-initialized="false"></div>
            <div id="summary-tab" class="tab-content" style="display:none;" data-tab="summary" data-initialized="false"></div>
            <div id="cars-tab" class="tab-content" style="display:none;" data-tab="cars" data-initialized="false"></div>
            <div id="concierge-tab" class="tab-content" style="display:none;" data-tab="concierge" data-initialized="false"></div>

            <!-- === TEMPLATES for Lazy-Loaded Tab Content === -->

            <template id="template-hotels-tab">
               <div class="tab-inner">
                  <?= view('tabs/hotels'); ?>
               </div>
            </template>

            <template id="template-flights-tab">
               <div class="tab-inner">
                  <?= view('tabs/flights'); ?>
               </div>
            </template>

            <template id="template-cars-tab">
               <div class="tab-inner">
                  <?= view('tabs/cars'); ?>
               </div>
            </template>

            <template id="template-guide-tab">
               <div class="tab-inner">
                  <?= view('tabs/guide'); ?>
               </div>
            </template>

            <template id="template-visa-tab">
               <div class="tab-inner">
                  <?= view('tabs/visa'); ?>
               </div>
            </template>

            <template id="template-insurance-tab">
               <div class="tab-inner">
                  <?= view('tabs/insurance'); ?>
               </div>
            </template>

            <template id="template-currency-tab">
               <div class="tab-inner">
                  <?= view('tabs/currency'); ?>
               </div>
            </template>

            <template id="template-weather-tab">
               <div class="tab-inner">
                  <?= view('tabs/weather'); ?>
               </div>
            </template>

            <template id="template-summary-tab">
               <div class="tab-inner">
                  <?= view('tabs/summary'); ?>
               </div>
            </template>

            <template id="template-destinations-tab">
               <div class="tab-inner">
                  <?= view('tabs/destinations'); ?>
               </div>
            </template>

            <template id="template-itinerary-tab">
               <div class="tab-inner">
                  <?= view('tabs/itinerary'); ?>
               </div>
            </template>

            <template id="template-packages-tab">
               <div class="tab-inner">
                  <?= view('tabs/packages'); ?>
               </div>
            </template>

            <template id="template-transportation-tab">
               <div class="tab-inner">
                  <?= view('tabs/transportation'); ?>
               </div>
            </template>

            <template id="template-emergency-tab">
               <div class="tab-inner">
                  <?= view('tabs/emergency'); ?>
               </div>
            </template>

            <template id="template-concierge-tab">
               <div class="tab-inner">
                  <?= view('tabs/concierge'); ?>
               </div>
            </template>

         </div>
      </div>

   </div>
</div>