<html>
<head>
		<link rel="stylesheet" href="style.css" type="text/css">
	<link rel= "stylesheet" href="https://fonts.googleapis.com/css?family=Trirong">
    </head>
    <title>Bahnauskunft</title>
	<body>
       <div class="header">
        <h1>Bahnauskunft</h1>

    </div>
    <div class="main">
         <form action="" method="post">
                <label for="bahnhof">Startbahnhof: </label><br>
			    <input type="text" name="bahnhof"><br><br>
                <button type="submit" name="search" class="button" value="search">Suchen</button>
                <button type="submit" name="search2" class="button" value="search2">Inspiration</button>


		</form>
        </div>
   
</html>

<?php
error_reporting(0);

function sonderzeichen($bahnhof){
    $bahnhof = str_replace("ä", "%C3%A4", $bahnhof);
    $bahnhof = str_replace("ö", "%C3%B6", $bahnhof);
    $bahnhof = str_replace("ü", "%C3%BC", $bahnhof);
    $bahnhof = str_replace(" ", "%20", $bahnhof);
    return $bahnhof;
}

if (isset($_POST['search']) AND (empty($_POST['bahnhof']))) {
    echo '<script type="text/javascript">
    window.onload = function () { alert("Bitte Startbahnhof auswählen !"); } 
    </script>';  
  }
    
if (isset($_POST['search']) AND (!empty($_POST['bahnhof']))  ){
    $bahnhof = $_POST['bahnhof']; 
    $bahnhof = ('*' . $bahnhof . '*');
    $bahnhof = sonderzeichen($bahnhof);



    $bahnhofsname = curl_init("https://api.deutschebahn.com/stada/v2/stations?searchstring=$bahnhof");   
    curl_setopt($bahnhofsname, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($bahnhofsname, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Authorization: Bearer 8d395d714a8f008cbeeedabf530ef8c2'
    ));
    $response = curl_exec($bahnhofsname);
    curl_close($bahnhofsname);
    $response = json_decode($response, true);   
    #Anzahl der vorhandenen Einträge 
    $total = $response['total'];
    # Ermitteln der Bahnhohfsnamen
    $bahnhofsname1 = $response['result'][0]['name'] ;   # Bahnhofsname für Textausgabe
    $bahnhofsname =  $response['result'][0]['name'] ;   # Bahnhofsname für searchstrings
    #Erfassen der Bahnhofsid
    $bahnhofsid = $response['result'][0]['evaNumbers'][0]['number'];  

    For($i = 0 ; $i < $total; $i++){
        $bahnhofsid = $response['result'][$i]['number'];
        $bahnhofsname1 = $response['result'][$i]['name'] ; 
        echo "<a href='http://localhost/bahnprojekt/bahn.php?bid=$bahnhofsid'><button><div class='button'>$bahnhofsname1</div></button></a>";


    }
 

} 



#Zufallsgenerator
if (isset($_POST['search2'])){

    #Zufallsgenerator
    $zufallsid = rand(1, 300);  # Zufälligen Bahnhofsnummer erstellen
    $textnr = rand (1,3);       # Variable für variable Textausgabe 

    #Zufallsgenerator
    $zielbahnhof = curl_init("https://api.deutschebahn.com/stada/v2/stations/$zufallsid");
    curl_setopt($zielbahnhof, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($zielbahnhof, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Authorization: Bearer 4efd3d697af66b24ba30c2f044ba23b6'
    ));
    $response = curl_exec($zielbahnhof);
    curl_close($zielbahnhof);
    $response = json_decode($response, true);  
    $zufallsbahnhof =  $response ['result'][0]['name'] ; 

    # Überprüfen, ob es bahnhof gibt 
    if ($zufallsbahnhof == NULL){
      
        
        while ($zufallsbahnhof == NULL) {
            $zufallsid = $zufallsid +1 ;
            $zielbahnhof = curl_init("https://api.deutschebahn.com/stada/v2/stations/$zufallsid");
            curl_setopt($zielbahnhof, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($zielbahnhof, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            'Authorization: Bearer 4efd3d697af66b24ba30c2f044ba23b6'
            ));
            $response = curl_exec($zielbahnhof);
            curl_close($zielbahnhof);
            $response = json_decode($response, true);  
            $zufallsbahnhof =  $response ['result'][0]['name'] ;    
    
        }
    
     
    }
    # Bahnhof kann gefunden werden 
    if ($zufallsbahnhof !=NULL) {
        $zielbahnhof = curl_init("https://api.deutschebahn.com/stada/v2/stations/$zufallsid");
        curl_setopt($zielbahnhof, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($zielbahnhof, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Authorization: Bearer 4efd3d697af66b24ba30c2f044ba23b6'
        ));
        $response = curl_exec($zielbahnhof);
        curl_close($zielbahnhof);
        $response = json_decode($response, true);  
        $zufallsbahnhof =  $response ['result'][0]['name'] ; 
        $ort =   array($response['result'][0]['mailingAddress']) ; 
        $city = $ort[0]['city'];
        $bahnhofsid = $response['result'][0]['number'];

    

       if($textnr == 1 ){
            echo " <div class='title'><b>$city</b> klingt doch gut für einen spontanen Trip gut.</div> ";
            echo " <div class='title'><a href='http://www.google.de/search?q=$city' target='_blank'>Weitere Informationen zu $city</a></div><br>";      
            echo " <div class='title'><a href='http://localhost/bahn.php?bid=$bahnhofsid'>genauere Bahnhofs</a>";
        }
        elseif($textnr == 2){
            echo "<div class='title' >Fahren wir doch gemeinsam nach <b>$city</b> ! </div>";
            echo "<div class='title' ><a href='http://www.google.de/search?q=$city'  target='_blank'>Weitere Informationen zu $city</a></div> </div>";
            echo " <div class='title'><a href='http://localhost/bahn.php?bid=$bahnhofsid'>genauere Bahnhofs</a>";
        }
       if($textnr == 3){
            echo " <div class='title' >Es gibt noch günstig Tickets nach <b>$city </b> !";
            echo " <div class='title' ><a href='http://www.google.de/search?q=$city'  target='_blank'>Weitere Informationen zu $city</a> </div> ";
            echo " <div class='title'><a href='http://localhost/bahn.php?bid=$bahnhofsid'>genauere Bahnhofs</a>";
        }
               
    }
        
}   

?>