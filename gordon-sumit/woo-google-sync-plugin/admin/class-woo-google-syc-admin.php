<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Woo_Google_Syc
 * @subpackage Woo_Google_Syc/admin
 */

require_once plugin_dir_path(dirname(__FILE__)) . './includes/class-woo-google-client.php';

/**
 * The admin-specific functionality of the plugin.
 *
 * This plugin helps to manage all product information via google spreadsheet with custom range options.
 * user can manage product from WooCommerce as well as from the google spreadsheet
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Woo_Google_Syc
 * @subpackage Woo_Google_Syc/admin
 * @author     Gordon Sumit <gordon.sumit@ithands.com>
 */
class Woo_Google_Syc_Admin extends wooGoogleClient
{

  protected $directory_name = 'product-images';
  private $productColumun = [];
  /**
   * The ID of this plugin.
   *
   * @since    1.0.0
   * @access   private
   * @var      string $plugin_name The ID of this plugin.
   */
  private $plugin_name;

  /**
   * The version of this plugin.
   *
   * @since    1.0.0
   * @access   private
   * @var      string $version The current version of this plugin.
   */
  private $version;

  /**
   * Initialize the class and set its properties.
   *
   * @param string $plugin_name The name of this plugin.
   * @param string $version The version of this plugin.
   * @since    1.0.0
   */
  public function __construct($plugin_name, $version)
  {

    if (!isWooGsSettingPage()) return; // return if its not plugin page

    require_once WC_GOOGLE_SYC_PATH . '/admin/lib/vendor/autoload.php';

    /**
     * Auth Link to check login
     */
    $this->auth_link = '';

    /**
     * Plugin name
     */
    $this->plugin_name = $plugin_name;

    $this->version = $version;

    if ($this->getClient() !== null) { // check if google client token exist
      $this->client = $this->getClient();
    } else {
      delete_option('wgs_google_token');
    }

    if ($this->get_token()) {
      if ($_POST && isset($_POST['subAction'])) {
       // validateSheet(); // validate google sheet format
      } else {
        if ($this->getClient() !== null && $_SERVER['REQUEST_URI'] == WOOGS_SETTING_PATH) {
          Woo_Google_Syc_Admin::setRangeWithSku();
        }
      }
    }
  }

  public function myMustLogin()
  {
    echo "You must log in to use this plugin...:)";
    die('BYE!');
  }

  public static function add_settings_tab($settings_tabs)
  {
    $settings_tabs['woo_google_sync'] = __('Woo Google Sync', 'woocommerce-settings-tab-demo');
    return $settings_tabs;
  }


  public function setRangeWithSku()
  {
    try {

      $client = wooGoogleClient::getClient();

      // return if client not found
      if (!$client) return false;

      $spreadsheetId = get_option('wc_settings_tab_google_sheet_id');
      $service = new Google_Service_Sheets($client);
      $rng[] = WOO_GS_SHEET_RANGE;
      $params = array(
        'ranges' => $rng
      );
      $response = $service->spreadsheets_values->batchGet($spreadsheetId, $params);
      $val = $response->getValueRanges()[0]->getValues();
      array_shift($val);
      $celData = [];
      // $val = array_reverse($val);
      foreach (array_filter($val) as $key => $item) {
        $newKey = $key + 2;
        $celData[$newKey]['sku'] = $item[$this->getKey('sku')];
        $celData[$newKey]['ID'] = $item[$this->getKey('ID')];
        $celData[$newKey]['cell'] = 'A' . $newKey . ':V' . $newKey;
        $celData[$newKey]['row'] = $newKey;
      }
      update_option('wooGS_sheet_sku_range', json_encode($celData));
      return true;

    } catch (Exception $e) {
      return $e;
    }
  }

  /**
   * Display fields settings
   */
  public function settings_tab()
  {
    woocommerce_admin_fields(getSettings());
    include_once 'partials/woo-google-syc-admin-display.php';
  }

  /**
   * Update field settings
   */
  function update_settings()
  {
    woocommerce_update_options(getSettings());
  }

  /**
   * Register the stylesheets for the admin area.
   *
   * @since    1.0.0
   */
  public function enqueue_styles()
  {

    /**
     * This function is provided for demonstration purposes only.
     *
     * An instance of this class should be passed to the run() function
     * defined in Woo_Google_Syc_Loader as all of the hooks are defined
     * in that particular class.
     *
     * The Woo_Google_Syc_Loader will then create the relationship
     * between the defined hooks and the functions defined in this
     * class.
     */

    wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/woo-google-syc-admin.css', array(), $this->version, 'all');

  }

  /**
   * Register the JavaScript for the admin area.
   *
   * @since    1.0.0
   */
  public function enqueue_scripts()
  {

    /**
     * This function is provided for demonstration purposes only.
     *
     * An instance of this class should be passed to the run() function
     * defined in Woo_Google_Syc_Loader as all of the hooks are defined
     * in that particular class.
     *
     * The Woo_Google_Syc_Loader will then create the relationship
     * between the defined hooks and the functions defined in this
     * class.
     */

    wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/woo-google-syc-admin.js', array('jquery'), $this->version, false);
    wp_localize_script($this->plugin_name, 'WCGoogleSync',
      array(
        'ajaxurl' => admin_url('admin-ajax.php'),
      )
    );
  }

