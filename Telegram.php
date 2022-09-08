<?php

class Telegram
{
    private $bot_token = '';
    private $data = [];
    private $log_errors;
    private $proxy;

    /// Class constructor
    /**
     * Create a Telegram instance from the bot token
     * \param $bot_token the bot token
     * \param $log_errors enable or disable the logging
     * \param $proxy array with the proxy configuration (url, port, type, auth)
     * \return an instance of the class.
     */
    public function __construct($bot_token, $log_errors = true, array $proxy = [])
    {
        $this->bot_token = $bot_token;
        $this->data = $this->getData();
        $this->log_errors = $log_errors;
        $this->proxy = $proxy;
    }

    /// Do requests to Telegram Bot API
    /**
     * Contacts the various API's endpoints
     * \param $api the API endpoint
     * \param $content the request parameters as array
     * \param $post boolean tells if $content needs to be sends
     * \return the JSON Telegram's reply.
     */
    public function endpoint($api, array $content, $post = true)
    {
        $url = 'https://api.telegram.org/bot'.$this->bot_token.'/'.$api;
        if ($post) {
            $reply = $this->sendAPIRequest($url, $content);
        } else {
            $reply = $this->sendAPIRequest($url, [], false);
        }

        return json_decode($reply, true);
    }

    /// Send a message
    /**
     * See <a href="https://core.telegram.org/bots/api#sendmessage">sendMessage</a> for the input values
     * \param $content the request parameters as array
     * \return the JSON Telegram's reply.
     */
    public function sendMessage(array $content)
    {
        return $this->endpoint('sendMessage', $content);
    }


    /// Get the data of the current message
    /** Get the POST request of a user in a Webhook or the message actually processed in a getUpdates() enviroment.
     * \return the JSON users's message.
     */
    public function getData()
    {
        if (empty($this->data)) {
            $rawData = file_get_contents('php://input');

            return json_decode($rawData, true);
        } else {
            return $this->data;
        }
    }

    /// Set the data currently used
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /// Send an payment invoice
    /**
     * See <a href="https://core.telegram.org/bots/api#sendinvoice">sendInvoice</a> for the input values
     * \param $content the request parameters as array
     * \return the JSON Telegram's reply.
     */
    public function sendInvoice(array $content)
    {
        return $this->endpoint('sendInvoice', $content);
    }


    /// Answer a PreCheckout query
    /**
     * See <a href="https://core.telegram.org/bots/api#answerprecheckoutquery">answerPreCheckoutQuery</a> for the input values
     * \param $content the request parameters as array
     * \return the JSON Telegram's reply.
     */
    public function answerPreCheckoutQuery(array $content)
    {
        return $this->endpoint('answerPreCheckoutQuery', $content);
    }

    private function sendAPIRequest($url, array $content, $post = true)
    {
        if (isset($content['chat_id'])) {
            $url = $url.'?chat_id='.$content['chat_id'];
            unset($content['chat_id']);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        }
        // 		echo "inside curl if";
        if (!empty($this->proxy)) {
            // 			echo "inside proxy if";
            if (array_key_exists('type', $this->proxy)) {
                curl_setopt($ch, CURLOPT_PROXYTYPE, $this->proxy['type']);
            }

            if (array_key_exists('auth', $this->proxy)) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxy['auth']);
            }

            if (array_key_exists('url', $this->proxy)) {
                // 				echo "Proxy Url";
                curl_setopt($ch, CURLOPT_PROXY, $this->proxy['url']);
            }

            if (array_key_exists('port', $this->proxy)) {
                // 				echo "Proxy port";
                curl_setopt($ch, CURLOPT_PROXYPORT, $this->proxy['port']);
            }
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        if ($result === false) {
            $result = json_encode(
                ['ok' => false, 'curl_error_code' => curl_errno($ch), 'curl_error' => curl_error($ch)]
            );
        }
        curl_close($ch);
        if ($this->log_errors) {
            if (class_exists('TelegramErrorLogger')) {
                $loggerArray = ($this->getData() == null) ? [$content] : [$this->getData(), $content];
                TelegramErrorLogger::log(json_decode($result, true), $loggerArray);
            }
        }

        return $result;
    }

}

/**
 * Telegram Error Logger Class.
 *
 */
class TelegramErrorLogger
{
    private static $self;

    /// Log request and response parameters from/to Telegram API
    /**
     * Prints the list of parameters from/to Telegram's API endpoint
     * \param $result the Telegram's response as array
     * \param $content the request parameters as array.
     */
    public static function log($result, $content, $use_rt = true)
    {
        try {
            if ($result['ok'] === false) {
                self::$self = new self();
                $e = new \Exception();
                $error = PHP_EOL;
                $error .= '==========[Response]==========';
                $error .= "\n";
                foreach ($result as $key => $value) {
                    if ($value == false) {
                        $error .= $key.":\t\t\tFalse\n";
                    } else {
                        $error .= $key.":\t\t".$value."\n";
                    }
                }
                $array = '=========[Sent Data]==========';
                $array .= "\n";
                if ($use_rt == true) {
                    foreach ($content as $item) {
                        $array .= self::$self->rt($item).PHP_EOL.PHP_EOL;
                    }
                } else {
                    foreach ($content as $key => $value) {
                        $array .= $key.":\t\t".$value."\n";
                    }
                }
                $backtrace = '============[Trace]===========';
                $backtrace .= "\n";
                $backtrace .= $e->getTraceAsString();
                self::$self->_log_to_file($error.$array.$backtrace);
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    /// Write a string in the log file adding the current server time
    /**
     * Write a string in the log file TelegramErrorLogger.txt adding the current server time
     * \param $error_text the text to append in the log.
     */
    private function _log_to_file($error_text)
    {
        try {
            $dir_name = 'logs';
            if (!is_dir($dir_name)) {
                mkdir($dir_name);
            }
            $fileName = $dir_name.'/'.__CLASS__.'-'.date('Y-m-d').'.txt';
            $myFile = fopen($fileName, 'a+');
            $date = '============[Date]============';
            $date .= "\n";
            $date .= '[ '.date('Y-m-d H:i:s  e').' ] ';
            fwrite($myFile, $date.$error_text."\n\n");
            fclose($myFile);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    private function rt($array, $title = null, $head = true)
    {
        $ref = 'ref';
        $text = '';
        if ($head) {
            $text = "[$ref]";
            $text .= "\n";
        }
        foreach ($array as $key => $value) {
            if ($value instanceof CURLFile) {
                $text .= $ref.'.'.$key.'= File'.PHP_EOL;
            } elseif (is_array($value)) {
                if ($title != null) {
                    $key = $title.'.'.$key;
                }
                $text .= self::rt($value, $key, false);
            } else {
                if (is_bool($value)) {
                    $value = ($value) ? 'true' : 'false';
                }
                if ($title != '') {
                    $text .= $ref.'.'.$title.'.'.$key.'= '.$value.PHP_EOL;
                } else {
                    $text .= $ref.'.'.$key.'= '.$value.PHP_EOL;
                }
            }
        }

        return $text;
    }
}
