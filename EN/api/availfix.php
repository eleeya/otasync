<?php


function wbAvailUpdate($lcode, $account, $dfrom, $dto, $konekcija)
{
  $today = date("Y-m-d");
  if(cmpDates($dto, $today) == -1)
    return;
  if(cmpDates($dfrom, $today) == -1)
    $dfrom = $today;

  $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
  $values = plansAvailValues($lcode, $dfrom, $dto, $konekcija);
  $wubook_values = []; // Format of wubook values: [{"id":"288966","days":[{"avail":3}]}, {"id":"288942","days":[{"avail":3}]}];
  foreach($values as $room_id => $date_values){
    if(true){ // Only for all rooms
      // Generate room object
      $room_object = [];
      $room_object["id"] = $room_id;
      $room_object["days"] = [];
      foreach($date_values as $date => $value){ // Not sure if this guarantees correct order
        // Generate single date object
        $date_object = [];
        $date_object["avail"] = $value;
        array_push($room_object["days"], $date_object);
      }
      array_push($wubook_values, $room_object); // Push room to wubook object
    }
  }
  // Format values for wubook
  $wubook_values = json_decode(json_encode($wubook_values));
  $dfrom = ymdToDmy($dfrom);
  makeRequest("update_avail", array($userToken, $lcode, $dfrom, $wubook_values));
  makeReleaseRequest("release_token", array($userToken));
}


require "main.php";

$konekcija = connectToDB();
$account = $_GET["account"];
$lcode = $_GET["lcode"];
$dfrom = $_GET["dfrom"];
$dto = $_GET["dto"];
$time = strtotime($dfrom);

$rooms = [];
$sql = "SELECT * FROM rooms_$lcode WHERE parent_room = 0";
$rezultat = mysqli_query($konekcija, $sql);
while($red = mysqli_fetch_assoc($rezultat)){
  array_push($rooms, $red);
}

$i = 0;
do {
  $date = date("Y-m-d", $time+$i*24*60*60);

  echo $date . ": ";
  for($j=0;$j<sizeof($rooms);$j++){
    $room_id = $rooms[$j]["id"];
    $room_avail = $rooms[$j]["availability"];
    $sql = "SELECT real_rooms FROM reservations_$lcode WHERE date_arrival <= '$date' AND date_departure > '$date' AND status = 1";
    $rezultat = mysqli_query ($konekcija, $sql);
    while($red = mysqli_fetch_assoc($rezultat)){
      $res_rooms = explode(",", $red["real_rooms"]);
      for($k=0;$k<sizeof($res_rooms);$k++){ // Dumb AF
        if($res_rooms[$k] == $room_id)
          $room_avail -= 1;
      }
    }
    if($room_avail < 0)
      $room_avail = 0;

    $sql = "UPDATE avail_values_$lcode SET room_$room_id = $room_avail WHERE avail_date = '$date'";
    $rezultat = mysqli_query($konekcija, $sql);
    if($rezultat){
      echo $rooms[$j]["shortname"] . " " . "($room_avail)" . "   ";
    }
  }
  echo "<br>";

  $i += 1;
} while(cmpDates($date, $dto) < 0);

wbAvailUpdate($lcode, $account, $dfrom, $dto, $konekcija);

echo "Uspesno sinhronizovana raspolozivost";

 ?>