  public function syncAction()
  {
    $this->{$_POST['subAction']}();
  }

  private function syncGoogleSheet()
  {
    $cellValues = (array)json_decode(get_option('wooGS_sheet_sku_range'));

    $products = [];

    if ($_POST['opt'] == 'specific') {

      $ranges = explode(',', $_POST['param']['specific']);
      $ids = [];
      $rowNumber = [];

      foreach ($ranges as $item) {
        preg_match_all('!\d+!', $item, $n);
        $rowNumber[] = $n[0][0];
        $ids[] = getProductBySku($cellValues[$n[0][0]]->sku);
      }

      $rowNumber = array_filter($rowNumber);
      $products = (array)getProductById($ids);

      foreach ($products as $key => $product) {

        $product = (array)$product;

        if (!empty($product['post_parent'])) {

          $sku = get_post_meta($product['post_parent'], '_sku', true);
          $_product = wc_get_product($product['post_parent']);

          // get all product variations
          $variations = $_product->get_available_variations();

          foreach (array_filter($variations) as $subKey => $variation) {

            if ($variation['variation_id'] == $product['ID']) {

              $attributes['value'] = implode(',', $variation['attributes']);
              $arrayData = $this->updateGoogleSheet($variation['variation_id'], $variation, 'variable', $sku, $attributes, 'all');
              $range = WOO_GS_SHEET_RANGE . "!A" . $rowNumber[$key] . ":W" . $rowNumber[$key];

              // set google sheet range
              $data[] = new Google_Service_Sheets_ValueRange([
                'range' => $range,
                'values' => [$arrayData],
              ]);
            }
          }
        } else {

          $_product = wc_get_product($product['ID']);
          $type = $_product->get_type();

          $attributes = [];

          foreach ($_product->get_attributes() as $attr => $attItem) {

            if (taxonomy_exists($attr)) {

              $attributes['name'][] = sanitize_html_class(str_replace('pa_', '', $attr));
              $attributes['global'][] = 1;
              $terms = [];

              foreach ($attItem->get_options() as $term) {
                $terms[] = get_term_by('id', $term, $attr)->slug;
              }

              $attributes['value'][] = implode(',', $terms);
              $attributes['visibility'][] = 1;
            } else {

              $attributes['name'][] = $attr;
              $attributes['value'][] = implode(',', $attItem->get_options());
              $attributes['global'][] = 0;
              $attributes['visibility'][] = 1;

            }
          }
          $arrayData = $this->updateGoogleSheet($product['ID'], $product, $type, '', $attributes, 'specific');

          $range = WOO_GS_SHEET_RANGE . "!A" . $rowNumber[$key] . ":W" . $rowNumber[$key];

          // set sheet range
          $data[] = new Google_Service_Sheets_ValueRange([
            'range' => $range,
            'values' => [$arrayData],
          ]);
        }
      }

      // set client and sheet ID
      $client = wooGoogleClient::getClient();
      $spreadsheetId = get_option('wc_settings_tab_google_sheet_id');
      $service = new Google_Service_Sheets($client);

      // set range body
      $body = new Google_Service_Sheets_BatchUpdateValuesRequest([
        'valueInputOption' => "RAW",
        'data' => array_filter($data)
      ]);

      try {
        // check if result positive
        $result = $service->spreadsheets_values->batchUpdate($spreadsheetId, $body);
        wp_send_json(['result' => $products,]);

      } catch (Exception $e) {
        wp_send_json(['error' => $e]);
      }

      wp_send_json(['action' => 'success']);
    } else {

      if ($_POST['opt'] == 'range') { // if sync type if range

        $from = $_POST['param']['rangeFrom'];
        $to = $_POST['param']['rangeTo'];

        // get rows number
        preg_match_all('!\d+!', $from . $to, $rowNumber);
        $numRange = range($rowNumber[0][0], $rowNumber[0][1], 1);

        foreach ($numRange as $num) {
          $ids[] = getProductBySku($cellValues[$num]->sku);
        }

        $products = (array)objectToArray(getProductById($ids));
        usort($products, cmp_by_optionNumber());
        $isCleared = $this->clearRow($rowNumber[0][0], $rowNumber[0][1]);

        if (!$isCleared) wp_send_json(['error' => 'something went wrong!']);

        $this->updateSheetInSpecific($products, $rowNumber);
        wp_send_json(['action' => 'success']);

      } else {
        // Clear all rows and start updating the sheet from zero
        $isCleared = $this->clearRow(); // clean rows

        // send response if something went wrong
        if (!$isCleared) wp_send_json(['error' => 'something went wrong!']);

        // fetch all products
        $products = getProducts();

        $response['action'] = 'updateSheetIntoChunks';
        $chunkArray = array_chunk($products, 10, true);
        $response['column'] = '';
        $response['status'] = 'chunked';
        $response['chunks'] = $chunkArray;
        $response['size'] = count($chunkArray);

        wp_send_json($response);
      }

    }

  }


