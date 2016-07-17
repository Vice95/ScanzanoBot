<?php
/**
 * Telegram Bot Class.
 * based on first version by @author Gabriele Grillo <gabry.grillo@alice.it>
 *
 */
//error_reporting(E_ERROR | E_WARNING | E_PARSE);
include('settings_t.php');


class Telegram {
	private $bot_id = TELEGRAM_BOT;
	private $data = array();
	private $updates = array();
	public $inited = false;
public $link = "";
 public function __construct($bot_id) {
        $this->bot_id = $bot_id;
        $this->data = $this->getData();
    }
    public function init() {
    	if ($this->inited) {
      	return true;
    	}
    }
    public function getMe() {
        return $this->endpoint("getMe", array(), false);
    }
    public function sendMessage(array $content) {
        return $this->endpoint("sendMessage", $content);
    }
    public function endpoint($api, array $content, $post = true) {
        $url = 'https://api.telegram.org/bot' . $this->bot_id . '/' . $api;
        if ($post)
        {
            return $this->sendAPIRequest($url, $content);
        }
        else
        {
            return $this->sendAPIRequest($url, array(), false);
        }
    }
    public function sendPhoto(array $content) {
        return $this->endpoint("sendPhoto", $content);
    }
    public function sendAudio(array $content) {
        return $this->endpoint("sendAudio", $content);
    }
    public function sendDocument(array $content) {
        return $this->endpoint("sendDocument", $content);
    }
    public function sendSticker(array $content) {
        return $this->endpoint("sendSticker", $content);
    }
    public function sendVideo(array $content) {
        return $this->endpoint("sendVideo", $content);
    }
    public function sendVoice(array $content) {
        return $this->endpoint("sendVoice", $content);
    }
    public function sendLocation(array $content) {
        return $this->endpoint("sendLocation", $content);
    }
    public function sendChatAction(array $content) {
        return $this->endpoint("sendChatAction", $content);
    }
    public function setWebhook($url) {

        $content = array('url' => $url);
        return $this->endpoint("setWebhook", $content);
    }
	public function removeWebhook() {
    	//$this->init();
    	$content = array('url' => '');
    	return $this->endpoint('setWebhook', $content);
  	}
    public function getData() {
        if (empty($this->data)) {
            $rawData = file_get_contents("php://input");
            return json_decode($rawData, true);
        } else {
            return $this->data;
        }
    }
    public function setData(array $data) {
        $this->data = data;
    }
    public function Text() {
        return $this->data["message"]["text"];
    }
    public function ChatID() {
        return $this->data["message"]["chat"]["id"];
    }
    public function Date() {
        return $this->data["message"]["date"];
    }
    public function FirstName() {
        return $this->data["message"]["from"]["first_name"];
    }
    public function LastName() {
        return $this->data["message"]["from"]["last_name"];
    }
    public function Username() {
        return $this->data["message"]["from"]["username"];
    }
    public function User_id(){
    	return $this->data["message"]["from"]["id"];
    }
    
    public function Location() {
        return $this->data["message"]["location"];
    }
    public function UpdateID() {
        return $this->data["update_id"];
    }
    public function UpdateCount() {
        return count($this->updates["result"]);
    }
	public function ReplyToMessage() {

        return $this->data["message"]["reply_to_message"];
    }
    public function MessageId() {

        return $this->data["message"]["message_id"];
    }
    public function messageFromGroup() {
        if ($this->data["message"]["chat"]["title"] == "") {
            return false;
        }
        return true;
    }

