<!DOCTYPE html>
<html>
<head>
    <title>Travelopro Hotel Search</title>
</head>
<body>
    <h1>Travelopro Hotel Search</h1>

    <?= form_open('hotelsearch') ?>

    <label>City Name:</label><br>
    <input type="text" name="city_name" value="<?= set_value('city_name', 'Bangalore') ?>" required><br><br>

    <label>Country Name:</label><br>
    <input type="text" name="country_name" value="<?= set_value('country_name', 'India') ?>" required><br><br>

    <label>Check-in Date:</label><br>
    <input type="date" name="checkin" value="<?= set_value('checkin', date('Y-m-d', strtotime('+1 day'))) ?>" required><br><br>

    <label>Check-out Date:</label><br>
    <input type="date" name="checkout" value="<?= set_value('checkout', date('Y-m-d', strtotime('+2 days'))) ?>" required><br><br>

    <label>Currency:</label><br>
    <input type="text" name="currency" value="<?= set_value('currency', 'INR') ?>"><br><br>

    <button type="submit">Search Hotels</button>

    <?= form_close() ?>

    <?php if (isset($error)): ?>
        <p style="color:red;">Error: <?= esc($error) ?></p>
    <?php elseif (isset($response)): ?>
        <p><strong>HTTP Status Code:</strong> <?= esc($http_code) ?></p>

        <h3>Raw API Response JSON:</h3>
        <pre style="background:#eee; padding:10px; max-height:400px; overflow:auto;">
<?= esc(json_encode($response, JSON_PRETTY_PRINT)) ?>
        </pre>

        <?php if (!empty($response['itineraries'])): ?>
            <h2>Available Hotels</h2>
            <ul>
                <?php foreach ($response['itineraries'] as $hotel): ?>
                    <li style="margin-bottom: 15px;">
                        <strong><?= esc($hotel['hotelName'] ?? 'No hotel name') ?></strong><br>
                        Address: <?= esc($hotel['address'] ?? 'N/A') ?><br>
                        Rating: <?= esc($hotel['hotelRating'] ?? 'N/A') ?><br>
                        Total Price: <?= esc($hotel['total'] ?? 'N/A') ?> <?= esc($hotel['currency'] ?? '') ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No hotels found or unexpected API format.</p>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (isset($response)): ?>
    <script>
        console.log('API Response:', <?= json_encode($response, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>);
    </script>
    <?php endif; ?>

</body>
</html>
