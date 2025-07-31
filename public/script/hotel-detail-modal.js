// Hotel Detail Modal Module
class HotelDetailModal {
    constructor() {
        this.modal = null;
        this.currentHotel = null;
        this.init();
    }

    init() {
        // Listen for show hotel details event
        document.addEventListener('showHotelDetails', (e) => {
            this.showModal(e.detail.hotel);
        });

        // Initialize modal elements
        document.addEventListener('DOMContentLoaded', () => {
            this.modal = document.getElementById('hotelDetailModal');
            this.bindEvents();
        });
    }

    bindEvents() {
        // Close modal when clicking outside
        if (this.modal) {
            this.modal.addEventListener('click', (e) => {
                if (e.target === this.modal) {
                    this.closeModal();
                }
            });
        }

        // Close modal with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal && this.modal.style.display !== 'none') {
                this.closeModal();
            }
        });
    }

    showModal(hotel) {
        if (!this.modal || !hotel) return;

        this.currentHotel = hotel;
        
        // Update modal content
        this.updateModalContent(hotel);
        
        // Show modal
        this.modal.style.display = 'flex';
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }

    updateModalContent(hotel) {
        // Update hotel name in header
        const modalHotelName = document.getElementById('modalHotelName');
        if (modalHotelName) {
            modalHotelName.textContent = hotel.hotelName || 'Hotel Name Not Available';
        }

        // Update modal body content
        const modalBody = this.modal.querySelector('.hotel-detail-content');
        if (modalBody) {
            modalBody.innerHTML = this.createDetailContent(hotel);
            
            // Bind events for interactive elements
            this.bindModalEvents();
        }
    }

    createDetailContent(hotel) {
        const rating = hotel.hotelRating || 0;
        const stars = this.generateStarRating(rating);
        const price = hotel.total || 0;
        const currency = hotel.currency || 'PHP';
        const address = hotel.address || 'Address not available';
        const city = hotel.city || '';
        const locality = hotel.locality || '';

        // Generate sample images (in real implementation, these would come from the API)
        const sampleImages = this.generateSampleImages(hotel);
        
        // Generate overview based on hotel data
        const overview = this.generateHotelOverview(hotel);

        return `
            <div class="hotel-detail-grid">
                <!-- Hotel Images -->
                <div class="hotel-images">
                    <div class="main-image">
                        <img src="${sampleImages[0]}" alt="${hotel.hotelName}" class="hotel-main-img">
                    </div>
                    <div class="thumbnail-images">
                        ${sampleImages.slice(1, 4).map(img => 
                            `<img src="${img}" alt="Hotel view" class="hotel-thumb" onclick="hotelDetailModal.changeMainImage('${img}')">`
                        ).join('')}
                    </div>
                </div>

                <!-- Hotel Information -->
                <div class="hotel-info">
                    <div class="hotel-rating-section">
                        <div class="stars">${stars}</div>
                        <span class="rating-text">${rating}/5 Stars</span>
                    </div>

                    <div class="hotel-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <div class="address">${this.escapeHtml(address)}</div>
                            <div class="city-locality">${this.escapeHtml(city)}${locality ? ', ' + locality : ''}</div>
                        </div>
                    </div>

                    <div class="hotel-price-section">
                        <div class="price-display">
                            <span class="currency">${currency}</span>
                            <span class="amount">${price}</span>
                            <span class="per-night">per night</span>
                        </div>
                    </div>

                    <!-- Overview -->
                    <div class="hotel-overview">
                        <h4>Overview</h4>
                        <p>${overview}</p>
                    </div>

                    <!-- Booking Options -->
                    <div class="booking-section">
                        <h4>Booking Details</h4>
                        
                        <div class="booking-form">
                            <div class="date-inputs">
                                <div class="input-group">
                                    <label for="checkinDate">Check-in</label>
                                    <input type="date" id="checkinDate" class="date-input">
                                </div>
                                <div class="input-group">
                                    <label for="checkoutDate">Check-out</label>
                                    <input type="date" id="checkoutDate" class="date-input">
                                </div>
                            </div>

                            <div class="guest-inputs">
                                <div class="input-group">
                                    <label for="adults">Adults</label>
                                    <select id="adults" class="guest-select">
                                        <option value="1">1 Adult</option>
                                        <option value="2" selected>2 Adults</option>
                                        <option value="3">3 Adults</option>
                                        <option value="4">4 Adults</option>
                                    </select>
                                </div>
                                <div class="input-group">
                                    <label for="children">Children</label>
                                    <select id="children" class="guest-select">
                                        <option value="0" selected>0 Children</option>
                                        <option value="1">1 Child</option>
                                        <option value="2">2 Children</option>
                                        <option value="3">3 Children</option>
                                    </select>
                                </div>
                            </div>

                            <div class="booking-actions">
                                <button class="book-now-btn" onclick="hotelDetailModal.bookHotel()">
                                    <i class="fas fa-calendar-check"></i>
                                    Book Now
                                </button>
                                <button class="add-to-favorites-btn" onclick="hotelDetailModal.addToFavorites()">
                                    <i class="far fa-heart"></i>
                                    Add to Favorites
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="additional-info">
                        <h4>Hotel Information</h4>
                        <div class="info-grid">
                            <div class="info-item">
                                <i class="fas fa-wifi"></i>
                                <span>Free WiFi</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-swimming-pool"></i>
                                <span>Swimming Pool</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-parking"></i>
                                <span>Free Parking</span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-utensils"></i>
                                <span>Restaurant</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    generateSampleImages(hotel) {
        // In a real implementation, these would come from the hotel data
        // For now, we'll use placeholder images
        const baseUrl = 'https://images.unsplash.com/';
        const hotelImages = [
            `${baseUrl}photo-1566073771259-6a8506099945?w=600&h=400&fit=crop`, // Hotel exterior
            `${baseUrl}photo-1631049307264-da0ec9d70304?w=300&h=200&fit=crop`, // Hotel room
            `${baseUrl}photo-1582719478250-c89cae4dc85b?w=300&h=200&fit=crop`, // Hotel lobby
            `${baseUrl}photo-1571896349842-33c89424de2d?w=300&h=200&fit=crop`  // Hotel pool
        ];
        
        return hotelImages;
    }

    generateHotelOverview(hotel) {
        const city = hotel.city || 'the Philippines';
        const rating = hotel.hotelRating || 3;
        
        let overview = `Experience comfort and hospitality at ${hotel.hotelName}, `;
        
        if (rating >= 4) {
            overview += `a premium ${rating}-star hotel located in the heart of ${city}. `;
        } else if (rating >= 3) {
            overview += `a comfortable ${rating}-star accommodation in ${city}. `;
        } else {
            overview += `a budget-friendly option in ${city}. `;
        }
        
        overview += `This hotel offers modern amenities and convenient access to local attractions, `;
        overview += `making it an ideal choice for both business and leisure travelers. `;
        overview += `Enjoy well-appointed rooms, friendly service, and a memorable stay in this beautiful destination.`;
        
        return overview;
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

    bindModalEvents() {
        // Set default dates
        this.setDefaultDates();
    }

    setDefaultDates() {
        const checkinInput = document.getElementById('checkinDate');
        const checkoutInput = document.getElementById('checkoutDate');
        
        if (checkinInput && checkoutInput) {
            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);
            
            checkinInput.value = today.toISOString().split('T')[0];
            checkoutInput.value = tomorrow.toISOString().split('T')[0];
            
            // Set minimum dates
            checkinInput.min = today.toISOString().split('T')[0];
            checkoutInput.min = tomorrow.toISOString().split('T')[0];
            
            // Update checkout min date when checkin changes
            checkinInput.addEventListener('change', () => {
                const checkinDate = new Date(checkinInput.value);
                const nextDay = new Date(checkinDate);
                nextDay.setDate(nextDay.getDate() + 1);
                checkoutInput.min = nextDay.toISOString().split('T')[0];
                
                if (checkoutInput.value <= checkinInput.value) {
                    checkoutInput.value = nextDay.toISOString().split('T')[0];
                }
            });
        }
    }

    changeMainImage(imageSrc) {
        const mainImg = document.querySelector('.hotel-main-img');
        if (mainImg) {
            mainImg.src = imageSrc;
        }
    }

    bookHotel() {
        if (!this.currentHotel) return;
        
        const checkinDate = document.getElementById('checkinDate')?.value;
        const checkoutDate = document.getElementById('checkoutDate')?.value;
        const adults = document.getElementById('adults')?.value;
        const children = document.getElementById('children')?.value;
        
        // In a real implementation, this would make an API call
        alert(`Booking ${this.currentHotel.hotelName}\nCheck-in: ${checkinDate}\nCheck-out: ${checkoutDate}\nGuests: ${adults} adults, ${children} children`);
    }

    addToFavorites() {
        if (!this.currentHotel) return;
        
        // In a real implementation, this would save to favorites
        alert(`${this.currentHotel.hotelName} added to favorites!`);
    }

    closeModal() {
        if (this.modal) {
            this.modal.style.display = 'none';
            document.body.style.overflow = ''; // Restore scrolling
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Global function for closing modal (called from HTML)
function closeHotelDetailModal() {
    if (window.hotelDetailModal) {
        window.hotelDetailModal.closeModal();
    }
}

// Initialize hotel detail modal when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.hotelDetailModal = new HotelDetailModal();
});

// Export for module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = HotelDetailModal;
}
