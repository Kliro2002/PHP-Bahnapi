<html>
<head>
<title>Bahnauskunft</title>
<link rel="stylesheet" href="style.css" type="text/css">	
<link rel= "stylesheet" href="https://fonts.googleapis.com/css?family=Trirong">
<script src="https://openlayers.org/api/OpenLayers.js"></script>
</head>
	<body>

       <div class="header">
        <h1>Bahnauskunft</h1>
        <p>Falscher Bahnhof ? <a href="http://localhost/bahnprojekt/index.php">Neuen Bahnhof auswählen</a></p>
        

    </div>
    <div class="main">
         <form action="" method="post">
                <label for="bahnhof">Bitte wählen sie die Zeiten für Ihre Züge aus: </label><br>
			    <br>

               <label for="datum">Datum: </label><br>
			         <input type="date" name="datum"><br>
               <label for="zeit">Zeit: </label><br>
			         <input type="time" name="zeit">
               <label for="arrive">Ankunft</label>
               <input type="checkbox" name="arrive">
               <label for="depart">Abfahrt</label>
               <input type="checkbox" name="depart"> <br>                     
               <button type="submit" name="search" class="button" value="search">Suchen</button>
    		</form>
    </div>

   
</html>


<?php
#
# Backend-PHP
#
error_reporting(0);

#Funktionen 

#Umwandeln Umlaute 
   function sonderzeichen($bahnhofsname){
    $bahnhofsname = str_replace("ä", "%C3%A4", $bahnhofsname);
    $bahnhofsname = str_replace("ö", "%C3%B6", $bahnhofsname);
    $bahnhofsname = str_replace("ü", "%C3%BC", $bahnhofsname);
    $bahnhofsname = str_replace(" " ,"%20", $bahnhofsname);
    return $bahnhofsname;       
    }


#Umwandeln des ":" bei der Zeit bei der Eingabe
   function sonderzeichen2($zeit){
    $zeit = str_replace(":", "%3A", $zeit);
    return $zeit;
   }
  
# Bahnhofsnummer bekommen

 $bahnhofsnummer = $_GET['bid'];

