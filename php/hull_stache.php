<?php 
require_once '../vendor/autoload.php';

$appId = null;
$mandrill = new Mandrill(getenv('MANDRILL_API_KEY'));

function findUser($userId)
{
  global $appId;
  $hullClient = new Hull_Client(array(
    "hull" => array(
      "host"  => getenv('HULL_ORG_URL'), 
      "appId" => $appId,
      "appSecret" => getenv('HULL_APP_SECRET')
    )
  ));
  return $hullClient->get($userId);
}

function getImageURL($imageId)
{
  global $appId;
  $hullClient = new Hull_Client(array(
    "hull" => array(
      "host"  => getenv('HULL_ORG_URL'), 
      "appId" => $appId,
      "appSecret" => getenv('HULL_APP_SECRET')
    )
  ));
  return $hullClient->imageUrl($imageId);
}

function sendImageByMail($imageURL, $userEmail)
{
  global $mandrill;
  $msg = new Mandrill_Messages($mandrill);
  $desc = array(
    'html' => "<a href=\"$imageURL\">Check it out</a>",
    'text' => "Check it out: $imageURL",
    'subject' => "You've been Hull-stached!",
    'from_email' => 'mo@hull.io',
    'from_name' => 'The hull.io moustache squad',
    'to' => array(array('email' => $userEmail))
  );
  $msg->send($desc);
  error_log($imageURL);
  error_log($userEmail);
}


$payload = json_decode(file_get_contents('php://input'), true);

if ($payload && $payload['objectType'] === 'activity' && $payload['eventName'] === 'create') {
  $data  = $payload['data'];
  $appId = $payload['appId'];
  $image = $data['object'];
  $user = findUser($image['resourceful_id']);

  sendImageByMail(getImageURL($image['id']), $user->identities[0]->email);
}

exit();

