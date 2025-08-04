<?php
// Расширенная диагностика для lecharmie.com
// Поместите этот файл в корень WordPress и откройте в браузере

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('wp-config.php');
require_once('wp-load.php');

echo "<h1>🔍 Расширенная диагностика Gift Cards Updater</h1>";

// 1. Проверяем путь к плагину
$possible_paths = [
    'giftcards-for-woocommerce/custom-giftcards-for-woocommerce.php',
    'custom-giftcards-for-woocommerce/custom-giftcards-for-woocommerce.php', 
    'gift-cards-woocommerce/custom-giftcards-for-woocommerce.php',
    'custom-gift-cards-woocommerce/custom-giftcards-for-woocommerce.php'
];

$plugin_file = null;
$plugin_slug = null;

foreach ($possible_paths as $path) {
    $full_path = WP_PLUGIN_DIR . '/' . $path;
    if (file_exists($full_path)) {
        $plugin_file = $full_path;
        $plugin_slug = $path;
        echo "<p style='color: green;'>✅ Найден плагин: $path</p>";
        break;
    }
}

if (!$plugin_file) {
    echo "<p style='color: red;'>❌ Плагин не найден ни по одному из путей!</p>";
    foreach ($possible_paths as $path) {
        echo "Проверен: " . WP_PLUGIN_DIR . '/' . $path . "<br>";
    }
    exit;
}

// 2. Проверяем активность плагина
$active_plugins = get_option('active_plugins', array());
$is_active = in_array($plugin_slug, $active_plugins);
echo "<strong>Плагин активен:</strong> " . ($is_active ? "ДА" : "НЕТ") . "<br>";

if (!$is_active) {
    echo "<p style='color: red;'>❌ ПРОБЛЕМА: Плагин не активен! Система обновлений работает только для активных плагинов.</p>";
}

// 3. Проверяем версию
$plugin_data = get_plugin_data($plugin_file);
echo "<strong>Текущая версия:</strong> " . $plugin_data['Version'] . "<br>";

// 4. Проверяем константы
echo "<h2>📊 Константы плагина:</h2>";
if (defined('CGFWC_VERSION')) {
    echo "<strong>CGFWC_VERSION:</strong> " . CGFWC_VERSION . "<br>";
} else {
    echo "<p style='color: red;'>❌ CGFWC_VERSION не определена</p>";
}

if (defined('CGFWC_PLUGIN_DIR')) {
    echo "<strong>CGFWC_PLUGIN_DIR:</strong> " . CGFWC_PLUGIN_DIR . "<br>";
} else {
    echo "<p style='color: red;'>❌ CGFWC_PLUGIN_DIR не определена</p>";
}

// 5. Проверяем класс GitHub Updater
echo "<h2>🔄 GitHub Updater класс:</h2>";
if (class_exists('CGFWC_GitHub_Updater')) {
    echo "<p style='color: green;'>✅ Класс CGFWC_GitHub_Updater загружен</p>";
} else {
    echo "<p style='color: red;'>❌ Класс CGFWC_GitHub_Updater НЕ загружен</p>";
    
    // Пытаемся загрузить вручную
    $updater_file = dirname($plugin_file) . '/includes/github-updater.php';
    if (file_exists($updater_file)) {
        echo "Пытаемся загрузить: $updater_file<br>";
        include_once $updater_file;
        if (class_exists('CGFWC_GitHub_Updater')) {
            echo "<p style='color: green;'>✅ Класс загружен после ручного подключения</p>";
        }
    }
}

// 6. Проверяем хуки WordPress
global $wp_filter;
echo "<h2>🔗 Хуки WordPress:</h2>";

$hooks_to_check = [
    'pre_set_site_transient_update_plugins',
    'plugins_api', 
    'admin_notices',
    'plugins_loaded'
];

