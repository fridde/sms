<?php

namespace Fridde;

class SMS
{
	public $settings;
	public $options;
	public $curl;
	public $response;
	public $error;

	function __construct($options = ["method" => "send", "message" => null, "to" => "", "from" => null,	"api" => "smsgateway", "settings" => null])
	{
		$this->setConfiguration($options);
	}

	public function setConfiguration($options)
	{
		$api_name = $options["api"] ?? false;
		$api_settings = $options["settings"]["sms_settings"][$api_name] ??
		($GLOBALS["SETTINGS"]["sms_settings"][$api_name] ?? false);

		if($api_settings === false){
			throw new \Exception("No settings given or found in the global scope");
		}
		$this->settings = $api_settings;
		$this->options = $options;
	}


	public function send()
	{
		$url = $this->getUrl();
		$query_fields = $this->prepareQueryFields();
		$query = http_build_query($query_fields);
		$headers = $this->prepareHeaders();

		$curl_options = ["url" => $url, "post" => 1, "postfields" => $query, "httpheader" => $headers];

		$this->curl = curl_init();
		$this->setCurlOptions("send", $curl_options);

		$this->response = curl_exec($this->curl);
		curl_close($this->curl);

		return $this->response;
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
			curl_setopt($this->curl, constant(strtoupper("curlopt_" . $option_name)), $option_value);
		}
		return $this;
	}

	public function standardizeMobNr($number){

		$nr = $number;
		$nr = preg_replace("/[^0-9]/", "", $nr);
		$trim_characters = ["0", "4", "6"]; // we need to trim from left to right order
		foreach($trim_characters as $char){
			$nr = ltrim($nr, $char);
		}
		if(in_array(substr($nr, 0, 2), ["70", "72", "73", "76"])){
			$nr = "+46" . $nr;
		}
		else if($nr != ""){
			$this->error = 'The number "' . $number . '" is probably not a swedish mobile number.';
			$nr = false;
		}
		return $nr;
	}
}
/*

DEPRECATED!

public function logg($data, $infoText = "", $filename = "toolbox.log")
{
$debug_info = array_reverse(debug_backtrace());
$chainFunctions = function($p,$n){
$class = (isset($n["class"]) ? "(". $n["class"] . ")" : "");
$p.='->' . $class . $n['function'] . ":" . $n["line"];
return $p;
};
$calling_functions = ltrim(array_reduce($debug_info, $chainFunctions), "->");
$file = pathinfo(reset($debug_info)["file"], PATHINFO_BASENAME);

$string = "\n\n####\n--------------------------------\n";
$string .= date("Y-m-d H:i:s");
$string .= ($infoText != "") ? "\n" . $infoText : "" ;
$string .= "\n--------------------------------\n";

if (is_string($data)) {
$string .= $data;
}
else {
$string .= print_r($data, true);
}
$string .= "\n----------------------------\n";
$string .= "Calling stack: " . $calling_functions . "\n";
$string .= $file . " produced this log entry";

file_put_contents($filename, $string, FILE_APPEND);
}
*/