  /**
   * @param int $start
   * @param int $end
   * @return bool|Google_Service_Sheets_ClearValuesResponse
   *  Clear sheet before start filling the data
   */
  private function clearRow($start = 2, $end = 0)
  {
    try {
      // set google client and sheet ID
      $client = wooGoogleClient::getClient();
      $spreadsheetId = get_option('wc_settings_tab_google_sheet_id');
      $service = new Google_Service_Sheets($client);

      $count = $service->spreadsheets_values->get($spreadsheetId, WOO_GS_SHEET_RANGE)->count();

      if (empty($end)) {
        $end = ($count > 1) ? $count : 2;
      }

      $range = WOO_GS_SHEET_RANGE . '!A' . $start . ':W' . $end;
      $requestBody = new Google_Service_Sheets_ClearValuesRequest();
      $response = $service->spreadsheets_values->clear($spreadsheetId, $range, $requestBody);

      if ($response) {
        $data = new Google_Service_Sheets_ValueRange([
          'range' => WOO_GS_SHEET_RANGE . '!A1',
          'values' => [array_merge(validSheetFormat(), get_option('wc_settings_tab_meta_keys'))],
        ]);
        $service->spreadsheets_values->append($spreadsheetId, WOO_GS_SHEET_RANGE . '!A1', $data, ["valueInputOption" => "RAW"]);

      }
      return $response;

    } catch (\Exception $e) {

      wp_send_json(['error' => $e->getMessage()]);
      return true;
    }
  }

  /**
   * @param $products
   * @param $rng
   * @return bool
   * Update sheet value with specific range
   */
  private function updateSheetInSpecific($products, $rng)
  {
    $arrayData = [];

    foreach ($products as $key => $product) {

      $product = (array)$product;

      if ($product['post_parent'] > 0) {

        $sku = get_post_meta($product['post_parent'], '_sku', true);
        $_product = wc_get_product($product['post_parent']);
        $variations = $_product->get_available_variations();

        foreach (array_filter($variations) as $subKey => $variation) {

          if ($variation['variation_id'] == $product['ID']) {

            $attributes['value'] = implode(',', $variation['attributes']);
            $arrayData[] = $this->updateGoogleSheet($variation['variation_id'], $variation, 'variable', $sku, $attributes, 'all');
          }
        }
      } else {

        $_product = wc_get_product($product['ID']);
        $type = $_product->get_type();
        $sku = get_post_meta($product['ID'], '_sku', true);

        $attributes = [];

        foreach ($_product->get_attributes() as $attr => $attItem) {

          if (taxonomy_exists($attr)) {

            $attributes['name'][] = sanitize_html_class(str_replace('pa_', '', $attr));
            $attributes['global'][] = 1;
            $terms = [];

            foreach ($attItem->get_options() as $term) {
              $terms[] = get_term_by('id', $term, $attr)->slug;
            }

            $attributes['value'][] = implode(',', $terms);
            $attributes['visibility'][] = 1;

          } else {

            $attributes['name'][] = $attr;
            $attributes['value'][] = implode(',', $attItem->get_options());
            $attributes['global'][] = 0;
            $attributes['visibility'][] = 1;
          }
        }
        // set sheet data in array
        $arrayData[] = $this->updateGoogleSheet($product['ID'], $product, $type, '', $attributes, 'specific');
      }
    }
    $client = wooGoogleClient::getClient();
    $spreadsheetId = get_option('wc_settings_tab_google_sheet_id');
    $service = new Google_Service_Sheets($client);
    $range = WOO_GS_SHEET_RANGE . "!A" . $rng[0][0] . ":W" . $rng[0][1];

    $data[] = new Google_Service_Sheets_ValueRange([
      'range' => $range,
      'values' => $arrayData,
    ]);

    try {

      $body = new Google_Service_Sheets_BatchUpdateValuesRequest([
        'valueInputOption' => "RAW",
        'data' => $data
      ]);

      $result = $service->spreadsheets_values->batchUpdate($spreadsheetId, $body);
      wp_send_json(['result' => $products,]);

    } catch (\Exception $e) {

      wp_send_json(['error' => $e->getMessage()]);
      return true;
    }
  }

