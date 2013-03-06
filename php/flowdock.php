<?php 



class Flowdock
{
    public static $_URL     = 'https://api.flowdock.com/v1/messages/team_inbox/';
    public static $_TOKEN   = 'flow-token-here';
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
        if (!$token && Flowdock::$_TOKEN) {
          $token = Flowdock::$_TOKEN; 
        }
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

$payload = $_REQUEST;

if ($payload && $payload['objectType']) {
  $name     = $payload['appName'];
  $tags     = array('hull-notif', $payload['appName'], $payload['objectType'], $payload['eventName']);
  if ($payload['data']['user'] && $payload['data']['user']['name']) {
    $subject  = $payload['data']['user']['name'];
  } else {
    $subject  = '';
  }
  $subject .= ' : ' . $payload['eventName'] . ' ' . $payload['objectType'];

  switch ($payload['objectType']) {
    case "badge":
      $content  = "A User just played at " . $payload['data']['name'] . " his name is " . $payload['data']['user']['name'];
      break;
  }

  Flowdock::TeamInbox($name, $subject, $content, $tags, $_GET['token']);
}
