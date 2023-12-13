<?php

/**
 * Generic message command
 *
 * Gets executed when any type of message is sent.
 *
 * In this conversation-related context, we must ensure that active conversations get executed correctly.
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Request;

class GenericmessageCommand extends SystemCommand
{
  /**
   * @var string
   */
  protected $name = 'genericmessage';

  /**
   * @var string
   */
  protected $description = 'Handle generic message';

  /**
   * @var string
   */
  protected $version = '1.0.0';

  /**
   * @var bool
   */
  protected $need_mysql = true;

  /**
   * Command execute method if MySQL is required but not available
   *
   * @return ServerResponse
   */
  public function executeNoDb(): ServerResponse
  {
    // Do nothing
    return Request::emptyResponse();
  }

  /**
   * Main command execution
   *
   * @return ServerResponse
   * @throws TelegramException
   */
  public function execute(): ServerResponse
  {
    $message = $this->getMessage();
    $chat = $message->getChat();
    // If a conversation is busy, execute the conversation command after handling the message.
    $conversation = new Conversation(
      $message->getFrom()->getId(),
      $chat->getId()
    );
    
    // Fetch conversation command if it exists and execute it.
    if ($conversation->exists() && $command = $conversation->getCommand()) {
      return $this->telegram->executeCommand($command);
    } else if ($webapp = $message->getWebAppData())
    {
      //var_dump($this);
      $msgarr = json_decode($this->update,true);
      $msgarr['message']['text'] = '/'.$webapp->getData();
      //$msgarr['message']['entities'] = [
      //  [
       // 'type' => 'bot_command', 
      //  'offset' => 0, 
     //   'length' => 6
     //   ],
   //   ];
    //  $msgarr['bot_username'] = $this->update->bot_username;
      
      $this->telegram->processUpdate(new Update($msgarr, $this->update->bot_username)); 
      return $this->telegram->executeCommand($webapp->getData());
    } 
    
    if ($chat->isPrivateChat()) {
      return $this->replyToChat("Sorry for failure to understand / unknown command!");
    }
    return Request::emptyResponse();
  }
}