  /**
   * @return bool
   * Update sheet rows in chunks
   */
  private function updateSheetIntoChunks()
  {
    $newKey = 0;

    $products = $_POST['chunk'];
    $arrayData = [];

    foreach ($products as $key => $product) {

      $_product = wc_get_product($product['ID']);
      $type = $_product->get_type();

      $sku = get_post_meta($product['ID'], '_sku', true);
      $attributes = [];

      foreach ($_product->get_attributes() as $attr => $attItem) {

        if (taxonomy_exists($attr)) {

          $attributes['name'][] = sanitize_html_class(str_replace('pa_', '', $attr));
          $attributes['global'][] = 1;
          $terms = [];

          foreach ($attItem->get_options() as $term) {

            $termMeta = get_term_by('id', $term, $attr);
            $terms[] = $termMeta->name;
          }

          $attributes['value'][] = implode(',', $terms);
          $attributes['visibility'][] = 1;

        } else {

          $attributes['name'][] = $attr;
          $attributes['value'][] = implode(',', $attItem->get_options());
          $attributes['global'][] = 0;
          $attributes['visibility'][] = 1;

        }
      }

      $arrayData[] = $this->updateGoogleSheet($product['ID'], $product, $type, '', $attributes, 'all');

      if ($type == 'variable') {

        $variations = $_product->get_available_variations();

        foreach (array_filter($variations) as $subKey => $variation) {

          $attributes['value'] = implode(',', $variation['attributes']);
          $arrayData[] = $this->updateGoogleSheet($variation['variation_id'], $variation, 'variable', $sku, $attributes, 'all');
        }
      }

    }

    try {

      $client = wooGoogleClient::getClient();
      $spreadsheetId = get_option('wc_settings_tab_google_sheet_id');
      $service = new Google_Service_Sheets($client);
      $range = WOO_GS_SHEET_RANGE . "!A2";

      $data = new Google_Service_Sheets_ValueRange([
        'range' => $range,
        'values' => $arrayData,
      ]);

      $conf = ["valueInputOption" => "RAW"];
      $result = $service->spreadsheets_values->append($spreadsheetId, $range, $data, $conf);

      wp_send_json(['result' => $result,]);

    } catch (\Exception $e) {

      wp_send_json(['error' => $e->getMessage()]);
      return true;
    }

  }

  private function categoryString($productID)
  {

    $categories = wc_get_product_cat_ids($productID);
    $catAr = [];
    $str = '';

    foreach ($categories as $id) {

      $cat = get_term_by('id', $id, 'product_cat');

      if (!in_array($cat->name, $catAr['cat'])) {
        if ($cat->parent > 0) {

          $parentTermName = get_term_by('id', $cat->parent, 'product_cat')->name;
          $catAr[$parentTermName][] = $cat->name;
        } else {
          $catAr['cat'][] = $cat->name;
        }
      }
    }
    foreach ($catAr['cat'] as $n => $catItem) {
      if (isset($catAr[$catItem])) {

        $str .= $catItem . '>' . implode('>', $catAr[$catItem]);
        unset($catAr['cat'][$n]);
      }

    }
    $res = explode(',', implode(',', $catAr['cat']) . ',' . $str);

    return implode(',', array_filter($res));
  }

  /**
   * @param $productId
   * @param $product
   * @param $type
   * @param $parent
   * @param $attributes
   * @param string $processType
   * @return array
   * Set values for updating the google sheet
   */
  private function updateGoogleSheet($productId, $product, $type, $parent, $attributes, $processType = '')
  {
    if ($parent) {

      $images = !empty($product['image']['url']) ? $product['image']['url'] : '';
      $sku = $product['sku'];
      $price = !empty($product['display_regular_price']) ? '$' . $product['display_regular_price'] : '$0';
      $salePrice = ($product['display_price'] < $product['display_regular_price']) ? '$' . $product['display_price'] : '';
      $productDescription = $product['variation_description'];
      $stcok_qty = !empty($product['max_qty']) ? $product['max_qty'] : '';
      $manageStock = !empty($product['manage_stock']) ? $product['manage_stock'] : 'yes';
      $stockStatus = !empty($product['is_in_stock']) ? '' . $product['is_in_stock'] . '' : 'instock';
      $attributeName = !empty($attributes['name']) ? implode('|', $attributes['name']) : '';
      $attributeValue = !empty($attributes['value']) ? $attributes['value'] : '';
      $attributeGlobal = '0';
      $attributeVisibility = '0';
      $productTitle = '';
      $post = get_post($productId);
      $status = $post->post_status;

    } else {

      $images = has_post_thumbnail($product['ID']) ? get_the_post_thumbnail_url($product['ID']) : '';
      $post = get_post($productId);
      $productTitle = $post->post_title;
      $status = $post->post_status;
      $sku = get_post_meta($product['ID'], '_sku', true);
      $price = get_post_meta($product['ID'], '_regular_price', true);
      $salePrice = get_post_meta($product['ID'], '_sale_price', true);
      $height = get_post_meta($product['ID'], '_height', true);
      $weight = get_post_meta($product['ID'], '_weight', true);
      $length = get_post_meta($product['ID'], '_length', true);
      $width = get_post_meta($product['ID'], '_width', true);
      $stcok_qty = get_post_meta($product['ID'], '_stock', true);
      $productExcerpt = get_the_excerpt($product['ID']);
      $manageStock = get_post_meta($product['ID'], '_manage_stock', true);
      $stockStatus = get_post_meta($product['ID'], '_stock_status', true);
      $categories = $this->categoryString($product['ID']);
      $attributeName = isset($attributes['name']) ? implode('|', $attributes['name']) : '';
      $attributeValue = isset($attributes['value']) ? implode('|', $attributes['value']) : '';
      $attributeGlobal = isset($attributes['global']) ? implode('|', $attributes['global']) : '';
      $attributeVisibility = isset($attributes['visibility']) ? implode('|', $attributes['visibility']) : '';


    }

    $customMeta = get_option('wc_settings_tab_meta_keys');

    $arr = [
      !empty($sku) ? strval($sku) : '',
      strval($productId),
      strval($price),
      strval($salePrice),
      !empty($productTitle) ? $productTitle : '',
      strval($type),
      strval($status),
      strval($parent),
      !empty($productExcerpt) ? $productExcerpt : '',
      !empty($weight) ? strval($weight) : '',
      !empty($length) ? strval($length) : '',
      !empty($width) ? strval($width) : '',
      !empty($height) ? strval($height) : '',
      $images,
      !empty($categories) ? $categories : '',
      strval($attributeName),
      strval($attributeValue),
      strval($attributeVisibility),
      strval($attributeGlobal),
      strval($manageStock),
      strval($stcok_qty),
      strval($stockStatus),
      date("F j, Y, g:i a")
    ];
    foreach ($customMeta as $meta) {
      $arr[] = ($metaValue = get_post_meta($product['ID'], $meta, true)) ? $metaValue : '';
    }
    return $arr;
  }

