#!/usr/bin/php
<?php
# @Author: Vincenzo Cerbino
# @Date:   2016-10-10T16:39:14+02:00
# @Email:  vicemail95@gmail.com
# @Last modified by:   Vincenzo Cerbino
# @Last modified time: 2016-11-19T21:30:47+01:00
# @License: MIT License



include('getUpdates.php');


//istanzia oggetto Telegram v
	$bot_id = TELEGRAM_BOT;
	$bot = new Telegram($bot_id);


	$inizio=1;
	$csv = array_map('str_getcsv',file(URL_USER_CSV));
	//var_dump($csv);
	$count = 0;
	foreach($csv as $data=>$csv1){
			$count = $count+1;
			}
	if ($count ==0 || $count ==1){
			echo "Nessun risultato trovato";
			}
	$trovato=0;
	for ($i=$inizio;$i<$count;$i++){
				eventi ($bot,$csv[$i][3],"oggi",$csv[$i][4]);
				echo $csv[$i][3]." oggi ".$csv[$i][4]."\n";
				
			}
function eventi ($telegram,$chat_id,$giorno,$name){

/*		$location="Ciao $name Ti ricordo gli eventi in programma nella giornata di oggi".json_decode("\ud83d\udc4e");
		$content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
		$telegram->sendMessage($content);*/

		//$text=str_replace(" ","%20",$text);
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
$contatore=0;
	for ($i=$inizio;$i<$count;$i++){

		$html =str_replace("/","-",$csv[$i][11]);
		//echo $html."\n";
		$from = strtotime($html);
		$html1 =str_replace("/","-",$csv[$i][12]);
		//echo $html1."\n";
		$to = strtotime($html1);

//echo "da: ".$from." a: ".$to." con oggi: ".$today."\n";
		if ($today >= $from && $today <= $to) {
				if($contatore==0){
					$location="Ciao $name Ti ricordo gli eventi in programma nella giornata di oggi".json_decode("\ud83d\udc4e");
			                $content = array('chat_id' => $chat_id, 'text' => $location,'disable_web_page_preview'=>true);
			                $telegram->sendMessage($content);
					$contatore=1;
				}
				//$homepage .="da: ".$from." a: ".$to." con oggi: ".$today."\n";
				$homepage ="------------------------------------------- \n";
				$homepage .="<b>Nome: </b>".$csv[$i][1]."\n";
				$homepage .="<b>Organizzato da: </b>".$csv[$i][3]."\n";
				if($csv[$i][9] !=NULL)
					$homepage .="<b>Pagamento: </b>".$csv[$i][9]."\n";
				$homepage .="<b>Tipologia: </b>".$csv[$i][4]."\n";
				if($csv[$i][2] !=NULL)
					$homepage .="<b>Descrizione: </b>".decode_entities($csv[$i][2])."\n";
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
					grab_image($csv[$i][18],$chat_id.".jpg");
					$img = curl_file_create("./".$chat_id.".jpg",'image/jpg');
					$content = array('chat_id' => $chat_id, 'photo' => $img);
					$telegram->sendPhoto($content);
					unlink("./".$chat_id.".jpg");
				}

				$content = array('chat_id' => $chat_id, 'text' => $homepage,'parse_mode'=>'HTML','disable_web_page_preview'=>true);
				$telegram->sendMessage($content);

				$content = array('chat_id' => $chat_id, 'latitude' =>$csv[$i][16],'longitude'=>$csv[$i][17]);//,'parse_mode'=>'HTML'
				$telegram->sendLocation($content);
				$line="**************************************";
                                $content = array('chat_id' => $chat_id, 'text' => $line,'disable_web_page_preview'=>true);
                                $telegram->sendMessage($content);

				}


	}
	if($homepage==""){
		$content = array('chat_id' => $chat_id, 'text' => "Nessun evento in programma per la giornata di ".$giorno,'parse_mode'=>'HTML','disable_web_page_preview'=>true);
		$telegram->sendMessage($content);
	}

	$option=array(array("Meteo","News Locali","Eventi"),array("Numeri Utili","Luoghi","Trasporti"),array("Segnala Disservizio","Foto","GeoRadar"),array("Informazioni"));
	$keyb = $telegram->buildKeyBoard($option, $onetime=false);
	$msg="Scegli una funzione:";
	$content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' =>$msg);
	$telegram->sendMessage($content);
}
function decode_entities($text) {
			$text= html_entity_decode($text,ENT_QUOTES,"ISO-8859-1"); #NOTE: UTF-8 does not work!
			$text= preg_replace('/&#(\d+);/me',"chr(\\1)",$text); #decimal notation
			$text= preg_replace('/&#x([a-f0-9]+);/mei',"chr(0x\\1)",$text);  #hex notation
		return $text;
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
