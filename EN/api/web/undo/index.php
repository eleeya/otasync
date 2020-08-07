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

$konekcija = connectToDB();
$action = getAction();

$user = getSession($key, $account, $konekcija);
$user_id = $user["id"];

// Check access here

$old_data = [];
$new_data = [];
$ret_val = [];
$ret_val["status"] = "ok";

$id = checkPost("id");

$sql = "SELECT * FROM changelog_$lcode WHERE id = '$id'";
$rezultat = mysqli_query($konekcija, $sql);
$change  = mysqli_fetch_assoc($rezultat);
if(!$change)
  fatal_error("Invalid ID", 200);
else {
  $change = fixChange($change);
}

if($change["action"] == "insert") // Item was inserted, so delete it from database
{
  // Weird way of getting correct table name, id field name and id
  $table_name = $change["data_type"] . "s";
  $id_name = "id";
  $id = $change["new_data"]->id;
  if($change["data_type"] == "reservation"){
    $table_name = $change["data_type"] . "s";
    $id_name = "id";
    $id = $change["new_data"]->id;
  }
  if($change["data_type"] == "reservation"){
    $id_name = "reservation_code";
    $id = $change["new_data"]->reservation_code;
  }
  if($change["data_type"] == "policy"){
    $table_name = "policies";
  }
  if($change["data_type"] == "category"){
    $table_name = "categories";
  }
  if($change["data_type"] == "pricingPlan"){
    $table_name = "prices";
  }
  if($change["data_type"] == "restrictionPlan"){
    $table_name = "restrictions";
  }
  $table_name = $table_name . "_" . $lcode;
  if($change["data_type"] == "user"){
    $table_name = "all_users";
  }

  $sql = "DELETE FROM $table_name WHERE $id_name = '$id'";
  $rezultat = mysqli_query($konekcija, $sql);
  if(!$rezultat)
    fatal_error("Database failed", 500);

  // Special cases
  if($change["data_type"] == "reservation"){
    $old_data = (array)$change["new_data"];
    // Wubook cancel and avail update
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
  if($change["data_type"] == "pricingPlan"){
    // Wubook delete
    // Wubook delete
    $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
    makeRequest("del_plan", array($userToken, $lcode, $id));
    makeReleaseRequest("release_token", array($userToken));
  }
  if($change["data_type"] == "restrictionPlan"){
    // Wubook delete
    $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
    makeRequest("rplan_del_rplan", array($userToken, $lcode, $id));
    makeReleaseRequest("release_token", array($userToken));
  }
  if($change["data_type"] == "room"){
    // Wubook delete
    $userToken = makeRequest("acquire_token", array($account, "davincijevkod966", "753fa793e9adb95321b061f05e29a78327645c05e097e376"));
    makeRequest("del_room", array($userToken, $lcode, $id));
    makeReleaseRequest("release_token", array($userToken));
  }

}

if($change["action"] == "delete") // Item was deleted, insert it back
{

  // Custom for reservation cancel only
  if($change["data_type"] == "reservation" && $change["old_data"]->status == 1){
    $id = $change["old_data"]->reservation_code;
    $sql = "UPDATE reservations_$lcode SET status = 1 WHERE reservation_code = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    if(!$rezultat)
      fatal_error("Database failed", 500);
    else {
      // Updating avail in database
      $old_data = (array)$change["old_data"];
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
      plansAvailUpdate($lcode, $date_arrival, $avail_date_departure, $avail_values, $avail_rooms, -1, $konekcija);
      wubookAvailUpdate($lcode, $account, $date_arrival, $avail_date_departure, $avail_rooms, $konekcija);
    }
  }
  else {
    // Weird way of getting correct table name
    $table_name = $change["data_type"] . "s";
    if($change["data_type"] == "policy"){
      $table_name = "policies";
    }
    if($change["data_type"] == "category"){
      $table_name = "categories";
    }
    if($change["data_type"] == "pricingPlan"){
      $table_name = "prices";
    }
    if($change["data_type"] == "restrictionPlan"){
      $table_name = "restrictions";
    }
    $table_name = $table_name . "_" . $lcode;
    if($change["data_type"] == "user"){
      $table_name = "all_users";
    }
    $old_data = $change["old_data"];
    $fields = [];
    $values = [];
    foreach($old_data as $key => $value){
      array_push($fields, $key);
      array_push($values, "'" . $value . "'");
    }
    $fields = implode(", ", $fields);
    $values = implode(", ", $values);
    $sql = "INSERT INTO $table_name ($fields) VALUES ($values)";
    $rezultat = mysqli_query($konekcija, $sql);
    if(!$rezultat)
      fatal_error("Database failed", 500);
  }


}

if($change["action"] == "edit") // Item was edited, edit it back
{
  // Complete custom for these 3
  if($change["data_type"] == "avail"){
    $old_data = $change["old_data"];
    for($i=0;$i<sizeof($old_data);$i++){
      $date = $old_data[$i]->avail_date;
      $values = [];
      foreach($old_data[$i] as $key => $value){
        if($key != "avail_date")
          array_push($values, $key . " = '" . $value . "'");
      }
      $values = implode(", ", $values);
      $sql = "UPDATE avail_values_$lcode SET $values WHERE avail_date = '$date'";
      $rezultat = mysqli_query($konekcija, $sql);
      if(!$rezultat)
        fatal_error("Database failed", 500);
    }
    // Wubook data
    $dfrom = $old_data[0]->avail_date;
    $dto = $old_data[sizeof($old_data) - 1]->avail_date;
    $rooms = [];
    foreach($old_data[0] as $key => $value){
      if($key != "avail_date"){
        $room = explode("_", $key);
        $room = $room[1];
      }
        array_push($rooms, $room);
    }
    wubookAvailUpdate($lcode, $account, $dfrom, $dto, $rooms, $konekcija);
  }
  else if($change["data_type"] == "price"){
    $old_data = $change["old_data"];
    $id = $old_data[0]->id;
    for($i=0;$i<sizeof($old_data);$i++){
      $date = $old_data[$i]->price_date;
      $values = [];
      foreach($old_data[$i] as $key => $value){
        if($key != "price_date" && $key != "id")
          array_push($values, $key . " = '" . $value . "'");
      }
      $values = implode(", ", $values);
      $sql = "UPDATE prices_values_$lcode SET $values WHERE price_date = '$date' AND id = '$id'";
      $rezultat = mysqli_query($konekcija, $sql);
      if(!$rezultat)
        fatal_error("Database failed", 500);
    }
    // Wubook data
    $dfrom = $old_data[0]->price_date;
    $dto = $old_data[sizeof($old_data) - 1]->price_date;
    $rooms = [];
    foreach($old_data[0] as $key => $value){
      if($key != "price_date" && $key != "id"){
        $room = explode("_", $key);
        $room = $room[1];
      }
        array_push($rooms, $room);
    }
    wubookPriceUpdate($lcode, $account, $dfrom, $dto, $id, $rooms, $konekcija);
  }
  else if($change["data_type"] == "restriction"){
    $old_data = $change["old_data"];
    $id = $old_data[0]->id;
    for($i=0;$i<sizeof($old_data);$i++){
      $date = $old_data[$i]->restriction_date;
      $values = [];
      foreach($old_data[$i] as $key => $value){
        if($key != "restriction_date" && $key != "id")
          array_push($values, $key . " = '" . $value . "'");
      }
      $values = implode(", ", $values);
      $sql = "UPDATE restrictions_values_$lcode SET $values WHERE restriction_date = '$date' AND id = '$id'";
      $rezultat = mysqli_query($konekcija, $sql);
      if(!$rezultat)
        fatal_error("Database failed", 500);
    }
    // Wubook data
    $dfrom = $old_data[0]->restriction_date;
    $dto = $old_data[sizeof($old_data) - 1]->restriction_date;
    $rooms = [];
    foreach($old_data[0] as $key => $value){
      if($key != "restriction_date" && $key != "id"){
        $room = explode("_", $key);
        $room = $room[1];
      }
        array_push($rooms, $room);
    }
    wubookRestrictionUpdate($lcode, $account, $dfrom, $dto, $id, $rooms, $konekcija);
  }
  else { // Normal data
    // Weird way of getting correct table name
    $table_name = $change["data_type"] . "s";
    $id_name = "id";
    $id = $change["new_data"]->id;
    if($change["data_type"] == "reservation"){
      $table_name = $change["data_type"] . "s";
      $id_name = "id";
      $id = $change["new_data"]->id;
    }
    if($change["data_type"] == "reservation"){
      $id_name = "reservation_code";
      $id = $change["new_data"]->reservation_code;
    }
    if($change["data_type"] == "policy"){
      $table_name = "policies";
    }
    if($change["data_type"] == "category"){
      $table_name = "categories";
    }
    if($change["data_type"] == "pricingPlan"){
      $table_name = "prices";
    }
    if($change["data_type"] == "restrictionPlan"){
      $table_name = "restrictions";
    }
    $table_name = $table_name . "_" . $lcode;
    if($change["data_type"] == "user"){
      $table_name = "all_users";
    }
    $old_data = $change["old_data"];
    $values = [];
    foreach($old_data as $key => $value){
      if($key != $id_name)
        array_push($values, $key . " = '" . $value . "'");
    }
    $values = implode(", ", $values);
    $sql = "UPDATE $table_name SET $values WHERE $id_name = '$id'";
    $rezultat = mysqli_query($konekcija, $sql);
    if(!$rezultat)
      fatal_error("Database failed", 500);
  }
  if($change["data_type"] == "reservation"){

    $old_data = (array)$change["new_data"];
    // Updating avail in database and on WB (old data)
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
    wubookAvailUpdate($lcode, $account, $date_arrival, $avail_date_departure, $avail_rooms, $konekcija);

    $new_data = (array)$change["old_data"];
    // Updating avail in database and on WB (new data)
    $real_rooms_list = explode(",", $new_data["real_rooms"]);
    $date_arrival = $new_data["date_arrival"];
    $date_departure = $new_data["date_departure"];
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
    plansAvailUpdate($lcode, $date_arrival, $avail_date_departure, $avail_values, $avail_rooms, -1, $konekcija);
    wubookAvailUpdate($lcode, $account, $date_arrival, $avail_date_departure, $avail_rooms, $konekcija);
  }

}


echo json_encode($ret_val);
$konekcija->close();

?>
