<?php

require_once  __DIR__ . '/../vendor/autoload.php';

use Timber\Timber;
use Timber\Site AS TimberSite;
use Timber\Menu AS TimberMenu;
use Timber\Twig_Filter as TwigFilter;
use Timber\Loader as TimberLoader;

use WS_2020\inc\Taxonomies;
use WS_2020\inc\CustomPostTypes;
use WS_2020\inc\AjaxRequests;
use WS_2020\inc\CustomFields;

class WS_Starter extends TimberSite {
   private $stylesheet_directory_uri;
   private $stylesheet_directory;

   public function __construct() {
      $this->stylesheet_directory_uri = get_stylesheet_directory_uri();
      $this->stylesheet_directory = get_stylesheet_directory();

      $this->custom_fields = new CustomFields();

      $this->configure();
      $this->manage_actions();
      $this->manage_filters();

      parent::__construct();
   }


   /**
    * Configure theme by setting up multiple WP options
    */
   public function configure() {
      define('DISALLOW_FILE_EDIT', true);
      define('ENFORCE_GZIP', true);

      show_admin_bar(false);

      add_theme_support('post-formats', 
         array(
            'aside',
            'image',
            'video',
            'quote',
            'link',
            'gallery',
            'audio',
         )
      );
      add_theme_support('post-thumbnails');
      add_theme_support('menus');
      add_theme_support('html5', array('comment-list', 'comment-form', 'search-form', 'gallery', 'caption'));
      add_theme_support('block-templates');

      add_theme_support( 'woocommerce' );

      // Init Timber
      new Timber();
      Timber::$dirname = array('templates', 'templates/partials', 'templates/layouts', 'templates/components', 'templates/pages');
      Timber::$cache = self::isLocalhost() ? false : true;

      // Init Taxonomies & Custom post types
      new Taxonomies();
      new CustomPostTypes();

      // Init Ajax requests
      new AjaxRequests();
   }

   private static function isLocalhost() {
      return in_array($_SERVER['REMOTE_ADDR'], ['localhost', '127.0.0.1', '::1']);
   }

   /**
    * Manage add_action && remove_action
    */
   public function manage_actions() {
      remove_action('template_redirect', 'rest_output_link_header', 11, 0);
      remove_action('admin_print_scripts', 'print_emoji_detection_script');
      remove_action('wp_print_styles', 'print_emoji_styles');
      remove_action('admin_print_styles', 'print_emoji_styles');
      remove_action('wp_head', 'rest_output_link_wp_head');
      remove_action('wp_head', 'wp_oembed_add_discovery_links');
      remove_action('wp_head', 'print_emoji_detection_script', 7);
      remove_action('wp_head', 'wlwmanifest_link');
      remove_action('wp_head', 'index_rel_link');
      remove_action('wp_head', 'rsd_link');
      remove_action('wp_head', 'wp_generator');

      remove_action( 'admin_color_scheme_picker', [$this, 'admin_color_scheme_picker'] );

      remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20, 0);
      remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail' );

      add_action('init', [$this, 'custom_menus']);
      // add_action('init', [$this, 'define_permalink_structure']);
      add_action('acf/init', [$this, 'custom_options_page']);
      add_action('acf/init', [$this->custom_fields, 'load']);
      add_action('wp_enqueue_scripts', [$this, 'register_scripts']);
      add_action('wp_print_styles', [$this, 'deregister_styles']);
      add_action('wp_footer', [$this, 'deregister_scripts']);
      add_action('admin_head', [$this, 'admin_dashboard_favicons']);
      add_action('rt_nginx_helper_after_purge_all', [$this, 'clear_twig_cache']);

      add_action('acf/init', [$this, 'my_acf_init_block_types']);

      add_action( 'woocommerce_init', [$this, 'remove_message_after_add_to_cart'], 99);

