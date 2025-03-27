<?php 

/**
 * обработка ajax запроса
 */
add_action('wp_ajax_search_cities', 'search_cities');
add_action('wp_ajax_nopriv_search_cities', 'search_cities');

function search_cities() {
    global $wpdb;

    if (!isset($_POST['search']) || empty($_POST['search'])) {
        wp_die();
    }
    //используем тот же запрос что и для рендеринга страницы
    $search = sanitize_text_field($_POST['search']);
    $cities = Cities_Table::get_query($search);

    if ($cities) {
        echo "<table border='1'><tr><th>Город</th><th>Страна</th><th>Температура</th></tr>";
        foreach ($cities as $city) {
            $temperature = WeatherAPI::get_temperature($city->latitude, $city->longitude);
            echo "<tr><td>{$city->city}</td><td>{$city->country}</td><td>{$temperature}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "Города не найдены.";
    }

    wp_die();
}