<?php
/**
* Telegram Bot ScanzanoBot
* @author Vincenzo Cerbino @ViCe95
*/
//include("settings_t.php");
include("Telegram.php");
include("QueryLocation.php");
//include("utils.php");
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
	$user =$update["message"]["from"]["username"];

	$this->shell($telegram,$text,$chat_id,$user_id,$location,$reply_to_msg);
	$db = NULL;

}

//gestisce l'interfaccia utente
function shell($telegram,$text,$chat_id,$user_id,$location,$reply_to_msg){
	date_default_timezone_set('Europe/Rome');
	$today = date("Y-m-d H:i:s");
	$log="";
	if ($text == "/start" || $text == "Informazioni") {
		$this->send_img($telegram,$chat_id,'logo.png');
		$reply = "Benvenuto su ScanzanoBot il bot del comune di Scanzano Jonico, creato da @ViCe95 e disponibile su http://github.com/vice95/ScanzanoBot.git";
		$content = array('chat_id' => $chat_id, 'text' => $reply,'disable_web_page_preview'=>true);
		$telegram->sendMessage($content);
		$img = curl_file_create('mt2019.png','image/png');
		$contentp = array('chat_id' => $chat_id, 'photo' => $img);
		$telegram->sendPhoto($contentp);
		$log=$today. ";new chat started;" .$chat_id. "\n";
		if ($text=="/start"){
			$handle = fopen("./users.txt", 'a');
			fwrite($handle,$user_id."\n");
			fclose($handle);
			$log=$today. ";new user started;" .$user_id. ";  ".$nome."  ".$cognome."   ".$user."\n";
			$log.=$today. ";new chat started;" .$chat_id. "\n";
			}
		$this->create_keyboard_temp($telegram,$chat_id,"base");
		
		}
	else if($location!=null){//gestione segnalazioni georiferite
		$this->create_keyboard_temp($telegram,$chat_id,"gps",$location["latitude"],$location["longitude"]);
		}
	else if ($text == "Foto") {
		$arrayfile=array();
		$arrayfile=$this->elencafiles("img/");
		$num=rand(0,count($arrayfile)-1);
		$this->send_img($telegram,$chat_id,'img/'.$arrayfile[$num]);
		$log=$today. ";new foto sent to ;" .$user_id. "   ".$user. "  at ".$chat_id."\n";
		
		//$log=$today. ";new chat started;" .$chat_id. "\n";
	}
	else if ($text == "Meteo") {
		$text="";
		//$this->send_img($telegram,$chat_id,'materaevents.png');
		//The script shows the latest temperature value from various selected personal Wunderground stations. Also, if desired, it shows today's forecast for the selected city. If you want to use Fahrenheit degrees, please change every occurrence of 'temp_c' to 'temp_f' in the code. Be careful to avoid excessive API calls: you have about 120 calls per day before breaking Wunderground Terms!
		//Script by flapane.com - Latest rev. 30-Dec-14
		$expiretime=30;     //cache expire time in minutes
		$apikey="3d577a6a7a7de53b"; //your wunderground api key - 500 calls per day maximum!
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
						}
					else
						{
						$temp_c[$i] = "(station not reporting in the last $pws_freshness_human_time) ".$parsed_json[$i]->{'current_observation'}->{'temp_c'}."\n";    //add a new line \n (a capo) to every value
						}
					$location_wunder[$i] = $parsed_json[$i]->{'current_observation'}->{'observation_location'}->{'city'}."\n";
					fwrite($handle,$temp_c[$i]);    //write temps
					fwrite($handle,$location_wunder[$i]);    //write locations
				}
				
				if(!empty ($forecastcity)){    //do you want to show the forecast for today?
					$parsed_json_forecast = json_decode(file_get_contents("http://api.wunderground.com/api/{$apikey}/forecast/lang:{$forecastlang}/q/{$forecastcity}.json"));
					for($j=0; $j<2; $j++) {
						$icon_url[$j] = $parsed_json_forecast->forecast->txt_forecast->forecastday[$j]->icon_url."\n";
						$part_of_day[$j] = $parsed_json_forecast->forecast->txt_forecast->forecastday[$j]->title.": ".$parsed_json_forecast->forecast->txt_forecast->forecastday[$j]->fcttext_metric."\n";
						fwrite($handle,$icon_url[$j]);    //write url of the daily forecast
						fwrite($handle,$part_of_day[$j]);    //write the forecasts for both parts of the day
					}
				}
		}

			// seconds to minutes - from http://forums.digitalpoint.com
		$minutes = floor($FileAge/60)."m";
		$secondsleft = $FileAge%60;
		$ago = "ago";
		if($secondsleft<10)
			$secondsleft = "0" . $secondsleft ."s";
		if($secondsleft>10)
			$secondsleft = $secondsleft ."s";
		if($minutes>$expiretime){    //avoid weird $FileAge values if cachefile has just been refreshed
			$minutes = "now";
			$secondsleft = "";
			$ago = "";
		}
    
		// Display most recent temperatures and their average value
		$display = file($cachename, FILE_IGNORE_NEW_LINES); //ignore \n for non-reporting stations
		foreach ($display as $key=>$value){
			if($key % 2 == 0){  
				$temperature[] = $value; // EVEN (righe del file cache pari)
			}
			else{
				$location_wunder[] = $value;  // ODD
			}
		}

		for($i=0; $i<$number_stations; $i++){
			$temperature_stripped[$i] = preg_replace("/[^0-9,.-]/", "", $temperature[$i]);
		}    

		$temp_avg = (array_sum($temperature_stripped)/$number_stations); //average temperature
		for($i=0; $i<$number_stations; $i++){
			if($temperature[$i] == null){ //if weather station is not reporting data
				$temperature[$i] = "N/A";
				$temp_avg = "N/A";
			}    
		$text.="\n".$temperature[$i]."°C (".$location_wunder[$i].") \n ";    
		}
		$text.= $temp_avg."°C (media) |\n updated $minutes$secondsleft $ago \n";

		// Display the forecasts ONLY if $forecastcity is not empty
		if(!empty ($forecastcity)){    //do you want to show the forecast for today?
		$lines = array_slice($display, -4);
		$text.="\nPrevisioni per oggi:\n$lines[1]";
		$text.="$lines[3]";
}
		
		$content = array('chat_id' => $chat_id, 'text' => $text,'disable_web_page_preview'=>true);
		$telegram->sendMessage($content);
		$log=$today. ";meteo sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
		//exit;
		

	}
	else if ($text == "Eventi") {
		$this->create_keyboard_temp($telegram,$chat_id,"eventi");
		$log=$today. ";eventi sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
		//exit;
		

	}
	else if ($text == "Luoghi") {
		$this->create_keyboard_temp($telegram,$chat_id,"luoghi");		
		$log=$today. ";luoghi sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
		//exit;
		

	}
	else if ($text == "Numeri Utili") {
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
	$log=$today. ";numeri sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
//exit;
}
	else if ($text == "oggi" || $text == "Oggi"){
		
		$location="Sto cercando gli eventi di Scanzano Jonico validi nella giornata di oggi";
		$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
		$telegram->sendMessage($content);
		$text=str_replace(" ","%20",$text);
		$urlgd  ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20%2A%20WHERE%20B%20IS%20NOT%20NULL&key=1sCNDsEAxgaBG-QtsPzlkdHfiLk16yaToaImYrEHyhLw&gid=1608377674";
		sleep (2);
		$inizio=1;
		$homepage ="";
		$csv = array_map('str_getcsv',file($urlgd));
//var_dump($csv[2]);
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
		$today=strtotime("today 00:00:00");//time();
//echo $count;
//  $count=3;
		

	for ($i=$inizio;$i<$count;$i++){

		$html =str_replace("/","-",$csv[$i][11]);
		//echo $html."\n";
		$from = strtotime($html);
		$html1 =str_replace("/","-",$csv[$i][12]);
		//echo $html1."\n";
		$to = strtotime($html1);
//echo " f ".$from."  ".$today." t ".$to;

		if ($today >= $from && $today <= $to) {
				//echo "da: ".$from." a: ".$to." con oggi: ".$today."\n";
				//$homepage .="da: ".$from." a: ".$to." con oggi: ".$today."\n";
				$homepage .="\n";
				$homepage .="<b>Nome: </b>".$csv[$i][1]."\n";
				$homepage .="<b>Organizzato da: </b>".$csv[$i][3]."\n";
				if($csv[$i][5] !=NULL)
					$homepage .="<b>Pagamento: </b>".$csv[$i][9]."\n";
				$homepage .="<b>Tipologia: </b>".$csv[$i][4]."\n";
				if($csv[$i][4] !=NULL)  
					$homepage .="<b>Descrizione: </b>".$this->decode_entities($csv[$i][2])."\n";
				$homepage .="<b>Inizio: </b>".$csv[$i][11]."\n";
				$homepage .="<b>Fine: </b>".$csv[$i][12]."\n";
				$homepage .="<b>Luogo: </b>".$csv[$i][13]."\n";
				if($csv[$i][12] !=NULL) 
					$homepage .="<b>Web: </b>".$csv[$i][7]."\n";
				if($csv[$i][13] !=NULL) 
					$homepage .="<b>Email: </b>".$csv[$i][6]."\n";
				if($csv[$i][16] !=NULL)  
				$homepage .="<b>Foto: </b>".$csv[$i][19]."\n";
				$homepage .="____________\n";
		}
}

//}

//	echo $alert;

$chunks = str_split($homepage, self::MAX_LENGTH);
foreach($chunks as $chunk) {
	$content = array('chat_id' => $chat_id, 'text' => $chunk,'parse_mode'=>'HTML','disable_web_page_preview'=>true);
	$telegram->sendMessage($content);
}
	$this->create_keyboard_temp($telegram,$chat_id,"eventi");
	$log=$today. ";eventi oggi sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";

//exit;
}
	else if ($text == "Farmacie") {
		//location_manager($telegram,$user_id,$chat_id,$location);
		/*$location="Sto cercando le Farmacie di Scanzano Jonico ";
		$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
		$telegram->sendMessage($content);
		sleep (1);
		$luogo='pharmacy';
		$comune="Scanzano";
		$this->cerca($telegram,$user_id,$chat_id,$comune,$luogo);*/
		
		exit;
	}
	
	else if ($text == "Indietro") {
		$this->create_keyboard_temp($telegram,$chat_id,"base");
		$log=$today. ";Indietro sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
		//exit;
	}
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
	else if ($text== "Culto"){
		$reply = utf8_encode("Ciao! Questo comando ti indica i luoghi di culto attorno alla tua posizione.
							Invia la tua posizione tramite apposita molletta che trovi in basso a sinistra nella chat.
							Tutti i dati sono prelevati da Openstreetmap.Data in licenza ODbL.
							© OpenStreetMap contributors
							http://www.openstreetmap.org/copyright");
							$this->cerca($telegram,$user_id,$chat_id,$comune,'museum');
		$content = array('chat_id' => $chat_id, 'text' => $reply);
		$telegram->sendMessage($content);
		$this->create_keyboard_temp($telegram,$chat_id,"luoghi");
		$log=$today. ";Culto sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
		//exit;
	}
	else if ($text == "GeoRadar") {
		$this->create_keyboard($telegram, $chat_id);
		$log=$today. ";Georadar sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
		}
	else if(substr_count($text, "@")>0){
		$text=explode("@", $text);
		$luogo=$text[0];
		$loc=explode(",", $text[1]);
		$lat=$loc[0];
		$lon=$loc[1];
		$comune=$this->location_manager($telegram,$user_id,$chat_id,$location,$lat,$lon);
		$msg="Sto cercando ".$luogo." nel Comune di: ".$comune." tramite le coordinate che hai inviato: ".$lat.",".$lon;
		$content = array('chat_id' => $chat_id, 'text' => $msg,'disable_web_page_preview'=>true);
		$telegram->sendMessage($content);
		$log=$today. ";Georadar keyboard sent to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";
		}
	if ($log==""){$log=$today. ";".$text." from to ;" .$user_id."   ".$user. "  at ".$chat_id."\n";}
	file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
}
//cerca P.O.I.
function cerca($telegram,$user_id,$chat_id,$location,$luogo){
		

		date_default_timezone_set('Europe/Rome');
				$today = date("Y-m-d H:i:s");
		
				/*$lon=$location["longitude"];
				$lat=$location["latitude"];
				$lat=$location["latitude"];*/
				
				//for debug Prato coordinates
				//$lon=11.0952;
				//$lat=43.8807;
				
				$lon=16.6887;
				$lat=40.2523;
				
			
				//prelevo dati da OSM sulla base della mia posizione
				$osm_data=give_osm_data($lat,$lon,$luogo);
				
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
							$nome=utf8_encode("Museo non identificato su Openstreetmap");
							$content = array('chat_id' => $chat_id, 'text' =>$nome);
							$telegram->sendMessage($content);
					}					
					$content_geo = array('chat_id' => $chat_id, 'latitude' =>$osm_element['lat'], 'longitude' =>$osm_element['lon']);
					$telegram->sendLocation($content_geo);
				 } 
				
				//crediti dei dati
				if((bool)$osm_data_dec->node)
				{
					$content = array('chat_id' => $chat_id, 'text' => utf8_encode("Questi sono i musei vicini a te (dati forniti tramite OpenStreetMap. Licenza ODbL © OpenStreetMap contributors)"));
					$bot_request_message=$telegram->sendMessage($content);				
				}else
				{
					$content = array('chat_id' => $chat_id, 'text' => utf8_encode("Non ci sono sono musei vicini, mi spiace! Se ne conosci uno nelle vicinanze mappalo su www.openstreetmap.org"));
					$bot_request_message=$telegram->sendMessage($content);	
				}
				
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
				$option=array(array("Meteo","Numeri Utili","Eventi"),array("Foto","Luoghi","Trasporti"),array("Segnala Disservizio","GeoRadar"),array("Informazioni"));
				$msg="Scegli una Funzione:";
			}
	else if ($opt== 'luoghi'){
				$option=array(array("Culto","Sanita'"),array("Turismo","Scuola"),array("Ricettivita'","Attivita' Commerciali"),array("Indietro"));
				$msg="Scegli una categoria:";
			}
	else if ($opt== 'trasporti'){
				$option=array(array("Sita","Ibus"),array("Liscio","Altro"),array("Indietro"));
				$msg="Scegli un'azienda:";
			}
	else if ($opt== 'eventi'){
				$option=array(array("Oggi","Settimana"),array("Indietro"));
				$msg="Scegli intervallo:";
			}
	else if ($opt== 'gps'){
			//	$option=array(array("Culto @ ".round($lat,10).",".round($lon,10),"Sanita' @ ".round($lat,10).",".round($lon,10)),array("Turismo @ ".round($lat,10).",".round($lon,10),"Scuola @ ".round($lat,10).",".round($lon,10)),array("Ricettivita' @ ".round($lat,10).",".round($lon,10),"Attivita' Commerciali @ ".round($lat,10).",".round($lon,10)),array("Indietro"));
				$option=array(array("Culto @ ".$lat.",".$lon,"Sanita' @ ".$lat.",".$lon),array("Turismo @ ".$lat.",".$lon,"Scuola @ ".$lat.",".$lon),array("Ricettivita' @ ".$lat.",".$lon,"Attivita' Commerciali @ ".$lat.",".$lon),array("Indietro"));
				$msg="Scegli Punto di interesse: ";
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
}

?>
