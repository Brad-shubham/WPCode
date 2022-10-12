<?php
/**
 * Contains google client related code
 */

class wooGoogleClient
{
  public function __construct()
  {
    //
  }

  public function getClient($authCode = null)
  {
    try {

      $client = new Google_Client();
      $client->setApplicationName('NKB Product');
      // FullAccess
      $client->setScopes(Google_Service_Sheets::SPREADSHEETS);

      $gs_credentials = get_option('wc_settings_tab_client_oauth');
      $gs_credentials = json_decode($gs_credentials, true);
      $gs_redirect_uri = WOO_GOOGLE_SYNC_AUTH_REDIRECT_URL;

      $client->setAuthConfig($gs_credentials);
      $client->setAccessType("offline");
      $client->setApprovalPrompt("force");
      $client->setRedirectUri($gs_redirect_uri);
      // $client->setPrompt('select_account consent');

      // Load previously authorized token from a file, if it exists.
      // The file token.json stores the user's access and refresh tokens, and is
      // created automatically when the authorization flow completes for the first
      // time.
      if ($token = Woo_Google_Syc_Admin::get_token()) {
        $accessToken = json_decode($token, true);
        $client->setAccessToken($accessToken);
      }

      // If there is no previous token or it's expired.
      if ($client->isAccessTokenExpired()) {
        // Refresh the token if possible, else fetch a new one.
        // var_dump($client->getRefreshToken());
        if ($client->getRefreshToken()) {
          $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        } else {
          // Request authorization from the user.

          if ($authCode) {
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);

            // Check to see if there was an error.
            if (array_key_exists('error', $accessToken)) {
              throw new Exception(join(', ', $accessToken));
            }

            $this->save_token(json_encode($accessToken));
          } else {
            $authUrl = $client->createAuthUrl();
            $this->auth_link = $authUrl;
          }
        }
      }

      delete_transient("wgs_client_error_notices");
      return $client;
    } catch (\Exception $e) {
//      echo '<pre>';
//      print_r(json_decode($e->getMessage()));
//      echo '</pre>';
//             wcgs_pa(json_decode($e->getMessage(), true));
//             set_transient("wcgs_client_error_notices", $this->parse_message($e), 30);
    }
  }

  private function save_token($token)
  {
    update_option('wgs_google_token', $token);
  }

  public function get_token()
  {
    //$token = get_option('wgs_google_token');
    $token = ' {"access_token":"ya29.a0AfH6SMBSWlUY0AH0-OJ3ky1FZJYILhl68S_3AMlot_1phsfTyIMT3-m0JKznXT5B6GQuyGMgIt0OkKaP4h0QGxcCpT2lg62FiyJ9k7ItkvfJLvWbfI5z1THzdBwOr7NxOQBAy0hg3FN2yCMOGCNcRDbbo6-u","expires_in":3599,"refresh_token":"1\/\/067xy-YqEaMM9CgYIARAAGAYSNwF-L9Ir2sK5xVSCMPwaMxrptjozHZTrNRwoZxm0FOxRaBTdWWoynwbnJ7C4ZKbKRJOZ3RqUAus","scope":"https:\/\/www.googleapis.com\/auth\/spreadsheets","token_type":"Bearer","created":1620711115}';
    return $token;
  }

  public function wooGSRest()
  {
    if ($this->get_token()) return;
    register_rest_route('woogs/v1', '/auth/', array(
      'methods' => 'GET',
      'callback' => $this->wooGoogleAuthCode(),
      'permission_callback' => '__return_true',
    ));
  }

  private function wooGoogleAuthCode()
  {

    if (!isset($_GET['code'])) wp_die('Code Not Found', 'Google Code invalid');

    $authCode = sanitize_text_field($_GET['code']);

    $this->getClient($authCode);

    $url = add_query_arg('wooGoogleCode', 'added', WOOGS_SETTING_URL);
    wp_redirect($url);
    exit;
  }

}
