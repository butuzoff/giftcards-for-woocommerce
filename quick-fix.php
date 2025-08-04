<?php
// Быстрое исправление проблемы с обновлениями
// Скопируйте код ниже в functions.php темы или в плагин Code Snippets

// Функция для принудительной проверки обновлений Gift Cards
function cgfwc_force_update_check() {
    // Очищаем все кэши
    delete_site_transient('update_plugins');
    delete_transient('cgfwc_github_latest');
    
    // Получаем информацию о релизе
    $response = wp_remote_get('https://api.github.com/repos/butuzoff/giftcards-for-woocommerce/releases/latest');
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $github_data = json_decode(wp_remote_retrieve_body($response), true);
    $github_version = ltrim($github_data['tag_name'], 'v');
    
    // Найти Gift Cards плагин
    $plugins = get_plugins();
    $gc_plugin = null;
    $gc_slug = null;
    
    foreach ($plugins as $slug => $plugin) {
        if (strpos($plugin['Name'], 'Gift') !== false && strpos($plugin['Name'], 'Card') !== false) {
            $gc_plugin = $plugin;
            $gc_slug = $slug;
            break;
        }
    }
    
    if (!$gc_plugin) {
        return false;
    }
    
    // Сравниваем версии
    if (version_compare($gc_plugin['Version'], $github_version, '<')) {
        // Получаем текущий transient
        $current_updates = get_site_transient('update_plugins');
        if (!$current_updates) {
            $current_updates = new stdClass();
        }
        if (!isset($current_updates->response)) {
            $current_updates->response = array();
        }
        
        // Добавляем обновление
        $current_updates->response[$gc_slug] = (object) array(
            'slug' => dirname($gc_slug),
            'new_version' => $github_version,
            'url' => $github_data['html_url'],
            'package' => $github_data['zipball_url'],
            'requires' => '5.0',
            'requires_php' => '7.4',
            'tested' => '6.4'
        );
        
        // Сохраняем
        set_site_transient('update_plugins', $current_updates, 12 * HOUR_IN_SECONDS);
        
        return true;
    }
    
    return false;
}

// Вызываем функцию при загрузке админки
add_action('admin_init', function() {
    if (current_user_can('update_plugins')) {
        cgfwc_force_update_check();
    }
});

// Добавляем уведомление в админку
add_action('admin_notices', function() {
    if (!current_user_can('update_plugins')) {
        return;
    }
    
    $response = wp_remote_get('https://api.github.com/repos/butuzoff/giftcards-for-woocommerce/releases/latest');
    if (is_wp_error($response)) {
        return;
    }
    
    $github_data = json_decode(wp_remote_retrieve_body($response), true);
    $github_version = ltrim($github_data['tag_name'], 'v');
    
    $plugins = get_plugins();
    foreach ($plugins as $slug => $plugin) {
        if (strpos($plugin['Name'], 'Gift') !== false && strpos($plugin['Name'], 'Card') !== false) {
            if (version_compare($plugin['Version'], $github_version, '<')) {
                echo '<div class="notice notice-warning is-dismissible">';
                echo '<p><strong>Gift Cards Plugin Update Available!</strong></p>';
                echo '<p>Version ' . esc_html($github_version) . ' is available (current: ' . $plugin['Version'] . '). ';
                echo '<a href="' . admin_url('plugins.php') . '">Update now</a> or ';
                echo '<a href="' . esc_url($github_data['html_url']) . '" target="_blank">view on GitHub</a>.</p>';
                echo '</div>';
            }
            break;
        }
    }
});
