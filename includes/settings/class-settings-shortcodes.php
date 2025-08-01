<?php
namespace Parfume_Reviews\Settings;

/**
 * Settings_Shortcodes class - Показва документация за shortcodes
 * 
 * Файл: includes/settings/class-settings-shortcodes.php
 * Извлечен от оригинален class-settings.php
 */
class Settings_Shortcodes {
    
    public function __construct() {
        // Няма нужда от хукове тук - те се управляват от главния Settings клас
    }
    
    /**
     * Регистрира настройките за shortcodes (документация)
     */
    public function register_settings() {
        // Shortcodes документация не изисква регистрация на настройки
        // Това е само информационна секция
    }
    
    /**
     * Рендерира секцията със shortcodes документация
     */
    public function render_section() {
        ?>
        <div class="shortcodes-documentation">
            <?php $this->render_shortcodes_overview(); ?>
            <?php $this->render_basic_shortcodes(); ?>
            <?php $this->render_filtering_shortcodes(); ?>
            <?php $this->render_archive_shortcodes(); ?>
            <?php $this->render_advanced_shortcodes(); ?>
            <?php $this->render_shortcode_generator(); ?>
        </div>
        
        <style>
        .shortcodes-documentation {
            max-width: 100%;
        }
        .shortcode-section {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .shortcode-section h3 {
            margin-top: 0;
            color: #0073aa;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 10px;
        }
        .shortcode-item {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .shortcode-item h4 {
            margin-top: 0;
            color: #32373c;
        }
        .shortcode-code {
            background: #23282d;
            color: #f8f8f2;
            padding: 10px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            margin: 10px 0;
        }
        .shortcode-attributes {
            background: #f1f1f1;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .shortcode-attributes ul {
            margin: 5px 0;
            padding-left: 20px;
        }
        .shortcode-example {
            background: #e7f3ff;
            border-left: 4px solid #0073aa;
            padding: 10px;
            margin: 10px 0;
        }
        .copy-shortcode {
            background: #0073aa;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 10px;
        }
        .copy-shortcode:hover {
            background: #005a87;
        }
        .shortcode-generator {
            background: #fff;
            border: 2px solid #0073aa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
        }
        .generator-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
        }
        .generator-form label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }
        .generator-output {
            grid-column: 1 / -1;
            margin-top: 15px;
        }
        </style>
        <?php
    }
    
