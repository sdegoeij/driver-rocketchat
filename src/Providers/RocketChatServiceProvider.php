<?php

namespace BotMan\Drivers\RocketChat\Providers;

use BotMan\Drivers\RocketChat\RocketChatDriver;
use Illuminate\Support\ServiceProvider;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Studio\Providers\StudioServiceProvider;

/**
 * Class RocketChatServiceProvider.
 *
 * @package BotMan\Drivers\RocketChat\Providers
 */
class RocketChatServiceProvider extends ServiceProvider {

  /**
   * Perform post-registration booting of services.
   *
   * @return void
   */
  public function boot() {
    if (!$this->isRunningInBotManStudio()) {
      $this->loadDrivers();
      $this->publishes([
        __DIR__ . '/../../stubs/rocketchat.php' => config_path('botman/rocketchat.php'),
      ]);
      $this->mergeConfigFrom(__DIR__ . '/../../stubs/rocketchat.php', 'botman.rocketchat');
    }
  }

  /**
   * Load BotMan drivers.
   */
  protected function loadDrivers() {
    DriverManager::loadDriver(RocketChatDriver::class);
  }

  /**
   * @return bool
   */
  protected function isRunningInBotManStudio() {
    return class_exists(StudioServiceProvider::class);
  }
}