<?php

namespace Fridde;

use Fridde\SmsGateway;

class SMS
{
	public $settings;
	public $options;
	public $curl;
	public $response;
	public $error;

	function __construct($options = [])
	{
		$def_api = SETTINGS["sms_settings"]["default_gateway"];
		$def_options = ["message" => null, "to" => null, "api" => $def_api];
		$GLOBALS["CONTAINER"]->get('Logger')->info(gettype($options));
		$this->options = $options + $def_options;
		$this->settings = SETTINGS["sms_settings"][$this->options["api"]];
	}

	public function send()
	{
		$api = $this->options["api"];

		if($api == "smsgateway"){
			$email = $this->settings["email"];
			$pw = $this->settings["password"];
			$options["device"] = $this->settings["device"];
			$options["number"] = $this->options["to"];
			$options["message"] = $this->options["message"];
			$SMS = new SmsGateway($email, $pw);
			return $SMS->sendMessage($options);
		}
	}


	public function prepareQueryFields()
	{
		$mandatory_fields = ["46elks" => ["send" => ["from", "to"]], "smsgateway" => ["send" => ["email", "password", "device", "to" => "number", "message", "send_at", "expires_at"]]];

		$ops = $this->options;
		$method = $ops["method"];
		$api = $ops["api"];
		$q = [];

		$fields = $mandatory_fields[$api][$method] ?? false;

		foreach($fields as $key => $value){
			$op_key = is_int($key) ? $value : $key;
			$q[$value] = $ops[$op_key] ?? ($this->settings[$value] ?? false);
			if($q[$value] === false){
				throw new \Exception("The mandatory field " . $value . " was omitted from the query!");
			}
		}

		return $q;
	}

	public function prepareHeaders()
	{
		$api = $this->options["api"] ?? false;
		if(!$api){
			throw new \Exception("Headers can't be prepared if no API is defined.");
		}
		$h = []; // headers_array

		switch($api){

			case "46elks":
			$h[] = "Authorization: Basic " . base64_encode($this->settings["username"] . ":" . $this->settings["password"]);
			break;
		}
		$h[] = "Content-type: application/x-www-form-urlencoded";

		return $h;
	}

	public function getUrl()
	{
		$endpoints = ["46elks" => "SMS", "smsgateway" => "messages/send"];

		$url = $this->settings["url"] . $endpoints[$this->api];

		return $url;
	}

	public function setCurlOptions($options = [])
	{
		$standard_curl_options = [ "post" => 1, "returntransfer" => 1, "header" => false, "ssl_verifypeer" => false, "timeout" => 10];
		$curl_options = array_merge($standard_curl_options, $options);

		foreach($curl_options as $option_name => $option_value){
			curl_setopt($this->curl, constant(mb_strtoupper("curlopt_" . $option_name)), $option_value);
		}
		return $this;
	}
}
