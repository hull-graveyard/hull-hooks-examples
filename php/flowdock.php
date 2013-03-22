<?php 

require_once '../vendor/autoload.php';

error_log("PING !");

class Flowdock
{
    public static $_URL     = 'https://api.flowdock.com/v1/messages/team_inbox/';
    public static $_SOURCE  = 'Hull Hooks Test';
    public static $_EMAIL   = 'notifications@hull.io';

    public static function TeamInbox($name, $subject, $content, $tags=array(), $token)
    {
        $data = array(
            'source'        => Flowdock::$_SOURCE,
            'from_address'  => Flowdock::$_EMAIL,
            'from_name'     => $name,
            'subject'       => $subject,
            'content'       => $content,
            'tags'          => $tags
        );
        $data_string = json_encode($data);
        $ch = curl_init(Flowdock::$_URL.$token);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string))
        );
        $result = curl_exec($ch);
    }
}

if (getenv("HULL_ORG_URL")) {
  $orgUrl = getenv("HULL_ORG_URL");
}

$appId = $_SERVER['HTTP_HULL_APP_ID'];

if (getenv("HULL_APP_SECRET_" . $appId)) {
  $appSecret = getenv("HULL_APP_SECRET_" . $appId);
}

if ($appId && $appSecret) {
  $hull = new Hull_Client(array(
    "hull" => array(
      "host"  => $orgUrl,
      "appId" => $appId,
      "appSecret" => $appSecret
    )
  ));


  try {
    $event = $hull->getEvent();
    $payload = $event->payload;

    if ($payload && $payload->objectType) {
      $name     = $payload->appName;
      $tags     = array('hull-notif', $payload->appName, $payload->objectType, $payload->eventName);
      $user     = $payload->data->user;
      if (!$user && $payload->data->actor) {
        $user = $payload->data->actor;
      }
      if ($user && $user->name) {
        $subject  = $user->name;
      } else {
        $subject  = '';
      }

      $subject .= ' : ' . $payload->eventName . ' ' . $payload->objectType;

      $key = $payload->eventName . '.' . $payload->objectType;

      switch ($key) {
        case "create.badge":
          $content  = "A User just played at " . $payload->data->name . " his name is " . $user->name;
          break;
        case "create.user_profile":
          $content  = "A new user just signed up to " . $user->name;
          break;
        case "create.image":
          $content  = "A User just created an image " . $user->name;
          break;
      }
      Flowdock::TeamInbox($name, $subject, $content, $tags, getenv("FLOWDOCK_API_TOKEN"));
    }

  } catch (Exception $e) {
    error_log("Error: " . $e);
  }

} else {
  error_log("Oops... no app secret or appId ?  - appId: [" . $appId . "] secret: [" . $appSecret . "]");
}