  private function setValueRange($value, $key, $processType)
  {
    if ($processType == 'all') {
      $key = $key + 2;
    }
    return new Google_Service_Sheets_ValueRange([
      'range' => WOO_GS_SHEET_RANGE . "!" . $value['range'] . ($key),
      'values' => [[$value['data']]],
    ]);
  }

  /**
   *$range = 'products!A1:J3';
   * $params = array(
   * 'ranges' => ['products!A1:J1','products!A5:J5']
   * );
   * $response = $service->spreadsheets_values->batchGet($spreadsheetId, $params);
   * // $numRows = $result->getValues() != null ? count($result->getValues()) : 0;
   * $values = $response->getValueRanges();
   */

  private function syncWoocommerce()
  {
    $values = $this->getProductFromSheet($_POST);
    if ($_POST['opt'] == 'all') {
      $this->productColumun = array_shift($values);
    } else {
      $this->productColumun = getColumns()[0];
    }

    $chunkArray = array_chunk($values, 10);
    $response['action'] = 'createProductInChunks';
    $response['column'] = $this->productColumun;
    $response['status'] = 'chunked';
    $response['chunks'] = $chunkArray;
    $response['size'] = count($chunkArray);
    wp_send_json($response);
    //$this->createProduct($values);
  }

  private function getProductFromSheet($postData)
  {
    try {

      $client = wooGoogleClient::getClient();
      $service = new Google_Service_Sheets($client);
      $spreadsheetId = get_option('wc_settings_tab_google_sheet_id');
      if ($postData['opt'] == 'specific') {
        $data = explode(',', $postData['param']['specific']);
        $rng = [];
        foreach ($data as $item) {
          preg_match_all('!\d+!', $item, $rowNumber);
          $rng[] = WOO_GS_SHEET_RANGE . '!A' . $rowNumber[0][0] . ':W' . $rowNumber[0][1];
        }
        $params = array(
          'ranges' => $rng
        );
        $response = $service->spreadsheets_values->batchGet($spreadsheetId, $params);
        if (count($rng) > 1) {
          foreach ($rng as $i => $n) {
            $values[] = $response->getValueRanges()[$i]->getValues()[0];
          }
          return $values;
        } else {
          return $response->getValueRanges()[0]->getValues();
        }
      } else {
        if ($postData['opt'] == 'range') {
          preg_match_all('!\d+!', $postData['param']['rangeFrom'] . $postData['param']['rangeTo'], $rowNumber);
          $range = WOO_GS_SHEET_RANGE . '!A' . $rowNumber[0][0] . ':W' . $rowNumber[0][1];
        } else {
          $range = WOO_GS_SHEET_RANGE;
        }
        $response = $service->spreadsheets_values->get($spreadsheetId, $range);
        return $response->getValues();
      }
    } catch (Exception $e) {
      wp_send_json(['error' => $e->getMessage()]);
      return true;
    }

  }

  public function wooChunkAction()
  {
    $this->{$_POST['subAction']}();
  }

  private function createProductInChunks()
  {
    $this->productColumun = $_POST['columns'];
    $this->createProduct($_POST['chunk']);
    wp_send_json(['message' => 'success']);
  }


