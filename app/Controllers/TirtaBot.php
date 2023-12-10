<?php

/**
 * This file is part of the PHP Telegram Bot example-bot package.
 * https://github.com/php-telegram-bot/example-bot/
 *
 * (c) PHP Telegram Bot Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * This configuration file is used to run the bot with the webhook method.
 *
 * Please note that if you open this file with your browser you'll get the "Input is empty!" Exception.
 * This is perfectly normal and expected, because the hook URL has to be reached only by the Telegram servers.
 */

namespace App\Controllers;

use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Exception\TelegramLogException;
use Longman\TelegramBot\TelegramLog;
use CodeIgniter\CLI\CLI;
use Telebot;

class TirtaBot extends BaseController
{

  protected $botconfig;

  protected $db;

  public function __construct()
  {
    $this->db = \Config\Database::connect();
    // Load all configuration options
    $this->botconfig = new \Config\Telebot();
  }

  protected function initialize(): Telebot
  {
    // Create Telegram API object
    $telegram = new Telebot($this->botconfig->api_key, $this->botconfig->bot_username);

    // Enable admin users
    $telegram->enableAdmins($this->botconfig->admins);

    // Add commands paths containing your custom commands
    $telegram->addCommandsPaths($this->botconfig->commands['paths']);

    // Enable MySQL if required
    $telegram->enableMySql([
      'host'     => $this->db->hostname,
      'user'     => $this->db->username,
      'password' => $this->db->password,
      'database' => $this->db->database,
    ], 'bot_');

    $telegram->setDownloadPath($this->botconfig->paths['download']);
    $telegram->setUploadPath($this->botconfig->paths['upload']);

    //$telegram->setUpdateFilter(array($filter, "filterUser"));

    // Load all command-specific configurations
    // foreach ($config['commands']['configs'] as $command_name => $command_config) {
    //     $telegram->setCommandConfig($command_name, $command_config);
    // }

    // Requests Limiter (tries to prevent reaching Telegram API limits)
    $telegram->enableLimiter($this->botconfig->limiter);

    return $telegram;
  }

  /**
   * Class constructor.
   */
  public function bothook()
  {

    try {

      // Handle telegram webhook request
      $this->initialize()->handle();
    } catch (TelegramException $e) {
      // Log telegram errors
      TelegramLog::error($e);

      // Uncomment this to output any errors (ONLY FOR DEVELOPMENT!)
      // echo $e;
    } catch (TelegramLogException $e) {
      // Uncomment this to output log initialisation errors (ONLY FOR DEVELOPMENT!)
      // echo $e;
    }
  }

  public function getUpdates()
  {
    if (!$this->request->isCLI()) {
      return redirect('/');
    }

    while (true) {
      try {
        $telegram = $this->initialize();
        $server_response = $telegram->handleGetUpdates();
        //var_dump($server_response);
        if ($server_response->isOk()) {
          $update_count = count($server_response->getResult());
          CLI::write(date('Y-m-d H:i:s') . " - Processed " . $update_count . " updates\n");
        } else {
          CLI::write(date('Y-m-d H:i:s') . " - Failed to fetch updates\n" . PHP_EOL);
          CLI::write($server_response->printError());
        }
        if ($telegram->isDbEnabled()) {
          $telegram->closeDB();
        }
      } catch (TelegramException $e) {
        // Log telegram errors
        TelegramLog::error($e);

        // Uncomment this to output any errors (ONLY FOR DEVELOPMENT!)
        CLI::write($e);
      } catch (TelegramLogException $e) {
        // Uncomment this to output log initialisation errors (ONLY FOR DEVELOPMENT!)
        CLI::write($e);
      }
    }
  }
}
