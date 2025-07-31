class HotelMap {
    constructor() {
        this.map = null;
        this.markers = [];
        this.infoWindow = null;
        this.currentHotels = [];
        this.isMapReady = false;
        this.isGoogleMapsLoaded = false;
        this.isInitializing = false;
        this.defaultCenter = { lat: 14.5995, lng: 120.9842 };
    }

    async init() {
        if (this.isInitializing) return;
        this.isInitializing = true;

        try {
            const mapContainer = document.getElementById('hotelMap');
            if (!mapContainer) {
                this.isInitializing = false;
                return false;
            }

            if (!this.isGoogleMapsLoaded) {
                await this.loadGoogleMapsAPI();
            }

            this.initializeMap();
            return true;
        } catch (error) {
            console.error('Failed to initialize hotel map:', error);
            this.isInitializing = false;
            return false;
        }
    }

    loadGoogleMapsAPI() {
        return new Promise((resolve, reject) => {
            if (this.isGoogleMapsAvailable()) {
                this.isGoogleMapsLoaded = true;
                resolve();
                return;
            }

            if (window.googleMapsLoading) {
                const checkLoaded = () => {
                    if (this.isGoogleMapsAvailable()) {
                        this.isGoogleMapsLoaded = true;
                        resolve();
                    } else if (window.googleMapsLoadError) {
                        reject(new Error('Google Maps API failed to load'));
                    } else {
                        setTimeout(checkLoaded, 100);
                    }
                };
                checkLoaded();
                return;
            }

            window.googleMapsLoading = true;

            const callbackName = 'initGoogleMapsCallback_' + Date.now();
            window[callbackName] = () => {
                console.log('Google Maps callback triggered');
                setTimeout(() => {
                    if (this.isGoogleMapsAvailable()) {
                        this.isGoogleMapsLoaded = true;
                        window.googleMapsLoading = false;
                        console.log('Google Maps API loaded and ready');
                        resolve();
                    } else {
                        window.googleMapsLoadError = true;
                        window.googleMapsLoading = false;
                        console.error('Google Maps API loaded but objects not available');
                        reject(new Error('Google Maps API objects not available'));
                    }
                }, 200);
            };

            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=AIzaSyANvqC2YcKdFiOB5ZSBRZbZfzOl5EzmVdU&loading=async&callback=${callbackName}`;
            script.async = true;
            script.defer = true;

            script.onerror = () => {
                window.googleMapsLoadError = true;
                window.googleMapsLoading = false;
                console.error('Failed to load Google Maps API script');
                reject(new Error('Failed to load Google Maps API script'));
            };

            document.head.appendChild(script);
        });
    }

    initializeMap() {
        const mapContainer = document.getElementById('hotelMap');
        if (!mapContainer) {
            console.warn('Hotel map container not found during initialization');
            this.isInitializing = false;
            return;
        }

        if (typeof google === 'undefined' || !google.maps || !google.maps.Map || !google.maps.MapTypeId) {
            console.error('Google Maps API not fully loaded');
            this.isInitializing = false;
            throw new Error('Google Maps API not fully loaded');
        }

        this.map = new google.maps.Map(mapContainer, {
            zoom: 6,
            center: this.defaultCenter,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            disableDefaultUI: true,
            gestureHandling: 'cooperative',
            clickableIcons: false,
            keyboardShortcuts: false,
            backgroundColor: '#ffffff',
            styles: [
                {
                    "featureType": "administrative.land_parcel",
                    "elementType": "labels",
                    "stylers": [
                        {
                            "visibility": "off"
                        }
                    ]
                },
                {
                    "featureType": "poi",
                    "elementType": "labels.text",
                    "stylers": [
                        {
                            "visibility": "off"
                        }
                    ]
                },
                {
                    "featureType": "poi.business",
                    "stylers": [
                        {
                            "visibility": "off"
                        }
                    ]
                },
                {
                    "featureType": "road",
                    "elementType": "labels.icon",
                    "stylers": [
                        {
                            "visibility": "off"
                        }
                    ]
                },
                {
                    "featureType": "road.local",
                    "elementType": "labels",
                    "stylers": [
                        {
                            "visibility": "off"
                        }
                    ]
                },
                {
                    "featureType": "transit",
                    "stylers": [
                        {
                            "visibility": "off"
                        }
                    ]
                }
            ]
        });

        this.infoWindow = new google.maps.InfoWindow();

        this.isMapReady = true;
        this.isInitializing = false;
        console.log('Hotel map initialized successfully');

        document.dispatchEvent(new CustomEvent('hotelMapReady'));
    }

    async addHotelMarkers(hotels) {
        if (!hotels || hotels.length === 0) {
            return;
        }

        if (!this.isMapReady) {
            try {
                const initialized = await this.init();
                if (!initialized) {
                    console.error('Failed to initialize map for hotel markers');
                    return;
                }
            } catch (error) {
                console.error('Error initializing map:', error);
                return;
            }
        }

        this.clearMarkers();
        
        this.currentHotels = hotels;
        const bounds = new google.maps.LatLngBounds();

        hotels.forEach((hotel, index) => {
            const lat = parseFloat(hotel.latitude);
            const lng = parseFloat(hotel.longitude);
            
            if (isNaN(lat) || isNaN(lng)) {
                console.warn('Invalid coordinates for hotel:', hotel.hotelName);
                return;
            }

            const position = { lat, lng };

            const marker = new google.maps.Marker({
                position: position,
                map: this.map,
                title: hotel.hotelName,
                icon: {
                    url: this.createHotelMarkerIcon(hotel.hotelRating),
                    scaledSize: new google.maps.Size(32, 32),
                    anchor: new google.maps.Point(16, 32),
                    labelOrigin: new google.maps.Point(16, -10)
                },
                label: {
                    text: hotel.hotelName,
                    color: '#000',
                    fontSize: '13px',
                    fontWeight: '600',
                    className: 'hotel-marker-label'
                },
                zIndex: 1000 - index
            });

            marker.addListener('click', () => {
                this.showHotelDetails(hotel, marker);
            });

            this.markers.push(marker);
            bounds.extend(position);
        });

        if (this.markers.length > 0) {
            this.map.fitBounds(bounds);

            google.maps.event.addListenerOnce(this.map, 'bounds_changed', () => {
                if (this.map.getZoom() > 15) {
                    this.map.setZoom(15);
                }
            });
        }
    }

    createHotelMarkerIcon(rating) {
        const canvas = document.createElement('canvas');
        canvas.width = 32;
        canvas.height = 32;
        const ctx = canvas.getContext('2d');

        ctx.beginPath();
        ctx.arc(16, 16, 15, 0, 2 * Math.PI);
        ctx.fillStyle = '#ffffff';
        ctx.fill();
        ctx.strokeStyle = '#000000';
        ctx.lineWidth = 2;
        ctx.stroke();

        ctx.fillStyle = '#000000';
        ctx.strokeStyle = '#000000';
        ctx.lineWidth = 1;

        ctx.fillRect(8, 18, 16, 4);
        ctx.fillRect(7, 14, 2, 6);
        ctx.fillRect(9, 22, 1, 3);
        ctx.fillRect(22, 22, 1, 3);
        ctx.fillRect(10, 16, 4, 2);

        return canvas.toDataURL();
    }

    showHotelDetails(hotel, marker) {
        const content = this.createInfoWindowContent(hotel);

        this.infoWindow.setContent(content);
        this.infoWindow.open(this.map, marker);

        setTimeout(() => {
            const viewDetailsBtn = document.getElementById(`viewDetails_${hotel.hotelId}`);
            if (viewDetailsBtn) {
                viewDetailsBtn.addEventListener('click', () => {
                    this.showDetailedHotelView(hotel);
                });
            }
        }, 100);
    }

    createInfoWindowContent(hotel) {
        const rating = hotel.hotelRating || 0;
        const stars = this.generateStarRating(rating);
        const price = hotel.total || 0;
        const currency = hotel.currency || 'PHP';

        const hotelImage = this.getHotelImage(hotel);

        return `
            <div class="hotel-info-window">
                <div class="hotel-info-image">
                    <img src="${hotelImage}" alt="${this.escapeHtml(hotel.hotelName)}" class="hotel-preview-img">
                </div>
                <h3 class="hotel-info-name">${this.escapeHtml(hotel.hotelName)}</h3>
                <div class="hotel-info-rating">
                    <span class="stars">${stars}</span>
                    <span class="rating-text">${rating}/5</span>
                </div>
                <div class="hotel-info-address">
                    <i class="fas fa-map-marker-alt"></i> ${this.escapeHtml(hotel.address)}
                </div>
                <div class="hotel-info-price">
                    <strong>${currency} ${price}</strong> per night
                </div>
                <button id="viewDetails_${hotel.hotelId}" class="view-details-btn">
                    <i class="fas fa-eye"></i> View Details
                </button>
            </div>
        `;
    }

    showDetailedHotelView(hotel) {
        this.infoWindow.close();

        const mapContainer = document.getElementById('hotelMapContainer');
        const detailPanel = document.getElementById('hotelDetailPanel');

        if (mapContainer) mapContainer.style.display = 'none';
        if (detailPanel) {
            detailPanel.style.display = 'block';
            this.populateHotelDetailPanel(hotel);
        }
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

    clearMarkers() {
        this.markers.forEach(marker => {
            marker.setMap(null);
        });
        this.markers = [];
    }

    getHotelImage(hotel) {
        const baseUrl = 'https://images.unsplash.com/';
        const hotelImages = [
            `${baseUrl}photo-1566073771259-6a8506099945?w=250&h=150&fit=crop`,
            `${baseUrl}photo-1631049307264-da0ec9d70304?w=250&h=150&fit=crop`,
            `${baseUrl}photo-1582719478250-c89cae4dc85b?w=250&h=150&fit=crop`,
            `${baseUrl}photo-1571896349842-33c89424de2d?w=250&h=150&fit=crop`
        ];

        const imageIndex = hotel.hotelId ? parseInt(hotel.hotelId.slice(-1)) % hotelImages.length : 0;
        return hotelImages[imageIndex];
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    centerOnLocation(lat, lng, zoom = 12) {
        if (this.isMapReady) {
            this.map.setCenter({ lat, lng });
            this.map.setZoom(zoom);
        }
    }

    getMap() {
        return this.map;
    }

    isReady() {
        return this.isMapReady;
    }

    isGoogleMapsAvailable() {
        return typeof google !== 'undefined' &&
               google.maps &&
               google.maps.Map &&
               google.maps.MapTypeId &&
               google.maps.Marker;
    }

    populateHotelDetailPanel(hotel) {
        const detailContent = document.getElementById('hotelDetailContent');
        if (!detailContent) return;

        const rating = hotel.hotelRating || 0;
        const stars = this.generateStarRating(rating);
        const price = hotel.total || 0;
        const currency = hotel.currency || 'PHP';
        const address = hotel.address || 'Address not available';
        const city = hotel.city || '';
        const locality = hotel.locality || '';

        const sampleImages = this.generateSampleImages(hotel);
        const overview = this.generateHotelOverview(hotel);

        detailContent.innerHTML = `
            <div class="hotel-detail-grid">
                <!-- Hotel Images -->
                <div class="hotel-images">
                    <div class="main-image">
                        <img src="${sampleImages[0]}" alt="${hotel.hotelName}" class="hotel-main-img">
                    </div>
                    <div class="thumbnail-images">
                        ${sampleImages.slice(1, 4).map(img =>
                            `<img src="${img}" alt="Hotel view" class="hotel-thumb" onclick="hotelMap.changeMainImage('${img}')">`
                        ).join('')}
                    </div>
                </div>

                <!-- Hotel Information -->
                <div class="hotel-info">
                    <h2 class="hotel-name">${this.escapeHtml(hotel.hotelName)}</h2>

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
                                <button class="book-now-btn" onclick="hotelMap.bookHotel('${hotel.hotelId}', '${hotel.hotelName}')">
                                    <i class="fas fa-calendar-check"></i>
                                    Book Now
                                </button>
                                <button class="add-to-favorites-btn" onclick="hotelMap.addToFavorites('${hotel.hotelId}', '${hotel.hotelName}')">
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

        this.setDefaultDates();
        this.bindBackToMapButton();
    }

    generateSampleImages(hotel) {
        const baseUrl = 'https://images.unsplash.com/';
        const hotelImages = [
            `${baseUrl}photo-1566073771259-6a8506099945?w=600&h=400&fit=crop`,
            `${baseUrl}photo-1631049307264-da0ec9d70304?w=300&h=200&fit=crop`,
            `${baseUrl}photo-1582719478250-c89cae4dc85b?w=300&h=200&fit=crop`,
            `${baseUrl}photo-1571896349842-33c89424de2d?w=300&h=200&fit=crop`
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

    setDefaultDates() {
        const checkinInput = document.getElementById('checkinDate');
        const checkoutInput = document.getElementById('checkoutDate');

        if (checkinInput && checkoutInput) {
            const today = new Date();
            const tomorrow = new Date(today);
            tomorrow.setDate(tomorrow.getDate() + 1);

            checkinInput.value = today.toISOString().split('T')[0];
            checkoutInput.value = tomorrow.toISOString().split('T')[0];

            checkinInput.min = today.toISOString().split('T')[0];
            checkoutInput.min = tomorrow.toISOString().split('T')[0];

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

    bindBackToMapButton() {
        const backBtn = document.getElementById('backToMapBtn');
        if (backBtn) {
            backBtn.addEventListener('click', () => {
                this.showMapView();
            });
        }
    }

    showMapView() {
        const mapContainer = document.getElementById('hotelMapContainer');
        const detailPanel = document.getElementById('hotelDetailPanel');

        if (detailPanel) detailPanel.style.display = 'none';
        if (mapContainer) mapContainer.style.display = 'block';
    }

    changeMainImage(imageSrc) {
        const mainImg = document.querySelector('.hotel-main-img');
        if (mainImg) {
            mainImg.src = imageSrc;
        }
    }

    bookHotel(hotelId, hotelName) {
        const checkinDate = document.getElementById('checkinDate')?.value;
        const checkoutDate = document.getElementById('checkoutDate')?.value;
        const adults = document.getElementById('adults')?.value;
        const children = document.getElementById('children')?.value;

        alert(`Booking ${hotelName}\nCheck-in: ${checkinDate}\nCheck-out: ${checkoutDate}\nGuests: ${adults} adults, ${children} children`);
    }

    addToFavorites(hotelId, hotelName) {
        alert(`${hotelName} added to favorites!`);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.hotelMap = new HotelMap();
});

if (typeof module !== 'undefined' && module.exports) {
    module.exports = HotelMap;
}