  private function createProduct($product_array)
  {
    if (!empty($product_array)):
      foreach ($product_array as $product):
        $existingProduct = getProductBySku($product[$this->getKey('sku')]);

        if (!$existingProduct) { // if product does not exist than create one
          if ($product[$this->getKey('type')] == 'simple') { // create simple type product
            $this->createSimpleProduct($product);
          } else { // create variable product
            if ($product[$this->getKey('parent')]) {
              $this->createProductVariation($product);
            } else {
              $this->createParentProductVariation($product);
            }
          }
        } else { // update existing product product ID is not empty
          if ($product[$this->getKey('sku')] !== 'FALSE') {
            $post = [
              'ID' => $existingProduct,
              'post_title' => $product[$this->getKey('titles')],
              'post_content' => '',
              'post_excerpt' => $product[$this->getKey('excerpt')],
            ];
            $product_id = wp_update_post($post, true);
            if (empty($product[$this->getKey('parent')])) {
              $this->createCategory($product_id, $product);
            }
            $type = ($product[$this->getKey('type')] == 'simple') ? 'simple' : 'variable';
            if (empty($product[$this->getKey('parent')])) {
              $this->setAttribute($product_id, $type, $product[$this->getKey('attributes')], $product[$this->getKey('attribute_value')], $product[$this->getKey('attribute_global')]);
            }
            $this->updateProductMeta($existingProduct, $product);
            $attachmentId = $this->updateMedia($existingProduct, $product[$this->getKey('images')]);
            if ($attachmentId) {
              update_post_meta($product_id, '_thumbnail_id', $attachmentId);
            }
          }
        }
      endforeach;
    endif;
  }

  /**
   * Create a new variable product (with new attributes if they are).
   * (Needed functions:
   * @param array $data | The data to insert in the product.
   * @since 3.0.0
   */
  private function createParentProductVariation($product)
  {

    $post = [
      'post_author' => '',
      'post_content' => '',
      'post_excerpt' => $product[$this->getKey('excerpt')],
      'post_status' => $product[$this->getKey('status')],
      'post_title' => wp_strip_all_tags($product[$this->getKey('titles')]),
      'post_name' => $product[$this->getKey('titles')],
      'post_parent' => '',
      'post_type' => "product",
    ];
    //Create Post
    $product_id = wp_insert_post($post);

    //set Product Category
    $this->createCategory($product_id, $product);

    //set product attributes
    $this->setAttribute($product_id, 'variable', $product[$this->getKey('attributes')], $product[$this->getKey('attribute_value')], $product[$this->getKey('attribute_global')]);
    wp_set_object_terms($product_id, 'variable', 'product_type');
    $this->updateProductMeta($product_id, $product);
    $attachmentId = $this->updateMedia($product_id, $product[$this->getKey('images')]);
    if ($attachmentId) {
      update_post_meta($product_id, '_thumbnail_id', $attachmentId);
    }
  }

  private function setAttribute($product_id, $type, $attributes, $attributeData, $isGlobal)
  {
    if (!empty($attributes)) {
      $attributes = explode('|', $attributes);
      $all_attribute_values = explode('|', $attributeData);
      $global = explode('|', $isGlobal);
      $product = wc_get_product($product_id);
      $wcAttributes = wc_get_attribute_taxonomies();
      $attSlugs = wp_list_pluck($wcAttributes, 'attribute_name');

      foreach ($attributes as $key => $attr) {
        if ($global[$key] > 0) {
          $attribute = trim(strtolower($attr));
          $attLabel = trim($attr);
          $taxonomy = 'pa_' . $attribute;

          if (!in_array($attribute, $attSlugs)) {
            $args = array(
              'slug' => sanitize_title($attribute),
              'name' => __($attLabel, 'woo-google-sync'),
              'type' => 'select',
              'orderby' => 'menu_order',
              'has_archives' => false,
            );
            $result = wc_create_attribute($args);
          }

          if (!taxonomy_exists($taxonomy)) {
            register_taxonomy(
              $taxonomy,
              'product_variation',
              array(
                'hierarchical' => true,
                'label' => ucfirst($attribute),
                'query_var' => true,
                'rewrite' => array('slug' => sanitize_title($attribute)), // The base slug
              ));
          }
          $terms = explode(',', trim($all_attribute_values[$key]));
          $termID = [];
          foreach ($terms as $term) {
            $term_name = ucfirst(trim($term));
            $term_slug = sanitize_title(trim($term));
            if (empty($term_slug) && $term_slug != ',') return;
            // Check if the Term name exist and if not we create it.
            if (!term_exists(trim($term), $taxonomy)) {
              $termID[] = wp_insert_term($term_name, $taxonomy, array('slug' => $term_slug))['term_id']; // Create the term
            } else {
              $termid = get_term_by('slug', $term_slug, $taxonomy)->term_id;
              wp_update_term($termid, $taxonomy, ['name' => $term_name, 'slug' => $term_slug]);
              $termID[] = $termid;
            }

          }

          $taxId = wc_attribute_taxonomy_id_by_name($taxonomy);
          $globalAttribute = new WC_Product_Attribute();
          $globalAttribute->set_id($taxId);
          $globalAttribute->set_name($taxonomy);
          $globalAttribute->set_options($termID);
          $globalAttribute->set_position(0);
          $globalAttribute->set_visible(1);
          if ($type != 'simple') {
            $globalAttribute->set_variation(true);
          }
          $attribute_object[] = $globalAttribute;
        } else {
          $attribute_values = explode(',', $all_attribute_values[$key]);

          $localAttribute = new WC_Product_Attribute();
          $localAttribute->set_id(0);
          $localAttribute->set_name($attr);
          $localAttribute->set_options($attribute_values);
          $localAttribute->set_position(0);
          $localAttribute->set_visible(1);
          if ($type != 'simple') {
            $localAttribute->set_variation(true);
          }
          $attribute_object[] = $localAttribute;
        }
        $product->set_attributes($attribute_object);
        $product->save();
      }
    }
  }

