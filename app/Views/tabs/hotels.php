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

