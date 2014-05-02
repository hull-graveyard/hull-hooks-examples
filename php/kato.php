<?php 

require_once '../vendor/autoload.php';

error_log("PING !");

class Kato
{

    public static function Post($data, $roomId) {

        $url = 'https://api.kato.im/rooms/' . $roomId . '/simple';
        $data_string = json_encode($data);
        $s = curl_init($url);
        curl_setopt($s, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($s, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($s, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($s, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string))
        );
        $out = curl_exec($s);
        if ($out === false) {
          $error = curl_error($s);
          curl_close($s);
          throw new Exception("oops ! " . $error);
        }
        $status = curl_getinfo($s, CURLINFO_HTTP_CODE);
        $response = curl_getinfo($s);
        curl_close($s);
        $response_headers = http_parse_headers(substr($out, 0, $response['header_size']));
        $response_body = substr($out, $response['header_size']);
        return json_encode(array("url" => $url, "headers" => $response_headers, "body" => $response_body));
    }
}

if (getenv("HULL_ORG_URL")) {
  $orgUrl = getenv("HULL_ORG_URL");
}


$appId = $_SERVER['HTTP_HULL_APP_ID'];

if (getenv("HULL_APP_SECRET_" . $appId)) {
  $appSecret = getenv("HULL_APP_SECRET_" . $appId);
}

$config = array(
  "hull" => array(
    "host"  => $orgUrl,
    "appId" => $appId,
    "appSecret" => $appSecret
  )
);



if ($appId && $appSecret) {
  $hull = new Hull_Client($config);

  try {

    $payload = $hull->getEvent()->payload;
    
    if ($payload && $payload->event) {
      $app_name     = $payload->app_name;
      $user     = $payload->data->user;
      if (!$user && $payload->data->actor) {
        $user = $payload->data->actor;
      }
      if ($user && $user->name) {
        $subject  = $user->name;
      } else {
        $subject  = '';
      }

      $data = array('from' => $app_name . " -> " . $user->name, "renderer" => "markdown", "color" => "red");
      $key = $payload->event;

      $text = false;

      switch ($key) {
        case "user_profile.create":
          $text = "**" . $key . "**\n";
          $text  .= "A new user just signed up: " . $user->name;
          break;
        case "comment.create":
          $text = "**" . $key . "**\n";
          $text .= "##### A User (" . $user->name . ") just created a comment\n";
          $text .= "> " . $payload->data->description;
          break;
      }
      if ($text) {
        $data['text'] = $text;
        $ret = Kato::Post($data, getenv('KATO_ROOM_ID'));
        echo $ret;
      }
    }

  } catch (Exception $e) {
    error_log("Error: " . $e);
  }

} else {
  error_log("Oops... no app secret or appId ?  - appId: [" . $appId . "] secret: [" . $appSecret . "]");
}
