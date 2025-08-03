<?php
defined( 'ABSPATH' ) || exit;

/**
 * Класс для автоматического обновления плагина с GitHub
 * Поддерживает обновления из GitHub Releases
 */
class CGFWC_GitHub_Updater {
    
    /**
     * GitHub репозиторий
     */
    private $github_repo = 'butuzoff/giftcards-for-woocommerce';
    
    /**
     * Основной файл плагина
     */
    private $plugin_file;
    
    /**
     * Slug плагина
     */
    private $plugin_slug;
    
    /**
     * Текущая версия плагина
     */
    private $current_version;
    
    /**
     * Конструктор класса
     */
    public function __construct( $plugin_file ) {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = plugin_basename( $plugin_file );
        $this->current_version = CGFWC_VERSION;
        
        // Хуки для обновления
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_github_update' ) );
        add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
        add_filter( 'upgrader_source_selection', array( $this, 'fix_github_source' ), 10, 4 );
        add_action( 'upgrader_process_complete', array( $this, 'after_update' ), 10, 2 );
        
        // Добавляем информацию в админку
        add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
    }
    
    /**
     * Проверяет наличие обновлений на GitHub
     */
    public function check_github_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }
        
        // Получаем информацию о последнем релизе
        $github_info = $this->get_github_release_info();
        
        if ( $github_info && version_compare( $this->current_version, $github_info->tag_name, '<' ) ) {
            $transient->response[ $this->plugin_slug ] = (object) array(
                'slug' => dirname( $this->plugin_slug ),
                'new_version' => $github_info->tag_name,
                'url' => $github_info->html_url,
                'package' => $github_info->zipball_url,
                'requires' => '5.0',
                'requires_php' => '7.4',
                'tested' => '6.4',
                'last_updated' => $github_info->published_at,
                'sections' => array(
                    'description' => $this->get_plugin_description(),
                    'changelog' => $this->format_changelog( $github_info->body ),
                    'installation' => $this->get_installation_instructions()
                )
            );
            
            // Логируем проверку обновления
            $this->log_update_check( $github_info );
        }
        
        return $transient;
    }
    
    /**
     * Получает информацию о последнем релизе с GitHub
     */
    private function get_github_release_info() {
        $cache_key = 'cgfwc_github_latest';
        $cached = get_transient( $cache_key );
        
        if ( $cached !== false ) {
            return $cached;
        }
        
        $response = wp_remote_get( 
            "https://api.github.com/repos/{$this->github_repo}/releases/latest",
            array( 
                'timeout' => 15,
                'headers' => array(
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url()
                )
            )
        );
        
        if ( is_wp_error( $response ) ) {
            $this->log_error( 'GitHub API request failed: ' . $response->get_error_message() );
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code( $response );
        if ( $response_code !== 200 ) {
            $this->log_error( "GitHub API returned status code: {$response_code}" );
            return false;
        }
        
        $data = json_decode( wp_remote_retrieve_body( $response ) );
        
        if ( ! $data || ! isset( $data->tag_name ) ) {
            $this->log_error( 'Invalid GitHub API response' );
            return false;
        }
        
        // Кэшируем результат на 12 часов
        set_transient( $cache_key, $data, 12 * HOUR_IN_SECONDS );
        
        return $data;
    }
    
    /**
     * Получает информацию о плагине для страницы обновления
     */
    public function plugin_info( $result, $action, $args ) {
        if ( $action !== 'plugin_information' ) {
            return $result;
        }
        
        if ( ! isset( $args->slug ) || $args->slug !== dirname( $this->plugin_slug ) ) {
            return $result;
        }
        
        $github_info = $this->get_github_release_info();
        
        if ( $github_info ) {
            return (object) array(
                'name' => 'Custom Giftcards for WooCommerce',
                'slug' => dirname( $this->plugin_slug ),
                'version' => $github_info->tag_name,
                'author' => 'FLANCER.EU',
                'author_profile' => 'https://flancer.eu',
                'last_updated' => $github_info->published_at,
                'requires' => '5.0',
                'requires_php' => '7.4',
                'tested' => '6.4',
                'download_link' => $github_info->zipball_url,
                'sections' => array(
                    'description' => $this->get_plugin_description(),
                    'changelog' => $this->format_changelog( $github_info->body ),
                    'installation' => $this->get_installation_instructions()
                )
            );
        }
        
        return $result;
    }
    
    /**
     * Исправляет структуру архива GitHub для WordPress
     */
    public function fix_github_source( $source, $remote_url, $upgrader, $hook_extra ) {
        if ( strpos( $remote_url, 'github.com' ) !== false ) {
            // GitHub архивы содержат папку с именем репозитория
            $plugin_dir = dirname( $this->plugin_slug );
            
            if ( is_dir( $source . '/' . $plugin_dir ) ) {
                // Перемещаем содержимое в корень
                $files = scandir( $source . '/' . $plugin_dir );
                foreach ( $files as $file ) {
                    if ( $file !== '.' && $file !== '..' ) {
                        rename( $source . '/' . $plugin_dir . '/' . $file, $source . '/' . $file );
                    }
                }
                rmdir( $source . '/' . $plugin_dir );
            }
        }
        
        return $source;
    }
    
    /**
     * Действия после обновления
     */
    public function after_update( $upgrader, $hook_extra ) {
        if ( $hook_extra['action'] === 'update' && $hook_extra['type'] === 'plugin' ) {
            if ( isset( $hook_extra['plugins'] ) && in_array( $this->plugin_slug, $hook_extra['plugins'] ) ) {
                // Очищаем кэш обновлений
                delete_transient( 'cgfwc_github_latest' );
                
                // Логируем успешное обновление
                $logger = wc_get_logger();
                $logger->info( "Plugin updated successfully from GitHub", [
                    'source' => 'giftcards',
                    'old_version' => $this->current_version,
                    'new_version' => CGFWC_VERSION,
                    'update_time' => current_time( 'mysql' )
                ] );
                
                // Показываем уведомление
                add_action( 'admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible">';
                    echo '<p><strong>Gift Cards Plugin</strong> has been updated successfully from GitHub!</p>';
                    echo '<p>New version: <strong>' . esc_html( CGFWC_VERSION ) . '</strong></p>';
                    echo '</div>';
                } );
            }
        }
    }
    
    /**
     * Добавляет ссылки в строку плагина
     */
    public function plugin_row_meta( $links, $file ) {
        if ( $file === $this->plugin_slug ) {
            $links[] = '<a href="' . esc_url( "https://github.com/{$this->github_repo}" ) . '" target="_blank">GitHub</a>';
            $links[] = '<a href="' . esc_url( "https://github.com/{$this->github_repo}/releases" ) . '" target="_blank">Releases</a>';
            $links[] = '<a href="' . esc_url( 'https://flancer.eu' ) . '" target="_blank">Support</a>';
        }
        return $links;
    }
    
    /**
     * Показывает уведомления в админке
     */
    public function admin_notices() {
        // Проверяем наличие обновлений только в админке
        if ( ! current_user_can( 'update_plugins' ) ) {
            return;
        }
        
        $github_info = $this->get_github_release_info();
        
        if ( $github_info && version_compare( $this->current_version, $github_info->tag_name, '<' ) ) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>Gift Cards Plugin Update Available!</strong></p>';
            echo '<p>Version ' . esc_html( $github_info->tag_name ) . ' is available. ';
            echo '<a href="' . admin_url( 'plugins.php' ) . '">Update now</a> or ';
            echo '<a href="' . esc_url( $github_info->html_url ) . '" target="_blank">view on GitHub</a>.</p>';
            echo '</div>';
        }
    }
    
    /**
     * Получает описание плагина
     */
    private function get_plugin_description() {
        return 'A comprehensive WooCommerce plugin for managing gift cards with partial usage support, email delivery, and advanced security features.';
    }
    
    /**
     * Форматирует changelog из GitHub
     */
    private function format_changelog( $body ) {
        if ( empty( $body ) ) {
            return 'No changelog available.';
        }
        
        // Конвертируем markdown в HTML
        $changelog = wp_kses_post( $body );
        
        // Добавляем базовые стили
        $changelog = '<div class="github-changelog">' . $changelog . '</div>';
        
        return $changelog;
    }
    
    /**
     * Получает инструкции по установке
     */
    private function get_installation_instructions() {
        return '
        <ol>
            <li>Upload the plugin files to the <code>/wp-content/plugins/giftcards-for-woocommerce/</code> directory</li>
            <li>Activate the plugin through the "Plugins" menu in WordPress</li>
            <li>Configure gift card products in WooCommerce admin</li>
            <li>Set up email delivery method in WooCommerce shipping settings</li>
        </ol>
        ';
    }
    
    /**
     * Логирует проверку обновления
     */
    private function log_update_check( $github_info ) {
        $logger = wc_get_logger();
        $logger->info( "GitHub update check performed", [
            'source' => 'giftcards',
            'current_version' => $this->current_version,
            'latest_version' => $github_info->tag_name,
            'update_available' => version_compare( $this->current_version, $github_info->tag_name, '<' ),
            'check_time' => current_time( 'mysql' )
        ] );
    }
    
    /**
     * Логирует ошибки
     */
    private function log_error( $message ) {
        $logger = wc_get_logger();
        $logger->error( $message, [
            'source' => 'giftcards',
            'component' => 'github_updater'
        ] );
    }
}

/**
 * Инициализация GitHub обновления
 */
function cgfwc_init_github_updater() {
    if ( ! class_exists( 'CGFWC_GitHub_Updater' ) ) {
        new CGFWC_GitHub_Updater( CGFWC_PLUGIN_DIR . 'custom-giftcards-for-woocommerce.php' );
    }
}

// Инициализируем обновление после загрузки плагина
add_action( 'plugins_loaded', 'cgfwc_init_github_updater', 20 ); 