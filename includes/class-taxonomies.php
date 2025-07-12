<?php
namespace Parfume_Reviews;

class Taxonomies {
    public function __construct() {
        add_action('init', array($this, 'register_taxonomies'));
        add_action('admin_init', array($this, 'add_taxonomy_meta_fields'));
        add_action('created_term', array($this, 'save_taxonomy_meta_fields'), 10, 3);
        add_action('edit_term', array($this, 'save_taxonomy_meta_fields'), 10, 3);
    }
    
    public function register_taxonomies() {
        $settings = get_option('parfume_reviews_settings', array());
        
        // Get the base parfume slug
        $parfume_slug = !empty($settings['parfume_slug']) ? $settings['parfume_slug'] : 'parfiumi';
        
        // Gender taxonomy
        $gender_slug = !empty($settings['gender_slug']) ? $settings['gender_slug'] : 'gender';
        
        $gender_labels = array(
            'name' => __('Genders', 'parfume-reviews'),
            'singular_name' => __('Gender', 'parfume-reviews'),
            'search_items' => __('Search Genders', 'parfume-reviews'),
            'all_items' => __('All Genders', 'parfume-reviews'),
            'parent_item' => __('Parent Gender', 'parfume-reviews'),
            'parent_item_colon' => __('Parent Gender:', 'parfume-reviews'),
            'edit_item' => __('Edit Gender', 'parfume-reviews'),
            'update_item' => __('Update Gender', 'parfume-reviews'),
            'add_new_item' => __('Add New Gender', 'parfume-reviews'),
            'new_item_name' => __('New Gender Name', 'parfume-reviews'),
            'menu_name' => __('Genders', 'parfume-reviews'),
        );
        
        register_taxonomy('gender', 'parfume', array(
            'labels' => $gender_labels,
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => $parfume_slug . '/' . $gender_slug,
                'with_front' => false,
                'hierarchical' => true
            ),
            'show_in_rest' => true,
            'public' => true,
            'publicly_queryable' => true,
        ));
        
        // Add default terms
        if (!term_exists('Мъжки парфюми', 'gender')) {
            wp_insert_term('Мъжки парфюми', 'gender');
        }
        if (!term_exists('Дамски парфюми', 'gender')) {
            wp_insert_term('Дамски парфюми', 'gender');
        }
        if (!term_exists('Арабски парфюми', 'gender')) {
            wp_insert_term('Арабски парфюми', 'gender');
        }
        
        // Aroma Type taxonomy
        $aroma_type_slug = !empty($settings['aroma_type_slug']) ? $settings['aroma_type_slug'] : 'aroma-type';
        
        $aroma_type_labels = array(
            'name' => __('Aroma Types', 'parfume-reviews'),
            'singular_name' => __('Aroma Type', 'parfume-reviews'),
            'search_items' => __('Search Aroma Types', 'parfume-reviews'),
            'all_items' => __('All Aroma Types', 'parfume-reviews'),
            'parent_item' => __('Parent Aroma Type', 'parfume-reviews'),
            'parent_item_colon' => __('Parent Aroma Type:', 'parfume-reviews'),
            'edit_item' => __('Edit Aroma Type', 'parfume-reviews'),
            'update_item' => __('Update Aroma Type', 'parfume-reviews'),
            'add_new_item' => __('Add New Aroma Type', 'parfume-reviews'),
            'new_item_name' => __('New Aroma Type Name', 'parfume-reviews'),
            'menu_name' => __('Aroma Types', 'parfume-reviews'),
        );
        
