

<?PHP


function sendNotification($rcode, $lcode, $account, $konekcija)
{
  // Channel data
  $channel_data = [];
  $sql = "SELECT logo, name, id FROM channels_$lcode";
  $rezultat = mysqli_query($konekcija, $sql);
  while($red = mysqli_fetch_assoc($rezultat)){
    $channel_data[$red['id']] = $red;
  }
  // Res data
  $sql = "SELECT * FROM reservations_$lcode WHERE reservation_code = '$rcode'";
  $rezultat = mysqli_query($konekcija, $sql);
  $res = fixReservation(mysqli_fetch_assoc($rezultat), $channel_data, $konekcija, $lcode);
  if($res["id_woodoo"] == "-1" && $res["id_woodoo"] == "")
    return;
  // Message
  if($res["status"] == 1)
    $message = "Otkazana rezervacija: " . ymdToDmy($res["date_arrival"]) . " - " . ymdToDmy($res["date_departure"]) . " (" . $res["customer_name"] . " " . $res["customer_surname"] . ")";
  else
    $message = "Nova rezervacija: " . ymdToDmy($res["date_arrival"]) . " - " . ymdToDmy($res["date_departure"]) . " (" . $res["customer_name"] . " " . $res["customer_surname"] . ")";
  $content = array("en" => $message);
  // Notification ids
  $ids = [];
  $sql = "SELECT id FROM all_users WHERE account = '$account' AND status != 0";
  $rezultat = mysqli_query($konekcija, $sql);
  while($user = mysqli_fetch_assoc($rezultat)){
    $user_id = $user["id"];
    $sql2 = "SELECT * FROM all_sessions WHERE id = '$user_id' AND notification_id != ''";
    $rezultat2 = mysqli_query($konekcija, $sql2);
    while($user_session = mysqli_fetch_assoc($rezultat2)){
      array_push($ids, $user_session["notification_id"]);
    }
  }
  // Sending too all 3 apps
  $fields = array(
      'app_id' => "671f8f35-b25a-42e9-9d8d-b48adfe4f088",
      'include_player_ids' => $ids,
      'data' => array("foo" => "bar"),
      'contents' => $content
  );
  $fields = json_encode($fields);
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_HEADER, FALSE);
  curl_setopt($ch, CURLOPT_POST, TRUE);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
  $response = curl_exec($ch);
  curl_close($ch);
}





?>