    /**
     * Рендерира общ преглед на shortcodes
     */
    private function render_shortcodes_overview() {
        ?>
        <div class="shortcode-section">
            <h3><?php _e('Преглед на Shortcodes', 'parfume-reviews'); ?></h3>
            <p><?php _e('Parfume Reviews плъгинът предоставя богат набор от shortcodes за показване на парфюмна информация във вашия сайт. Всички shortcodes могат да бъдат използвани в постове, страници и widgets.', 'parfume-reviews'); ?></p>
            
            <div class="shortcode-example">
                <strong><?php _e('Бързо започване:', 'parfume-reviews'); ?></strong>
                <p><?php _e('Копирайте и поставете някой от shortcodes по-долу директно в своето съдържание. Повечето shortcodes работят без настройки, но могат да бъдат персонализирани с параметри.', 'parfume-reviews'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Рендерира основните shortcodes
     */
    private function render_basic_shortcodes() {
        ?>
        <div class="shortcode-section">
            <h3><?php _e('Основни Shortcodes', 'parfume-reviews'); ?></h3>
            
            <div class="shortcode-item">
                <h4><?php _e('Рейтинг на парфюм', 'parfume-reviews'); ?></h4>
                <p><?php _e('Показва рейтинг звездички за текущия парфюм.', 'parfume-reviews'); ?></p>
                <div class="shortcode-code">
                    [parfume_rating]
                    <button type="button" class="copy-shortcode" onclick="copyToClipboard('[parfume_rating]')"><?php _e('Копирай', 'parfume-reviews'); ?></button>
                </div>
                <div class="shortcode-attributes">
                    <strong><?php _e('Параметри:', 'parfume-reviews'); ?></strong>
                    <ul>
                        <li><code>show_empty="true"</code> - <?php _e('Показва празен рейтинг ако няма оценка', 'parfume-reviews'); ?></li>
                        <li><code>show_average="true"</code> - <?php _e('Показва средната оценка като число', 'parfume-reviews'); ?></li>
                    </ul>
                </div>
                <div class="shortcode-example">
                    <strong><?php _e('Пример:', 'parfume-reviews'); ?></strong>
                    <div class="shortcode-code">[parfume_rating show_empty="false" show_average="true"]</div>
                </div>
            </div>
            
            <div class="shortcode-item">
                <h4><?php _e('Детайли за парфюм', 'parfume-reviews'); ?></h4>
                <p><?php _e('Показва подробна информация за текущия парфюм (марка, тип, сезон, etc.).', 'parfume-reviews'); ?></p>
                <div class="shortcode-code">
                    [parfume_details]
                    <button type="button" class="copy-shortcode" onclick="copyToClipboard('[parfume_details]')"><?php _e('Копирай', 'parfume-reviews'); ?></button>
                </div>
                <div class="shortcode-attributes">
                    <strong><?php _e('Параметри:', 'parfume-reviews'); ?></strong>
                    <ul>
                        <li><code>show_brand="true"</code> - <?php _e('Показва марката', 'parfume-reviews'); ?></li>
                        <li><code>show_type="true"</code> - <?php _e('Показва типа аромат', 'parfume-reviews'); ?></li>
                        <li><code>show_season="true"</code> - <?php _e('Показва подходящия сезон', 'parfume-reviews'); ?></li>
                        <li><code>show_notes="true"</code> - <?php _e('Показва ароматните ноти', 'parfume-reviews'); ?></li>
                    </ul>
                </div>
            </div>
            
            <div class="shortcode-item">
                <h4><?php _e('Магазини за парфюм', 'parfume-reviews'); ?></h4>
                <p><?php _e('Показва списък с магазини където може да се закупи парфюма.', 'parfume-reviews'); ?></p>
                <div class="shortcode-code">
                    [parfume_stores]
                    <button type="button" class="copy-shortcode" onclick="copyToClipboard('[parfume_stores]')"><?php _e('Копирай', 'parfume-reviews'); ?></button>
                </div>
                <div class="shortcode-attributes">
                    <strong><?php _e('Параметри:', 'parfume-reviews'); ?></strong>
                    <ul>
                        <li><code>show_prices="true"</code> - <?php _e('Показва цените', 'parfume-reviews'); ?></li>
                        <li><code>show_logos="true"</code> - <?php _e('Показва логата на магазините', 'parfume-reviews'); ?></li>
                        <li><code>limit="0"</code> - <?php _e('Ограничава броя показани магазини (0 = всички)', 'parfume-reviews'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Рендерира shortcodes за филтриране
     */
    private function render_filtering_shortcodes() {
        ?>
        <div class="shortcode-section">
            <h3><?php _e('Филтриране и Търсене', 'parfume-reviews'); ?></h3>
            
            <div class="shortcode-item">
                <h4><?php _e('Филтри за парфюми', 'parfume-reviews'); ?></h4>
                <p><?php _e('Показва интерактивни филтри за търсене на парфюми по различни критерии.', 'parfume-reviews'); ?></p>
                <div class="shortcode-code">
                    [parfume_filters]
                    <button type="button" class="copy-shortcode" onclick="copyToClipboard('[parfume_filters]')"><?php _e('Копирай', 'parfume-reviews'); ?></button>
                </div>
                <div class="shortcode-attributes">
                    <strong><?php _e('Параметри:', 'parfume-reviews'); ?></strong>
                    <ul>
                        <li><code>show_brands="true"</code> - <?php _e('Показва филтър за марки', 'parfume-reviews'); ?></li>
                        <li><code>show_gender="true"</code> - <?php _e('Показва филтър за пол', 'parfume-reviews'); ?></li>
                        <li><code>show_type="true"</code> - <?php _e('Показва филтър за тип аромат', 'parfume-reviews'); ?></li>
                        <li><code>show_season="true"</code> - <?php _e('Показва филтър за сезон', 'parfume-reviews'); ?></li>
                        <li><code>show_notes="true"</code> - <?php _e('Показва филтър за ноти', 'parfume-reviews'); ?></li>
                        <li><code>show_intensity="true"</code> - <?php _e('Показва филтър за интензивност', 'parfume-reviews'); ?></li>
                        <li><code>ajax="true"</code> - <?php _e('Използва AJAX за динамично филтриране', 'parfume-reviews'); ?></li>
                    </ul>
                </div>
                <div class="shortcode-example">
                    <strong><?php _e('Пример:', 'parfume-reviews'); ?></strong>
                    <div class="shortcode-code">[parfume_filters show_brands="true" show_gender="true" ajax="true"]</div>
                </div>
            </div>
            
            <div class="shortcode-item">
                <h4><?php _e('Решетка с парфюми', 'parfume-reviews'); ?></h4>
                <p><?php _e('Показва парфюми в решетъчен изглед с възможности за филтриране.', 'parfume-reviews'); ?></p>
                <div class="shortcode-code">
                    [parfume_grid]
                    <button type="button" class="copy-shortcode" onclick="copyToClipboard('[parfume_grid]')"><?php _e('Копирай', 'parfume-reviews'); ?></button>
                </div>
                <div class="shortcode-attributes">
                    <strong><?php _e('Параметри:', 'parfume-reviews'); ?></strong>
                    <ul>
                        <li><code>posts_per_page="12"</code> - <?php _e('Брой парфюми за показване', 'parfume-reviews'); ?></li>
                        <li><code>columns="4"</code> - <?php _e('Брой колони в решетката', 'parfume-reviews'); ?></li>
                        <li><code>orderby="date"</code> - <?php _e('Подреждане по (date, title, menu_order, etc.)', 'parfume-reviews'); ?></li>
                        <li><code>order="DESC"</code> - <?php _e('Посока на подреждане (ASC или DESC)', 'parfume-reviews'); ?></li>
                        <li><code>brand=""</code> - <?php _e('Филтър по конкретна марка', 'parfume-reviews'); ?></li>
                        <li><code>gender=""</code> - <?php _e('Филтър по пол', 'parfume-reviews'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Рендерира архивни shortcodes
     */
    private function render_archive_shortcodes() {
        ?>
        <div class="shortcode-section">
            <h3><?php _e('Архивни Shortcodes', 'parfume-reviews'); ?></h3>
            
            <div class="shortcode-item">
                <h4><?php _e('Всички марки', 'parfume-reviews'); ?></h4>
                <p><?php _e('Показва списък или решетка с всички марки парфюми.', 'parfume-reviews'); ?></p>
                <div class="shortcode-code">
                    [all_brands_archive]
                    <button type="button" class="copy-shortcode" onclick="copyToClipboard('[all_brands_archive]')"><?php _e('Копирай', 'parfume-reviews'); ?></button>
                </div>
                <div class="shortcode-attributes">
                    <strong><?php _e('Параметри:', 'parfume-reviews'); ?></strong>
                    <ul>
                        <li><code>columns="4"</code> - <?php _e('Брой колони', 'parfume-reviews'); ?></li>
                        <li><code>show_count="true"</code> - <?php _e('Показва броя парфюми на марката', 'parfume-reviews'); ?></li>
                        <li><code>hide_empty="true"</code> - <?php _e('Скрива марки без парфюми', 'parfume-reviews'); ?></li>
                        <li><code>orderby="name"</code> - <?php _e('Подреждане по име', 'parfume-reviews'); ?></li>
                        <li><code>limit="0"</code> - <?php _e('Ограничава броя марки (0 = всички)', 'parfume-reviews'); ?></li>
                    </ul>
                </div>
            </div>
            
            <div class="shortcode-item">
                <h4><?php _e('Всички ноти', 'parfume-reviews'); ?></h4>
                <p><?php _e('Показва списък с всички ароматни ноти.', 'parfume-reviews'); ?></p>
                <div class="shortcode-code">
                    [all_notes_archive]
                    <button type="button" class="copy-shortcode" onclick="copyToClipboard('[all_notes_archive]')"><?php _e('Копирай', 'parfume-reviews'); ?></button>
                </div>
                <div class="shortcode-attributes">
                    <strong><?php _e('Параметри:', 'parfume-reviews'); ?></strong>
                    <ul>
                        <li><code>columns="6"</code> - <?php _e('Брой колони', 'parfume-reviews'); ?></li>
                        <li><code>show_count="true"</code> - <?php _e('Показва броя парфюми с тази нота', 'parfume-reviews'); ?></li>
                        <li><code>hide_empty="true"</code> - <?php _e('Скрива ноти без парфюми', 'parfume-reviews'); ?></li>
                    </ul>
                </div>
            </div>
            
            <div class="shortcode-item">
                <h4><?php _e('Всички парфюмеристи', 'parfume-reviews'); ?></h4>
                <p><?php _e('Показва списък с всички парфюмеристи.', 'parfume-reviews'); ?></p>
                <div class="shortcode-code">
                    [all_perfumers_archive]
                    <button type="button" class="copy-shortcode" onclick="copyToClipboard('[all_perfumers_archive]')"><?php _e('Копирай', 'parfume-reviews'); ?></button>
                </div>
                <div class="shortcode-attributes">
                    <strong><?php _e('Параметри:', 'parfume-reviews'); ?></strong>
                    <ul>
                        <li><code>columns="3"</code> - <?php _e('Брой колони', 'parfume-reviews'); ?></li>
                        <li><code>show_bio="false"</code> - <?php _e('Показва биография на парфюмериста', 'parfume-reviews'); ?></li>
                        <li><code>show_count="true"</code> - <?php _e('Показва броя парфюми', 'parfume-reviews'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Рендерира advanced shortcodes
     */
    private function render_advanced_shortcodes() {
        ?>
        <div class="shortcode-section">
            <h3><?php _e('Разширени Shortcodes', 'parfume-reviews'); ?></h3>
            
            <div class="shortcode-item">
                <h4><?php _e('Най-нови парфюми', 'parfume-reviews'); ?></h4>
                <p><?php _e('Показва най-новите публикувани парфюми.', 'parfume-reviews'); ?></p>
                <div class="shortcode-code">
                    [latest_parfumes]
                    <button type="button" class="copy-shortcode" onclick="copyToClipboard('[latest_parfumes]')"><?php _e('Копирай', 'parfume-reviews'); ?></button>
                </div>
                <div class="shortcode-attributes">
                    <strong><?php _e('Параметри:', 'parfume-reviews'); ?></strong>
                    <ul>
                        <li><code>count="6"</code> - <?php _e('Брой парфюми за показване', 'parfume-reviews'); ?></li>
                        <li><code>columns="3"</code> - <?php _e('Брой колони в решетката', 'parfume-reviews'); ?></li>
                        <li><code>show_excerpt="true"</code> - <?php _e('Показва кратко описание', 'parfume-reviews'); ?></li>
                    </ul>
                </div>
            </div>
            
            <div class="shortcode-item">
                <h4><?php _e('Препоръчани парфюми', 'parfume-reviews'); ?></h4>
                <p><?php _e('Показва парфюми маркирани като препоръчани (featured).', 'parfume-reviews'); ?></p>
                <div class="shortcode-code">
                    [featured_parfumes]
                    <button type="button" class="copy-shortcode" onclick="copyToClipboard('[featured_parfumes]')"><?php _e('Копирай', 'parfume-reviews'); ?></button>
                </div>
                <div class="shortcode-attributes">
                    <strong><?php _e('Параметри:', 'parfume-reviews'); ?></strong>
                    <ul>
                        <li><code>count="4"</code> - <?php _e('Брой парфюми за показване', 'parfume-reviews'); ?></li>
                        <li><code>columns="4"</code> - <?php _e('Брой колони', 'parfume-reviews'); ?></li>
                        <li><code>show_badge="true"</code> - <?php _e('Показва "препоръчан" бадж', 'parfume-reviews'); ?></li>
                    </ul>
                </div>
            </div>
            
            <div class="shortcode-item">
                <h4><?php _e('Най-високо оценени', 'parfume-reviews'); ?></h4>
                <p><?php _e('Показва парфюми с най-висок рейтинг.', 'parfume-reviews'); ?></p>
                <div class="shortcode-code">
                    [top_rated_parfumes]
                    <button type="button" class="copy-shortcode" onclick="copyToClipboard('[top_rated_parfumes]')"><?php _e('Копирай', 'parfume-reviews'); ?></button>
                </div>
                <div class="shortcode-attributes">
                    <strong><?php _e('Параметри:', 'parfume-reviews'); ?></strong>
                    <ul>
                        <li><code>count="5"</code> - <?php _e('Брой парфюми', 'parfume-reviews'); ?></li>
                        <li><code>min_rating="4"</code> - <?php _e('Минимален рейтинг за включване', 'parfume-reviews'); ?></li>
                        <li><code>show_rating="true"</code> - <?php _e('Показва рейтинга', 'parfume-reviews'); ?></li>
                    </ul>
                </div>
            </div>
            
            <div class="shortcode-item">
                <h4><?php _e('Подобни парфюми', 'parfume-reviews'); ?></h4>
                <p><?php _e('Показва парфюми подобни на текущия (базирано на ноти и тип).', 'parfume-reviews'); ?></p>
                <div class="shortcode-code">
                    [parfume_similar]
                    <button type="button" class="copy-shortcode" onclick="copyToClipboard('[parfume_similar]')"><?php _e('Копирай', 'parfume-reviews'); ?></button>
                </div>
                <div class="shortcode-attributes">
                    <strong><?php _e('Параметри:', 'parfume-reviews'); ?></strong>
                    <ul>
                        <li><code>count="4"</code> - <?php _e('Брой подобни парфюми', 'parfume-reviews'); ?></li>
                        <li><code>exclude_current="true"</code> - <?php _e('Изключва текущия парфюм', 'parfume-reviews'); ?></li>
                        <li><code>match_brand="false"</code> - <?php _e('Включва само от същата марка', 'parfume-reviews'); ?></li>
                    </ul>
                </div>
            </div>
            
            <div class="shortcode-item">
                <h4><?php _e('Продукти от марка', 'parfume-reviews'); ?></h4>
                <p><?php _e('Показва всички парфюми от конкретна марка.', 'parfume-reviews'); ?></p>
                <div class="shortcode-code">
                    [parfume_brand_products]
                    <button type="button" class="copy-shortcode" onclick="copyToClipboard('[parfume_brand_products]')"><?php _e('Копирай', 'parfume-reviews'); ?></button>
                </div>
                <div class="shortcode-attributes">
                    <strong><?php _e('Параметри:', 'parfume-reviews'); ?></strong>
                    <ul>
                        <li><code>brand=""</code> - <?php _e('Slug на марката (задължителен)', 'parfume-reviews'); ?></li>
                        <li><code>count="12"</code> - <?php _e('Брой парфюми', 'parfume-reviews'); ?></li>
                        <li><code>columns="4"</code> - <?php _e('Брой колони', 'parfume-reviews'); ?></li>
                        <li><code>exclude=""</code> - <?php _e('ID на парфюми за изключване', 'parfume-reviews'); ?></li>
                    </ul>
                </div>
                <div class="shortcode-example">
                    <strong><?php _e('Пример:', 'parfume-reviews'); ?></strong>
                    <div class="shortcode-code">[parfume_brand_products brand="chanel" count="8" columns="4"]</div>
                </div>
            </div>
            
            <div class="shortcode-item">
                <h4><?php _e('Последно разгледани', 'parfume-reviews'); ?></h4>
                <p><?php _e('Показва парфюмите които потребителят е разглеждал последно.', 'parfume-reviews'); ?></p>
                <div class="shortcode-code">
                    [parfume_recently_viewed]
                    <button type="button" class="copy-shortcode" onclick="copyToClipboard('[parfume_recently_viewed]')"><?php _e('Копирай', 'parfume-reviews'); ?></button>
                </div>
                <div class="shortcode-attributes">
                    <strong><?php _e('Параметри:', 'parfume-reviews'); ?></strong>
                    <ul>
                        <li><code>count="5"</code> - <?php _e('Брой парфюми', 'parfume-reviews'); ?></li>
                        <li><code>title=""</code> - <?php _e('Заглавие на секцията', 'parfume-reviews'); ?></li>
                        <li><code>show_empty="false"</code> - <?php _e('Показва съобщение ако няма разгледани', 'parfume-reviews'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Рендерира shortcode генератор
     */
    private function render_shortcode_generator() {
        ?>
        <div class="shortcode-generator">
            <h3><?php _e('Shortcode Генератор', 'parfume-reviews'); ?></h3>
            <p><?php _e('Използвайте този инструмент за лесно генериране на shortcodes с персонализирани параметри.', 'parfume-reviews'); ?></p>
            
            <div class="generator-form">
                <div>
                    <label for="shortcode-type"><?php _e('Тип Shortcode:', 'parfume-reviews'); ?></label>
                    <select id="shortcode-type">
                        <option value=""><?php _e('Изберете...', 'parfume-reviews'); ?></option>
                        <option value="parfume_grid"><?php _e('Решетка с парфюми', 'parfume-reviews'); ?></option>
                        <option value="latest_parfumes"><?php _e('Най-нови парфюми', 'parfume-reviews'); ?></option>
                        <option value="featured_parfumes"><?php _e('Препоръчани парфюми', 'parfume-reviews'); ?></option>
                        <option value="top_rated_parfumes"><?php _e('Най-високо оценени', 'parfume-reviews'); ?></option>
                        <option value="all_brands_archive"><?php _e('Всички марки', 'parfume-reviews'); ?></option>
                        <option value="all_notes_archive"><?php _e('Всички ноти', 'parfume-reviews'); ?></option>
                        <option value="parfume_filters"><?php _e('Филтри', 'parfume-reviews'); ?></option>
                    </select>
                </div>
                
                <div>
                    <label for="shortcode-count"><?php _e('Брой елементи:', 'parfume-reviews'); ?></label>
                    <input type="number" id="shortcode-count" min="1" max="50" value="6">
                </div>
                
                <div>
                    <label for="shortcode-columns"><?php _e('Брой колони:', 'parfume-reviews'); ?></label>
                    <select id="shortcode-columns">
                        <option value="2">2</option>
                        <option value="3" selected>3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                    </select>
                </div>
                
                <div>
                    <label for="shortcode-orderby"><?php _e('Подреждане по:', 'parfume-reviews'); ?></label>
                    <select id="shortcode-orderby">
                        <option value="date"><?php _e('Дата', 'parfume-reviews'); ?></option>
                        <option value="title"><?php _e('Заглавие', 'parfume-reviews'); ?></option>
                        <option value="menu_order"><?php _e('Ред', 'parfume-reviews'); ?></option>
                        <option value="rand"><?php _e('Случайно', 'parfume-reviews'); ?></option>
                    </select>
                </div>
                
                <div class="generator-output">
                    <label for="generated-shortcode"><?php _e('Генериран Shortcode:', 'parfume-reviews'); ?></label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input type="text" id="generated-shortcode" readonly class="large-text" style="font-family: monospace;">
                        <button type="button" class="button button-primary" onclick="copyGeneratedShortcode()"><?php _e('Копирай', 'parfume-reviews'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Генератор на shortcodes
            $('#shortcode-type, #shortcode-count, #shortcode-columns, #shortcode-orderby').on('change', function() {
                generateShortcode();
            });
            
            function generateShortcode() {
                var type = $('#shortcode-type').val();
                var count = $('#shortcode-count').val();
                var columns = $('#shortcode-columns').val();
                var orderby = $('#shortcode-orderby').val();
                
                if (!type) {
                    $('#generated-shortcode').val('');
                    return;
                }
                
                var shortcode = '[' + type;
                
                if (count && count !== '6') {
                    shortcode += ' count="' + count + '"';
                }
                
                if (columns && columns !== '3') {
                    shortcode += ' columns="' + columns + '"';
                }
                
                if (orderby && orderby !== 'date') {
                    shortcode += ' orderby="' + orderby + '"';
                }
                
                shortcode += ']';
                
                $('#generated-shortcode').val(shortcode);
            }
        });
        
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('<?php _e('Shortcode копиран в клипборда!', 'parfume-reviews'); ?>');
            }, function(err) {
                // Fallback
                var textArea = document.createElement("textarea");
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                    alert('<?php _e('Shortcode копиран в клипборда!', 'parfume-reviews'); ?>');
                } catch (err) {
                    alert('<?php _e('Грешка при копиране. Моля копирайте ръчно.', 'parfume-reviews'); ?>');
                }
                document.body.removeChild(textArea);
            });
        }
        
        function copyGeneratedShortcode() {
            var shortcode = document.getElementById('generated-shortcode').value;
            if (shortcode) {
                copyToClipboard(shortcode);
            } else {
                alert('<?php _e('Първо генерирайте shortcode.', 'parfume-reviews'); ?>');
            }
        }
        </script>
        <?php
    }
    
    /**
     * Получава всички регистрирани shortcodes от плъгина
     */
    public function get_available_shortcodes() {
        return array(
            'parfume_rating' => __('Рейтинг на парфюм', 'parfume-reviews'),
            'parfume_details' => __('Детайли за парфюм', 'parfume-reviews'),
            'parfume_stores' => __('Магазини за парфюм', 'parfume-reviews'),
            'parfume_filters' => __('Филтри за парфюми', 'parfume-reviews'),
            'parfume_similar' => __('Подобни парфюми', 'parfume-reviews'),
            'parfume_brand_products' => __('Продукти от марка', 'parfume-reviews'),
            'parfume_recently_viewed' => __('Последно разгледани', 'parfume-reviews'),
            'parfume_grid' => __('Решетка с парфюми', 'parfume-reviews'),
            'latest_parfumes' => __('Най-нови парфюми', 'parfume-reviews'),
            'featured_parfumes' => __('Препоръчани парфюми', 'parfume-reviews'),
            'top_rated_parfumes' => __('Най-високо оценени', 'parfume-reviews'),
            'all_brands_archive' => __('Всички марки', 'parfume-reviews'),
            'all_notes_archive' => __('Всички ноти', 'parfume-reviews'),
            'all_perfumers_archive' => __('Всички парфюмеристи', 'parfume-reviews'),
        );
    }
    
    /**
     * Проверява дали shortcode е регистриран
     */
    public function is_shortcode_registered($shortcode) {
        return shortcode_exists($shortcode);
    }
    
    /**
     * Получава статистики за използването на shortcodes
     */
    public function get_shortcode_usage_stats() {
        global $wpdb;
        
        $shortcodes = array_keys($this->get_available_shortcodes());
        $stats = array();
        
        foreach ($shortcodes as $shortcode) {
            $count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) 
                FROM {$wpdb->posts} 
                WHERE post_content LIKE %s 
                AND post_status = 'publish'
            ", '%[' . $shortcode . '%'));
            
            $stats[$shortcode] = intval($count);
        }
        
        return $stats;
    }
}