  private function createProductVariation($product)
  {
    // Get the Variable product object (parent)
    //  echo $product[$this->getKey('parent')];
    $parentProductID = getProductBySku($product[$this->getKey('parent')]);
    $parentProduct = wc_get_product($parentProductID);
    $attributes = [];
    if (!empty($product[$this->getKey('attributes')])) {
      $attributes = explode('|', $product[$this->getKey('attributes')]);
    } else {
      foreach ($parentProduct->get_attributes() as $key => $attr) {
        $attributes[] = $key;
      }
    }

    $variation_post = array(
      'post_title' => $parentProduct->get_name(),
      'post_name' => 'product-' . $parentProductID . '-variation',
      'post_parent' => $parentProductID,
      'post_status' => $product[$this->getKey('status')],
      'post_type' => 'product_variation',
      'guid' => $parentProduct->get_permalink()
    );

    // Creating the product variation
    $variation_id = wp_insert_post($variation_post);
    $attachmentId = $this->updateMedia($variation_id, $product[$this->getKey('images')]);
    if ($attachmentId) {
      update_post_meta($variation_id, '_thumbnail_id', $attachmentId);
    }
    // Get an instance of the WC_Product_Variation object
    $variation = new WC_Product_Variation($variation_id);

    // Iterating through the variations attributes

    foreach ($attributes as $key => $attribute) {
      $attribute = trim(strtolower($attribute));
      if (taxonomy_exists('pa_' . $attribute)) {
        $taxonomy = 'pa_' . $attribute; // The attribute taxonomy
      } else {
        $taxonomy = $attribute; // The attribute taxonomy
      }
      // If taxonomy doesn't exists we create it (Thanks to Carl F. Corneil)
      if (!taxonomy_exists($taxonomy)) {
        register_taxonomy(
          $taxonomy,
          'product_variation',
          array(
            'hierarchical' => false,
            'label' => ucfirst($attribute),
            'query_var' => true,
            'rewrite' => array('slug' => sanitize_title($attribute)), // The base slug
          ));
      }

      $term = explode('|', $product[$this->getKey('attribute_value')]);
      $term_name = ucfirst(trim($term[$key]));
      $term_slug = sanitize_title(trim($term[$key]));

      // Check if the Term name exist and if not we create it.
      if (!term_exists(trim($term[$key]), $taxonomy))
        wp_insert_term($term_name, $taxonomy, array('slug' => $term_slug)); // Create the term

      // Set attribute values
      wp_set_post_terms($parentProductID, $term_name, $taxonomy, true);
      update_post_meta($variation_id, 'attribute_' . $taxonomy, $term_slug);
    }

## Set/save all other data

// sku
    if (!empty($product[$this->getKey('sku')]))
      $variation->set_sku($product[$this->getKey('sku')]);

// Prices
    if (empty($product[$this->getKey('sale_price')])) {
      $variation->set_price($product[$this->getKey('price')]);
    } else {
      $variation->set_price($product[$this->getKey('sale_price')]);
      $variation->set_sale_price($product[$this->getKey('sale_price')]);
    }
    $variation->set_regular_price($product[$this->getKey('price')]);

// Stock
    if (!empty($product[$this->getKey('stock_quantity')])) {
      $variation->set_stock_quantity($product[$this->getKey('stock_quantity')]);
      $variation->set_manage_stock(true);
      $variation->set_stock_status('');
    } else {
      $variation->set_manage_stock(false);
    }

    $variation->set_weight(''); // weight (reseting)

    $variation->save(); // Save the data

  }

  /**
   * @param $product
   * Create Simple type product
   */
  private function createSimpleProduct($product)
  {
    $post = [
      'post_author' => '',
      'post_content' => '',
      'post_excerpt' => $product[$this->getKey('excerpt')],
      'post_status' => $product[$this->getKey('status')],
      'post_title' => wp_strip_all_tags($product[$this->getKey('titles')]),
      'post_name' => $product[$this->getKey('titles')],
      'post_parent' => '',
      'post_type' => "product",
    ];
    //Create Post
    $product_id = wp_insert_post($post);

    //set Product Category
    $this->createCategory($product_id, $product);

    //set product type
    wp_set_object_terms($product_id, $product[4], 'product_type');
    $this->updateProductMeta($product_id, $product);

    //set product attributes
    $this->setAttribute($product_id, 'simple', $product[$this->getKey('attributes')], $product[$this->getKey('attribute_value')], $product[$this->getKey('attribute_global')]);

    $attachmentId = $this->updateMedia($product_id, $product[$this->getKey('images')]);
    if ($attachmentId) {
      update_post_meta($product_id, '_thumbnail_id', $attachmentId);
    }
  }

