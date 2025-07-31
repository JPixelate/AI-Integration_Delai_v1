// Hotel Search JavaScript Module
class HotelSearch {
    constructor() {
        this.apiBase = window.APP_CONFIG?.API_BASE || '';
        this.currentSearchParams = null;
        this.init();
    }

    init() {
        // Initialize event listeners when the hotels tab is loaded
        document.addEventListener('DOMContentLoaded', () => {
            this.bindEvents();
            this.setDefaultDates();
        });
    }

    bindEvents() {
        // Hotel search form submission
        const hotelForm = document.getElementById('hotelSearchForm');
        if (hotelForm) {
            hotelForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.performSearch();
            });
        }

        // Listen for tab initialization to bind events
        document.addEventListener('tabInitialized', (e) => {
            if (e.detail.tabId === 'hotels-tab') {
                this.bindEventsAfterTabLoad();
            }
        });
    }

    bindEventsAfterTabLoad() {
        const hotelForm = document.getElementById('hotelSearchForm');
        if (hotelForm && !hotelForm.hasAttribute('data-bound')) {
            hotelForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.performSearch();
            });
            hotelForm.setAttribute('data-bound', 'true');
        }
        this.setDefaultDates();
    }

    setDefaultDates() {
        const checkinInput = document.getElementById('checkin');
        const checkoutInput = document.getElementById('checkout');
        
        if (checkinInput && checkoutInput) {
            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);
            const dayAfter = new Date(today);
            dayAfter.setDate(dayAfter.getDate() + 2);

            checkinInput.value = tomorrow.toISOString().split('T')[0];
            checkoutInput.value = dayAfter.toISOString().split('T')[0];
            
            // Set minimum dates
            checkinInput.min = today.toISOString().split('T')[0];
            checkoutInput.min = tomorrow.toISOString().split('T')[0];

            // Update checkout min date when checkin changes
            checkinInput.addEventListener('change', () => {
                const checkinDate = new Date(checkinInput.value);
                const minCheckout = new Date(checkinDate);
                minCheckout.setDate(minCheckout.getDate() + 1);
                checkoutInput.min = minCheckout.toISOString().split('T')[0];
                
                if (checkoutInput.value <= checkinInput.value) {
                    checkoutInput.value = minCheckout.toISOString().split('T')[0];
                }
            });
        }
    }

    async performSearch() {
        const formData = this.getFormData();
        if (!this.validateFormData(formData)) {
            return;
        }

        this.showLoadingState();
        this.currentSearchParams = formData;

        try {
            const response = await fetch(`${this.apiBase}/api/hotel/search`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Search failed');
            }

            if (data.success) {
                this.displayResults(data.data);
            } else {
                this.showErrorState(data.message || 'No results found');
            }

        } catch (error) {
            console.error('Hotel search error:', error);
            this.showErrorState(error.message || 'An error occurred while searching for hotels');
        }
    }

    getFormData() {
        return {
            destination: document.getElementById('destination')?.value || '',
            country: document.getElementById('country')?.value || 'Philippines',
            checkin: document.getElementById('checkin')?.value || '',
            checkout: document.getElementById('checkout')?.value || '',
            adults: parseInt(document.getElementById('adults')?.value || '2'),
            children: parseInt(document.getElementById('children')?.value || '0'),
            rooms: parseInt(document.getElementById('rooms')?.value || '1'),
            currency: 'USD'
        };
    }

    validateFormData(data) {
        if (!data.destination.trim()) {
            this.showValidationError('Please enter a destination');
            return false;
        }

        if (!data.checkin) {
            this.showValidationError('Please select a check-in date');
            return false;
        }

        if (!data.checkout) {
            this.showValidationError('Please select a check-out date');
            return false;
        }

        const checkinDate = new Date(data.checkin);
        const checkoutDate = new Date(data.checkout);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (checkinDate < today) {
            this.showValidationError('Check-in date cannot be in the past');
            return false;
        }

        if (checkoutDate <= checkinDate) {
            this.showValidationError('Check-out date must be after check-in date');
            return false;
        }

        return true;
    }

    showValidationError(message) {
        // Create or update validation error display
        let errorDiv = document.getElementById('validationError');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.id = 'validationError';
            errorDiv.className = 'validation-error';
            errorDiv.style.cssText = `
                background: #f8d7da;
                color: #721c24;
                padding: 10px;
                border-radius: 5px;
                margin: 10px 0;
                border: 1px solid #f5c6cb;
            `;
            const form = document.getElementById('hotelSearchForm');
            form.appendChild(errorDiv);
        }
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';

        // Hide after 5 seconds
        setTimeout(() => {
            errorDiv.style.display = 'none';
        }, 5000);
    }

    showLoadingState() {
        this.hideAllStates();
        const loadingState = document.getElementById('hotelLoadingState');
        if (loadingState) {
            loadingState.style.display = 'block';
        }

        // Disable search button
        const searchBtn = document.getElementById('searchHotelsBtn');
        if (searchBtn) {
            searchBtn.disabled = true;
            searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';
        }
    }

    showErrorState(message) {
        this.hideAllStates();
        const errorState = document.getElementById('hotelErrorState');
        const errorMessage = document.getElementById('errorMessage');
        
        if (errorState && errorMessage) {
            errorMessage.textContent = message;
            errorState.style.display = 'block';
        }

        this.resetSearchButton();
    }

    showEmptyState() {
        this.hideAllStates();
        const emptyState = document.getElementById('noHotelResults');
        const mapContainer = document.getElementById('hotelMapContainer');

        if (emptyState) {
            emptyState.style.display = 'block';
        }

        // Hide map when no results
        if (mapContainer) {
            mapContainer.style.display = 'none';
        }

        this.resetSearchButton();
    }

    hideAllStates() {
        const states = ['hotelLoadingState', 'hotelResults', 'hotelErrorState', 'noHotelResults'];
        states.forEach(stateId => {
            const element = document.getElementById(stateId);
            if (element) {
                element.style.display = 'none';
            }
        });

        // Also hide map container by default
        const mapContainer = document.getElementById('hotelMapContainer');
        if (mapContainer) {
            mapContainer.style.display = 'none';
        }
    }

    resetSearchButton() {
        const searchBtn = document.getElementById('searchHotelsBtn');
        if (searchBtn) {
            searchBtn.disabled = false;
            searchBtn.innerHTML = '<i class="fas fa-search"></i> Search Hotels';
        }
    }

    displayResults(data) {
        this.hideAllStates();
        this.resetSearchButton();

        const resultsContainer = document.getElementById('hotelResults');
        const hotelsList = document.getElementById('hotelsList');
        const mapContainer = document.getElementById('hotelMapContainer');

        if (!resultsContainer || !hotelsList) {
            console.error('Results containers not found');
            return;
        }

        // Check if we have hotels data
        const hotels = data.hotels || data.itineraries || data.results || [];

        if (!hotels || hotels.length === 0) {
            this.showEmptyState();
            return;
        }

        // Show map container
        if (mapContainer) {
            mapContainer.style.display = 'block';
        }

        // Clear previous results
        hotelsList.innerHTML = '';

        // Display hotels in list
        hotels.forEach(hotel => {
            const hotelCard = this.createHotelCard(hotel);
            hotelsList.appendChild(hotelCard);
        });

        // Show results container
        resultsContainer.style.display = 'block';

        // Add hotels to map
        this.addHotelsToMap(hotels);
    }

    createHotelCard(hotel) {
        const card = document.createElement('div');
        card.className = 'hotel-card';

        const hotelName = hotel.hotelName || hotel.name || 'Hotel Name Not Available';
        const address = hotel.address || hotel.location || 'Address not available';
        const rating = hotel.hotelRating || hotel.rating || hotel.starRating || 0;
        const price = hotel.total || hotel.price || hotel.totalPrice || 0;
        const currency = hotel.currency || 'USD';

        // Generate star rating
        const stars = this.generateStarRating(rating);

        card.innerHTML = `
            <div class="hotel-name">${this.escapeHtml(hotelName)}</div>
            <div class="hotel-rating">
                <span class="stars">${stars}</span>
                <span class="rating-text">${rating}/5</span>
            </div>
            <div class="hotel-address">
                <i class="fas fa-map-marker-alt"></i> ${this.escapeHtml(address)}
            </div>
            <div class="hotel-price">
                <div>
                    <span class="price-amount">${currency} ${price}</span>
                    <div class="price-per-night">per night</div>
                </div>
                <button class="book-btn" onclick="hotelSearch.bookHotel('${hotel.id || ''}')">
                    <i class="fas fa-calendar-check"></i> Book Now
                </button>
            </div>
        `;

        return card;
    }

    generateStarRating(rating) {
        const fullStars = Math.floor(rating);
        const hasHalfStar = rating % 1 >= 0.5;
        let stars = '';

        for (let i = 0; i < fullStars; i++) {
            stars += '<i class="fas fa-star"></i>';
        }

        if (hasHalfStar) {
            stars += '<i class="fas fa-star-half-alt"></i>';
        }

        const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
        for (let i = 0; i < emptyStars; i++) {
            stars += '<i class="far fa-star"></i>';
        }

        return stars;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    bookHotel(hotelId) {
        // Placeholder for booking functionality
        alert('Booking functionality will be implemented here. Hotel ID: ' + hotelId);
    }

    // Method to be called from chat when hotel search is triggered
    searchFromChat(searchParams) {
        // Show hotels tab first
        const hotelsTab = document.getElementById('hotels-tab');
        if (hotelsTab) {
            hotelsTab.style.display = 'block';
            
            // Initialize tab if not already done
            if (hotelsTab.getAttribute('data-initialized') !== 'true') {
                initializeTabContent('hotels-tab', hotelsTab);
            }
        }

        // Wait a bit for tab to load, then populate form and search
        setTimeout(() => {
            this.populateFormFromChat(searchParams);
            this.performSearch();
        }, 100);
    }

    populateFormFromChat(params) {
        if (params.destination) {
            const destInput = document.getElementById('destination');
            if (destInput) destInput.value = params.destination;
        }

        if (params.country) {
            const countrySelect = document.getElementById('country');
            if (countrySelect) countrySelect.value = params.country;
        }

        if (params.checkin) {
            const checkinInput = document.getElementById('checkin');
            if (checkinInput) checkinInput.value = params.checkin;
        }

        if (params.checkout) {
            const checkoutInput = document.getElementById('checkout');
            if (checkoutInput) checkoutInput.value = params.checkout;
        }

        if (params.adults) {
            const adultsSelect = document.getElementById('adults');
            if (adultsSelect) adultsSelect.value = params.adults;
        }

        if (params.children) {
            const childrenSelect = document.getElementById('children');
            if (childrenSelect) childrenSelect.value = params.children;
        }

        if (params.rooms) {
            const roomsSelect = document.getElementById('rooms');
            if (roomsSelect) roomsSelect.value = params.rooms;
        }
    }

    // Add hotels to map
    async addHotelsToMap(hotels) {
        if (!window.hotelMap) {
            console.error('Hotel map instance not found');
            return;
        }

        try {
            await window.hotelMap.addHotelMarkers(hotels);
        } catch (error) {
            console.error('Failed to add hotels to map:', error);
        }
    }
}

// Global retry function
function retryHotelSearch() {
    if (window.hotelSearch && window.hotelSearch.currentSearchParams) {
        window.hotelSearch.performSearch();
    }
}

// Initialize hotel search when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.hotelSearch = new HotelSearch();
});

// Export for module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = HotelSearch;
}
