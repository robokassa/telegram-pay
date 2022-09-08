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

Настройка bot.php
---------
 
 ```php
 <?php
include 'Telegram.php';

$bot = new Telegram('TELEGRAM TOKEN'); //токен вашего бота

$data = $bot->getData();
$chat_id = $data['message'] ['chat']['id'];
$labeled = array('label' => 'Руб', 'amount' => 10000); //label и сумма заказа
$checkout = $data['pre_checkout_query']['id'];
$success = $data['message']['successful_payment'];

$invoice = array(
    'chat_id' => $chat_id,
    'title' => 'Оплата через Робокассу',
    'description' => 'Тестовый товар №1',
    'payload' => 'test',
    'provider_token' => '11111111111:LIVE:637955761195928888', //токен выданный через бот Robokassa
    'currency' => 'RUB',
    'prices' => json_encode((array($labeled))),
    'provider_data' => '{"InvoiceId":100,"Receipt":{"sno":"osn","items":[{"name":"Товар","quantity":1,"sum":100,"tax":"vat110","payment_method":"full_payment","payment_object":"commodity","nomenclature_code":"123456"}]}}');

if (trim($data['message']['text']) == '/pay') {
    $bot->sendInvoice($invoice);
}

if ($checkout) {
    $content = array('pre_checkout_query_id' => $checkout, 'ok' => true);
    $bot->answerPrecheckoutQuery($content);
} else if ($success) {
    $content = array('chat_id' => $chat_id, 'text' => 'Successful payment');
    $bot->sendMessage($content);
}
```

Передача номенклатуры и номера заказа
---------

Данные передаются с помощью переменной, в которую помещается JSON со значениями InvoiceId(номер заказа) и Receipt[(номенклатура согласно документации Robokassa)](https://docs.robokassa.ru/fiscalization/)
