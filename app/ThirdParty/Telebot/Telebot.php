<?php

//namespace MonitorBot\App;

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\DB;
class Telebot extends Telegram
{
  public function closeDB() {
    if (DB::isDbConnected()) {
      $this->pdo = null;
    }
    return $this->pdo === null;
  }
}