      add_action( 'woocommerce_register_form_start', [$this, 'add_extra_field_sign'] );
      add_action( 'woocommerce_created_customer', [$this, 'save_extra_field_sign'] );
   }

   public function add_extra_field_sign() {
   ?>
      <p class="form-row form-row-last">
         <input placeholder="<?php _e( 'Last name', 'woocommerce' ); ?> *"
         type="text"
         class="input-text"
         name="billing_last_name"
         id="reg_billing_last_name"
         value="<?php if ( ! empty( $_POST['billing_last_name'] ) ) esc_attr_e( $_POST['billing_last_name'] ); ?>" />
      </p>

      <p class="form-row form-row-first">
         <input placeholder="<?php _e( 'First name', 'woocommerce' ); ?> *"
         type="text"
         class="input-text"
         name="billing_first_name"
         id="reg_billing_first_name"
         value="<?php if ( ! empty( $_POST['billing_first_name'] ) ) esc_attr_e( $_POST['billing_first_name'] ); ?>" />
      </p>

      <p class="form-row form-row-last">
         <input placeholder="Nom de l'entreprise *"
         type="text"
         class="input-text"
         name="billing_company"
         id="reg_billing_company"
         value="<?php if ( ! empty( $_POST['billing_company'] ) ) esc_attr_e( $_POST['billing_company'] ); ?>" />
      </p>

      <!--<p class="form-row form-row-last">
         <select name="test" id="">
            <option value="" selected disabled hidden>Spécialité *</option>
            <option value="1">Secteur 1</option>
            <option value="2">Secteur 2</option>
            <option value="3">Secteur 3</option>
         </select>
      </p>-->


      <p class="form-row form-row-last">
         <input placeholder="<?php _e( 'Adresse', 'woocommerce' ); ?> *"
         type="text"
         class="input-text"
         name="billing_address_1"
         id="reg_billing_address_1"
         value="<?php if ( ! empty( $_POST['billing_address_1'] ) ) esc_attr_e( $_POST['billing_address_1'] ); ?>" />
      </p>

      <p class="form-row form-row-last">
         <input placeholder="<?php _e( 'Code postal', 'woocommerce' ); ?> *"
         type="text"
         class="input-text"
         name="billing_postcode"
         id="reg_billing_postcode"
         value="<?php if ( ! empty( $_POST['billing_postcode'] ) ) esc_attr_e( $_POST['billing_postcode'] ); ?>" />
      </p>

      <p class="form-row form-row-last">
         <input placeholder="<?php _e( 'Ville', 'woocommerce' ); ?> *"
         type="text"
         class="input-text"
         name="billing_city"
         id="reg_billing_city"
         value="<?php if ( ! empty( $_POST['billing_city'] ) ) esc_attr_e( $_POST['billing_city'] ); ?>" />
      </p>
      

      <p class="form-row form-row-last">
         <input placeholder="N° de téléphone *"
         type="text"
         class="input-text"
         name="billing_phone"
         id="reg_billing_phone"
         value="<?php if ( ! empty( $_POST['billing_address_1'] ) ) esc_attr_e( $_POST['billing_address_1'] ); ?>" />
      </p>
   <?php
   }

   public function save_extra_field_sign() {
      if ( isset( $_POST['billing_first_name'] ) ) {
             //First name field which is by default
             update_user_meta( $customer_id, 'first_name', sanitize_text_field( $_POST['billing_first_name'] ) );
             // First name field which is used in WooCommerce
             update_user_meta( $customer_id, 'billing_first_name', sanitize_text_field( $_POST['billing_first_name'] ) );
      }
      if ( isset( $_POST['billing_last_name'] ) ) {
             // Last name field which is by default
             update_user_meta( $customer_id, 'last_name', sanitize_text_field( $_POST['billing_last_name'] ) );
             // Last name field which is used in WooCommerce
             update_user_meta( $customer_id, 'billing_last_name', sanitize_text_field( $_POST['billing_last_name'] ) );
      }

      if ( isset( $_POST['billing_last_name'] ) ) {
             // Last name field which is used in WooCommerce
             update_user_meta( $customer_id, 'billing_company', sanitize_text_field( $_POST['billing_company'] ) );
      }

      
   }







   public function remove_message_after_add_to_cart(){
      if( isset( $_GET['add-to-cart'] ) ){
         wc_clear_notices();
      }
   }

   public function my_acf_init_block_types() {
      if (function_exists('acf_register_block_type')) {
         acf_register_block_type(array(
            'name'              => 'page_accueil',
            'title'             => __('Page d\'accueil'),
            'description'       => __('Ajouter le contenu de la page d\'accueil.'),
            'render_template'   => 'custom-blocks/home.php',
            'category'          => 'formatting',
            'icon'              => 'admin-comments',
            'post_types'        => array('page'),
            'mode'              => 'edit',
         ));
      }
   }

   /**
    * Manage add_filter && remove_filter
    */
   public function manage_filters() {
      remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
      remove_filter('the_content_feed', 'wp_staticize_emoji');
      remove_filter('comment_text_rss', 'wp_staticize_emoji');
      
      add_filter('xmlrpc_enabled', '__return_false');
      add_filter('timber_context', [$this, 'add_to_context']);
      add_filter('get_twig', [$this, 'add_to_twig']);
      add_filter('wp_mail_from', [$this, 'wp_mail_sender_email']);
      add_filter('wp_mail_from_name', [$this, 'wp_mail_sender_name']);
      add_filter('wp_mail_content_type',[$this, 'set_mail_content_type']);

      add_filter('upload_mimes', [$this, 'allow_svg']);
      add_filter('wpseo_breadcrumb_links', [$this, 'edit_breadcrumb']);

      add_filter( 'add_to_cart_text', [$this, 'woo_custom_product_add_to_cart_text'] );
      // add_filter( 'woocommerce_product_add_to_cart_text', [$this, 'woo_custom_product_add_to_cart_text'] );

      add_filter( 'woocommerce_add_to_cart_fragments', [ $this, 'cart_link_fragment' ] );

      add_filter( 'woocommerce_product_tabs', [$this, 'woo_new_product_tab'] );

      add_filter('use_block_editor_for_post_type', [$this, 'prefix_disable_gutenberg'], 10, 2);

      add_filter( 'woocommerce_cart_item_name', [$this, 'quadlayers_product_image_checkout'], 9999, 3 ); 
   }


   public function quadlayers_product_image_checkout ( $name, $cart_item, $cart_item_key ) {
      if ( ! is_checkout() ) {return $name;}

      $product = $cart_item['data'];
      $thumbnail = $product->get_image( array( '180', '180' ), array( 'class' => 'alignleft' ) ); 
      /*Above you can change the thumbnail size by changing array values e.g. array(‘100’, ‘100’) and also change alignment to alignright*/
      return $thumbnail . $name;
   }

   public function prefix_disable_gutenberg($current_status, $post_type) {
      // Use your post type key instead of 'product'
      if ($post_type === 'documents') return false;
      return $current_status;
   }
   

   public function woo_new_product_tab( $tabs ) {
      if( is_product() ) {
         $fields = get_field('ajouter_un_onglet', get_the_ID());

         if($fields) {
            foreach ($fields as $k => $field) {
               $tabs[$k] = array(
                  'title'    => $field['nom_de_longlet'],
                  'priority' => 1,
                  'callback' => [$this, 'woo_new_product_tab_content'],
                  'content'  => $field['texte_de_longlet']
               );
            }
         }
      }

      return $tabs;
   }

   public function woo_new_product_tab_content($name, $content)  {
      echo '<h2>' . $content['title'] . '</h2>';
      echo $content['content'];
   }


   public function cart_link_fragment( $fragments ) {
      $fragments['a.cart-mini-contents'] = Timber::compile(
            'woocommerce/cart/fragment-link.twig',
            [ 'cart' => WC()->cart ]
      );

      return $fragments;
   }
   
   public function woo_custom_product_add_to_cart_text() {
      return; 
   }

   /**
    * Yoast breadcrumb edit
    */
    function edit_breadcrumb($links) {
      if(is_tax('annuaire_ville')){
         $url = get_site_url().'/nos-clients/';
         $original = $links;
         $inserted = array([
            "url" => $url,
            "text" => 'Nos clients',
            "id" => 'unset'
         ]);

         array_splice( $original, 1, 0, $inserted );
         $links = $original;
      }




      
      if(is_singular( 'praticiens' )){
         $url = get_site_url().'/nos-clients/';
         $original = $links;
         $inserted = array([
            "url" => $url,
            "text" => 'Nos clients',
            "id" => 'unset'
         ]);

         array_splice( $original, 1, 0, $inserted );
         $links = $original;
      }

      // Rebase array keys
      $links = array_values( $links );

      // Return modified array
      return $links;
   }

   /**
   * Allow SVGs upload
   */
   function allow_svg($mimes) {
      $mimes['svg'] = 'image/svg+xml';
      return $mimes;
   }
   

   //Make sure we send HTML
   function set_mail_content_type() {
      return "text/html";
   }

   /**
    * Configure PHPMailer just before sending email
    */
   public function add_dkim_to_email($phpmailer) {
      // $dir = __DIR__;
      // $phpmailer->DKIM_domain = "nom-de-domaine.fr";
      // $phpmailer->DKIM_private = "{$dir}/../dkim/dkim.private.key";
      // $phpmailer->DKIM_selector = "mail";
      // $phpmailer->DKIM_passphrase = '';
      // $phpmailer->DKIM_identity = $phpmailer->From;
      $phpmailer->IsHTML(true);
   }

   /**
    * Change wp_mail sender email header
    */
   public function wp_mail_sender_email($original_email) {
      return 'nepasrepondre@contour-paris.com';
   }

   /**
    * Change wp_mail sender name header
    */
   public function wp_mail_sender_name($original_name) {
      return 'Contour Paris';
   }

   /**
    * Load CSS & JS
    */
   public function register_scripts() {
      $js_files = glob($this->stylesheet_directory . '/js/*.js');
      $css_files = glob($this->stylesheet_directory . '/css/*.css');

      wp_register_script('ws-vendor-js', $this->stylesheet_directory_uri.'/js/'.basename($js_files[1]), null, null, true);
      wp_register_script('ws-app-js', $this->stylesheet_directory_uri.'/js/'.basename($js_files[0]), ['ws-vendor-js'], null, true);
      wp_register_style('ws-app', $this->stylesheet_directory_uri.'/css/'.basename($css_files[0]), [], null);
      
      wp_enqueue_script('ws-vendor-js');
      wp_enqueue_script('ws-app-js');
      wp_enqueue_style('ws-app');
   }

   /**
    * Remove useless Wordpress styles
    */
   public function deregister_styles() {
      wp_dequeue_style('wp-block-library');
   }

   /**
    * Remove useless Wordpress scripts
    */
   public function deregister_scripts() {
      wp_deregister_script('wp-embed');
      // wp_deregister_script('jquery');
   }

   /**
    * Automaticaly define permalink structure
    */
   public function define_permalink_structure() {
      global $wp_rewrite;
      $wp_rewrite->set_permalink_structure('/%postname%/');
      $wp_rewrite->flush_rules();
   }

   /**
    * Load favicon for admin dashboard
    */
   public function admin_dashboard_favicons() {
      $html = "
            <link rel='apple-touch-icon' sizes='57x57' href='{$this->stylesheet_directory_uri}/images/favicon/apple-icon-57x57.png'>
            <link rel='apple-touch-icon' sizes='60x60' href='{$this->stylesheet_directory_uri}/images/favicon/apple-icon-60x60.png'>
            <link rel='apple-touch-icon' sizes='72x72' href='{$this->stylesheet_directory_uri}/images/favicon/apple-icon-72x72.png'>
            <link rel='apple-touch-icon' sizes='76x76' href='{$this->stylesheet_directory_uri}/images/favicon/apple-icon-76x76.png'>
            <link rel='apple-touch-icon' sizes='114x114' href='{$this->stylesheet_directory_uri}/images/favicon/apple-icon-114x114.png'>
            <link rel='apple-touch-icon' sizes='120x120' href='{$this->stylesheet_directory_uri}/images/favicon/apple-icon-120x120.png'>
            <link rel='apple-touch-icon' sizes='144x144' href='{$this->stylesheet_directory_uri}/images/favicon/apple-icon-144x144.png'>
            <link rel='apple-touch-icon' sizes='152x152' href='{$this->stylesheet_directory_uri}/images/favicon/apple-icon-152x152.png'>
            <link rel='apple-touch-icon' sizes='180x180' href='{$this->stylesheet_directory_uri}/images/favicon/apple-icon-180x180.png'>
            <link rel='icon' type='image/png' sizes='192x192' href='{$this->stylesheet_directory_uri}/images/favicon/android-icon-192x192.png'>
            <link rel='icon' type='image/png' sizes='32x32' href='{$this->stylesheet_directory_uri}/images/favicon/favicon-32x32.png'>
            <link rel='icon' type='image/png' sizes='96x96' href='{$this->stylesheet_directory_uri}/images/favicon/favicon-96x96.png'>
            <link rel='icon' type='image/png' sizes='16x16' href='{$this->stylesheet_directory_uri}/images/favicon/favicon-16x16.png'>
            <link rel='manifest' href='{$this->stylesheet_directory_uri}/images/favicon/manifest.json'>
        ";
        echo $html;
   }
   
   public function custom_options_page() {
      // Site options page
      $parent = acf_add_options_page(array(
         'page_title' => __('Configuration'),
         'menu_title' => __('Configuration'),
         'menu_slug'  => 'ws-options',
         'position'	 => 80,
         'redirect'   => true,
      ));

      // Analytics
      // $analytics = acf_add_options_page(array(
      //    'page_title'  => __('Analytics'),
      //    'menu_title'  => __('Analytics'),
      //    'menu_slug'   => 'ws-options-analytics',
      //    'parent_slug' => $parent['menu_slug']
      // ));

      // Global options
      $globalOptions = acf_add_options_page(array(
         'page_title'  => __('Gestion de contenus globaux'),
         'menu_title'  => __('Gestion de contenus globaux'),
         'menu_slug'   => 'ws-options-contents',
         'parent_slug' => $parent['menu_slug']
      ));

      $globalOptions = acf_add_options_page(array(
         'page_title'  => __('Coordonnées'),
         'menu_title'  => __('Coordonnées'),
         'menu_slug'   => 'ws-options-contact-informations',
         'parent_slug' => $parent['menu_slug']
      ));

      $globalOptions = acf_add_options_page(array(
         'page_title'  => __('Foire à questions'),
         'menu_title'  => __('Foire à questions'),
         'menu_slug'   => 'ws-options-faq',
         'parent_slug' => $parent['menu_slug']
      ));

      // $globalOptions = acf_add_options_page(array(
      //    'page_title'  => __('Section Instagram'),
      //    'menu_title'  => __('Section Instagram'),
      //    'menu_slug'   => 'ws-options-instagram',
      //    'parent_slug' => $parent['menu_slug']
      // ));

      $globalOptions = acf_add_options_page(array(
         'page_title'  => __('Section contact'),
         'menu_title'  => __('Section contact'),
         'menu_slug'   => 'ws-options-contact',
         'parent_slug' => $parent['menu_slug']
      ));

      $sectionECom = acf_add_options_page(array(
         'page_title'  => __('Section E-Commerce'),
         'menu_title'  => __('Section E-Commerce'),
         'menu_slug'   => 'ws-options-ecom',
         'parent_slug' => $parent['menu_slug']
      ));


   }

   /**
    * Manage WP menus
    * (Don't forget to call them in add_to_context)
    */
   public function custom_menus() {
      register_nav_menu('menu-main', __('Menu Principal'));
      register_nav_menu('menu-right', __('Menu Haut Droite'));
      register_nav_menu('menu-footer', __('Menu Footer'));
      register_nav_menu('menu-ecom', __('Menu ecommerce'));
   }


   /* --------------------- */ 
   /* --- TIMBER METHOD --- */
   /* --------------------- */

   public function add_to_context($context) {
      $context['site']        = $this;
      $context['menu']        = new TimberMenu('menu-main');
      $context['menu_right']  = new TimberMenu('menu-right');
      $context['menuFooter']  = new TimberMenu('menu-footer');
      $context['menuEcom']    = new TimberMenu('menu-ecom');
      $context['options']     = get_fields('option');
      $context['contactForm'] = get_fields(24);

      foreach($context['options']['section_avis']['temoignages'] as $tem){
         $tem->job = get_field('job', $tem->ID);
      }

      //check if user is connected
      if (is_user_logged_in()) {
         $context['isConnected'] = true;
      }else{
         $context['isConnected'] = false;
      }
      

      function page_type() {
         global $wp_query, $post;
         
         $page_type           = false;

         $available_templates = wp_get_theme()->get_page_templates();
         $template_filename   = basename(get_page_template());
         $template            = isset($available_templates[$template_filename]) ? $available_templates[$template_filename] : '';

         if (is_home() && get_option('page_for_posts')) {
            $page_type = 'home';
         } elseif (is_front_page() && get_option('page_on_front')) {
            $page_type = 'blog';
         } else {
            if (function_exists('is_shop') && is_shop() && get_option('woocommerce_shop_page_id') != '') {
               $page_type = 'shop';
            } elseif($wp_query->queried_object->post_parent == get_option('woocommerce_shop_page_id')) {
               $page_type = 'shop';
            } elseif ($template == 'Modèle de page – Accueil shop') {
               $page_type = 'shop';
            } elseif ($template == 'Modèle de page – Marketing') {
               $page_type = 'shop';
            } elseif ($template == 'Modèle de page – Documents') {
               $page_type = 'shop';
            } elseif ($template == 'Maintenance Template') {
               $page_type = 'shop';
            } elseif ($wp_query->queried_object->post_type == 'documents') {
               $page_type = 'shop';
            } elseif (is_singular( 'product' )) {
               $page_type = 'shop';
            } elseif (is_tax('product_cat')) {
               $page_type = 'shop';
            } else {
               if (function_exists('is_cart') && is_cart() && get_option('woocommerce_cart_page_id') != '') {
                  $page_type = 'cart';
               } else {
                  if (function_exists('is_checkout') && is_checkout() && get_option('woocommerce_checkout_page_id') != '') {
                     $page_type = 'checkout';
                  } else {
                     if (function_exists('is_account_page') && is_account_page() && get_option('woocommerce_myaccount_page_id') != '') {
                        $page_type = 'account';
                     } else {
                        if ($wp_query && !empty($wp_query->queried_object) && !empty($wp_query->queried_object->ID)) {
                           $page_type = 'queried_'.$wp_query->queried_object->ID;
                        } else {
                           if (!empty($post->ID)) {
                              $page_type = 'post_'.$post->ID;
                           }
                        }
                     }
                  }
               }
            }
         }

         return $page_type;
      }

      $context['page_type'] = page_type();
      $context['shop_types'] = array('shop', 'cart', 'checkout', 'account');

      $filters_args = array(
         'orderby'    => 'menu_order',
         'order'      => 'ASC',
         'hide_empty' => false,
         // 'exclude'    => array(16),
         // 'parent'     => 0,
      );
      $terms = get_terms( 'product_cat', $filters_args );

      //Pour chaque cat, on récup des infos supplémentaires
      foreach ($terms as $key => $cat) {
            $thumb_id = get_term_meta( $cat->term_id, 'thumbnail_id', true );
            $cat->thumbnail = wp_get_attachment_url( $thumb_id );
            $cat->permalink = get_term_link( $cat->term_id, 'product_cat' );
            $cat->entry = get_field('page_entry', $cat);
      }

      $context['cart'] = WC()->cart;

      $cart = WC()->cart->get_cart();

      $front_cart = [
         'total' => 0,
         'products' => [],
      ];

      foreach ( $cart as $key => $cart_item ) {
         $item = $cart_item['data'];
         $parent_id = wp_get_post_parent_id($cart_item['variation_id']);
         
         if($parent_id != 0){ //Variable product
            $type = 'variable';
            $product = wc_get_product($parent_id);
            $variation = $item;
            $product_id = $parent_id;
            $variation_id = $cart_item['variation_id'];
            $prod_terms = get_the_terms($parent_id, 'product_cat');
         }

         else{ //Simple product
            $type = 'simple';
            $product = $item;
            $variation = $item;
            $product_id = $cart_item['product_id'];
            $variation_id = $cart_item['product_id'];
            $prod_terms = get_the_terms($cart_item['product_id'], 'product_cat');
         }

         $front_cart['total'] += $item->get_price() *  $cart_item['quantity'];

         $datas = [
            'cart_item_key' => $key,
            'type' => $type,
            'name' => $product->get_name(),
            'quantity' => $cart_item['quantity'],
            'variation_id' => $variation_id,
            'product_id' => $product_id,
            'thumbnail' => wp_get_attachment_url($product->get_image_id()),
            'line_subtotal' => number_format($cart_item['line_subtotal'], 2, ',', ' ').'€ HT', 
            'line_subtotal_tax' => number_format($cart_item['line_subtotal_tax'], 2, ',', ' ').'€',
            'line_total' => number_format($cart_item['line_total'], 2, ',', ' ').'€ HT',
            'line_tax' => number_format($cart_item['line_tax'], 2, ',', ' ').'€',
            'item_price' => number_format($cart_item['line_subtotal'] / $cart_item['quantity'], 2, ',', ' ').'€ HT',
            'item_tax' => number_format($cart_item['line_subtotal_tax'] / $cart_item['quantity'], 2, ',', ' ').'€',
            'sku' => $variation->get_sku(),
            'regular_price' => number_format($variation->get_regular_price(), 2, ',', ' ').' € HT',
            'sale_price' => $variation->get_sale_price() ? number_format($variation->get_sale_price(), 2, ',', ' ').'€ HT' : '',
            'price' => number_format($variation->get_price(), 2, ',', ' ').'€ HT',
            'stock_qty' => $variation->get_stock_quantity(),
            'attributes' => $variation->get_attributes(),
         ];

         if($prod_terms){
            $datas['main_category'] = $prod_terms[0]->name;
         }

         array_push($front_cart['products'], $datas);
      }

      $context['front_cart'] = $front_cart;



      // var_dump($context['front_cart']);
      // exit('ok');





      $context['account_link'] = get_permalink( get_option('woocommerce_myaccount_page_id') );





      //detect all taxonomy of current product

      if (is_product()) {
         $product_taxonomies = get_the_terms(get_the_ID(), 'product_cat');

         $context['breadTEST']= array(
            'accueil' => array(
               'title' => 'Accueil',
               'link' => get_home_url()
            ),
            'shop' => array(
               'title' => get_the_title( get_option('woocommerce_shop_page_id') ),
               'link' => get_permalink( get_option('woocommerce_shop_page_id') )
            ),
            'category' => array(
               'title' => $product_taxonomies[0]->name,
               'link' => get_term_link($product_taxonomies[0]->term_id)
            ),
            'product' => array(
               'title' => get_the_title(),
               'link' => get_permalink()
            )
         );
      }

      $notifications = get_posts(array(
         'post_type' => 'notifications',
         'posts_per_page' => -1,
         'orderby' => 'date',
         'order' => 'DESC',
      ));

      function object_to_array($obj) {
         if(is_object($obj)) $obj = (array) $obj;
         if(is_array($obj)) {
            $new = array();
            foreach($obj as $key => $val) {
                  $new[$key] = object_to_array($val);
            }
         }
         else $new = $obj;
         return $new;       
      }

      $context['notifications'] = object_to_array($notifications);


      //if user is connected, get his past order
      if (is_user_logged_in()) {
         $customer = wp_get_current_user();
         // Get all customer orders
         $customer_orders = get_posts(array(
            'numberposts' => -1,
            'meta_key' => '_customer_user',
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_value' => get_current_user_id(),
            'post_type' => wc_get_order_types(),
            'post_status' => array_keys(wc_get_order_statuses()),
            // 'post_status' => array('wc-processing'),
         ));

         $Order_Array = []; //
         foreach ($customer_orders as $customer_order) {

            $orderq = wc_get_order($customer_order);

            $Order_Array[] = [
                  "id"        => $orderq->get_id(),
                  "value"     => $orderq->get_total(),
                  "status"    => $orderq->get_status(),
                  "date"      => $orderq->get_date_created()->date_i18n('d F Y à h\\hi'),
                  "post_date" => $orderq->get_date_created()->date('Y-m-d H:i:s', false, true),
                  "post_type" => 'order'
            ];
         }
         
         function cmp($a, $b){
            $ad = strtotime($a['post_date']);
            $bd = strtotime($b['post_date']);

            return ($ad-$bd);
         }

         $context['notifications'] = array_merge($context['notifications'], $Order_Array);

         usort($context['notifications'], 'cmp');
      }

      $context['notifications'] = array_reverse($context['notifications']);

      return $context;
   }

   public function add_to_twig($twig) {
      $twig->addExtension(new Twig\Extension\StringLoaderExtension());
      $twig->addFilter(new TwigFilter('tagtohtml', function($text) {
         $tags = [
            '[b]'  => '<strong>',
            '[/b]' => '</strong>',
            '[br]' => '<br/>',
         ];
           return str_replace(array_keys($tags), array_values($tags), $text);
      }));

      $twig->addFilter(new TwigFilter('notags', function($text) {
         $tags = [
            '[b]'  => '',
            '[/b]' => '',
            '[br]' => '',
            '<em>' => '',
            '</em>' => ''
         ];
           return str_replace(array_keys($tags), array_values($tags), $text);
      }));

      $twig->addFilter(new TwigFilter('removep', function($text) {
         $tags = [
            '<p>'  => '',
            '</p>' => '',
         ];
           return str_replace(array_keys($tags), array_values($tags), $text);
      }));

      

      return $twig;
   }

   /**
    * CLear twig cache on nginx-helper purge_all
    */
   private function clear_twig_cache()
   {
      $loader = new TimberLoader();
      $loader->clear_cache_twig();
   }

   
}

new WS_Starter();

function shortcode_nobr($atts, $content){
   return '<div style="white-space: nowrap; display:inline-block;">'.$content.'</div>';
}
add_shortcode('nobr', 'shortcode_nobr');

function timber_set_product( $post ) {
    global $product;

    if ( is_woocommerce() ) {
        $product = wc_get_product( $post->ID );
    }
}