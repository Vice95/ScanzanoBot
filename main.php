<?php
/**
* Telegram Bot ScanzanoBot
* @author Vincenzo Cerbino @ViCe95
*/
include("Telegram.php");
include("QueryLocation.php");

class mainloop{
const MAX_LENGTH = 4096;
public $log=LOG_FILE;
function start($telegram,$update){

	date_default_timezone_set('Europe/Rome');
	$today = date("Y-m-d H:i:s");
	//$data=new getdata();
	// Instances the class

	/* If you need to manually take some parameters
	*  $result = $telegram->getData();
	*  $text = $result["message"] ["text"];
	*  $chat_id = $result["message"] ["chat"]["id"];
	*/


	$text = $update["message"] ["text"];
	$chat_id = $update["message"] ["chat"]["id"];
	$user_id=$update["message"]["from"]["id"];
	$location=$update["message"]["location"];
	$reply_to_msg=$update["message"]["reply_to_message"];
	$nome =$update["message"]["from"]["first_name"];
	$cognome =$update["message"]["from"]["last_name"];
	$user=$update["message"]["from"]["username"];
	
	$result = $telegram->getData();
	$image = $result["message"] ["photo"];
	
	
	$this->shell($telegram,$text,$chat_id,$user_id,$location,$reply_to_msg,$nome,$cognome,$user,$image);//,$image);
	$db = NULL;
}

//gestisce l'interfaccia utente
function shell($telegram,$text,$chat_id,$user_id,$location,$reply_to_msg,$nome,$cognome,$user,$image){
	date_default_timezone_set('Europe/Rome');
	$today = date("Y-m-d H:i:s");
	$log="";
	$tag=array(	"Culto" => "place_of_worship",
			"Sanita'"=>"hospital",
			"Turismo"=>"tourism",
			"Scuola"=>"school",
			"Ricettivita'"=>"restaurant",
			"Attivita' Commerciali"=>"shop",
			"Parcheggi"=>"parking",
			"Distributori Carburanti"=>"fuel"
			);
	if ($text == "/start" || $text == "Informazioni") {
		$this->send_img($telegram,$chat_id,'logo.png');
		$reply = "Benvenuto ".$nome." ".$cognome."su ScanzanoBot il bot del comune di Scanzano Jonico, creato da @ViCe95 e disponibile su http://github.com/vice95/ScanzanoBot.git";
		$content = array('chat_id' => $chat_id, 'text' => $reply,'disable_web_page_preview'=>true);
		$telegram->sendMessage($content);
		$img = curl_file_create('mt2019.png','image/png');
		$contentp = array('chat_id' => $chat_id, 'photo' => $img);
		$telegram->sendPhoto($contentp);
		$log=$today. ";info to;" .$chat_id. "\n";
		if ($text=="/start"){
			$row=1;
			$trovato=0;
			if (($handler = fopen(USER_DB, "r")) !== FALSE) {
				while (($data = fgetcsv($handler, 1000, ";")) !== FALSE) {
				$num = count($data);
				$row++;
				if($data[0]==$user_id)
					$trovato=1;
				}
			fclose($handle);
			}
			if($trovato==0){
				$handle = fopen(USER_DB, 'a');
				fwrite($handle,$user_id.";".$nome.";@".$user.";".date("H:i:s d/m/Y"). "\n");
				fclose($handle);
			}
				$log=$today. ";new user started;@" .$user_id. ";  ".$nome."  ".$cognome."   ".$user."\n";
				$log.=$today. ";new chat started;" .$chat_id. "\n";
			}
		$this->create_keyboard_temp($telegram,$chat_id,"base");
		}
	else if ($text == "Foto") {
		$arrayfile=array();
		$arrayfile=$this->elencafiles("img/");
		$num=rand(0,count($arrayfile)-1);
		$this->send_img($telegram,$chat_id,'img/'.$arrayfile[$num]);
		$log=$today. ";new foto sent to ;" .$user_id. "   ".$user. "  at ".$chat_id."\n";
	}
	else if ($text == "Meteo") {
	$this->meteo($telegram,$user_id,$chat_id);
	$log=$today. ";meteo sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
	}
	else if ($text == "Numeri Utili") {
	$this->numeri($telegram,$user_id,$chat_id);
	$log=$today. ";numeri sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
}
	/*************************Events*******************************/
	else if ($text == "Eventi") {
		$this->create_keyboard_temp($telegram,$chat_id,"eventi");
		$log=$today. ";eventi sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
	}
	else if ($text == "oggi" || $text == "Oggi"){
		$this->eventi ($telegram,$user_id,$chat_id,"oggi");
		$log=$today. ";eventi oggi sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
}
	else if ($text == "domani" || $text == "Domani"){
		$this->eventi ($telegram,$user_id,$chat_id,"domani");
		$log=$today. ";eventi domani sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
}
	
	/*************************Trasporti*******************************/
	else if ($text == "Trasporti") {
		$this->create_keyboard_temp($telegram,$chat_id,"trasporti");		
		$log=$today. ";Trasporti sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
		//exit;
	}
	else if ($text == "Sita"){
		$content = array('chat_id' => $chat_id, 'text' => "http://www.sitasudtrasporti.it/orari",'disable_web_page_preview'=>false);
		$telegram->sendMessage($content);
		$this->create_keyboard_temp($telegram,$chat_id,"trasporti");
		$log=$today. ";Sita sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
		//exit;
		}
	else if ($text == "Liscio"){
		$content = array('chat_id' => $chat_id, 'text' => "http://www.autolineeliscio.it/",'disable_web_page_preview'=>false);
		$telegram->sendMessage($content);
		$this->create_keyboard_temp($telegram,$chat_id,"trasporti");
		$log=$today. ";Liscio sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
		//exit;
		}
	else if ($text == "Ibus"){
		$content = array('chat_id' => $chat_id, 'text' => "http://www.ibus.it/",'disable_web_page_preview'=>false);
		$telegram->sendMessage($content);
		$this->create_keyboard_temp($telegram,$chat_id,"trasporti");
		$log=$today. ";Ibus sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
		//exit;
		}
	else if ($text == "Corse Mare"){
		$this->corsemare($telegram,$user_id,$chat_id);
		$this->create_keyboard_temp($telegram,$chat_id,"trasporti");
		$log=$today. ";corse mare sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
		//exit;
		}
	/*************************POI*******************************/
	else if ($text == "Luoghi") {
		$this->create_keyboard_temp($telegram,$chat_id,"luoghi");		
		$log=$today. ";luoghi sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
	}
	else if ($text== "Culto"){
		$reply = utf8_encode("Ciao! Questo comando ti indica i luoghi di culto attorno alla tua posizione.
							Invia la tua posizione tramite apposita molletta che trovi in basso a sinistra nella chat.
							Tutti i dati sono prelevati da Openstreetmap.Data in licenza ODbL.
							©  OpenStreetMap contributors
							http://www.openstreetmap.org/copyright");
		$content = array('chat_id' => $chat_id, 'text' => $reply);
		$telegram->sendMessage($content);
		$this->cerca($telegram,$user_id,$chat_id,$comune,'place_of_worship','luoghi di culto',5500,40.250509,16.700422);
		$this->create_keyboard_temp($telegram,$chat_id,"luoghi");
		$log=$today. ";Culto sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
		//exit;
	}
	else if ($text== "Parcheggi"){
		$reply = utf8_encode("Ciao! Questo comando ti indica i parcheggi attorno alla tua posizione.
							Invia la tua posizione tramite apposita molletta che trovi in basso a sinistra nella chat.
							Tutti i dati sono prelevati da Openstreetmap.Data in licenza ODbL.
							©  OpenStreetMap contributors
							http://www.openstreetmap.org/copyright");
		$content = array('chat_id' => $chat_id, 'text' => $reply);
		$telegram->sendMessage($content);
		$this->cerca($telegram,$user_id,$chat_id,$comune,'parking','parcheggi',3000,40.250509,16.700422);
		$this->create_keyboard_temp($telegram,$chat_id,"luoghi");
		$log=$today. ";parcheggi  sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
		//exit;
	}
	else if ($text== "Distributori Carburanti"){
		$reply = utf8_encode("Ciao! Questo comando ti indica i Distributori Carburanti attorno alla tua posizione.
							Invia la tua posizione tramite apposita molletta che trovi in basso a sinistra nella chat.
							Tutti i dati sono prelevati da Openstreetmap.Data in licenza ODbL.
							©  OpenStreetMap contributors
							http://www.openstreetmap.org/copyright");
		$content = array('chat_id' => $chat_id, 'text' => $reply);
		$telegram->sendMessage($content);
		$this->cerca($telegram,$user_id,$chat_id,$comune,'fuel','Distributori Carburanti',3000,40.250509,16.700422);
		$this->create_keyboard_temp($telegram,$chat_id,"luoghi");
		$log=$today. ";parcheggi  sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
		//exit;
	}
	else if ($text== "Scuola"){
		$reply = utf8_encode("Ciao! Questo comando ti indica le scuole attorno alla tua posizione.
							Invia la tua posizione tramite apposita molletta che trovi in basso a sinistra nella chat.
							Tutti i dati sono prelevati da Openstreetmap.Data in licenza ODbL.
							© OpenStreetMap contributors
							http://www.openstreetmap.org/copyright");
		$content = array('chat_id' => $chat_id, 'text' => $reply);
		$telegram->sendMessage($content);
		$this->cerca($telegram,$user_id,$chat_id,$comune,'school','scuole',2000,40.250509,16.700422);
		$this->create_keyboard_temp($telegram,$chat_id,"luoghi");
		$log=$today. ";Scuola sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
		//exit;
	}
	/*************************GeoRadar*******************************/
	else if ($text == "GeoRadar") {
		//$this->create_keyboard($telegram, $chat_id);
		$forcehide=$telegram->buildKeyBoardHide(true);
		$reply = "Ti suggeriro' i punti di interesse piu' vicino a te. Inviami la tua posizione cliccando sulla graffetta (📎) in basso e, se vuoi, puoi cliccare due volte sulla mappa e spostare il Pin Rosso in un luogo specifico oppure invia Indietro.";
		$content = array('chat_id' => $chat_id, 'text' => $reply,'reply_markup' => $telegram->buildForceReply(true));
		$telegram->sendMessage($content);
		//$content = array('chat_id' => $chat_id, 'text' => "", 'reply_markup' =>$forcehide);
		$log=$today. ";Georadar sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
		}
	else if($location!=NULL && $reply_to_msg["text"]=="Ti suggeriro' i punti di interesse piu' vicino a te. Inviami la tua posizione cliccando sulla graffetta (📎) in basso e, se vuoi, puoi cliccare due volte sulla mappa e spostare il Pin Rosso in un luogo specifico oppure invia Indietro."){
		$this->create_keyboard_temp($telegram,$chat_id,"gps", $location['latitude'],$location['longitude']);
		}
	else if(substr_count($text, "@")>0){
		$text=explode("@", $text);
		$luogo=rtrim($text[0]);
		$loc=explode(",", $text[1]);
		$lat=$loc[0];
		$lon=$loc[1];
		//$comune=$this->location_manager($telegram,$user_id,$chat_id,$location,$lat,$lon);
		$msg="Sto cercando ".$luogo." nelle tue vicinanze";
		$content = array('chat_id' => $chat_id, 'text' => $msg,'disable_web_page_preview'=>true);
		$telegram->sendMessage($content);
		$this->cerca($telegram,$user_id,$chat_id,$comune,$tag[$luogo],$luogo,2000,$lat,$lon);
		$log=$today. ";Georadar keyboard sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
		}
	/*************************Disservizi*******************************/
	else if ($text== "Segnala Disservizio"){
		$reply = utf8_encode("Ciao! Questo comando ti consente di inviare una foto o un messaggio per denunciare un disservizio nella citta'");
		$content = array('chat_id' => $chat_id, 'text' => $reply);
		$telegram->sendMessage($content);
		$reply = utf8_encode("Invia la tua posizione tramite apposita molletta che trovi in basso a sinistra nella chat.");
		$content = array('chat_id' => $chat_id, 'text' => $reply,'reply_markup' => $telegram->buildForceReply(true));
		$telegram->sendMessage($content);
	}
	else if($location!=NULL && $reply_to_msg["text"]=="Invia la tua posizione tramite apposita molletta che trovi in basso a sinistra nella chat."){
		$reply = utf8_encode("Invia una foto o un messaggio per evidenziare il problema");
		$content = array('chat_id' => $chat_id, 'text' => $reply,'reply_markup' => $telegram->buildForceReply(true));
		$telegram->sendMessage($content);
		file_put_contents($chat_id, $location['latitude'].",".$location['longitude'], FILE_APPEND | LOCK_EX);

	}
	else if($reply_to_msg["text"]=="Invia una foto o un messaggio per evidenziare il problema"){
		$gps=explode(',',file_get_contents($chat_id));
		if($text!=NULL){
			file_put_contents(SEGNALAZIONE, $text."\n", FILE_APPEND | LOCK_EX);
			$reply="Segnalazione inviata da ScanzanoBot:\n".$text;
			$content = array('chat_id' => ID_SEGNALAZIONE, 'text' => $reply);//,'parse_mode'=>'HTML'
			$telegram->sendMessage($content);
			$content = array('chat_id' => ID_SEGNALAZIONE, 'latitude' =>$gps[0],'longitude'=>$gps[1]);//,'parse_mode'=>'HTML'
			$telegram->sendLocation($content);
			
		}
		else if($image!=NULL){
			$file = $telegram->getFile($telegram-> getPhoto());
			$res=json_decode($file,true);
			$iname=date("Y-m-d H:i:s").".jpg";
			$telegram->downloadFile($res["result"]["file_path"], "./segnalazioni/".$iname);
			
			$reply="Segnalazione inviata da ScanzanoBot:\n";
			$content = array('chat_id' => ID_SEGNALAZIONE, 'text' => $reply);//,'parse_mode'=>'HTML'
			$telegram->sendMessage($content);
			
			$img = curl_file_create("./segnalazioni/".$iname,'image/jpg'); 
			$content = array('chat_id' => ID_SEGNALAZIONE, 'photo' => $img );
			$telegram->sendPhoto($content);
			
			$content = array('chat_id' => ID_SEGNALAZIONE, 'latitude' =>$gps[0],'longitude'=>$gps[1]);//,'parse_mode'=>'HTML'
			$telegram->sendLocation($content);

		}
		$reply = utf8_encode("La segnalazione e' stata presa in carico, grazie per la tua collaborazione !!");
		$content = array('chat_id' => $chat_id, 'text' => $reply);
		$telegram->sendMessage($content);
		unlink($chat_id);
		//$log=$today. ";Disservizio sent from ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
		$this->create_keyboard_temp($telegram,$chat_id,"base",0,0);
		}
	/*************************News*******************************/
	else if ($text == "News Locali") {
		$this->create_keyboard_temp($telegram,$chat_id,"news");
		$log=$today. ";news sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
	}
	else if ($text == "Il Metapontino"){
		$url="ilmetapontino.it";
		$this->testata($telegram,$user_id,$chat_id,$url);
		$log=$today. ";$text sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
	}
	else if ($text == "JonicaTv"){
		$url="jonica.tv";
		$this->testata($telegram,$user_id,$chat_id,$url);
		$log=$today. ";$text sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
	}
	else if ($text == "Filippo Mele"){
		$url="filippomele.blogspot.com";
		$this->testata($telegram,$user_id,$chat_id,$url);
		$log=$today. ";$text sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
	}
	else if ($text == "Amministrative"){
		$url="http://www.comune.scanzanojonico.mt.it/index.php";
		$this->testata($telegram,$user_id,$chat_id,$url);
		$log=$today. ";$text sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
	}
	/*************************Utils*******************************/
	else if ($text == "Indietro") {
		$this->create_keyboard_temp($telegram,$chat_id,"base");
		$log=$today. ";Indietro sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
	}
	
	if ($log==""){$log=$today. ";".$text." from to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";}
	file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
}
//cerca P.O.I.
function cerca($telegram,$user_id,$chat_id,$location,$luogo,$msg,$around,$lat,$lon){
		

		date_default_timezone_set('Europe/Rome');
				$today = date("Y-m-d H:i:s");
		
				/*$lon=$location["longitude"];
				$lat=$location["latitude"];
				$lat=$location["latitude"];*/
				
				//for debug Prato coordinates
				//$lon=11.0952;
				//$lat=43.8807;
				
				/*$lon=16.700422;
				$lat=40.250509;*/
				
			
				//prelevo dati da OSM sulla base della mia posizione
				$osm_data=give_osm_data($lat,$lon,$luogo,$around);
				
				//rispondo inviando i dati di Openstreetmap
				$osm_data_dec = simplexml_load_string($osm_data);
				//per ogni nodo prelevo coordinate e nome
				foreach ($osm_data_dec->node as $osm_element) {
					$nome="";					
					foreach ($osm_element->tag as $key) {
						if ($key['k']=='name')
						{
							$nome=utf8_encode($key['v']);
							$content = array('chat_id' => $chat_id, 'text' =>$nome);
							$telegram->sendMessage($content);
						}
					}
					//gestione musei senza il tag nome
					if($nome=="")
					{
							/*$nome=utf8_encode($msg." non identificato su Openstreetmap");
							$content = array('chat_id' => $chat_id, 'text' =>$nome);
							$telegram->sendMessage($content);*/
					}					
					$content_geo = array('chat_id' => $chat_id, 'latitude' =>$osm_element['lat'], 'longitude' =>$osm_element['lon']);
					$telegram->sendLocation($content_geo);
				 } 
				
				//crediti dei dati
				if((bool)$osm_data_dec->node)
				{	$text=$msg." vicino a te (dati forniti tramite OpenStreetMap. Licenza ODbL© OpenStreetMap contributors)";
					/*$content = array('chat_id' => $chat_id, 'text' => utf8_encode($text));
					$bot_request_message=$telegram->sendMessage($content);				*/
				}else
				{	$text="Non ci sono ".$msg." vicino, mi spiace! Se ne conosci uno nelle vicinanze mappalo su www.openstreetmap.org";
					/*$content = array('chat_id' => $chat_id, 'text' => utf8_encode());
					$bot_request_message=$telegram->sendMessage($content);	*/
				}
				$content = array('chat_id' => $chat_id, 'text' => utf8_encode($text));
				$bot_request_message=$telegram->sendMessage($content);
		/*$chunks = str_split($data, self::MAX_LENGTH);
		foreach($chunks as $chunk) {
			$forcehide=$telegram->buildForceReply(true);
			$content = array('chat_id' => $chat_id, 'text' => $chunk,'disable_web_page_preview'=>true);

			$telegram->sendMessage($content);

			}
		$content = array('chat_id' => $chat_id, 'text' => "Digita un Comune oppure invia la tua posizione tramite la graffetta (📎)");
		$telegram->sendMessage($content);*/

		//	}
/*
			 $reply = "Hai selezionato un comando non previsto. Ricordati che devi prima inviare la tua posizione cliccando sulla graffetta (📎) ";
			 $content = array('chat_id' => $chat_id, 'text' => $reply);
			 $telegram->sendMessage($content);

			 $log=$today. ";wrong command sent;" .$chat_id. "\n";
			 //$this->create_keyboard($telegram,$chat_id);
*/
}

// Crea la tastiera
function create_keyboard($telegram, $chat_id){
	 $forcehide=$telegram->buildKeyBoardHide(true);
	 $content = array('chat_id' => $chat_id, 'text' => "Invia la tua posizione cliccando sulla graffetta (📎) in basso e, se vuoi, puoi cliccare due volte sulla mappa e spostare il Pin Rosso in un luogo specifico oppure invia Indietro", 'reply_markup' =>$forcehide);
	 $telegram->sendMessage($content);

 }

function create_keyboard_temp($telegram, $chat_id,$opt,$lat,$lon){
	if ($opt=='base'){
				$option=array(array("Meteo","News Locali","Eventi"),array("Numeri Utili","Luoghi","Trasporti"),array("Segnala Disservizio","Foto","GeoRadar"),array("Informazioni"));
				$msg="Scegli una Funzione:";
			}
	else if ($opt== 'luoghi'){
				$option=array(array("Culto","Sanita'"),array("Turismo","Scuola"),array("Ricettivita'","Attivita' Commerciali"),array("Parcheggi","Distributori Carburanti"),array("Indietro"));
				$msg="Scegli una categoria:";
			}
	else if ($opt== 'trasporti'){
				$option=array(array("Sita","Ibus"),array("Liscio","Corse Mare"),array("Indietro"));
				$msg="Scegli un'azienda:";
			}
	else if ($opt== 'eventi'){
				$option=array(array("Oggi","Domani"),array("Indietro"));
				$msg="Scegli intervallo:";
			}
	else if ($opt== 'gps'){
			//	$option=array(array("Culto @ ".round($lat,10).",".round($lon,10),"Sanita' @ ".round($lat,10).",".round($lon,10)),array("Turismo @ ".round($lat,10).",".round($lon,10),"Scuola @ ".round($lat,10).",".round($lon,10)),array("Ricettivita' @ ".round($lat,10).",".round($lon,10),"Attivita' Commerciali @ ".round($lat,10).",".round($lon,10)),array("Indietro"));
				$option=array(array("Culto @ ".$lat.",".$lon,"Sanita' @ ".$lat.",".$lon),array("Turismo @ ".$lat.",".$lon,"Scuola @ ".$lat.",".$lon),array("Ricettivita' @ ".$lat.",".$lon,"Attivita' Commerciali @ ".$lat.",".$lon),array("Parcheggi @ ".$lat.",".$lon,"Distributori Carburanti @ ".$lat.",".$lon),array("Indietro"));
				$msg="Scegli Punto di interesse: ";
			}
	else if ($opt=='news'){
				$option=array(array("Amministrative"),array("Il Metapontino"),array("JonicaTv"),array("Filippo Mele"),array("Indietro"));
				$msg="Scegli una Testata:";
			}
	$keyb = $telegram->buildKeyBoard($option, $onetime=false);
	$content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' =>$msg);
	$telegram->sendMessage($content);
}

function send_img($telegram,$chat_id,$img){
	$img = curl_file_create($img,'image/png');
	$contentp = array('chat_id' => $chat_id, 'photo' => $img);
	$telegram->sendPhoto($contentp);
}

//converte coordinate in indirizzo
function location_manager($telegram,$user_id,$chat_id,$location,$lat,$lon){

			/*$lon=$location["longitude"];
			$lat=$location["latitude"];*/
			$alert="";
			$lat=trim($lat, " ");   
			$lon=trim($lon, " "); 
			$alert="";
			$reply="http://nominatim.openstreetmap.org/reverse?format=json&lat=".$lat."&lon=".$lon."&zoom=18&addressdetails=1";
			$json_string = file_get_contents($reply);
			$parsed_json = json_decode($json_string);
		//	var_dump($parsed_json);
			$comune="";
			$temp_c1 =$parsed_json->{'display_name'};
			if ($parsed_json->{'address'}->{'town'}) {
				$temp_c1 .="\nCittà: ".$parsed_json->{'address'}->{'town'};
				$comune .=$parsed_json->{'address'}->{'town'};
			}else 	$comune .=$parsed_json->{'address'}->{'city'};
			if ($parsed_json->{'address'}->{'village'}) $comune .=$parsed_json->{'address'}->{'village'};
			return $comune;

	}

function decode_entities($text) {
			$text= html_entity_decode($text,ENT_QUOTES,"ISO-8859-1"); #NOTE: UTF-8 does not work!
			$text= preg_replace('/&#(\d+);/me',"chr(\\1)",$text); #decimal notation
			$text= preg_replace('/&#x([a-f0-9]+);/mei',"chr(0x\\1)",$text);  #hex notation
		return $text;
}

function elencafiles($dirname){
	$arrayfiles=Array();
	if(file_exists($dirname)){
		$handle = opendir($dirname);
		while (false !== ($file = readdir($handle))) { 
			if(is_file($dirname.$file)){
				array_push($arrayfiles,$file);
			}
		}
	$handle = closedir($handle);
	}
	rsort($arrayfiles);
	return $arrayfiles;
}
function meteo($telegram,$user_id,$chat_id){
		$text="";
		//The script shows the latest temperature value from various selected personal Wunderground stations. Also, if desired, it shows today's forecast for the selected city. If you want to use Fahrenheit degrees, please change every occurrence of 'temp_c' to 'temp_f' in the code. Be careful to avoid excessive API calls: you have about 120 calls per day before breaking Wunderground Terms!
		//Script by flapane.com - Latest rev. 30-Dec-14
		$expiretime=30;     //cache expire time in minutes
		$apikey=WAPI; //your wunderground api key - 500 calls per day maximum!
		$cachename="./wunder_temp.txt"; //name of the cachefile
		$forecastlang="IT"; //select forecasts language using uppercase letters
		$forecastcity="Italy/Scanzano"; //in form of State/City in english. Leave it EMPTY if you don't want any forecasts
		$number_stations="2"; //total number of PWS stations.
		$station[0]="IBASILIC56"; //wunderground station name (just click on one of the personal weather stations and it will show you their name, ie. KNYBROOK40 for Williamsburg, NYC)
		$station[1]="IBASILIC18";
		//Add another station using: $station[2]="wunderground station name";

		//do not mess with the next lines!!!
		if (!file_exists($cachename)) {    //create the cachefile if it doesn't exist
			 $create = fopen($cachename, 'w');
			  chmod ("$cachename", 0644); //set chmod 644
			 fclose($create);
		}

		// Is the file older than $expiretime, or is the file new/empty?
		$FileAge = time() - filemtime($cachename);    // Calculate file age in seconds
		if ($FileAge > ($expiretime * 60) || 0 == filesize($cachename)){
			$handle = fopen($cachename, 'wb');    // Now refresh the cachefile with newer content    
				for($i=0; $i<$number_stations; $i++){
					$parsed_json[$i] = json_decode(file_get_contents("http://api.wunderground.com/api/{$apikey}/conditions/q/pws:{$station[$i]}.json"));
					
					$pws_freshness = (($parsed_json[$i]->{'current_observation'}->{'local_epoch'})-($parsed_json[$i]->{'current_observation'}->{'observation_epoch'})); //elapsed time since PWS sent updated data
					$pws_freshness_human_time = round($pws_freshness/3600, 0)." hour(s)";
					if (preg_replace("/[^0-9,.]/", "", $pws_freshness_human_time) > 24) 
						{
						$pws_freshness_human_time = round($pws_freshness/86400, 1)." day(s)";
						}
						
					if ($pws_freshness < 3600) //the PWS has been sending fresh data in the last hour
						{
						$temp_c[$i] = $parsed_json[$i]->{'current_observation'}->{'temp_c'}."\n";    //add a new line \n (a capo) to every value
						$hum[$i] = $parsed_json[$i]->{'current_observation'}->{'relative_humidity'}."\n";
						$wind_dir[$i]=$parsed_json[$i]->{'current_observation'}->{'wind_dir'}."\n";
						$wind_kph[$i]=$parsed_json[$i]->{'current_observation'}->{'wind_kph'}."\n";
						$pressure_mb[$i]=$parsed_json[$i]->{'current_observation'}->{'pressure_mb'}."\n";
						$precip_today_in[$i]=$parsed_json[$i]->{'current_observation'}->{'precip_today_in'}."\n";
						}
					else
						{
						$temp_c[$i] = "(La stazione non sta rilevando i dati da $pws_freshness_human_time) ".$parsed_json[$i]->{'current_observation'}->{'temp_c'}."\n";    //add a new line \n (a capo) to every value
						}
					$location_wunder[$i] = $parsed_json[$i]->{'current_observation'}->{'observation_location'}->{'city'}."\n";
					fwrite($handle,$temp_c[$i]);    //write temps
					fwrite($handle,$hum[$i]);    //write hums
					fwrite($handle,$wind_dir[$i]);    //write wind_dir
					fwrite($handle,$wind_kph[$i]);		//write wind kph
					fwrite($handle,$pressure_mb[$i]);	//write pressure   
					fwrite($handle,$precip_today_in[$i]);	//write prec 
					fwrite($handle,$location_wunder[$i]);    //write locations
				}
				
				if(!empty ($forecastcity)){    //do you want to show the forecast for today?
					$parsed_json_forecast = json_decode(file_get_contents("http://api.wunderground.com/api/{$apikey}/forecast/lang:{$forecastlang}/q/{$forecastcity}.json"));
					for($j=0; $j<2; $j++) {
						//$icon_url[$j] = $parsed_json_forecast->forecast->txt_forecast->forecastday[$j]->icon_url."\n";
						$part_of_day[$j] = $parsed_json_forecast->forecast->txt_forecast->forecastday[$j]->title.": ".$parsed_json_forecast->forecast->txt_forecast->forecastday[$j]->fcttext_metric."\n";
						fwrite($handle,$icon_url[$j]);    //write url of the daily forecast
						fwrite($handle,$part_of_day[$j]);    //write the forecasts for both parts of the day
					}
				}
		}

			// seconds to minutes - from http://forums.digitalpoint.com
		$minutes = floor($FileAge/60)."m";
		$secondsleft = $FileAge%60;
		$ago = "fa";
		if($secondsleft<10)
			$secondsleft = "0" . $secondsleft ."s";
		if($secondsleft>10)
			$secondsleft = $secondsleft ."s";
		if($minutes>$expiretime){    //avoid weird $FileAge values if cachefile has just been refreshed
			$minutes = "ora";
			$secondsleft = "";
			$ago = "";
		}
    
		// Display most recent temperatures and their average value
		$display = file($cachename, FILE_IGNORE_NEW_LINES); //ignore \n for non-reporting stations
		foreach ($display as $key=>$value){
			if($key % 7 == 0){  
				$temperature[] = $value; // EVEN (righe del file cache pari)
			}
			else if($key % 7 == 1){  
				$hum[] = $value; // EVEN (righe del file cache pari)
			}
			else if($key % 7 == 2){  
				$wind_dir[] = $value; // EVEN (righe del file cache pari)
			}
			else if($key % 7 == 3){  
				$wind_kph[] = $value; // EVEN (righe del file cache pari)
			}
			else if($key % 7 == 4){  
				$pressure_mb[] = $value; // EVEN (righe del file cache pari)
			}
			else if($key % 7 == 5){  
				$precip_today_in[] = $value; // EVEN (righe del file cache pari)
			}
			else if($key % 7 == 6){
				$location_wunder[] = $value;  // ODD
			}
		}

		for($i=0; $i<$number_stations; $i++){
			$temperature_stripped[$i] = preg_replace("/[^0-9,.-]/", "", $temperature[$i]);
			$hum_stripped[$i] = preg_replace("/[^0-9,.-]/", "", $hum[$i]);
		}    

		$temp_avg = (array_sum($temperature_stripped)/$number_stations); //average temperature
		for($i=0; $i<$number_stations; $i++){
			if($temperature[$i] == null){ //if weather station is not reporting data
				$temperature[$i] = "N/A";
				$temp_avg = "N/A";
			}    
		$text.="<b>(".rtrim($location_wunder[$i]).") </b>\n
				<b>Temperatura: </b>".rtrim($temperature[$i])."°C 
				<b>Umidita': </b>".rtrim($hum[$i])."
				<b>Direzione vento: </b>".rtrim($wind_dir[$i])."
				<b>Velocita' vento: </b>".rtrim($wind_kph[$i])."km/h
				<b>Pressione:		</b>".rtrim($pressure_mb[$i])."hPa
				<b>Precipitazioni:	</b>".rtrim($precip_today_in[$i])."mm\n\n\n";    
		}
		$text.= "Media ".$temp_avg."°C | aggiornato $minutes $secondsleft $ago \n";

		// Display the forecasts ONLY if $forecastcity is not empty
		if(!empty ($forecastcity)){    //do you want to show the forecast for today?
		$lines = array_slice($display, -4);
		$dati=explode(":",$lines[2],2);
		$dati1=explode(":",$lines[3],2);
		$text.="\n<b>Previsioni per oggi:</b>\n<b>$dati[0]:</b> $dati[1]\n\n";
		$text.="<b>$dati1[0]:</b> $dati1[1]\n\n";
}
		
		$content = array('chat_id' => $chat_id, 'text' => $text,'parse_mode'=>'HTML','disable_web_page_preview'=>true);
		$telegram->sendMessage($content);
		
		//exit;
	}
function eventi ($telegram,$user_id,$chat_id,$giorno){
	
	$location="Sto cercando gli eventi di Scanzano Jonico validi nella giornata di ".$giorno;
		$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
		$telegram->sendMessage($content);
		$text=str_replace(" ","%20",$text);
		$urlgd  ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20%2A%20WHERE%20B%20IS%20NOT%20NULL&key=1sCNDsEAxgaBG-QtsPzlkdHfiLk16yaToaImYrEHyhLw&gid=1608377674";
		sleep (2);
		$inizio=1;
		$homepage ="";
		$csv = array_map('str_getcsv',file($urlgd));
//var_dump($csv[8]);
		$count = 0;
		foreach($csv as $data=>$csv1){
				$count = $count+1;
				}
		if ($count ==0 || $count ==1){
				$location="Nessun risultato trovato";
				$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
				$telegram->sendMessage($content);
		}
		date_default_timezone_set('Europe/Rome');
		date_default_timezone_set("UTC");
		if($giorno=="oggi")
				$today=strtotime("today 00:00:00");//time();
		elseif($giorno=="domani")
				$today=strtotime("tomorrow 00:00:00");

	for ($i=$inizio;$i<$count;$i++){

		$html =str_replace("/","-",$csv[$i][11]);
		//echo $html."\n";
		$from = strtotime($html);
		$html1 =str_replace("/","-",$csv[$i][12]);
		//echo $html1."\n";
		$to = strtotime($html1);

//echo "da: ".$from." a: ".$to." con oggi: ".$today."\n";
		if ($today >= $from && $today <= $to) {
				
				//$homepage .="da: ".$from." a: ".$to." con oggi: ".$today."\n";
				$homepage .="____________\n";
				$homepage .="<b>Nome: </b>".$csv[$i][1]."\n";
				$homepage .="<b>Organizzato da: </b>".$csv[$i][3]."\n";
				if($csv[$i][9] !=NULL)
					$homepage .="<b>Pagamento: </b>".$csv[$i][9]."\n";
				$homepage .="<b>Tipologia: </b>".$csv[$i][4]."\n";
				if($csv[$i][2] !=NULL)  
					$homepage .="<b>Descrizione: </b>".$this->decode_entities($csv[$i][2])."\n";
				$homepage .="<b>Data: </b>".$csv[$i][11]."\n";
				$homepage .="<b>Ora: </b>".$csv[$i][10]."\n";
				$homepage .="<b>Luogo: </b>".$csv[$i][13]."-".$csv[$i][14]."\n";
				if($csv[$i][9] !=NULL)
					$homepage .="<b>Accesso: </b>".$csv[$i][9]."\n";

				if($csv[$i][5] !=NULL)
					$homepage .="<b>Telefono: </b>".$csv[$i][5]."\n";
				if($csv[$i][8] !=NULL)
					 $homepage .="<b>Link evento: </b>".$csv[$i][8]."\n";

				if($csv[$i][7] !=NULL) 
					$homepage .="<b>Web: </b>".$csv[$i][7]."\n";
				if($csv[$i][6] !=NULL) 
					$homepage .="<b>Email: </b>".$csv[$i][6]."\n";
				if($csv[$i][16] !=NULL)  
				//$homepage .="<b>Foto: </b>".$csv[$i][19]."\n";
				$homepage .="____________\n";
				//echo $csv[$i][18];
				if($csv[$i][18]!=NULL){
					$this->grab_image($csv[$i][18],$chat_id.".jpg");
					$img = curl_file_create("./".$chat_id.".jpg",'image/jpg'); 
					$content = array('chat_id' => $chat_id, 'photo' => $img);
					$telegram->sendPhoto($content);
					unlink("./".$chat_id.".jpg");
				}
				
				$content = array('chat_id' => $chat_id, 'text' => $homepage,'parse_mode'=>'HTML','disable_web_page_preview'=>true);
				$telegram->sendMessage($content);

				$content = array('chat_id' => $chat_id, 'latitude' =>$csv[$i][16],'longitude'=>$csv[$i][17]);//,'parse_mode'=>'HTML'
				$telegram->sendLocation($content);
				}
				/*else{
					$content = array('chat_id' => $chat_id, 'text' => "Nessun evento in programma per la giornata di oggi",'parse_mode'=>'HTML','disable_web_page_preview'=>true);
					$telegram->sendMessage($content);
				}*/
	
	}
	if($homepage==""){
	$content = array('chat_id' => $chat_id, 'text' => "Nessun evento in programma per la giornata di ".$giorno,'parse_mode'=>'HTML','disable_web_page_preview'=>true);
	$telegram->sendMessage($content);
	}
//	echo $alert;
/*if($homepage!=NULL){
	$chunks = str_split($homepage, self::MAX_LENGTH);
	foreach($chunks as $chunk) {
		$content = array('chat_id' => $chat_id, 'photo' => $img );
		$telegram->sendPhoto($content);
		$content = array('chat_id' => $chat_id, 'text' => $chunk,'parse_mode'=>'HTML','disable_web_page_preview'=>true);
		$telegram->sendMessage($content);

		$content = array('chat_id' => $chat_id, 'latitude' =>$gps[0],'longitude'=>$gps[1]);//,'parse_mode'=>'HTML'
		$telegram->sendLocation($content);
	}
}
*/
	$this->create_keyboard_temp($telegram,$chat_id,"eventi");
	
//exit;

}
function numeri($telegram,$user_id,$chat_id){
		$location="Sto caricando la mia rubrica ";
		$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
		$telegram->sendMessage($content);
		$text=str_replace(" ","%20",$text);
		$urlgd  ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20%2A%20WHERE%20B%20IS%20NOT%20NULL&key=1w9x3UOvrLqBGluc0nJ0TrGq_kEph1gl1CMEY4AOF3po&gid=5831";
		sleep (2);
		$inizio=1;
		$homepage ="";
		$csv = array_map('str_getcsv',file($urlgd));
		var_dump($csv);
		$count = 0;
		foreach($csv as $data=>$csv1){
				$count = $count+1;
				}
		if ($count ==0 || $count ==1){
				$location="Nessun risultato trovato";
				$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
				$telegram->sendMessage($content);
		}
		
		

	for ($i=$inizio;$i<$count;$i++){
				$homepage .="\n";
				$homepage .="<b>Nome: </b>".$csv[$i][1]."\n";
				$homepage .="<b>Numero di telefono : </b>".$csv[$i][3]."\n";
				$homepage .="<b>Indirizzo: </b>".$csv[$i][6]."\n";
				if($csv[$i][2] !=NULL)
					$homepage .="<b>Tipologia: </b>".$csv[$i][2]."\n";
				if($csv[$i][4] !=NULL)  
					$homepage .="<b>Sito web: </b>".$csv[$i][4]."\n";
				if($csv[$i][5] !=NULL)  
					$homepage .="<b>E-mail: </b>".$csv[$i][5]."\n";
				$homepage .="____________\n";
		
}

//}

//	echo $alert;

$chunks = str_split($homepage, self::MAX_LENGTH);
foreach($chunks as $chunk) {
	$content = array('chat_id' => $chat_id, 'text' => $chunk,'parse_mode'=>'HTML','disable_web_page_preview'=>true);
	$telegram->sendMessage($content);
}
	$this->create_keyboard_temp($telegram,$chat_id,"base");
	}
function corsemare($telegram,$user_id,$chat_id){
	$location="Sto caricando le tratte ";
		$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
		$telegram->sendMessage($content);
		$text=str_replace(" ","%20",$text);
		$urlgd  ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20%2A%20WHERE%20B%20IS%20NOT%20NULL&key=1QaEvZp0vni5vkhTFb9hW0gBQpIm4Tf458vLukqVgXL0&gid=0";
		sleep (2);
		$inizio=1;
		$homepage ="";
		$csv = array_map('str_getcsv',file($urlgd));
		var_dump($csv);
		$count = 0;
		foreach($csv as $data=>$csv1){
				$count = $count+1;
				}
		if ($count ==0 || $count ==1){
				$location="Nessun corsa trovata";
				$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
				$telegram->sendMessage($content);
		}
		
		

	for ($i=$inizio;$i<$count;$i++){
				$homepage ="===========================\n";
				$homepage .="<b>".$csv[$i][1]."</b>\n";
				$content = array('chat_id' => $chat_id, 'text' => $homepage,'parse_mode'=>'HTML','disable_web_page_preview'=>true);
				$telegram->sendMessage($content);
				for($j=0;$j<$csv[$i][0];$j++){
					$homepage ="<b>Ora: </b>".$csv[$i][2+4*$j]."\n";
					$homepage .="<b>Fermata: </b>".$csv[$i][3+4*$j]."\n";
					$content = array('chat_id' => $chat_id, 'text' => $homepage,'parse_mode'=>'HTML','disable_web_page_preview'=>true);
					$telegram->sendMessage($content);
					//gps
					$content = array('chat_id' => $chat_id, 'latitude' =>$csv[$i][4+4*$j],'longitude'=>$csv[$i][5+4*$j]);//,'parse_mode'=>'HTML'
					$telegram->sendLocation($content);
					}
				
}

//}

//	echo $alert;
/*
$chunks = str_split($homepage, self::MAX_LENGTH);
foreach($chunks as $chunk) {
	$content = array('chat_id' => $chat_id, 'text' => $chunk,'parse_mode'=>'HTML','disable_web_page_preview'=>true);
	$telegram->sendMessage($content);
}*/
	//$this->create_keyboard_temp($telegram,$chat_id,"base");
	}
function testata($telegram,$user_id,$chat_id,$url){
	$content = array('chat_id' => $chat_id, 'text' => $url,'disable_web_page_preview'=>false);
	$telegram->sendMessage($content);
}
function grab_image($url,$saveto){
    $ch = curl_init ($url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
    $raw=curl_exec($ch);
    curl_close ($ch);
    if(file_exists($saveto)){
        unlink($saveto);
    }
    $fp = fopen($saveto,'x');
    fwrite($fp, $raw);
    fclose($fp);
}
}

?>
