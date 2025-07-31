<div class="hotel-search-container">
    <!-- Loading State -->
    <div id="hotelLoadingState" class="loading-state" style="display: none;">
        <div class="loading-spinner"></div>
        <p>Searching for hotels...</p>
    </div>

    <!-- Map Panel - Primary View -->
    <div id="hotelMapContainer" class="map-container" style="display: none;">
        <div id="hotelMap" class="hotel-map"></div>
    </div>

    <!-- Hotel Detail Panel - Replaces Map When Pin Clicked -->
    <div id="hotelDetailPanel" class="hotel-detail-panel" style="display: none;">
        <div class="detail-header">
            <button id="backToMapBtn" class="back-to-map-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Map
            </button>
        </div>
        <div id="hotelDetailContent" class="hotel-detail-content">
            <!-- Hotel details will be populated here -->
        </div>
    </div>

    <!-- No Results -->
    <div id="noHotelResults" class="no-results" style="display: none;">
        <i class="fas fa-search"></i>
        <h3>No Hotels Found</h3>
        <p>We couldn't find any hotels matching your criteria.</p>
    </div>
</div>

<style>
.hotel-search-container {
    max-width: 100%;
}

.section-title {
    color: #2c3e50;
    margin-bottom: 20px;
    font-size: medium;
    display: flex;
    align-items: center;
    gap: 10px;
}

.loading-state {
    text-align: center;
    padding: 40px;
}

.loading-spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #007bff;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.results-section {
    margin-top: 20px;
}

.results-title {
    color: #2c3e50;
    margin-bottom: 15px;
}

.hotels-list {
    display: grid;
    gap: 15px;
}

.no-results {
    text-align: center;
    padding: 40px;
    color: #666;
}

/* Map Container Styles - Full Height/Width */
.map-container {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100%;
    height: 100%;
    background: white;
    overflow: hidden;
    border-radius: 12px;
}

.hotel-map {
    width: 100%;
    height: 100%;
    border: none;
    display: block;
    border-radius: 12px;
}

/* Info Window Styles */
.hotel-info-window {
    max-width: 280px;
    padding: 0;
    overflow: hidden;
    border-radius: 8px;
}

.hotel-info-image {
    width: 100%;
    height: 120px;
    overflow: hidden;
    margin-bottom: 10px;
}

.hotel-preview-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.hotel-info-window > *:not(.hotel-info-image) {
    padding: 0 12px;
}

.hotel-info-window .view-details-btn {
    margin: 10px 12px 12px 12px;
}

.hotel-info-name {
    font-size: 16px;
    font-weight: bold;
    color: #2c3e50;
    margin: 0 0 8px 0;
}

.hotel-info-rating {
    color: #f39c12;
    font-size: 14px;
    margin-bottom: 8px;
}

.hotel-info-address {
    color: #666;
    font-size: 12px;
    margin-bottom: 8px;
}

.hotel-info-address i {
    margin-right: 4px;
}

.hotel-info-price {
    color: #27ae60;
    font-size: 14px;
    font-weight: bold;
    margin-bottom: 10px;
}

.view-details-btn {
    background: #007bff;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 4px;
    transition: background 0.3s ease;
}

.view-details-btn:hover {
    background: #0056b3;
}

/* Hotel Detail Panel Styles - Full Height */
.hotel-detail-panel {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100%;
    height: 100%;
    background: white;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.detail-header {
    padding: 15px 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
}

.back-to-map-btn {
    background: #007bff;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.back-to-map-btn:hover {
    background: #0056b3;
    transform: translateX(-2px);
}

.hotel-detail-panel .hotel-detail-content {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
}

/* Hotel Detail Modal Styles */
.hotel-detail-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 12px;
    max-width: 600px;
    max-height: 80vh;
    width: 90%;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8f9fa;
}

.modal-header h3 {
    margin: 0;
    color: #2c3e50;
    font-size: 18px;
}

.close-modal {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: #666;
    padding: 5px;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.close-modal:hover {
    background: #e9ecef;
    color: #333;
}

.modal-body {
    padding: 20px;
    max-height: 60vh;
    overflow-y: auto;
}

/* Hotel Detail Content Styles */
.hotel-detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.hotel-images .main-image {
    margin-bottom: 10px;
}

.hotel-main-img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 8px;
}

.thumbnail-images {
    display: flex;
    gap: 5px;
}

.hotel-thumb {
    width: 60px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
    cursor: pointer;
    opacity: 0.7;
    transition: opacity 0.3s ease;
}

.hotel-thumb:hover {
    opacity: 1;
}

.hotel-info h4 {
    color: #2c3e50;
    margin: 15px 0 10px 0;
    font-size: 16px;
}

.hotel-rating-section {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.hotel-rating-section .stars {
    color: #f39c12;
}

.hotel-rating-section .rating-text {
    color: #666;
    font-size: 14px;
}

.hotel-location {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin-bottom: 15px;
    color: #666;
}

.hotel-location i {
    color: #007bff;
    margin-top: 2px;
}

.address {
    font-weight: 500;
    color: #333;
}

.city-locality {
    font-size: 12px;
    color: #666;
}

.hotel-price-section {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.price-display {
    display: flex;
    align-items: baseline;
    gap: 5px;
}

.currency {
    font-size: 14px;
    color: #666;
}

.amount {
    font-size: 24px;
    font-weight: bold;
    color: #27ae60;
}

.per-night {
    font-size: 12px;
    color: #666;
}

.hotel-overview p {
    color: #666;
    line-height: 1.5;
    font-size: 14px;
}

.booking-form {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
}

.date-inputs, .guest-inputs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 15px;
}

.input-group {
    display: flex;
    flex-direction: column;
}

.input-group label {
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
    font-weight: 500;
}

.date-input, .guest-select {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.booking-actions {
    display: flex;
    gap: 10px;
}

.book-now-btn, .add-to-favorites-btn {
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all 0.3s ease;
}

.book-now-btn {
    background: #28a745;
    color: white;
}

.book-now-btn:hover {
    background: #218838;
}

.add-to-favorites-btn {
    background: white;
    color: #007bff;
    border: 1px solid #007bff;
}

.add-to-favorites-btn:hover {
    background: #007bff;
    color: white;
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #666;
}

.info-item i {
    color: #007bff;
    width: 16px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .hotel-detail-grid {
        grid-template-columns: 1fr;
    }

    .date-inputs, .guest-inputs {
        grid-template-columns: 1fr;
    }

    .booking-actions {
        flex-direction: column;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }
}

.no-results i {
    font-size: medium;
    margin-bottom: 20px;
    color: #ccc;
}
</style>
