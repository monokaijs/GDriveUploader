<?php

/*  Author:  https://github.io/MonokaiJs  */
/*  Website: https://nstudio.pw  */

require 'vendor/autoload.php';

$credentials_file = "credentials.json";

if (!file_exists($credentials_file)) {
	die("Failed to get credentials file. Go to Drive API Page to get one.");
}

function getClient()
{
	global $credentials_file;
    $client = new Google_Client();
    $client->setApplicationName('Google Drive API PHP Quickstart');
    $client->setScopes(Google_Service_Drive::DRIVE);
    $client->setAuthConfig($credentials_file);
    $client->setAccessType('offline');

    // Load previously authorized credentials from a file.
    $credentialsPath = 'token.json';
    if (file_exists($credentialsPath)) {
        $accessToken = json_decode(file_get_contents($credentialsPath), true);
    } else {
        // Request authorization from the user.
        $authUrl = $client->createAuthUrl();
        printf("Open the following link in your browser:\n%s\n", $authUrl);
        print 'Enter verification code: ';
        $authCode = trim(fgets(STDIN));

        // Exchange authorization code for an access token.
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

        // Check to see if there was an error.
        if (array_key_exists('error', $accessToken)) {
            throw new Exception(join(', ', $accessToken));
        }

        // Store the credentials to disk.
        if (!file_exists(dirname($credentialsPath))) {
            mkdir(dirname($credentialsPath), 0700, true);
        }
        file_put_contents($credentialsPath, json_encode($accessToken));
        printf("Credentials saved to %s\n", $credentialsPath);
    }
    $client->setAccessToken($accessToken);
    if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        file_put_contents($credentialsPath, json_encode(array_merge($accessToken, $client->getAccessToken())));
    }
    return $client;
}

$client = getClient();
$service = new Google_Service_Drive($client);

if (isset($_POST['submit'])) {
	$file_name = basename($_FILES['file']['name']);
	$file_path = $_FILES["file"]["tmp_name"];
	$finfo = finfo_open(FILEINFO_MIME_TYPE);						// to get file mimeType
	$service = new Google_Service_Drive($client);
	$file = new Google_Service_Drive_DriveFile();									// create new file
	$mime_type = finfo_file($finfo, $file_path);
	$file->setName($file_name);
	$file->setDescription('This is a '.$mime_type.' document');
	$file->setMimeType($mime_type);
	$newFile = $service->files->create(
		$file,
		array(
			'data' => file_get_contents($file_path),
			'mimeType' => $mime_type
		)
	);

	$newPermission = new Google_Service_Drive_Permission();
	$newPermission->setType('anyone');
	$newPermission->setRole('reader');

	// insert $newPermission to the file if you need to set it public
	
	$service->permissions->create($newFile->id, $newPermission);
	
	finfo_close($finfo);
	print_r($newFile);
	echo "https://drive.google.com/open?id=" .$newFile->id;
}





