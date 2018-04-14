<?php

namespace Fridde;

class ShortMessageService
{
    public $settings;
    public $options;
    public $curl;
    public $response;
    public $error;
    public $status;

    public function __construct(array $options = [])
    {
        $def_api = SETTINGS['sms_settings']['default_gateway'];
        $def_options = ['message' => null, 'to' => null, 'api' => $def_api];
        $this->options = $options + $def_options;
        $this->settings = SETTINGS['sms_settings'][$this->options['api']];
    }

    public function send($debug_number = null)
    {
        $api = $this->options['api'];

        if ($api === 'smsgateway') {
            $email = $this->settings['email'];
            $pw = $this->settings['password'];
            $options['device'] = $this->settings['device'];
            $options['number'] = $this->options['to'];
            $options['message'] = $this->options['message'];
            if (!empty($debug_number)) {
                $options['message'] = '['.$options['number'].'] '.$options['message'];
                $options['number'] = $debug_number;
            }

            $SMS = new SmsGateway($email, $pw);
            $this->response = $SMS->sendMessage($options)['response'];
            $success = empty($this->response['success']) ? false : bool($this->response['success']);
            $errors_exist = ! empty($this->response['result']['fails']);
            $this->status = $success && !$errors_exist ? 'success' : 'failure';

            return $this;
        }
    }


    public function prepareQueryFields()
    {
        $mandatory_fields = [
            '46elks' => ['send' => ['from', 'to']],
            'smsgateway' => [
                'send' => [
                    'email',
                    'password',
                    'device',
                    'to' => 'number',
                    'message',
                    'send_at',
                    'expires_at',
                ],
            ],
        ];

        $ops = $this->options;
        $method = $ops['method'];
        $api = $ops['api'];
        $q = [];

        $fields = $mandatory_fields[$api][$method];

        foreach ($fields as $key => $value) {
            $op_key = is_int($key) ? $value : $key;
            $q[$value] = $ops[$op_key] ?? ($this->settings[$value] ?? false);
            if ($q[$value] === false) {
                throw new \Exception('The mandatory field '.$value.' was omitted from the query!');
            }
        }

        return $q;
    }

    public function prepareHeaders()
    {
        $api = $this->options['api'] ?? false;
        if (!$api) {
            throw new \Exception('Headers can\'t be prepared if no API is defined.');
        }
        $h = []; // headers_array

        switch ($api) {

            case '46elks':
                $h[] = 'Authorization: Basic '.base64_encode(
                        $this->settings['username'].':'.$this->settings['password']
                    );
                break;
        }
        $h[] = 'Content-type: application/x-www-form-urlencoded';

        return $h;
    }

    public function getUrl()
    {
        $endpoints = ['46elks' => 'SMS', 'smsgateway' => 'messages/send'];

        return $this->settings['url'].$endpoints[$this->options['api']];
    }

    public function setCurlOptions($options = [])
    {
        $standard_curl_options = [
            'post' => 1,
            'returntransfer' => 1,
            'header' => false,
            'ssl_verifypeer' => false,
            'timeout' => 10,
        ];
        $curl_options = array_merge($standard_curl_options, $options);

        foreach ($curl_options as $option_name => $option_value) {
            curl_setopt($this->curl, constant(mb_strtoupper('curlopt_'.$option_name)), $option_value);
        }

        return $this;
    }
}
