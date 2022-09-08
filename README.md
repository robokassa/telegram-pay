# telegram-pay

Требования
---------

* PHP >= 5.3
* CURL должен установлен на сервере.
* Ключ Telegram API, вы можете получить его в[ @BotFather ](https://core.telegram.org/bots#botfather) после создания бота.

Для вебхука:
* ДЕЙСТВУЮЩИЙ SSL-сертификат.

Установка
---------

Скопируйте Telegram.php и bot.php на ваш сервер

Активация WebHook Telegram
---------

Укажите токен вашего бота и адрес bot.php на сервере и перейдите по адресу https://api.telegram.org/bot(BOT_TOKEN)/setWebhook?url=https://yoursite.com/bot.php
 
