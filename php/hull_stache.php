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

function sendImageByMail($imageURL, $userEmail, $userName)
{
  global $mandrill;

  $vars = array('name'=> $userName );
  $email = apply_template('./hull_stache/mail_template_inlined.php', $vars);
  $hullstached = base64_encode(file_get_contents($imageURL));
  $imagedata = base64_encode(file_get_contents("./hull_stache/logo.png"));
  $twitter = base64_encode(file_get_contents('./hull_stache/twitter.png'));
  $desc = array(
    'html' => $email,
    'text' => "Check it out: $imageURL",
    'subject' => "$userName, you've been hullstached!",
    'from_email' => 'mo@hull.io',
    'from_name' => 'The hull.io moustache squad',
    'images' => array(
      array(
        'type'   => 'image/png',
        'name'   => 'hull_logo_image',
        'content'=> $imagedata
      ),
      array(
        'type'   => 'image/png',
        'name'   => 'hull_twitter_image',
        'content'=> $twitter
      ),
      array(
        'type'   => 'image/jpeg',
        'name'   => 'hullstached_image',
        'content'=> $hullstached
      )
    ),
    'to' => array(array('email' => $userEmail))
  );

  $msg = new Mandrill_Messages($mandrill);
  $msg->send($desc);
  error_log($imageURL);
  error_log($userEmail);
}


/**
 * Execute a PHP template file and return the result as a string.
 */
function apply_template($tpl_file, $vars = array(), $include_globals = true)
{
  extract($vars);
  if ($include_globals) extract($GLOBALS, EXTR_SKIP);
  ob_start();
  require($tpl_file);
  $applied_template = ob_get_contents();
  ob_end_clean();
  return $applied_template;
}

$event = new Hull_Event(file_get_contents('php://input'), getenv('HULL_APP_SECRET'));
$payload = $event->payload;

if ($payload && $payload->objectType === 'activity' && $payload->eventName === 'create') {
  $data  = $payload->data;
  $appId = $payload->appId;
  $image = $data->object;
  $user = findUser($image->resourceful_id);

  sendImageByMail(getImageURL($image->id), $user->identities[0]->email, $user->identities[0]->name);
}

exit();