  /**
   * @param $product_id
   * @param $product
   * Update product Meta
   */
  private function updateProductMeta($product_id, $product)
  {
    // check if sales price exist
    if (isset($product[$this->getKey('sale_price')])) {
      $price = $product[$this->getKey('sale_price')];
    } else {
      // else add price as actual price
      $price = $product[$this->getKey('price')];
    }

    // set array for product meta data
    $data = [
      '_sku' => $product[$this->getKey('sku')],
      '_stock_status' => isset($product[$this->getKey('stock_status')]) ? $product[$this->getKey('stock_status')] : '',
      '_manage_stock' => isset($product[$this->getKey('manage_stock')]) ? $product[$this->getKey('manage_stock')] : '',
      '_stock' => isset($product[$this->getKey('stock_quantity')]) ? $product[$this->getKey('stock_quantity')] : '',
      '_price' => $price ? $price : '',
      '_regular_price' => isset($product[$this->getKey('price')]) ? $product[$this->getKey('price')] : '',
      '_sale_price' => isset($product[$this->getKey('sale_price')]) ? $product[$this->getKey('sale_price')] : '',
      '_weight' => isset($product[$this->getKey('weight')]) ? $product[$this->getKey('weight')] : '',
      '_length' => isset($product[$this->getKey('length')]) ? $product[$this->getKey('length')] : '',
      '_width' => isset($product[$this->getKey('width')]) ? $product[$this->getKey('width')] : '',
      '_height' => isset($product[$this->getKey('height')]) ? $product[$this->getKey('height')] : '',
    ];

    /**
     * filter to update or add new meta data
     * @param array $data The current product meta array.
     */
    $data = apply_filters('woo_gs_meta_data', $data);

    foreach ($data as $key => $item) { // iterate for each meta value
      addMetaData($product_id, $key, $item);
    }
  }

  /**
   *
   * Update product media
   *
   * @param int $product_id current loop product ID
   * @param string $imageUrl image url to download and save in media
   *
   * @return array|int|WP_Error  return attachment ID
   */
  private function updateMedia($product_id, $imageUrl)
  {
    $upload_dir = wp_upload_dir();
    $attachment_id = 0;
    $filename = basename($imageUrl);
    if ($this->doesFileExists($filename)) {
      $attachment_id = $this->doesFileExists($filename);
    } else {
      if (wp_mkdir_p($upload_dir['path'])) {
        $file = $upload_dir['path'] . '/' . $filename;
      } else {
        $file = $upload_dir['basedir'] . '/' . $filename;
      }

      if (file_put_contents($file, file_get_contents($imageUrl))) {
        $wp_file_type = wp_check_filetype($filename, null);
        $attachment = array(
          'post_mime_type' => $wp_file_type['type'],
          'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
          'post_name' => preg_replace('/\.[^.]+$/', '', $filename),
          'post_content' => '',
          'post_status' => 'inherit'
        );
        $attachment_id = wp_insert_attachment($attachment, $file);

        if (!is_wp_error($attachment_id)) {
          $attachment_data = wp_generate_attachment_metadata($attachment_id, $file);
          wp_update_attachment_metadata($attachment_id, $attachment_data);
        }
      }
    }
    return $attachment_id;
  }

  /**
   * @param $filename
   * @return int
   * Check if file already exist
   */
  private function doesFileExists($filename)
  {
    global $wpdb;
    return intval($wpdb->get_var("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value LIKE '%/$filename'"));
  }

  /**
   * @param $product_id
   * @param $product
   * Add new product category
   */
  private function createCategory($product_id, $product)
  {

    if (!empty($product[$this->getKey('categories')]) && trim($product[$this->getKey('categories')]) != ',') {
      $productCat = explode(',', $product[$this->getKey('categories')]);
      $allTermIds = [];
      foreach ($productCat as $cat) {
        if (strpos($cat, '>') !== false) {
          $cat = explode('>', $cat);
          $innerCat = explode('|', $cat[1]);
          if ($innerCat) {
            $parentTermId = $this->checkCategory($product_id, trim($cat[0]));
            $allTermIds[] = $parentTermId;
            foreach ($innerCat as $subCat) {
              $childTermId = $this->checkCategory($product_id, trim($subCat));
              $allTermIds[] = $childTermId;
              $update = wp_update_term($childTermId, 'product_cat', array(
                'parent' => $parentTermId,
              ));
            }
          }
        } else {
          $allTermIds[] = $this->checkCategory($product_id, trim($cat));
        }
      }
      wp_set_object_terms($product_id, $allTermIds, 'product_cat');
    }
  }

  /**
   * @param $product_id
   * @param $cat
   * @return int|mixed
   * Check if category already exist
   */
  private function checkCategory($product_id, $cat)
  {
    $CategoryId = get_term_by('name', $cat, 'product_cat');
    if ($CategoryId) return $CategoryId->term_id; // return with ID if exist

    $term = wp_insert_term($cat, 'product_cat');
    return $term['term_id'];
  }

  /**
   * @param $item
   * @return false|int|string
   * return column key value
   */
  private function getKey($item)
  {
    return array_search($item, $this->productColumun);
  }


}
