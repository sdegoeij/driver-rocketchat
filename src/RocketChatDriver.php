<?php

namespace BotMan\Drivers\RocketChat;

use BotMan\BotMan\Drivers\HttpDriver;
use BotMan\BotMan\Interfaces\WebAccess;
use BotMan\BotMan\Users\User;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Request;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\BotMan\Messages\Incoming\Answer;

/**
 * Class RocketChatDriver.
 *
 * @property string $errorMessage
 * @property int $replyStatusCode
 *
 * @package BotMan\Drivers\RocketChat
 */
class RocketChatDriver extends HttpDriver {

  const DRIVER_NAME = 'RocketChat';

  /** @var array */
  protected $messages = [];

  /** @var array */
  protected $files = [];

  /** @var array */
  protected $auth = [];

  /**
   * {@inheritdoc}
   */
  public function buildPayload(Request $request) {
    $this->payload = $request->request->all();
    $this->event = Collection::make($this->payload);
    $this->files = Collection::make($request->files->all());
    $this->config = Collection::make($this->config->get('rocketchat'));
    // Don't authenticate for CLI requests. (eg, artisan).
    if (php_sapi_name() !== 'cli') {
      $this->authenticate();
    }
  }

  /**
   * Verify that the request is valid against the configured token.
   *
   * @return boolean
   */
  protected function isValidRequest() {
    return !empty($this->config->get('token')) && ($this->event->get('token') === $this->config->get('token'));
  }

  /**
   * {@inheritdoc}
   */
  public function matchesRequest() {
    return $this->config->get('matchingKeys') !== NULL && Collection::make($this->config->get('matchingKeys'))
        ->diff($this->event->keys())
        ->isEmpty() && $this->isValidRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function isConfigured() {
    if (empty($this->config->get('token')) || empty($this->config->get('endpoint')) || empty($this->config->get('matchingKeys'))) {
      return FALSE;
    }
    return TRUE;
  }

  public function authenticate() {
    $this->auth = RocketChatAuthentication::getAuth(
      $this->config->get('bot')['username'],
      $this->config->get('bot')['password'],
      $this->config->get('endpoint')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function sendRequest($endpoint, array $parameters, IncomingMessage $matchingMessage) {
    // Not available with the rocketchat driver.
  }

  /**
   * {@inheritdoc}
   */
  public function getMessages() {
    if (empty($this->messages)) {
      $text = $this->event->get('text');
      $userId = $this->event->get('user_id');
      $message = new IncomingMessage($text, $userId, $userId, $this->payload);
      $message->setIsFromBot((bool) $this->event->get('bot'));
      $this->messages = [$message];
    }
    return $this->messages;
  }

  /**
   * {@inheritdoc}
   */
  public function getUser(IncomingMessage $matchingMessage) {
    $payload = Collection::make($matchingMessage->getPayload());
    $userId = $payload->get('user_id');
    $username = $payload->get('user_name');
    return new User($userId, NULL, NULL, $username);
  }

  /**
   * {@inheritdoc}
   */
  public function getConversationAnswer(IncomingMessage $message) {
    return Answer::create($message->getText())->setMessage($message);
  }

  /**
   * {@inheritdoc}
   */
  public function buildServicePayload($message, $matchingMessage, $additionalParameters = []) {
    if (!$message instanceof WebAccess && !$message instanceof OutgoingMessage) {
      $this->errorMessage = 'Unsupported message type.';
      $this->replyStatusCode = 500;
    }
    $payload = Collection::make($matchingMessage->getPayload());
    if ($payload->get('channel_name') === NULL) {
      // Direct Message answer
      return [
        'text' => $message->getText(),
        'roomId' => $payload->get('user_id'),
      ];
    }
    // Channel answer
    return [
      'text' => $message->getText(),
      'roomId' => $payload->get('channel_id'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function sendPayload($payload) {
    $url = str_finish($this->config->get('endpoint'), '/') . 'api/v1/chat.postMessage';
    $response = $this->http->post($url, [], $payload, [
      'Content-Type: application/json',
      'X-Auth-Token: ' . $this->auth['token'],
      'X-User-Id: ' . $this->auth['user_id'],
    ], TRUE);
    return $response;
  }

}
