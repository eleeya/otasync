<?php

require '../../main.php';

if($_SERVER['REQUEST_METHOD'] == "OPTIONS"){
    http_response_code(200);
    die();
}
else if ($_SERVER['REQUEST_METHOD'] != "POST"){
  fatal_error("Invalid method", 405);
}

$key = checkPost("key");
$lcode = checkPost("lcode");
$account = checkPost("account");
$id = checkPost("id");

$konekcija = connectToDB();
$action = getAction();

$user = getSession($key, $account, $konekcija);
$user_id = $user["id"];

// Check access here

$old_data = [];
$new_data = [];
$ret_val = [];
$ret_val["status"] = "ok";

if($action == "reservation")
{

  // Remember data
  $sql = "SELECT * FROM reservations_$lcode WHERE reservation_code = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if($rezultat)
    $old_data = mysqli_fetch_assoc($rezultat);
  else
    fatal_error("Invalid ID", 200);
  if($old_data == null)
    fatal_error("Invalid ID", 400);

  if($old_data["id_woodoo"] != "" && $old_data["id_woodoo"] != "-2" && $old_data["id_woodoo"] != "-1"){
    fatal_error("Cannot cancel channel reservation", 403);
  }
  $today = date("Y-m-d");
  $deleted_advance = dateDiff($today, $old_data["date_arrival"]);
  if($old_data["status"] == 1){
    $sql = "UPDATE reservations_$lcode SET status = 5, deleted_advance = $deleted_advance, date_canceled = '$today' WHERE reservation_code = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    if($rezultat){
      $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
      makeUncheckedRequest("cancel_reservation", array($userToken, $lcode, $id));
      makeReleaseRequest("release_token", array($userToken));

      // Updating avail in database
      $real_rooms_list = explode(",", $old_data["real_rooms"]);
      $date_arrival = $old_data["date_arrival"];
      $date_departure = $old_data["date_departure"];
      $avail_rooms = [];
      $avail_values = [];
      for($i=0;$i<sizeof($real_rooms_list);$i++){
        $room = $real_rooms_list[$i];
        if(in_array($room, $avail_rooms)){ // Add one to value if room already added
          $avail_values[$room] += 1;
        }
        else { // Push to array of rooms and set to one if room isn't already added
          array_push($avail_rooms, $room);
          $avail_values[$room] = 1;
        }
      }
      $date_obj = date_create($date_departure); // Reduce the departure date by 1
      date_add($date_obj, date_interval_create_from_date_string("-1 day"));
      $avail_date_departure = date_format($date_obj, "Y-m-d");
      plansAvailUpdate($lcode, $date_arrival, $avail_date_departure, $avail_values, $avail_rooms, 1, $konekcija);
    }
    else {
      fatal_error("Database error", 500); // Server failed
    }
  }
  else {
    $sql = "DELETE FROM reservations_$lcode WHERE reservation_code = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
  }
  // Fixing guests
  $guests_list = explode(",", $old_data["guest_ids"]);
  for($i=0;$i<sizeof($guests_list);$i++){
    repairGuestData($guests_list[$i], $lcode, $konekcija);
  }

}

echo json_encode($ret_val);
$konekcija->close();

?>
