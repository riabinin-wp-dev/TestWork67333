<?php

/**
 * класс регистрации
 */
class Register
{
    public function __construct()
    {
        self::init();
    }

    /**
     * ининциализация
     */
    static function init()
    {
        add_action('init', [__CLASS__, 'register_cities_post_type']);
        add_action('add_meta_boxes', [__CLASS__, 'add_city_meta_boxes']);
        add_action('save_post', [__CLASS__, 'save_city_meta']);
        add_action('init', [__CLASS__, 'register_countries_taxonomy']);
        add_action('customize_register', [__CLASS__, 'add_api_to_customizer']);
        add_action('widgets_init', [__CLASS__, 'register_weather_city_widget']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_custom_page_script']);
        WeatherAPI::init();
    }

    /**
     * регистрация скриптов
     */
    static function enqueue_custom_page_script()
    {
        wp_enqueue_script('custom-template', get_stylesheet_directory_uri() . '/assets/js/custom-template.js',  array(),  null, true);
        wp_localize_script('custom-template', 'CitySearchData', array('ajaxurl' => admin_url('admin-ajax.php'),));
    }

    /**
     * регистрация custom post type - cities
     */
    static function register_cities_post_type()
    {
        $labels = [
            'name'               => 'Города',
            'singular_name'      => 'Город',
            'menu_name'          => 'Города',
            'add_new'            => 'Добавить город',
            'add_new_item'       => 'Добавить новый город',
            'edit_item'          => 'Редактировать город',
            'new_item'           => 'Новый город',
            'view_item'          => 'Просмотр города',
            'search_items'       => 'Поиск городов',
            'not_found'          => 'Города не найдены',
            'not_found_in_trash' => 'В корзине городов не найдено',
        ];

        $args = [
            'label'              => 'Города',
            'labels'             => $labels,
            'public'             => true,
            'has_archive'        => true,
            'menu_position'      => 5,
            'menu_icon'          => 'dashicons-location-alt',
            'supports'           => ['title', 'editor', 'thumbnail'],
            'rewrite'            => ['slug' => 'cities'],
        ];

        register_post_type('cities', $args);
    }

    /**
     * регистрация метабокса
     */
    static function add_city_meta_boxes()
    {
        add_meta_box(
            'city_coordinates',
            'Координаты города',
            [__CLASS__, 'render_city_meta_boxes'],
            'cities',
            'side'
        );
    }

    /**
     * callback metabox
     */

    static function render_city_meta_boxes($post)
    {
        $latitude = get_post_meta($post->ID, '_city_latitude', true);
        $longitude = get_post_meta($post->ID, '_city_longitude', true);
        wp_nonce_field('save_city_meta', 'city_meta_nonce'); ?>
        <p>
            <label for="city_latitude">Широта:</label>
            <input type="text" id="city_latitude" name="city_latitude" value="<?php echo esc_attr($latitude); ?>" style="width: 100%;">
        </p>
        <p>
            <label for="city_longitude">Долгота:</label>
            <input type="text" id="city_longitude" name="city_longitude" value="<?php echo esc_attr($longitude); ?>" style="width: 100%;">
        </p>
    <?php
    }

    /**
     * сохранение метабокса
     */
    static function save_city_meta($post_id)
    {
        if (!isset($_POST['city_meta_nonce']) || !wp_verify_nonce($_POST['city_meta_nonce'], 'save_city_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (isset($_POST['city_latitude'])) {
            update_post_meta($post_id, '_city_latitude', sanitize_text_field($_POST['city_latitude']));
        }

        if (isset($_POST['city_longitude'])) {
            update_post_meta($post_id, '_city_longitude', sanitize_text_field($_POST['city_longitude']));
        }
    }

    /**
     * регистрируем таксономию
     */
    static function register_countries_taxonomy()
    {
        $labels = [
            'name'              => 'Страны',
            'singular_name'     => 'Страна',
            'search_items'      => 'Поиск стран',
            'all_items'         => 'Все страны',
            'edit_item'         => 'Редактировать страну',
            'update_item'       => 'Обновить страну',
            'add_new_item'      => 'Добавить новую страну',
            'new_item_name'     => 'Название новой страны',
            'menu_name'         => 'Страны',
        ];

        $args = [
            'labels'            => $labels,
            'public'            => true,
            'hierarchical'      => true,
            'show_admin_column' => true,
            'rewrite'           => ['slug' => 'countries'],
        ];

        register_taxonomy('countries', ['cities'], $args);
    }

    /**
     * добавим api ключ в кастомайзер, чтоб не хранить в коде
     */
    static function add_api_to_customizer($wp_customize)
    {
        $wp_customize->add_setting('api_openweathermap', [
            'default' => '',
            'transport' => 'refresh',
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        $wp_customize->add_control('api_openweathermap', [
            'label' => __('API Openweathermap', 'storefront'),
            'section' => 'storefront_more',
            'settings' => 'api_openweathermap',
            'type' => 'text',
            'description' => __('Введите API ключ', 'storefront'),
        ]);
    }

    /**
     * зарегистрируем наш виджет 
     */
    static function register_weather_city_widget()
    {
        register_widget('Weather_City_Widget');
    }
}


/**
 * класс для получения температуры
 */
class WeatherAPI
{
    private static $api_key;

    /**
     * Устанавливаем API-ключ 
     */
    public static function init()
    {
        self::$api_key = get_theme_mod('api_openweathermap');
    }

    /**
     * Получение температуры по координатам
     */
    public static function get_temperature($latitude, $longitude)
    {
        if (empty(self::$api_key)) {
            return 'Ошибка: API ключ не задан';
        }

        $url = "https://api.openweathermap.org/data/2.5/weather?lat={$latitude}&lon={$longitude}&appid=" . self::$api_key;

        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
            return 'Ошибка получения данных';
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($data['main']['temp']) && is_numeric($data['main']['temp'])) {
            // Температуру переводим в Цельсий
            return round($data['main']['temp'] - 273.15, 1) . ' °C';
        }

        return 'Нет данных';
    }
}


/**
 * виджет выбора города
 */
class Weather_City_Widget extends WP_Widget
{
    public function __construct()
    {
        parent::__construct(
            'weather_city_widget',
            'Погода в городе',
            ['description' => 'Выберите город, чтобы показать его температуру']
        );
    }

    /**
     * Настройки виджета в админке
     */
    public function form($instance)
    {
        $selected_city = !empty($instance['city_id']) ? $instance['city_id'] : '';
        $cities = get_posts(['post_type' => 'cities', 'numberposts' => -1]); ?>
        <p>
            <label for="<?php echo $this->get_field_id('city_id'); ?>">Выберите город:</label>
            <select id="<?php echo $this->get_field_id('city_id'); ?>" name="<?php echo $this->get_field_name('city_id'); ?>">
                <option value="">-- Выбрать --</option>
                <?php foreach ($cities as $city): ?>
                    <option value="<?php echo $city->ID; ?>"
                        <?php selected($selected_city, $city->ID); ?>>
                        <?php echo esc_html($city->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
    <?php
    }

    /**
     * Вывод информации в сайдбар
     */
    public function widget($args, $instance)
    {
        $city_id = !empty($instance['city_id']) ? $instance['city_id'] : '';

        if (!$city_id) {
            echo '<p>Город не выбран.</p>';
            return;
        }

        //получаем необходимы данные
        $city_name = get_the_title($city_id);
        $latitude = get_post_meta($city_id, '_city_latitude', true);
        $longitude = get_post_meta($city_id, '_city_longitude', true);
        $temperature = WeatherAPI::get_temperature($latitude, $longitude);

        echo $args['before_widget'];
        echo $args['before_title'] . esc_html($city_name) . $args['after_title'];
        echo '<p>Температура: ' . esc_html($temperature) . '</p>';
        echo $args['after_widget'];
    }


    /**
     * Сохранение настроек
     */
    public function update($new_instance, $old_instance)
    {
        $instance = [];
        $instance['city_id'] = (!empty($new_instance['city_id'])) ? intval($new_instance['city_id']) : '';
        return $instance;
    }
}

/**
 * класс для построениия таблиц
 */
class Cities_Table
{
    /**
     * рендерим таблицу
     */
    public function create_table()
    {
        $cities = self::get_query();
        return self::create_markup($cities);
    }

    /**
     * запрос к бд
     */
    static function get_query($search = '')
    {
        global $wpdb;

        $query = "
        SELECT p.ID, p.post_title AS city, t.name AS country, 
            lat.meta_value AS latitude, lon.meta_value AS longitude
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
        LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
        LEFT JOIN {$wpdb->postmeta} lat ON p.ID = lat.post_id AND lat.meta_key = '_city_latitude'
        LEFT JOIN {$wpdb->postmeta} lon ON p.ID = lon.post_id AND lon.meta_key = '_city_longitude'
        WHERE p.post_type = 'cities' AND p.post_status = 'publish'
        AND tt.taxonomy = 'countries'
            ";

        // Если есть слово с поиска, добавляем условие
        if (!empty($search)) {
            $search = esc_sql($wpdb->esc_like($search)); 
            $query .= " AND p.post_title LIKE '%{$search}%'";
        }

        return $wpdb->get_results($query);
    }
    
    /**
     * формируем разметку
     */
    static function create_markup($cities)
    {
        ob_start(); ?>
        <table border="1">
            <tr>
                <th>Город</th>
                <th>Страна</th>
                <th>Температура</th>
            </tr> <?php

                    foreach ($cities as $city) :
                        $temperature = WeatherAPI::get_temperature($city->latitude, $city->longitude); ?>

                <tr>
                    <td><?php echo esc_attr($city->city); ?></td>
                    <td><?php echo esc_attr($city->country); ?></td>
                    <td><?php echo esc_attr($temperature); ?></td>
                </tr> <?php
                    endforeach; ?>
        </table>
         <?php
        return ob_get_clean();
    }
}