        register_taxonomy('aroma_type', 'parfume', array(
            'labels' => $aroma_type_labels,
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => $parfume_slug . '/' . $aroma_type_slug,
                'with_front' => false,
                'hierarchical' => true
            ),
            'show_in_rest' => true,
            'public' => true,
            'publicly_queryable' => true,
        ));
        
        // Add default terms
        $default_aroma_types = array(
            'Тоалетна вода',
            'Парфюмна вода',
            'Парфюм',
            'Парфюмен елексир'
        );
        
        foreach ($default_aroma_types as $type) {
            if (!term_exists($type, 'aroma_type')) {
                wp_insert_term($type, 'aroma_type');
            }
        }
        
        // Brands taxonomy (using 'marki' to avoid conflicts)
        $brands_slug = !empty($settings['brands_slug']) ? $settings['brands_slug'] : 'marki';
        
        $brands_labels = array(
            'name' => __('Brands', 'parfume-reviews'),
            'singular_name' => __('Brand', 'parfume-reviews'),
            'search_items' => __('Search Brands', 'parfume-reviews'),
            'all_items' => __('All Brands', 'parfume-reviews'),
            'parent_item' => __('Parent Brand', 'parfume-reviews'),
            'parent_item_colon' => __('Parent Brand:', 'parfume-reviews'),
            'edit_item' => __('Edit Brand', 'parfume-reviews'),
            'update_item' => __('Update Brand', 'parfume-reviews'),
            'add_new_item' => __('Add New Brand', 'parfume-reviews'),
            'new_item_name' => __('New Brand Name', 'parfume-reviews'),
            'menu_name' => __('Brands', 'parfume-reviews'),
        );
        
        register_taxonomy('marki', 'parfume', array(
            'labels' => $brands_labels,
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => $parfume_slug . '/' . $brands_slug,
                'with_front' => false,
                'hierarchical' => true
            ),
            'show_in_rest' => true,
            'public' => true,
            'publicly_queryable' => true,
        ));
        
        // Add default brands
        $default_brands = array(
            'Giorgio Armani', 'Tom Ford', 'Rabanne', 'Dior', 'Dolce&Gabbana', 'Lattafa', 
            'Jean Paul Gaultier', 'Versace', 'Carolina Herrera', 'Yves Saint Laurent',
            'Hugo Boss', 'Valentino', 'Bvlgari', 'Guerlain', 'Xerjoff', 'Mugler',
            'Chanel', 'Montblanc', 'Prada', 'Armaf', 'Lancôme', 'Azzaro', 'Mancera',
            'Hermès', 'Givenchy', 'Calvin Klein', 'Narciso Rodriguez', 'Emporio Armani',
            'Gucci', 'Montale', 'Jimmy Choo', 'Novellista', 'Lalique', 'Afnan',
            'Marc Jacobs', 'Parfums de Marly', 'Nishane', 'Mercedes-Benz', 'Burberry',
            'Boucheron', 'JOOP!', 'Bentley', 'Viktor&Rolf', 'Swiss Arabian', 'Ralph Lauren',
            'Moschino', 'Salvatore Ferragamo', 'Maison Margiela', 'Acqua di Parma',
            'Zadig & Voltaire', 'Creed', 'Chloé', 'Donna Karan', 'Elie Saab',
            'Karl Lagerfeld', 'Lanvin', 'Amouage', 'Cartier', 'Cacharel', 'By Kilian',
            'Roberto Cavalli', 'Tiziana Terenzi', 'Michael Kors', 'Van Cleef & Arpels',
            'S.T. Dupont', 'Issey Miyake', 'Escentric Molecules', 'Davidoff', 'Cerruti',
            'Nasomatto', 'Sospiro', 'Guess', 'Diesel', 'Ariana Grande', 'Al Haramain',
            'Nina Ricci', 'Jesus Del Pozo', 'Rasasi', 'Kenzo', 'Zimaya', 'Philipp Plein',
            'Maison Francis Kurkdjian', 'Nautica', 'Abercrombie & Fitch'
        );
        
        foreach ($default_brands as $brand) {
            if (!term_exists($brand, 'marki')) {
                wp_insert_term($brand, 'marki');
            }
        }
        
        // Season taxonomy
        $season_slug = !empty($settings['season_slug']) ? $settings['season_slug'] : 'season';
        
        $season_labels = array(
            'name' => __('Seasons', 'parfume-reviews'),
            'singular_name' => __('Season', 'parfume-reviews'),
            'search_items' => __('Search Seasons', 'parfume-reviews'),
            'all_items' => __('All Seasons', 'parfume-reviews'),
            'parent_item' => __('Parent Season', 'parfume-reviews'),
            'parent_item_colon' => __('Parent Season:', 'parfume-reviews'),
            'edit_item' => __('Edit Season', 'parfume-reviews'),
            'update_item' => __('Update Season', 'parfume-reviews'),
            'add_new_item' => __('Add New Season', 'parfume-reviews'),
            'new_item_name' => __('New Season Name', 'parfume-reviews'),
            'menu_name' => __('Seasons', 'parfume-reviews'),
        );
        
        register_taxonomy('season', 'parfume', array(
            'labels' => $season_labels,
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => $parfume_slug . '/' . $season_slug,
                'with_front' => false,
                'hierarchical' => true
            ),
            'show_in_rest' => true,
        ));
        
        // Add default seasons
        $default_seasons = array('Пролет', 'Лято', 'Есен', 'Зима');
        
        foreach ($default_seasons as $season) {
            if (!term_exists($season, 'season')) {
                wp_insert_term($season, 'season');
            }
        }
        
        // Intensity taxonomy
        $intensity_slug = !empty($settings['intensity_slug']) ? $settings['intensity_slug'] : 'intensity';
        
        $intensity_labels = array(
            'name' => __('Intensities', 'parfume-reviews'),
            'singular_name' => __('Intensity', 'parfume-reviews'),
            'search_items' => __('Search Intensities', 'parfume-reviews'),
            'all_items' => __('All Intensities', 'parfume-reviews'),
            'parent_item' => __('Parent Intensity', 'parfume-reviews'),
            'parent_item_colon' => __('Parent Intensity:', 'parfume-reviews'),
            'edit_item' => __('Edit Intensity', 'parfume-reviews'),
            'update_item' => __('Update Intensity', 'parfume-reviews'),
            'add_new_item' => __('Add New Intensity', 'parfume-reviews'),
            'new_item_name' => __('New Intensity Name', 'parfume-reviews'),
            'menu_name' => __('Intensities', 'parfume-reviews'),
        );
        
        register_taxonomy('intensity', 'parfume', array(
            'labels' => $intensity_labels,
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => $parfume_slug . '/' . $intensity_slug,
                'with_front' => false,
                'hierarchical' => true
            ),
            'show_in_rest' => true,
        ));
        
        // Add default intensities
        $default_intensities = array('Силни', 'Средни', 'Леки');
        
        foreach ($default_intensities as $intensity) {
            if (!term_exists($intensity, 'intensity')) {
                wp_insert_term($intensity, 'intensity');
            }
        }
        
        // Notes taxonomy
        $notes_slug = !empty($settings['notes_slug']) ? $settings['notes_slug'] : 'notes';
        
        $notes_labels = array(
            'name' => __('Notes', 'parfume-reviews'),
            'singular_name' => __('Note', 'parfume-reviews'),
            'search_items' => __('Search Notes', 'parfume-reviews'),
            'all_items' => __('All Notes', 'parfume-reviews'),
            'parent_item' => __('Parent Note', 'parfume-reviews'),
            'parent_item_colon' => __('Parent Note:', 'parfume-reviews'),
            'edit_item' => __('Edit Note', 'parfume-reviews'),
            'update_item' => __('Update Note', 'parfume-reviews'),
            'add_new_item' => __('Add New Note', 'parfume-reviews'),
            'new_item_name' => __('New Note Name', 'parfume-reviews'),
            'menu_name' => __('Notes', 'parfume-reviews'),
        );
        
        register_taxonomy('notes', 'parfume', array(
            'labels' => $notes_labels,
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => $parfume_slug . '/' . $notes_slug,
                'with_front' => false,
                'hierarchical' => true
            ),
            'show_in_rest' => true,
        ));
        
        // Add default notes
        $default_notes = array(
            'Ванилия', 'Бергамот', 'Мускус', 'Пачули', 'Жасмин', 'Кедрово дърво',
            'Сандалово дърво', 'Роза', 'Зърна от тонка', 'Кехлибар', 'Лавандула',
            'Ветивер', 'Лимон', 'Мандарина', 'Портокалов цвят', 'Дървесни нотки',
            'Розов пипер', 'Градински Чай', 'Ирис', 'Кардамон', 'Дъбов мъх',
            'Грейпфрут', 'Джинджифил', 'Кожа', 'Пипер', 'Тамян', 'Праскова',
            'Здравец', 'Бензоин', 'Канела', 'Касис', 'Виолетов лист', 'Портокал',
            'Ябълка', 'Тубероза', 'Момина сълза', 'Иланг-иланг', 'Круша',
            'Индийско орехче', 'Морски акорди', 'Амброксан', 'Ананас', 'Нероли',
            'Мента', 'Малина', 'Шафран', 'Лабданум', 'Кашмеран', 'Агарово дърво (Оуд)',
            'Божур', 'Горчив портокал', 'Амбра', 'Фрезия', 'Амбъруд', 'Кориандър',
            'Карамел', 'Хелиотроп', 'Тютюн', 'Пикантни нотки', 'Розмарин',
            'Гваяково дърво', 'Кипарис', 'Елеми', 'Кашмир', 'Бразилско палисандрово дърво',
            'Магнолия', 'Мед', 'Орхидея', 'Цитруси', 'Кафе', 'Кокосов орех',
            'Босилек', 'Карамфил', 'Гардения', 'Водна лилия', 'Теменужка', 'Пралина',
            'Плодови нотки', 'Зелени листа', 'Личи', 'Хвойна', 'Пъпеш', 'Лотос',
            'Сол', 'Слива', 'Алдехиди', 'Велур', 'Горчив бадем', 'Какаов цвят',
            'Давана', 'Кестен', 'Кайсия', 'Ром', 'Плодове от хвойна', 'Зелена мандарина',
            'Анасон', 'Чай', 'Мащерка', 'Толу балсам', 'Нар', 'Смокиня', 'Женско биле',
            'Бадемов цвят', 'Амбрет', 'Лайм', 'Кимион', 'Корен от перуника',
            'Звездовиден анасон', 'Съчуански пипер', 'Листа от касис', 'Нарцис',
            'Стиракс', 'Мимоза', 'Петитгрейн', 'Тъмен шоколад', 'Папирус', 'Османтус',
            'Ревен', 'Череша', 'Бели цветя', 'Артемизия', 'Кумарин', 'Ела', 'Зюмбюл',
            'Какаова шушулка', 'Бяла кожа', 'Дъб', 'Акигалауд', 'Кокосова вода',
            'Зелена слива', 'Арганово дърво', 'Франджипани', 'Манго', 'Тютюнев цвят',
            'Смирна', 'Цвете Тиаре', 'Перуански балсам', 'Минерални нотки',
            'Червени плодове', 'Червена боровинка', 'Червена ябълка', 'Юзу', 'Ягода',
            'Амарето', 'Захар', 'Галбанум', 'Бадемово дърво', 'Алкохол', 'Бреза',
            'Iso E Super', 'Тропически плодове', 'Трюфел', 'Опопонакс', 'Сушени плодове',
            'Маракуя', 'Лимон Вербена', 'Помароза', 'Махония', 'Мляко', 'Хедион',
            'Маслиново дърво', 'Флорални нотки', 'Семена от моркови', 'Пчелен восък',
            'Прахообразни нотки', 'Уиски', 'Цвете от зелен чай', 'Махагон',
            'Чай от жасмин', 'Циклама', 'Шамфъстък', 'Цитрон', 'Дюля', 'Крем',
            'Черна череша', 'Краставица', 'Кора от мандарина', 'Коняк', 'Дафинов лист',
            'Земни нотки', 'Водорасли', 'Лайка', 'Амбертън', 'Лешник', 'Бамбук',
            'Зелени клони', 'Евкалипт', 'Куркума', 'Водка', 'Клементин', 'Ликвидамбар',
            'Канабис', 'Кафява захар', 'Диня', 'Касия', 'Естрагон', 'Захарни бадеми',
            'Бор', 'Лен', 'Джин', 'Фурми', 'Бензин', 'Амбретолид', 'Кремък', 'Амброкс',
            'Иглолистно дърво', 'Глициния', 'Смокиново листо', 'Силколид', 'Люляк',
            'Лимонов цвят', 'Лимоново дърво', 'Лимонови кори', 'Невен', 'Сусам',
            'Петалия', 'Сладък грах', 'Пелин', 'Мирабел', 'Манинка', 'Френско грозде',
            'Морски водорасли', 'Ориенталски нотки', 'Миртъл', 'Тиково дърво',
            'Орлови нокти', 'Шипка', 'Водни нотки', 'Червен портокал', 'Мирабела',
            'Чай Ърл Грей', 'Чампака', 'Шисо', 'Сладък акорд', 'Цъфтеж на папая',
            'Явор', 'Чиното', 'Черешов цвят', 'Целина', 'Цибетка', 'Цвят на праскова',
            'Билкови нотки', 'Ягодов кисел бонбон', 'Ябълково дърво', 'Лед', 'Гуава',
            'Кайсиев цвят', 'Амарилис', 'Калоне', 'Амил салицилат', 'Вишна', 'Калипсон',
            'Камбанка', 'Бял тютюн', 'Камък', 'Лава', 'Датура', 'Бяла върба',
            'Амброценид', 'Бяла джинджифилова лилия', 'Кактус', 'Дейзи', 'Каламанси',
            'Какаово масло', 'Детелина', 'Амирис', 'Годжи Бери', 'Лавров цвят',
            'Грозде', 'Бананово листо', 'Гваякол', 'Захаросан джинджифил', 'Бергамот цвят',
            'Асафетида', 'Бял касис', 'Лили', 'Корен от ангелика', 'Горски плодове',
            'Абаносово дърво', 'Захаросани плодове', 'Беламбра дърво', 'Бананово цвете',
            'Абсент', 'Бей Есенция', 'Безсмъртниче', 'Карамбола', 'Кумкуат',
            'Кора от грейпфрут', 'Боровинка', 'Киви', 'Кулфи', 'Жълти цветя',
            'Доматено листо', 'Бонбони', 'Захарен памук', 'Дрифтдъруд', 'Копринено Дърво',
            'Палмово листо', 'Сено', 'Нощен цъфтящ жасмин', 'Трици', 'Мистикал',
            'Серенолид', 'Смокиново дърво', 'Тоник вода', 'Озонови нотки',
            'Огнена лилия', 'Стриди', 'Орех', 'Нектарин', 'Пеларгониум', 'Папая',
            'Тамаринд', 'Сироп от череши', 'Панакота', 'Пименто', 'Памуково цвете',
            'Сапун', 'Синфонид', 'Тимур', 'Морошка', 'Слънчоглед', 'Пшеница',
            'Лист от круша', 'Лист от къпина', 'Риган', 'Мандарин портокалов цвят',
            'Райска ябълка', 'Резене', 'Маслиново цвете', 'Хибискус', 'Резеда',
            'Листа от пименто', 'Хайвер', 'Пясък', 'Мастика', 'Маршмелоу', 'Макадамия',
            'Метални нотки', 'Тръстика', 'Меринги', 'Семена от пименто', 'Помело',
            'Лицея Кубеба', 'Лимонена трева', 'Пуканки', 'Меденки', 'Хелветолид'
        );
        
        foreach ($default_notes as $note) {
            if (!term_exists($note, 'notes')) {
                wp_insert_term($note, 'notes');
            }
        }
        
        // Perfumer taxonomy
        $perfumers_slug = !empty($settings['perfumers_slug']) ? $settings['perfumers_slug'] : 'parfumers';
        
        $perfumer_labels = array(
            'name' => __('Perfumers', 'parfume-reviews'),
            'singular_name' => __('Perfumer', 'parfume-reviews'),
            'search_items' => __('Search Perfumers', 'parfume-reviews'),
            'all_items' => __('All Perfumers', 'parfume-reviews'),
            'parent_item' => __('Parent Perfumer', 'parfume-reviews'),
            'parent_item_colon' => __('Parent Perfumer:', 'parfume-reviews'),
            'edit_item' => __('Edit Perfumer', 'parfume-reviews'),
            'update_item' => __('Update Perfumer', 'parfume-reviews'),
            'add_new_item' => __('Add New Perfumer', 'parfume-reviews'),
            'new_item_name' => __('New Perfumer Name', 'parfume-reviews'),
            'menu_name' => __('Perfumers', 'parfume-reviews'),
        );
        
        register_taxonomy('perfumer', 'parfume', array(
            'labels' => $perfumer_labels,
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array(
                'slug' => $parfume_slug . '/' . $perfumers_slug,
                'with_front' => false,
                'hierarchical' => true
            ),
            'show_in_rest' => true,
        ));
        
        // Add default perfumers
        $default_perfumers = array(
            'Алберто Морилас', 'Куентин Биш', 'Доминик Ропион', 'Оливие Кресп',
            'Ан Флипо', 'Натали Лорсън', 'Соня Констант', 'Франсоа Демаши',
            'Карлос Бенаим', 'Кристоф Рейно', 'Пиер Монтал', 'Антоан Мейсъндю',
            'Оливер Полж', 'Кристин Нагел', 'Луиза Търнър', 'Жак Полж',
            'Натали Гарсия-Чето', 'Оливие Пешу', 'Даниела Андрие', 'Франсис Куркджиян',
            'Фабрис Пелегрин', 'Тиери Васер', 'Аник Менардо', 'Мари Саламан',
            'Мишел Алмайрак', 'Амандин Клер-Мари', 'Дафне Бъги', 'Жак Кавалие',
            'Лок Донг', 'Бруно Йованович', 'Софи Лаббе', 'Фани Бал', 'Жан-Кристоф Еро',
            'Жулиет Карагеузоглу', 'Каролайн Дюмур', 'Орелиен Гишар', 'Жан-Пиер Бетуар',
            'Шаямала Мезондю', 'Хамид Мерати-Кашани', 'Гийом Флавини', 'Сесил Матън',
            'Крис Морис', 'Розендо Матеу', 'Джули Масе', 'Никола Боневил',
            'Сидони Лансесър', 'Жан-Марк Шайлан', 'Антоан Ли', 'Жан-Клод Елена',
            'Пиер Бурдон', 'Жак Хюклие', 'Калис Бекер', 'Карин Дюброй-Серени',
            'Пиер Негрин', 'Емили Копърман', 'Матилде Лоран', 'Кристън Карбонел',
            'Хари Фримонт', 'Паоло Теренци', 'Геза Шьон', 'Пиер Варгне',
            'Адриана Медина-Баез', 'Оливие Крийд', 'Морис Русел', 'Дора Багриш',
            'Жак Герлен', 'Жан Луи Сюзак', 'Клемент Гавари', 'Клер Кейн',
            'Хонорин Блан', 'Кристиан Провенцано', 'Филип Романо', 'Пол Герлен',
            'Ралф Швигер', 'Оливие Гилотин', 'Ричард Херпин', 'Родриго Флорес-Ру',
            'Сесил Зарокян', 'Надеж Льо Гарлантезек', 'Мишел Жерар', 'Мая Лърноут',
            'Лукас Суизак', 'София Гройсман', 'Лоран Брюйер', 'Паскал Гаурин',
            'Ан Готлиб', 'Жак Флори', 'Вероник Ниберг', 'Елис Бенат', 'Джерард Хаури',
            'Ервин Крийд', 'Домитил Бертие', 'Беатрис Пике', 'Жан Гишар',
            'Алиенор Масне', 'Илиас Ермендис', 'Давид Апел', 'Виолайн Колас',
            'Беноа Лапуза', 'Алексис Дадие', 'Едуард Флечър', 'Джерард Антъни',
            'Едмънд Рудницка', 'Алесандро Гуалтиери'
        );
        
        foreach ($default_perfumers as $perfumer) {
            if (!term_exists($perfumer, 'perfumer')) {
                wp_insert_term($perfumer, 'perfumer');
            }
        }
    }
    
    public function add_taxonomy_meta_fields() {
        // Add fields to brand taxonomy
        add_action('marki_add_form_fields', array($this, 'add_brand_image_field'), 10, 2);
        add_action('marki_edit_form_fields', array($this, 'edit_brand_image_field'), 10, 2);
        
        // Add fields to perfumer taxonomy
        add_action('perfumer_add_form_fields', array($this, 'add_perfumer_image_field'), 10, 2);
        add_action('perfumer_edit_form_fields', array($this, 'edit_perfumer_image_field'), 10, 2);
    }
    
    public function add_brand_image_field($taxonomy) {
        ?>
        <div class="form-field term-group">
            <label for="brand-image-id"><?php _e('Brand Logo', 'parfume-reviews'); ?></label>
            <input type="hidden" id="brand-image-id" name="brand-image-id" class="custom_media_url" value="">
            <div id="brand-image-wrapper"></div>
            <p>
                <input type="button" class="button button-secondary pr_tax_media_button" id="pr_tax_media_button" name="pr_tax_media_button" value="<?php _e('Add Image', 'parfume-reviews'); ?>" />
                <input type="button" class="button button-secondary pr_tax_media_remove" id="pr_tax_media_remove" name="pr_tax_media_remove" value="<?php _e('Remove Image', 'parfume-reviews'); ?>" />
            </p>
        </div>
        <?php
    }
    
    public function edit_brand_image_field($term, $taxonomy) {
        $image_id = get_term_meta($term->term_id, 'brand-image-id', true);
        ?>
        <tr class="form-field term-group-wrap">
            <th scope="row">
                <label for="brand-image-id"><?php _e('Brand Logo', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <input type="hidden" id="brand-image-id" name="brand-image-id" value="<?php echo $image_id; ?>">
                <div id="brand-image-wrapper">
                    <?php if ($image_id) { ?>
                        <?php echo wp_get_attachment_image($image_id, 'thumbnail'); ?>
                    <?php } ?>
                </div>
                <p>
                    <input type="button" class="button button-secondary pr_tax_media_button" id="pr_tax_media_button" name="pr_tax_media_button" value="<?php _e('Add Image', 'parfume-reviews'); ?>" />
                    <input type="button" class="button button-secondary pr_tax_media_remove" id="pr_tax_media_remove" name="pr_tax_media_remove" value="<?php _e('Remove Image', 'parfume-reviews'); ?>" />
                </p>
            </td>
        </tr>
        <?php
    }
    
    public function add_perfumer_image_field($taxonomy) {
        ?>
        <div class="form-field term-group">
            <label for="perfumer-image-id"><?php _e('Perfumer Photo', 'parfume-reviews'); ?></label>
            <input type="hidden" id="perfumer-image-id" name="perfumer-image-id" class="custom_media_url" value="">
            <div id="perfumer-image-wrapper"></div>
            <p>
                <input type="button" class="button button-secondary pr_tax_media_button" id="pr_tax_media_button" name="pr_tax_media_button" value="<?php _e('Add Image', 'parfume-reviews'); ?>" />
                <input type="button" class="button button-secondary pr_tax_media_remove" id="pr_tax_media_remove" name="pr_tax_media_remove" value="<?php _e('Remove Image', 'parfume-reviews'); ?>" />
            </p>
        </div>
        <?php
    }
    
    public function edit_perfumer_image_field($term, $taxonomy) {
        $image_id = get_term_meta($term->term_id, 'perfumer-image-id', true);
        ?>
        <tr class="form-field term-group-wrap">
            <th scope="row">
                <label for="perfumer-image-id"><?php _e('Perfumer Photo', 'parfume-reviews'); ?></label>
            </th>
            <td>
                <input type="hidden" id="perfumer-image-id" name="perfumer-image-id" value="<?php echo $image_id; ?>">
                <div id="perfumer-image-wrapper">
                    <?php if ($image_id) { ?>
                        <?php echo wp_get_attachment_image($image_id, 'thumbnail'); ?>
                    <?php } ?>
                </div>
                <p>
                    <input type="button" class="button button-secondary pr_tax_media_button" id="pr_tax_media_button" name="pr_tax_media_button" value="<?php _e('Add Image', 'parfume-reviews'); ?>" />
                    <input type="button" class="button button-secondary pr_tax_media_remove" id="pr_tax_media_remove" name="pr_tax_media_remove" value="<?php _e('Remove Image', 'parfume-reviews'); ?>" />
                </p>
            </td>
        </tr>
        <?php
    }
    
    public function save_taxonomy_meta_fields($term_id, $tt_id, $taxonomy) {
        if (isset($_POST['brand-image-id']) && $taxonomy == 'marki') {
            update_term_meta($term_id, 'brand-image-id', absint($_POST['brand-image-id']));
        }
        
        if (isset($_POST['perfumer-image-id']) && $taxonomy == 'perfumer') {
            update_term_meta($term_id, 'perfumer-image-id', absint($_POST['perfumer-image-id']));
        }
    }
}