foreach ($hooks_to_check as $hook) {
    $exists = isset($wp_filter[$hook]);
    echo "<strong>$hook:</strong> " . ($exists ? "Зарегистрирован" : "НЕ зарегистрирован") . "<br>";
    
    if ($exists && isset($wp_filter[$hook]->callbacks)) {
        foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                if (is_array($callback['function']) && isset($callback['function'][0])) {
                    if (is_object($callback['function'][0]) && get_class($callback['function'][0]) === 'CGFWC_GitHub_Updater') {
                        echo "&nbsp;&nbsp;↳ <span style='color: green;'>CGFWC_GitHub_Updater подключен к $hook</span><br>";
                    }
                }
            }
        }
    }
}

// 7. Тест GitHub API
echo "<h2>🌐 GitHub API тест:</h2>";
$github_url = "https://api.github.com/repos/butuzoff/giftcards-for-woocommerce/releases/latest";
$response = wp_remote_get($github_url, array(
    'timeout' => 15,
    'headers' => array(
        'Accept' => 'application/vnd.github.v3+json',
        'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url()
    )
));

if (is_wp_error($response)) {
    echo "<p style='color: red;'>❌ Ошибка API: " . $response->get_error_message() . "</p>";
} else {
    $code = wp_remote_retrieve_response_code($response);
    echo "<strong>HTTP код:</strong> $code<br>";
    
    if ($code === 200) {
        $data = json_decode(wp_remote_retrieve_body($response), true);
        echo "<strong>Последняя версия GitHub:</strong> " . $data['tag_name'] . "<br>";
        echo "<strong>Дата релиза:</strong> " . $data['published_at'] . "<br>";
        
        // Тест сравнения версий
        $current = $plugin_data['Version'];
        $github = $data['tag_name'];
        
        // Убираем префикс v если есть
        $github_clean = ltrim($github, 'v');
        
        echo "<h3>🔍 Сравнение версий:</h3>";
        echo "<strong>Текущая:</strong> $current<br>";
        echo "<strong>GitHub:</strong> $github (очищенная: $github_clean)<br>";
        
        $comparison = version_compare($current, $github_clean, '<');
        echo "<strong>Обновление доступно:</strong> " . ($comparison ? "ДА" : "НЕТ") . "<br>";
        echo "<strong>version_compare('$current', '$github_clean', '<'):</strong> " . ($comparison ? "true" : "false") . "<br>";
        
    } else {
        echo "<p style='color: red;'>❌ HTTP ошибка: $code</p>";
    }
}

// 8. Принудительный тест системы обновлений
echo "<h2>⚡ Принудительный тест:</h2>";

if (class_exists('CGFWC_GitHub_Updater') && $is_active) {
    // Очищаем кэш
    delete_site_transient('update_plugins');
    delete_transient('cgfwc_github_latest');
    
    // Создаем экземпляр и тестируем
    $updater = new CGFWC_GitHub_Updater($plugin_file);
    
    // Эмулируем проверку обновлений
    $transient = new stdClass();
    $transient->checked = array($plugin_slug => $plugin_data['Version']);
    
    $result = $updater->check_github_update($transient);
    
    if (isset($result->response[$plugin_slug])) {
        echo "<p style='color: green;'>✅ Обновление обнаружено принудительно!</p>";
        echo "<strong>Новая версия:</strong> " . $result->response[$plugin_slug]->new_version . "<br>";
    } else {
        echo "<p style='color: red;'>❌ Обновление не обнаружено даже принудительно</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Не могу выполнить принудительный тест</p>";
}

// 9. Рекомендации
echo "<h2>💡 Рекомендации:</h2>";
if (!$is_active) {
    echo "<p style='color: red;'><strong>КРИТИЧНО:</strong> Активируйте плагин!</p>";
}

echo "<p>1. Если плагин неактивен - активируйте его<br>";
echo "2. Проверьте логи PHP на наличие ошибок<br>";
echo "3. Убедитесь что нет конфликтов с другими плагинами<br>";
echo "4. Попробуйте временно отключить кэширование</p>";
