<?php
/**
 * Helper function file
 */

/**
 * @param $product_id
 * @param $metaKey
 * @param $metValue
 * Update product meta data
 */
function addMetaData($product_id, $metaKey, $metValue)
{
  update_post_meta($product_id, $metaKey, $metValue);
}

/**
 * @param $sku
 * @return string|null
 * Return product by product SKU
 */
function getProductBySku($sku)
{

  global $wpdb;
  $product_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sku' AND meta_value='%s' LIMIT 100", $sku));

  // return product exist with given SKU
  if ($product_id) return $product_id;

  return null; // return null if not product found
}


/**
 * @return int[]|WP_Post[]
 */
function getProducts()
{

  return get_posts([
    'numberposts' => -1,
    'meta_key' => '_sku',
    'post_type' => 'product',
    'meta_query' => [
      [
        'key' => '_sku',
        'value' => array('', 'FALSE'),
        'compare' => 'NOT IN'
      ],
    ]
  ]);
}

/**
 * @param $ids
 * @return int[]|WP_Post[]
 */
function getProductById($ids)
{

  // return product as per product ID
  return get_posts([
    'post_type' => ['product', 'product_variation'],
    'post__in' => $ids,
    'meta_key' => '_sku',
    'numberposts' => -1,
    'meta_query' => [
      [
        'key' => '_sku',
        'value' => array('', 'FALSE'),
        'compare' => 'NOT IN'
      ],
    ]
  ]);
}

/**
 * @param $object
 * @return array
 * Covert object to array
 */
function objectToArray($object)
{
  $res = [];
  foreach ($object as $obj) {
    $obj = (array)$obj;
    $res[] = $obj;
  }
  return $res;
}

function getSettings()
{
  $settings = array(
    'section_title' => array(
      'name' => __('Add Google Credentials & Sheet ID', 'woocommerce-settings-tab-demo'),
      'type' => 'title',
      'desc' => '',
      'id' => 'wc_settings_tab_demo_section_title'
    ),
    'google_credentials' => array(
      'name' => __('Google Client Credentials', 'woocommerce-settings-tab-demo'),
      'type' => 'text',
      'desc' => __('Copy/paste google credentials you downloaded from Google Console', 'woocommerce-settings-tab-demo'),
      'id' => 'wc_settings_tab_client_oauth'
    ),
    'sheetID' => array(
      'name' => __('Google Sheet ID', 'woocommerce-settings-tab-demo'),
      'type' => 'text',
      'desc' => __('Paste here the Google Sheet ID to import products/categories from', 'woocommerce-settings-tab-demo'),
      'id' => 'wc_settings_tab_google_sheet_id'
    ),
    'meta_keys' => array(
      'name' => __('Google Sheet ID', 'woocommerce-settings-tab-demo'),
      'type' => 'multiselect',
      'options'=>fetchMetaKeys(),
     // 'value'=>get_option('wc_settings_tab_meta_keys'),
      'desc' => __('Paste here the Google Sheet ID to import products/categories from', 'woocommerce-settings-tab-demo'),
      'id' => 'wc_settings_tab_meta_keys'
    ),
    'auth_redirect_url' => array(
      'name' => __('Redirect URL', 'woocommerce-settings-tab-demo'),
      'type' => 'text',
      'default' => WOO_GOOGLE_SYNC_AUTH_REDIRECT_URL,
      'desc' => __('Copy this redirect URL and paste into Google credentials as per guide.', 'woocommerce-settings-tab-demo'),
      'id' => 'auth_redirect_url',
      'custom_attributes' => array('readonly' => 'readonly'),
    ),
    'section_end' => array(
      'type' => 'sectionend',
      'id' => 'wc_settings_tab_demo_section_end'
    )
  );
  return apply_filters('woo_gs_settings_tab_settings', $settings);
}



function validSheetFormat()
{
  $columns = ['sku', 'ID', 'price', 'sale_price', 'titles', 'type', 'status', 'parent', 'excerpt', 'weight', 'length', 'width',
    'height', 'images', 'categories', 'attributes', 'attribute_value', 'attribute_visiblity', 'attribute_global', 'manage_stock',
    'stock_quantity',
    'stock_status', 'last_sync'];

  /**
   * Alter sheet columns using filter for validating
   * @param array $columns
   */
  return apply_filters('woo_gs_sheet_column', $columns);
}

function getColumns()
{
  try {
    $client = wooGoogleClient::getClient();
    $spreadsheetId = get_option('wc_settings_tab_google_sheet_id');
    $service = new Google_Service_Sheets($client);
    $rng[] = WOO_GS_SHEET_RANGE . '!A1:W1';
    $params = array(
      'ranges' => $rng
    );
    $response = $service->spreadsheets_values->batchGet($spreadsheetId, $params);
    return $response->getValueRanges()[0]->getValues();
  } catch (Exception $e) {
    wp_send_json(['error' => $e->getMessage()]);
    return true;
  }
}

function validateSheet()
{
  $sheetColumns = json_encode(getColumns()[0]);
  $columns = json_encode(validSheetFormat());
  if ($sheetColumns != $columns) {
    wp_send_json(['error' => 'Sheet format doest not matched!!']);
  }
}


/**
 * @param $a
 * @param $b
 * @return mixed
 * Sorting to numbers
 */
function cmp_by_optionNumber($a, $b)
{
  return $a["post_parent"] - $b["post_parent"];
}

function fetchMetaKeys()
{
  global $wpdb;
  $keys = $wpdb->get_col(
    "
			SELECT meta_key
			FROM $wpdb->postmeta
			GROUP BY meta_key
			ORDER BY meta_key"
  );
  $newKey = [];
  foreach ($keys as $key){
    $newKey[$key]=$key;
  }
  return $newKey;
}