    //gestisce un invio in broadcast a tutti gli utenti registrati in un database
    public function sendMessageAll($type, $user, $content)
    {
		$apiendpoint = ucfirst($type);
		if ($type == 'photo' || $type == "audio" || $type == "video" || $type == "document") {
			$mimetype = mime_content_type($content);
			$content = new CurlFile($content, $mimetype);
				$link = $this->data["message"]["photo"]["file_path"];

		} elseif ($type == "message") {
			$type = 'text';
		}
		print_r($user);
		$ch = curl_init("https://api.telegram.org/bot".$this->bot_id."/send".$apiendpoint);
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_HEADER => false,
			CURLOPT_HTTPHEADER => array(
				'Host: api.telegram.org',
				'Content-Type: multipart/form-data'
			),
			CURLOPT_POSTFIELDS => array(
				'chat_id' => $user,
				$type => $content
			),
			CURLOPT_TIMEOUT => 0,
			CURLOPT_CONNECTTIMEOUT => 6000,
			CURLOPT_SSL_VERIFYPEER => false
		));
		curl_exec($ch);
		curl_close($ch);
    }

    //costruisce la tastiera del servizio
    public function buildKeyBoard(array $options, $onetime = true, $resize = true, $selective = true) {
        $replyMarkup = array(
            'keyboard' => $options,
            'one_time_keyboard' => $onetime,
            'resize_keyboard' => $resize,
            'selective' => $selective
        );
        $encodedMarkup = json_encode($replyMarkup, true);
        return $encodedMarkup;
    }

	 public function buildKeyBoardHide($selective = true) {
        $replyMarkup = array(
            'hide_keyboard' => true,
            'selective' => $selective
        );
        $encodedMarkup = json_encode($replyMarkup, true);
        return $encodedMarkup;
    }
    public function buildForceReply($selective = true) {
        $replyMarkup = array(
            'force_reply' => true,
            'selective' => $selective
        );
        $encodedMarkup = json_encode($replyMarkup, true);
        return $encodedMarkup;
    }

/// Use this method to get basic info about a file and prepare it for downloading
    /**
     *  Use this method to get basic info about a file and prepare it for downloading. For the moment, bots can download files of up to 20MB in size. On success, a File object is returned. The file can then be downloaded via the link https://api.telegram.org/file/bot<token>/<file_path>, where <file_path> is taken from the response. It is guaranteed that the link will be valid for at least 1 hour. When the link expires, a new one can be requested by calling getFile again.
     * \param $file_id String File identifier to get info about
     * \return the JSON Telegram's reply
     */
    public function getFile($file_id) {
        $content = array('file_id' => $file_id);
        return $this->endpoint("getFile", $content);
    }

    /// Use this method to download a file
    /**
     *  Use this method to to download a file from the Telegram servers.
     * \param $telegram_file_path String File path on Telegram servers
     * \param $local_file_path String File path where save the file
     */
    public function downloadFile($telegram_file_path, $local_file_path) {
        $file_url = "https://api.telegram.org/file/bot" . $this->bot_id . "/" . $telegram_file_path;
        $in = fopen($file_url, "rb");
        $out = fopen($local_file_path, "wb");

        while ($chunk = fread($in, 8192)) {
            fwrite($out, $chunk, 8192);
        }
        fclose($in);
        fclose($out);
    }


public function getPhoto() {
    /*
$AllPhotoSize = array(
        'Photo_XS' => $this->data["message"]["photo"][0]["file_id"],
        'Photo_S' =>   $this->data["message"]["photo"][1]["file_id"],
        'Photo_M' =>  $this->data["message"]["photo"][2]["file_id"],
        'Photo_L' =>  $this->data["message"]["photo"][3]["file_id"]
    );          
    return ...$AllPhotoSize
*/
    return $this->data["message"]["photo"][2]["file_id"];
}



    public function getUpdates($offset = 0, $limit = 100, $timeout = 0, $update = true) {
        $content = array('offset' => $offset, 'limit' => $limit, 'timeout' => $timeout);
        $reply = $this->endpoint("getUpdates", $content);
        $this->updates = json_decode($reply, true);
        if ($update) {
            $last_element_id = $this->updates["result"][count($this->updates["result"]) - 1]["update_id"] + 1;
            $content = array('offset' => $last_element_id, 'limit' => "1", 'timeout' => $timeout);
            $this->endpoint("getUpdates", $content);
        }
        return $this->updates;
    }
    public function serveUpdate($update) {
        $this->data = $this->updates["result"][$update];
    }

    private function sendAPIRequest($url, array $content, $post = true) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
// Helper for Uploading file using CURL
if (!function_exists('curl_file_create')) {
    function curl_file_create($filename, $mimetype = '', $postname = '') {
        return "@$filename;filename="
                . ($postname ? : basename($filename))
                . ($mimetype ? ";type=$mimetype" : '');
    }
}
?>
