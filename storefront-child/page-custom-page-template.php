<?php

/**
 *
 * Template name: Custom page template
 *
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">

        <!-- форма поиска -->
        <form id="search-city-form">
            <input type="text" id="search-city" placeholder="Введите город">
            <button type="submit">Найти</button>
        </form>

        <!-- Вывод результатов -->
        <div id="city-results"></div>

        <?php do_action('before_temperature_table'); ?>

        <!-- рендеринг таблицы -->
        <?php if (class_exists('Cities_Table')) {
            $table = new Cities_Table();
            echo  $table->create_table();
        } ?>

        <?php do_action('after_temperature_table'); ?>

    </main><!-- #main -->
</div><!-- #primary -->
<?php
get_footer();
