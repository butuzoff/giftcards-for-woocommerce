<?php
/**
 * Принудительная активация системы обновлений
 * Поместите в корень WordPress сайта и откройте в браузере
 */

require_once('wp-config.php');
require_once('wp-load.php');

echo "<h1>🚀 Принудительная активация проверки обновлений</h1>";

// Найти плагин Gift Cards
$all_plugins = get_plugins();
$gc_plugin = null;
$gc_slug = null;

foreach ($all_plugins as $slug => $plugin_data) {
    if (strpos($plugin_data['Name'], 'Gift') !== false && strpos($plugin_data['Name'], 'Card') !== false) {
        $gc_plugin = $plugin_data;
        $gc_slug = $slug;
        break;
    }
}

if (!$gc_plugin) {
    echo "<p style='color: red;'>❌ Gift Cards плагин не найден!</p>";
    
    echo "<h2>Все установленные плагины:</h2>";
    foreach ($all_plugins as $slug => $plugin_data) {
        echo "<strong>$slug:</strong> " . $plugin_data['Name'] . " (v" . $plugin_data['Version'] . ")<br>";
    }
    exit;
}

echo "<p style='color: green;'>✅ Найден плагин: " . $gc_plugin['Name'] . "</p>";
echo "<strong>Slug:</strong> $gc_slug<br>";
echo "<strong>Версия:</strong> " . $gc_plugin['Version'] . "<br>";

// Проверяем активность
$active_plugins = get_option('active_plugins', array());
$is_active = in_array($gc_slug, $active_plugins);
echo "<strong>Активен:</strong> " . ($is_active ? "ДА" : "НЕТ") . "<br>";

if (!$is_active) {
    echo "<p style='color: orange;'>⚠️ Плагин неактивен. Активирую...</p>";
    activate_plugin($gc_slug);
    echo "<p style='color: green;'>✅ Плагин активирован!</p>";
}

// Принудительно очищаем ВСЕ кэши обновлений
delete_site_transient('update_plugins');
delete_site_transient('update_themes');
delete_site_transient('update_core');
delete_transient('cgfwc_github_latest');

// Удаляем кэш конкретно для нашего плагина
$cache_keys = [
    'cgfwc_github_latest',
    'giftcards_github_check',
    'update_plugins_' . md5($gc_slug)
];

foreach ($cache_keys as $key) {
    delete_transient($key);
    delete_site_transient($key);
}

echo "<p style='color: green;'>✅ Все кэши очищены</p>";

// Эмулируем проверку обновлений WordPress
echo "<h2>🔄 Эмуляция проверки обновлений...</h2>";

// Создаем фейковый transient как делает WordPress
$fake_transient = new stdClass();
$fake_transient->checked = array();

// Добавляем все активные плагины
foreach ($active_plugins as $plugin_path) {
    if (isset($all_plugins[$plugin_path])) {
        $fake_transient->checked[$plugin_path] = $all_plugins[$plugin_path]['Version'];
    }
}

echo "<strong>Плагинов в проверке:</strong> " . count($fake_transient->checked) . "<br>";

// Запускаем хук проверки обновлений
$updated_transient = apply_filters('pre_set_site_transient_update_plugins', $fake_transient);

echo "<h2>📊 Результаты проверки:</h2>";

if (isset($updated_transient->response) && !empty($updated_transient->response)) {
    echo "<p style='color: green;'>✅ Найдены обновления для " . count($updated_transient->response) . " плагинов:</p>";
    
    foreach ($updated_transient->response as $plugin_path => $update_data) {
        echo "<strong>$plugin_path:</strong> ";
        if (is_object($update_data) && isset($update_data->new_version)) {
            echo "новая версия " . $update_data->new_version . "<br>";
            
            // Проверяем, наш ли это плагин
            if ($plugin_path === $gc_slug) {
                echo "<p style='color: green;'><strong>🎉 НАЙДЕНО ОБНОВЛЕНИЕ ДЛЯ GIFT CARDS!</strong></p>";
            }
        } else {
            echo "данные об обновлении получены<br>";
        }
    }
} else {
    echo "<p style='color: orange;'>⚠️ Обновления не найдены</p>";
}

// Проверяем GitHub API напрямую
echo "<h2>🌐 Прямая проверка GitHub:</h2>";
$github_response = wp_remote_get('https://api.github.com/repos/butuzoff/giftcards-for-woocommerce/releases/latest');

if (!is_wp_error($github_response)) {
    $github_data = json_decode(wp_remote_retrieve_body($github_response), true);
    echo "<strong>GitHub версия:</strong> " . $github_data['tag_name'] . "<br>";
    echo "<strong>Текущая версия:</strong> " . $gc_plugin['Version'] . "<br>";
    
    $github_version = ltrim($github_data['tag_name'], 'v');
    $needs_update = version_compare($gc_plugin['Version'], $github_version, '<');
    echo "<strong>Требуется обновление:</strong> " . ($needs_update ? "ДА" : "НЕТ") . "<br>";
}

// Сохраняем принудительный transient
if (isset($updated_transient->response) && !empty($updated_transient->response)) {
    set_site_transient('update_plugins', $updated_transient, 12 * HOUR_IN_SECONDS);
    echo "<p style='color: green;'>✅ Данные об обновлениях сохранены в WordPress</p>";
}

echo "<h2>🎯 Следующие шаги:</h2>";
echo "<p>1. Перейдите в <a href='/wp-admin/update-core.php'>Консоль → Обновления</a><br>";
echo "2. Или в <a href='/wp-admin/plugins.php'>Плагины → Установленные</a><br>";
echo "3. Проверьте наличие уведомлений об обновлении Gift Cards</p>";
