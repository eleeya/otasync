<?php

require 'main.php';

$konekcija = connectToDB();

$properties = [];
$sql = "SELECT * FROM all_properties";
$rezultat = mysqli_query($konekcija, $sql);
while($red = mysqli_fetch_assoc($rezultat)){
  array_push($properties, $red);
}

for($j=0;$j<sizeof($properties);$j++){
  $lcode = $properties[$j]["lcode"];
  $account = $properties[$j]["account"];

  $userToken = makeUncheckedRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
  if($userToken[0] != 0){
    echo "Invalid property $lcode - $account <br>";
    continue;
  }
  else {
    $userToken = $userToken[1];
    makeUncheckedRequest("unmark_reservations", array($userToken, $lcode));
    $actions = 1; // Already did 1 actions with token
    $rezervacijeAPI = makeUncheckedRequest("fetch_new_bookings", array($userToken, $lcode, 1, 1));
    if($rezervacijeAPI[0] != 0)
        continue;
    else
        $rezervacijeAPI = $rezervacijeAPI[1];
    $duzina = sizeof($rezervacijeAPI);
    while($duzina > 0)
    {
      for($i=0;$i<$duzina;$i++)
      {
        $reservation = $rezervacijeAPI[$i];
        insertWubookReservation($lcode, $account, $reservation);
      }
      $rezervacijeAPI = makeUncheckedRequest("fetch_new_bookings", array($userToken, $lcode, 1, 1));
      if($userToken[0] != 0)
        break;
      $actions += 1;
      if($actions > 50)
      {
        makeReleaseRequest("release_token", array($userToken));
        $userToken = makeUncheckedRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
        if($userToken[0] != 0)
          break;
        $actions = 0;
      }
      $duzina = sizeof($rezervacijeAPI);
    }
    makeReleaseRequest("release_token", array($userToken));
    echo "Finished property $lcode = $account <br>";
  }
  flush();
}


?>
