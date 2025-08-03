<?php
// Временный файл для отладки системы обновлений
// Поместите этот файл в корень сайта и откройте в браузере

// Подключаем WordPress
require_once('wp-config.php');
require_once('wp-load.php');

echo "<h1>🔍 Диагностика системы обновлений Gift Cards Plugin</h1>";

// 1. Проверяем текущую версию плагина
$plugin_file = WP_PLUGIN_DIR . '/giftcards-for-woocommerce/custom-giftcards-for-woocommerce.php';
if (file_exists($plugin_file)) {
    $plugin_data = get_plugin_data($plugin_file);
    echo "<h2>📦 Информация о плагине:</h2>";
    echo "<strong>Версия:</strong> " . $plugin_data['Version'] . "<br>";
    echo "<strong>Название:</strong> " . $plugin_data['Name'] . "<br>";
    echo "<strong>Файл:</strong> " . plugin_basename($plugin_file) . "<br>";
    
    // Проверяем константу версии
    if (defined('CGFWC_VERSION')) {
        echo "<strong>CGFWC_VERSION:</strong> " . CGFWC_VERSION . "<br>";
    }
} else {
    echo "<p style='color: red;'>❌ Плагин не найден по пути: $plugin_file</p>";
    
    // Ищем плагин в других местах
    $possible_paths = [
        'custom-giftcards-for-woocommerce/custom-giftcards-for-woocommerce.php',
        'giftcards-for-woocommerce/custom-giftcards-for-woocommerce.php',
        'gift-cards-woocommerce/custom-giftcards-for-woocommerce.php'
    ];
    
    foreach ($possible_paths as $path) {
        $full_path = WP_PLUGIN_DIR . '/' . $path;
        if (file_exists($full_path)) {
            echo "<p style='color: green;'>✅ Найден по пути: $full_path</p>";
            $plugin_data = get_plugin_data($full_path);
            echo "<strong>Версия:</strong> " . $plugin_data['Version'] . "<br>";
        }
    }
}

// 2. Проверяем GitHub API
echo "<h2>🔗 GitHub API проверка:</h2>";
$github_url = "https://api.github.com/repos/butuzoff/giftcards-for-woocommerce/releases/latest";
$response = wp_remote_get($github_url, array('timeout' => 15));

if (is_wp_error($response)) {
    echo "<p style='color: red;'>❌ Ошибка API: " . $response->get_error_message() . "</p>";
} else {
    $data = json_decode(wp_remote_retrieve_body($response), true);
    echo "<strong>Последний релиз:</strong> " . $data['tag_name'] . "<br>";
    echo "<strong>Дата публикации:</strong> " . $data['published_at'] . "<br>";
    echo "<strong>ZIP URL:</strong> " . $data['zipball_url'] . "<br>";
}

// 3. Проверяем кэш WordPress
echo "<h2>💾 Кэш обновлений:</h2>";
$update_plugins = get_site_transient('update_plugins');
echo "<strong>Кэш update_plugins существует:</strong> " . (($update_plugins !== false) ? "Да" : "Нет") . "<br>";

$cgfwc_cache = get_transient('cgfwc_github_latest');
echo "<strong>Кэш cgfwc_github_latest существует:</strong> " . (($cgfwc_cache !== false) ? "Да" : "Нет") . "<br>";

if ($cgfwc_cache) {
    echo "<strong>Кэшированная версия:</strong> " . $cgfwc_cache->tag_name . "<br>";
}

// 4. Принудительная очистка кэша и проверка
echo "<h2>🔄 Принудительная проверка:</h2>";
delete_site_transient('update_plugins');
delete_transient('cgfwc_github_latest');
echo "<p style='color: green;'>✅ Кэш очищен</p>";

// 5. Эмуляция проверки обновлений
if (class_exists('CGFWC_GitHub_Updater')) {
    echo "<p style='color: green;'>✅ Класс CGFWC_GitHub_Updater загружен</p>";
} else {
    echo "<p style='color: red;'>❌ Класс CGFWC_GitHub_Updater НЕ загружен</p>";
}

// 6. Проверяем хуки
global $wp_filter;
$hook_exists = isset($wp_filter['pre_set_site_transient_update_plugins']);
echo "<strong>Хук pre_set_site_transient_update_plugins:</strong> " . ($hook_exists ? "Зарегистрирован" : "НЕ зарегистрирован") . "<br>";

echo "<h2>🔧 Рекомендации:</h2>";
echo "<p>1. Проверьте правильность пути к плагину<br>";
echo "2. Убедитесь, что плагин активирован<br>";
echo "3. Проверьте версию - должна быть меньше чем v1.0.11-test<br>";
echo "4. Очистите кэш и проверьте снова через 5-10 минут</p>";