#Eingabe erkennen
if(!empty($_POST['search'])){
    # Verarbeitung Datum/Uhrzeit
    $datumzw = $_POST['datum'];      # Eingabe des Datum
    $zeit = $_POST['zeit'];          # Eingabe des Zeitpunktes
    $zeit2  = sonderzeichen2($zeit);
    $datum =  "$datumzw" . "T" . "$zeit" ;


    #
    # Datum für Ausgabe
    #
    $datumjahr = substr($datum, -16, 4)   ;
    $datummonat = substr($datum, -11, 2)   ;
    $datumtag = substr($datum, -8, 2)   ;


    #
    # API- Verbindungen
    #

    #Bahnhofsnamen erfassen
    $bahnhofsname = curl_init("https://api.deutschebahn.com/stada/v2/stations/$bahnhofsnummer");   
    curl_setopt($bahnhofsname, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($bahnhofsname, CURLOPT_HTTPHEADER, array(
    'Accept: application/json',
    'Authorization: Bearer 8d395d714a8f008cbeeedabf530ef8c2'
    ));
    $response = curl_exec($bahnhofsname);
    curl_close($bahnhofsname);
    $response = json_decode($response, true);   
    
    $bahnhofsname1 = $response['result'][0]['name'] ;   # Bahnhofsname  Textausgabe
    $bahnhofsname =  $response['result'][0]['name'] ;   # Bahnhofsname  int. Weiterverarbeitung
    $bahnhofsname = sonderzeichen($bahnhofsname);

    # Erfassen der Bahnhofsid
    $bahnhofsid = $response['result'][0]['evaNumbers'][0]['number'];  

    #Koordinaten des Bahnhofs erfassen 

    $geolocation = curl_init("https://api.deutschebahn.com/stada/v2/stations/$bahnhofsnummer");   
    curl_setopt($geolocation, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($geolocation, CURLOPT_HTTPHEADER, array(
    'Accept: application/json',
    'Authorization: Bearer 8d395d714a8f008cbeeedabf530ef8c2'
    ));
    $response = curl_exec($geolocation);
    curl_close($geolocation);
    $response = json_decode($response, true);   
    $geolocation = array($response['result'][0]['evaNumbers'][0]['geographicCoordinates']);
    $laengengrad = $geolocation[0]['coordinates'][0];
    $breitengrad = $geolocation[0]['coordinates'][1];




    #Erfassen ob Bahnhof behindertengerechten Zugang zum Gleis hat
    $rampe = curl_init("https://api.deutschebahn.com/stada/v2/stations?searchstring=$bahnhofsname") ;
    curl_setopt($rampe, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($rampe, CURLOPT_HTTPHEADER, array(
    'Accept: application/json',
    'Authorization: Bearer 8d395d714a8f008cbeeedabf530ef8c2'
    ));
    $response = curl_exec($rampe);
    curl_close($rampe);
    $response = json_decode($response, true);   
    $rampe =  $response['result'][0]['hasSteplessAccess']; #Wert 
    if ($rampe = 'yes'){
    $rampe = " Dieser Bahnhof besitzt eine stufenlose Einstiegsmöglichkeit ";
    }
    elseif($rampe = 'no'){    
    $rampe = " Dieser Bahnhof besitzt keine stufenlose Einstiegsmöglichkeit ";
    }
    else{
    $rampe = "Dieser Bahnhof besitzt nur teilweise eine stufenlose Einstiegsmöglichkeit";
    }
    $einstiegshilfe = $response['result'][0]['hasMobilityService'];




    #Adresse des Bahnhofs erfassen

    $mailingaddresse = curl_init("https://api.deutschebahn.com/stada/v2/stations/$bahnhofsnummer");   
    curl_setopt($mailingaddresse, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($mailingaddresse, CURLOPT_HTTPHEADER, array(
    'Accept: application/json',
    'Authorization: Bearer 8d395d714a8f008cbeeedabf530ef8c2'
    ));
    $response = curl_exec($mailingaddresse);
    curl_close($mailingaddresse);
    $response = json_decode($response, true);   
    $mailingaddresse = array($response['result'][0]['mailingAddress']);
    $federalState = $response['result'][0]['federalState'];    
    $city =  $mailingaddresse[0]['city'];
    $street = $mailingaddresse[0]['street'];
    $zipcode = $mailingaddresse[0]['zipcode'];


    $bahninfo = curl_init("https://api.deutschebahn.com/stada/v2/stations/$bahnhofsnummer");   
    curl_setopt($bahninfo, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($bahninfo, CURLOPT_HTTPHEADER, array(
    'Accept: application/json',
    'Authorization: Bearer 8d395d714a8f008cbeeedabf530ef8c2'
    ));
    $response = curl_exec($bahninfo);
    curl_close($bahninfo);
    $response = json_decode($response, true);   
    $bahninfo = array($response['result'][0]);
 
    #Wifi
    $wifi = $bahninfo[0]['hasWiFi'];
    if ($wifi == TRUE){
       $wifi = "Bahnhof besitz WLAN";
    }
    elseif($wifi == FALSE){
    $wifi =  "Dieser Bahnhof besitzt kein WLAN";
    }
    #DB-Lounge
    $DBLounge = $bahninfo[0]['hasDBLounge'];
    if ($DBLounge == TRUE){
        $DBLounge = "Dieser Bahnhof besitz DBLounge";
     }
     elseif($DBLounge == FALSE){
     $DBLounge =  "Dieser Bahnhof besitzt keine DBLounge";
     }
   


    #
    # Arrive oder Departzeiten von Zugen anzeigen
    #

    $datencheckA = curl_init("https://api.deutschebahn.com/freeplan/v1/arrivalBoard/$bahnhofsid?date=$datum");
    curl_setopt($datencheckA, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($datencheckA, CURLOPT_HTTPHEADER, array(
    'Accept: application/json',
    'Authorization: Bearer 8d395d714a8f008cbeeedabf530ef8c2'
    ));
    $response = curl_exec($datencheckA);
    curl_close($datencheckA);
    $response = json_decode($response, true);   
    $datencheckA = $response [0]['name'];
  ####  echo $datencheckA;

    $datencheckB = curl_init("https://api.deutschebahn.com/freeplan/v1/arrivalBoard/$bahnhofsid?date=$datum");
    curl_setopt($datencheckB, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($datencheckB, CURLOPT_HTTPHEADER, array(
    'Accept: application/json',
    'Authorization: Bearer 8d395d714a8f008cbeeedabf530ef8c2'
    ));
    $response = curl_exec($datencheckB);
    curl_close($datencheckB);
    $response = json_decode($response, true);   
    $datencheckB = $response [0]['name'];
  #  echo $datencheckB;

    if (($datencheckA != NULL) OR ($datencheckB != NULL)){
        #Ausgabe Überschriften/Layout 
        echo "<div class='title'><h3>Informationen zum Bahnhof ' "   . $bahnhofsname1 . " '</h3></div>";

        if (isset($_POST['arrive'])  AND (!isset($_POST['depart']))) {
            echo "<div class='title'><h3>Ankünfte der nächsten Züge am  ". $datumtag. ".". $datummonat. ".". $datumjahr. "</h3></div>";

            $datumarrive = curl_init("https://api.deutschebahn.com/freeplan/v1/arrivalBoard/$bahnhofsid?date=$datum");
            curl_setopt($datumarrive, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($datumarrive, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            'Authorization: Bearer 8d395d714a8f008cbeeedabf530ef8c2'
            ));
            $response = curl_exec($datumarrive);
            curl_close($datumarrive);
            $response = json_decode($response, true);   
        }
        if (isset($_POST['depart'])  AND (!isset($_POST['arrive']))) {
            echo "<div class='title'><h3>Abfahrten der nächsten Züge am ". $datumtag. ".". $datummonat. ".". $datumjahr. "</h3></div>";
            $datumdepart = curl_init("https://api.deutschebahn.com/freeplan/v1/departureBoard/$bahnhofsid?date=$datum");
            curl_setopt($datumdepart, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($datumdepart, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            'Authorization: Bearer 8d395d714a8f008cbeeedabf530ef8c2'
            ));
            $response = curl_exec($datumdepart);
            curl_close($datumdepart);
            $response = json_decode($response, true);  
        }

        $detailidliste = array($response); # Array für Detailsid 
        $stopID = $response[$i]['stopId'];                        # Erstellen der Variable für StopID des Startbahnhofs
     
     
        For($i = 0; $i < 12; $i++){
            $zugname =  $response[$i]['name'] ;                     # Erstellen der Variable für  Zugname
            $zugtyp =  $response[$i]['type'] ;                      # Erstellen der Variable für  Zugtyp
            $ziel =  $response[$i]['stopName'] ;                    # Erstellen der Variable für   ziel
            $herkunft =  $response[$i]['origin'];                   # Erstellen der Variable für  Herkunft (Start)
            $gleis =  $response[$i]['track'] ;                      # Erstellen der Variable für  Gleis
            $ankunft = substr($response[$i]['dateTime'], -5) ;      # Erstellen der Variable für Ankunft
            $detailid = $response[$i]['detailsId'];                 # Erstellen der Variable für Journey ID für jeden Zug
         
            # Falls es kein Gleis gibt
           if($gleis == NULL){
             $gleis = " Nicht Verfügbar";
           }

           

           
            # Bei Auswahl Ankunft Arrive
            if (isset($_POST['arrive'])  AND (!isset($_POST['depart']))) {
                $i++;

                echo "
                 <div class='row'>
                 <div class='arrive'>
                 <h3>Ankünft $i</h3><br>
                 Nächste Abfahrt:" . $zugname . "<br>
                 (" . $zugtyp . ")          <br>
                 Ziel:" . $ziel .           "<br>
                 Herkunft:" . $herkunft .   "<br> 
                 Gleis:" . $gleis .         "<br>
                 Uhrzeit: " . $ankunft .    "<br>


                 </div> 
                 </div> 
                ";
                $i--;
            }
            # Bei Auswahl Abfahrt Depart
            if (isset($_POST['depart'])  AND (!isset($_POST['arrive']))) {
                $i++;
                echo "
                <div class='row'>
                <div class='depart'>
                <h3> Abfahrt " .  $i . "</h3><br>
                Zugname:" . $zugname . "<br>
                (Zugtyp: " . $zugtyp . ")<br>
                Ziel:" . $ziel . " <br>
                Gleis:" . $gleis . " <br>
                Uhrzeit: " . $ankunft . "<br>    
                </div> 
                </div>      ";
                $i--;
           }
           #Bei keiner Auswahl
           if (!isset($_POST['depart']) AND (!isset($_POST['arrive']))) {
             echo '<script type="text/javascript">
             window.onload = function () { alert("Fehlende Auswahl: Bitte Ankunft oder Abfahrt auswählen!"); } 
             </script>';  
           }
           #Bei Auswahl beider 
           if (isset($_POST['depart']) AND (isset($_POST['arrive']))) {
            echo "<div class='title'Bitte nur Ankunft oder Abfahrt auswählen</div>";
           }

        }



        #Überprüfen, ob es eine FFP2-Maskenpflicht gibt 
        $ffp2 = curl_init("https://api.deutschebahn.com/freeplan/v1/journeyDetails/$detailid");
        curl_setopt($ffp2, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($ffp2, CURLOPT_HTTPHEADER, array(
        'Accept: application/json' ,
        'Authorization: Bearer 8d395d714a8f008cbeeedabf530ef8c2'
        ));
        $response = curl_exec($ffp2);
        curl_close($ffp2);
        $response = json_decode($response, true); 
        $ffp2 = $response ['result'][0]['key'] ;
        echo $ffp2;
        if ($ffp2 = true){
             $pflicht = " JA ";  
        }           
        else{    
            $pflicht = " NEIN ";
        } 

        #
        # Öffnungszeiten DB Schalter
        #

        $times = curl_init("https://api.deutschebahn.com/stada/v2/stations/$bahnhofsnummer");
        curl_setopt($times, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($times, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Authorization: Bearer 8d395d714a8f008cbeeedabf530ef8c2'
        ));
        $response = curl_exec($times);
        curl_close($times);
        $response = json_decode($response, true);   
        $times = array($response ['result'][0]['localServiceStaff']['availability']);
        

        $mondaystart       = $times[0]['monday']['fromTime'];
        $mondayend         = $times[0]['monday']['toTime'];
        $tuesdaystart      = $times[0]['tuesday']['fromTime'];
        $tuesdayend        = $times[0]['tuesday']['toTime'];
        $wednesdaystart    = $times[0]['wednesday']['fromTime'];
        $wednesdayend      = $times[0]['wednesday']['toTime'];
        $thursdaystart     = $times[0]['thursday']['fromTime'];
        $thursdayend       = $times[0]['thursday']['toTime'];
        $fridaystart       = $times[0]['friday']['fromTime'];
        $fridayend         = $times[0]['friday']['toTime'];
        $saturdaystart     = $times[0]['saturday']['fromTime'];
        $saturdayend       = $times[0]['saturday']['toTime'];
        $sundaystart       = $times[0]['sunday']['fromTime'];
        $sundayend         = $times[0]['sunday']['toTime'];
        $holidaystart      = $times[0]['holiday']['fromTime'];
        $holidayend        = $times[0]['holiday']['toTime'];
       
        #
        # Ausgabe des Fahrplans 
        #


        echo "<h3>Fahrplan  der Zügen</h3>";
        For($j = 0; $j < 12; $j++){
          $detailid = $detailidliste[0][$j]['detailsId'];
          $detailid = str_replace("%", "%25", $detailid);
          $zname = $detailidliste[0][$j]['name'];

          $strecken = curl_init("https://api.deutschebahn.com/fahrplan-plus/v1/journeyDetails/$detailid");
          curl_setopt($strecken, CURLOPT_RETURNTRANSFER, true); 
          curl_setopt($strecken, CURLOPT_HTTPHEADER, array(
          'Accept: application/json' ,
          'Authorization: Bearer 8d395d714a8f008cbeeedabf530ef8c2'
          
          ));
          $response = curl_exec($strecken);
          curl_close($strecken);
          $response = json_decode($response, true); 
          $strecken = array($response);
            
         
          echo " <br><b>$zname</b> <br> ";

          For($k = 0; $k < 21; $k++){
            $zielbahnhof = $stationen;
            $stationen = $strecken[0][$k]['stopName'];
            $stopIDliste = $strecken[0][$k]['stopName'];
            if($stationen == NULL) {
              break;
            }
            else{
              $arrive = $strecken[0][$k]['arrTime'];
              $depTime = $strecken[0][$k]['depTime'];
              if($arrive == NULL) {
                $arrive = "Startbahnhof";
              }
              elseif($arrive != NULL){

              }

              if($depTime == NULL){
                $depTime = "Zielbahnof";
              }
              if($stationen != NULL)
              {
              echo " - ";
              }

                echo " $stationen  (Ankunft: $arrive |Abfahrt: $depTime)";
           



            }

          }

        }

        #
        # Ausgabe Informationen Allgemein
        #

        
        echo "<div class='title'><h3> ". $bahnhofsname1. " - allgemeine Informationen  </h3><br></div>";
        #Ausgabe der Bahn/Zuginformationen sowie der Map
        echo "
        <div class='spalten'>
            <div class='columnall' >
             <b>Informationen zum Bahnhof </b><br>
             Der Bahnhof befindet sich in der $street in $city ($zipcode) |$federalState <br>
             Koordinaten des Bahnhofs: Längengrad $laengengrad |Breitengrad $breitengrad <br>
             $wifi <br>
             $DBLounge<br>

  
               
            </div>  
            <div class='columnall' >
             <b>Öffnungszeiten DB-Infoschalter</b><br>
             Montag :Von $mondaystart bis $mondayend<br>
             Dienstag  :Von $tuesdaystart bis $tuesdayend<br>
             Mittwoch  :Von $wednesdaystart bis $wednesdayend<br>
             Donnerstag:Von $thursdaystart bis $thursdayend<br>
             Freitag   :Von $fridaystart bis $fridayend<br>
             Samstag   :Von $saturdaystart bis $saturdayend<br>
             Sonntag   :Von $sundaystart bis $sundayend<br>
             Ferien    :Von $holidaystart bis $holidayend<br>
               
            </div>  
            <div class='columnall' >
             <b>Informationen zum Transport</b><br>
             <br> Behindertengerecht: " . $rampe . "<br>
             Mobilitätshilfe : $einstiegshilfe <br>
             FFP2-Maskenpflicht: $pflicht<br>

               
            </div>  

       </div>

       <script>
            function init() {
             map = new OpenLayers.Map('basicMap');
             var mapnik = new OpenLayers.Layer.OSM();
             map.addLayer(mapnik);
             map.setCenter(new OpenLayers.LonLat($laengengrad,$breitengrad)
                .transform(
                 new OpenLayers.Projection('EPSG:4326'), 
                   new OpenLayers.Projection('EPSG:900913') 
                 ), 15 // Zoom level
               );

             var lonLat = new OpenLayers.LonLat( $laengengrad,$breitengrad )
               .transform(
                 new OpenLayers.Projection('EPSG:4326'), // transform from WGS 1984
                 map.getProjectionObject() // to Spherical Mercator Projection
               );
               var markers = new OpenLayers.Layer.Markers( 'Markers' );
               map.addLayer(markers);
               
               markers.addMarker(new OpenLayers.Marker(lonLat));  
            
            }

        </script>

    <body onload='init();' >
    <div id='basicMap'></div> //
    </body>
      ";

           
    
    } 
    else{
      # Ausgabe falls kein Eintrag zum Bahnhof gefunden wurde 
        echo"(<div class='title' >Dieser Bahnhof besitzt keine Farhplaninformationen: Dies kann daran liegen, dass der Bahnhof zu klein oder noch nicht ergänzt wurden ist. Für weitere Informationen wenden Sie sich an den Support (030  2970)!";
    }


}

?>