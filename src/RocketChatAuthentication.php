<?php

namespace BotMan\Drivers\RocketChat;

use GuzzleHttp\Client;

class RocketChatAuthentication {

  /**
   * Checks if the bot is logged in to Rocket.Chat via the API.
   *
   * @param string $userId
   *   The userId for Rocket.Chat
   * @param string $authToken
   *   The auth token for Rocket.Chat
   * @param string $endpoint
   *   The Rocket.Chat endpoint
   *
   * @return bool
   *   TRUE if logged in, FALSE if not.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public static function checkAuth($userId, $authToken, $endpoint) {
    $url = str_finish($endpoint, '/') . 'api/v1/me';
    $client = new Client();
    $response = $client->request('GET', $url, [
      'headers' => [
        'Content-Type' => 'application/json',
        'X-User-Id' => $userId,
        'X-Auth-Token' => $authToken,
      ],
    ]);
    $data = json_decode((string) $response->getBody(), TRUE);
    return isset($data['success']) && $data['success'];
  }

  /**
   * Performs a login for the bot via the Rocket.Chat API.
   *
   * @param string $username
   *   The bot's username
   * @param string $password
   *   The bot's password
   * @param string $endpoint
   *   The Rocket.Chat endpoint
   *
   * @return array|bool
   *   An
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public static function getAuth($username, $password, $endpoint) {
    $url = str_finish($endpoint, '/') . 'api/v1/login';
    $client = new Client();
    $response = $client->request('POST', $url, [
      'headers' => [
        'Content-Type' => 'application/json',
      ],
      'json' => [
        'username' => $username,
        'password' => $password,
      ],
    ]);
    $data = json_decode((string) $response->getBody(), TRUE);
    if (isset($data['status']) && ($data['status'] == 'success')) {
      return [
        'user_id' => $data['data']['userId'],
        'token' => $data['data']['authToken'],
      ];
    }
    return FALSE;
  }

}
