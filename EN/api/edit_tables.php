<?php

require "main.php";

$konekcija = connectToDB();

$lcodes = [];
$sql = "SELECT lcode FROM all_properties";
$rezultat = mysqli_query($konekcija, $sql);
while($red = mysqli_fetch_assoc($rezultat)){
  array_push($lcodes, $red["lcode"]);
}

for($i=0;$i<sizeof($lcodes);$i++){
  $lcode = $lcodes[$i];
  $sql = "ALTER TABLE `prices_$lcode` ADD `board` VARCHAR(63) NOT NULL DEFAULT 'nb' AFTER `booking_engine`, ADD `restriction_plan` VARCHAR(63) NOT NULL DEFAULT '0' AFTER `board`;";
  mysqli_query($konekcija, $sql);
  $sql = "ALTER TABLE `extras_$lcode` ADD `specific_rooms` VARCHAR(63) NOT NULL DEFAULT '[]' AFTER `rooms`";
  mysqli_query($konekcija, $sql);
  $sql = "ALTER TABLE `invoices_$lcode` ADD `room_id` VARCHAR(63) NOT NULL DEFAULT '' AFTER `mark`;";
  mysqli_query($konekcija, $sql);
}

/*
SELECT * FROM information_schema.tables WHERE table_name LIKE '%1485558698'

*/

 ?>
