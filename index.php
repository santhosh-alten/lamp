<?php
ini_set('display_errors', true);
error_reporting(E_ALL);
// Sample code showing OnePage CRM API usage
$api_login = 'YOUR_ONEPAGECRM_LOGIN';
$api_password = 'YOUR_ONEPAGECRM_PASSWORD';
// Make OnePage CRM API call
function make_api_call($url, $http_method, $post_data = array(), $uid = null, $key = null)
{
	$full_url = 'https://app.onepagecrm.com/api/v3/'.$url;
	$ch = curl_init($full_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $http_method);
	$timestamp = time();
	$auth_data = array($uid, $timestamp, $http_method, sha1($full_url));
    $request_headers = array();
    // For POST and PUT requests we will send data as JSON
    // as with regular "form data" request we won't be able
    // to send more complex structures
    if($http_method == 'POST' || $http_method == 'PUT'){
        $request_headers[] = 'Content-Type: application/json';
        $json_data = json_encode($post_data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        $auth_data[] = sha1($json_data);
    }
    // Set auth headers if we are logged in
    if($key != null){
        $hash = hash_hmac('sha256', implode('.', $auth_data), $key);
        $request_headers[] = "X-OnePageCRM-UID: $uid";
        $request_headers[] = "X-OnePageCRM-TS: $timestamp";
        $request_headers[] = "X-OnePageCRM-Auth: $hash";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
    $result = json_decode(curl_exec($ch));
    curl_close($ch);
    if($result->status > 99){
        echo "API call error: {$result->message}\n";
        return null;
    }
    return $result;
}
// Login
echo "Login action...\n";
$data = make_api_call('login.json', 'POST', array('login' => $api_login, 'password' => $api_password));
if($data == null){
    exit;
}
// Get UID and API key from result
$uid = $data->data->user_id;
$key = base64_decode($data->data->auth_key);
echo "Logged in, your UID is : {$uid}\n";
// Get contacts list
echo "Getting contacts list...\n";
$contacts = make_api_call('contacts.json', 'GET', array(), $uid, $key);
if($data == null){
    exit;
}
echo "We have {$contacts->data->total_count} contacts.\n";
// Create sample contact and delete it just after
echo "Creating new contact...\n";
$contact_data = array(
    'first_name' => 'Jonh',
    'last_name' => 'Doe',
    'company_name' => 'Acme Inc.',
    'tags' => array('api_test'),
    'emails' => array(
        array('type' => 'work', 'value' => 'john.doe@example.com'),
        array('type' => 'other', 'value' => 'johny@example.com')
        )
    );
$new_contact = make_api_call('contacts.json', 'POST', $contact_data, $uid, $key);
if($new_contact == null){
    exit;
}
$cid = $new_contact->data->contact->id;
echo "Contact created with ID : {$cid}\n";
// Create an action for this contact
echo "Creating action for contact...\n";
$action_data = array(
    'contact_id' => $cid,
    'date' => '2016-05-06',
    'text' => 'Call John with estimate',
    'status' => 'date'
    );
$new_action = make_api_call('actions.json', 'POST', $action_data, $uid, $key);
if($new_action == null){
    exit;
}
$aid = $new_action->data->action->id;
echo "Action created with ID : {$aid}\n";
echo "Deleting this contact...\n";
make_api_call("contacts/$cid.json", 'DELETE', array(), $uid, $key);
echo "Finished...\n";
