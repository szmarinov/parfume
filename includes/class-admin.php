<?php
/**
 * Admin class for Parfume Catalog plugin
 * 
 * @package Parfume_Catalog
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Parfume_Catalog_Admin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Parfume Catalog', 'parfume-catalog'),
            __('Parfume Catalog', 'parfume-catalog'),
            'manage_options',
            'parfume-catalog',
            array($this, 'admin_page'),
            'dashicons-products',
            26
        );
        
        add_submenu_page(
            'parfume-catalog',
            __('Настройки', 'parfume-catalog'),
            __('Настройки', 'parfume-catalog'),
            'manage_options',
            'parfume-catalog',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'parfume-catalog',
            __('Парфюми', 'parfume-catalog'),
            __('Всички парфюми', 'parfume-catalog'),
            'manage_options',
            'edit.php?post_type=parfumes'
        );
        
        add_submenu_page(
            'parfume-catalog',
            __('Добави парфюм', 'parfume-catalog'),
            __('Добави парфюм', 'parfume-catalog'),
            'manage_options',
            'post-new.php?post_type=parfumes'
        );
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting('parfume_catalog_settings', 'parfume_catalog_settings');
        
        // General section
        add_settings_section(
            'parfume_catalog_general',
            __('Общи настройки', 'parfume-catalog'),
            array($this, 'general_section_callback'),
            'parfume_catalog_settings'
        );
        
        // URLs section
        add_settings_section(
            'parfume_catalog_urls',
            __('URL структури', 'parfume-catalog'),
            array($this, 'urls_section_callback'),
            'parfume_catalog_settings'
        );
    }
    
    /**
     * Admin page callback
     */
    public function admin_page() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
        ?>
        <div class="wrap">
            <h1><?php _e('Parfume Catalog - Настройки', 'parfume-catalog'); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=parfume-catalog&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Общи', 'parfume-catalog'); ?>
                </a>
                <a href="?page=parfume-catalog&tab=urls" class="nav-tab <?php echo $active_tab == 'urls' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('URL структури', 'parfume-catalog'); ?>
                </a>
                <a href="?page=parfume-catalog&tab=stores" class="nav-tab <?php echo $active_tab == 'stores' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Магазини', 'parfume-catalog'); ?>
                </a>
                <a href="?page=parfume-catalog&tab=scraper" class="nav-tab <?php echo $active_tab == 'scraper' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Product Scraper', 'parfume-catalog'); ?>
                </a>
                <a href="?page=parfume-catalog&tab=comparison" class="nav-tab <?php echo $active_tab == 'comparison' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Сравнение', 'parfume-catalog'); ?>
                </a>
                <a href="?page=parfume-catalog&tab=comments" class="nav-tab <?php echo $active_tab == 'comments' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Коментари', 'parfume-catalog'); ?>
                </a>
                <a href="?page=parfume-catalog&tab=blog" class="nav-tab <?php echo $active_tab == 'blog' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Блог', 'parfume-catalog'); ?>
                </a>
            </h2>
            
            <form method="post" action="options.php">
                <?php
                switch($active_tab) {
                    case 'general':
                        $this->render_general_tab();
                        break;
                    case 'urls':
                        $this->render_urls_tab();
                        break;
                    case 'stores':
                        $this->render_stores_tab();
                        break;
                    case 'scraper':
                        $this->render_scraper_tab();
                        break;
                    case 'comparison':
                        $this->render_comparison_tab();
                        break;
                    case 'comments':
                        $this->render_comments_tab();
                        break;
                    case 'blog':
                        $this->render_blog_tab();
                        break;
                    default:
                        $this->render_general_tab();
                }
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render general tab
     */
    private function render_general_tab() {
        settings_fields('parfume_catalog_settings');
        do_settings_sections('parfume_catalog_settings');
        submit_button();
    }
    
    /**
     * Render URLs tab
     */
    private function render_urls_tab() {
        $settings = get_option('parfume_catalog_settings', array());
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Архив URL', 'parfume-catalog'); ?></th>
                <td>
                    <input type="text" name="parfume_catalog_settings[archive_slug]" 
                           value="<?php echo esc_attr($settings['archive_slug'] ?? 'parfiumi'); ?>" 
                           class="regular-text" />
                    <p class="description"><?php _e('URL за архивната страница на парфюмите', 'parfume-catalog'); ?></p>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
        <?php
    }
    
    /**
     * Render stores tab
     */
    private function render_stores_tab() {
        if (class_exists('Parfume_Catalog_Stores')) {
            $stores = new Parfume_Catalog_Stores();
            $stores->render_stores_section();
        }
    }
    
    /**
     * Render scraper tab
     */
    private function render_scraper_tab() {
        echo '<h3>' . __('Product Scraper настройки', 'parfume-catalog') . '</h3>';
        echo '<p>' . __('Тук ще се управляват настройките за автоматичното скрейпване на продукти.', 'parfume-catalog') . '</p>';
    }
    
    /**
     * Render comparison tab
     */
    private function render_comparison_tab() {
        if (class_exists('Parfume_Catalog_Comparison')) {
            $comparison = new Parfume_Catalog_Comparison();
            $comparison->render_comparison_section();
        }
    }
    
    /**
     * Render comments tab
     */
    private function render_comments_tab() {
        echo '<h3>' . __('Коментари и ревюта', 'parfume-catalog') . '</h3>';
        echo '<p>' . __('Управление на потребителските ревюта и коментари.', 'parfume-catalog') . '</p>';
    }
    
    /**
     * Render blog tab
     */
    private function render_blog_tab() {
        if (class_exists('Parfume_Catalog_Blog')) {
            $blog = new Parfume_Catalog_Blog();
            $blog->render_blog_section();
        }
    }
    
    /**
     * General section callback
     */
    public function general_section_callback() {
        echo '<p>' . __('Основни настройки на плъгина', 'parfume-catalog') . '</p>';
    }
    
    /**
     * URLs section callback
     */
    public function urls_section_callback() {
        echo '<p>' . __('Настройки на URL структурите', 'parfume-catalog') . '</p>';
    }
}