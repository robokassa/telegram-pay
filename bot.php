<?php
include 'Telegram.php';

$bot = new Telegram('TELEGRAM TOKEN');

$data = $bot->getData();
$chat_id = $data['message'] ['chat']['id'];
$labeled = array('label' => 'Руб', 'amount' => 10000);
$checkout = $data['pre_checkout_query']['id'];
$success = $data['message']['successful_payment'];

$invoice = array(
    'chat_id' => $chat_id,
    'title' => 'Оплата через Робокассу',
    'description' => 'Тестовый товар №1',
    'payload' => 'test',
    'provider_token' => '11111111111:LIVE:637955761195928888',
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