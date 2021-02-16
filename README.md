# PHP SDK

[![version](https://img.shields.io/badge/version-0.1-green.svg)](https://semver.org)

This SDK allows easy interaction with the XQ servers for tracking and securing your communications.

You may integrate this library into existing or new PHP applications in order to enable XQ support.


## 1. Installation

### Prerequisites
- XQ API Keys ( Generate keys at  https://manage.xqmsg.com )
- PHP 7.4 or higher
- Memcached 1.6 or higher.
- PHPUnit 9.x ( For unit tests).

### General Settings
API keys and other settings can be applied to the SDK in one of two ways:
1. **Environment Variables**: API keys can be set via PHP environment variables. The following variables are supported:

   | KEY                  | DEFAULT VALUE                     |
   | -------------------- | --------------------------------- |
   | DASHBOARD_API_KEY    | None ( Required )                 |
   | SUBSCRIPTION_API_KEY | None ( Required )                 |
   | VALIDATION_API_KEY   | None ( Required )                 |
   | URL_DASHBOARD        | https://dashboard.xqmsg.net/v2    |
   | URL_SUBSCRIPTION     | https://subscription.xqmsg.net/v2 |
   | URL_VALIDATION       | https://validation.xqmsg.net/v2   |
   | URL_QUANTUM          | https://quantum.xqmsg.net/v2      |
   | ORGANIZATION_TAG     | xq                                |

   

2. **Config.php**: The Config.php file is located in the `src` directory.  The environment values shown above may also be set directly within this file. If corresponding environment variables are set as well, they will override the values in this file.

### Cache Configuration

Users can configure which mechanism to use for caching user data. You can modify the default caching mechanism by editing your `Config.php` and changing the `CACHE_CLASS` value to your preferred caching implementation:

```php
// Default caching class
public const CACHE_CLASS = SessionCacheController::class;
```

- **SessionCacheController (Default)** : This uses PHP's default session management for storing user information. It is lightweight and involves no external dependencies, but data will be lost once the session is destroyed. 

- **MemcachedController**: Uses memcached to manage data. Requires an accessible memcached installation. 

  

### Running PHPUnit Tests

Run the following command from inside the main project folder to run the unit tests:

````bash
SUBSCRIPTION_API_KEY=YOUR_SUB_KEY \
VALIDATION_API_KEY=YOUR_VAL_KEY \
DASHBOARD_API_KEY=YOUR_DASHBOARD_KEY \
php /path/to/phpunit-9x.phar --no-configuration --test-suffix php tests
````

**Note:** If the Config.php file was modified to include the keys directly, they will not need to be included above.



## 2. Basic Usage

###  SDK Initialization

```php
// Initialize the XQ PHP SDK
$sdk = new XQSDK();
```



### Authenticating a User 

Before data can be encrypted or decrypted, a user must be have a valid access token. 

```php

$arguments = [
	'user' => "john@email.com"
];

$response = RequestAccess::with($sdk)->run( $arguments );
if (!$response->succeeded()){
	// Something went wrong...
	echo . $response->status();
	die();
}
// Success - A preauthorization token will be stored in memory.
// This will be replaced with a real authorization token once the user
// has successfully validated their email address by clicking on the link  // they received.
```

An email with an authorization link and a PIN code will be sent to the email address provided in the request. From this point on, a user has two ways of obtaining a valid access token:

  **i. Clicking Confirmation Link**
After clicking on the confirmation link in the email, the user can then exchange their preauthorization token ( received in the previous step ) for a valid access token:

```php
$arguments = [
	'code' => "123456"
];

$response = ValidateAccessRequest::with($sdk)->run($arguments);
if (!$response->succeeded()){
	// Something went wrong...
	echo . $response->status();
	die();
}

// Success - User is Authorized.
```

### Switching User Profiles

Authorized users have their access token cached. This allows them to switch between different profiles, and potentially start and stop the application without needing to reauthorize every time. 

```php

// Assuming a user previously logged on with Jane's email account and
// want to use it for any upcoming actions ( like sending an email):

// 1. Save your active profile:
$activeProfile = $sdk->getCache()->getActiveProfile();

// Switch to Jane's profile
$sdk->switchProfile("jane@email.com");

// After performing actions as Jane, switch back to previous profile:
$sdk->switchProfile($activeProfile);

```

### Viewing your Access Token
An access token can be stored out-of-band and reused for as long as it is valid. If this is done, ensure that it is stored in a manner that only authorized users have access to it.

```php

// Reference the cache instance.
$cache = $sdk->getCache();

// Get the active user profile:
$activeProfile = $cache->getActiveProfile();

// Get the XQ access token ( if available ):
$access_token = $cache->getXQAccess($activeProfile);

// Restore an access token:
$cache->addXQAccess($activeProfile, access_token );
```


###  Encrypting a Message

The most straightforward way to encrypt a mesage is by using the `Encrypt.runWith` method:

```php

// The message to encrypt
$msg = "Hello World";

// The message recipients
$recipients = "john@email.com";

$response = Encrypt::with($sdk)->runWith( 
	"Hello World", // The message to encrypt
  "john@email.com", // The message recipients
  new AESAlgorithm(),  // The encryption algorithm
  24, // The number of hours before the message expires
  [
  	'subject' => 'Sample Message',  // Additional dashboard metadata
  ]
);

if (!$response->succeeded()){
	// Something went wrong...
	echo . $response->status();
	die();
}


// Encryption was successful.
// The data can be used in any fashion the user like.
// Below, it is URL encoded for transmission as an email.
$encrypted = $response->json();
$url = $sdk->encodeLink($encrypted->data, $encrypted->token);

echo "Encrypted URL: " . $url;

```

See the `Encrypt.php` file for more details and available options when encrypting.

### Decrypting a Message

Encrypted text can be decrypted via `Decrypt.run` or `Decrypt.runWith`:

```php

// Assuming the message was received as a url,
// it would first need to be decoded.

$encrypted = $sdk->decodeLink($url);

$response = Decrypt::with($sdk)->run( (array) $encrypted );

if (!$response->succeeded()){
	// Something went wrong...
	echo . $response->status();
	die();
}

echo "Decrypted Content: " . $response->raw();
```

###  Encrypting a File

Files can be encrypted via `EncryptFile.run` or `EncryptFile.runWith`. 

```php

// Create a file "upload" using a local file.
$filename = "sample.txt";
$sourcePath = __DIR__ . "/" . $filename;
$source = File::uploaded([
	"name" => $filename,
	"tmp_name" => $sourcePath,
	"type" => mime_content_type($sourcePath),
	"error" => UPLOAD_ERR_OK,
	"size" => filesize( $sourcePath )
]);

// This is where our encrypted file will be saved in the filesystem.
// If this is not provided, the result will returned and not saved.
$targetPath =  __DIR__ . "/Sample-encrypted.txt.xqf";

// The number of hours before the key expires.
$expiresIn = 48;
           
$response = EncryptFile::with($sdk)->runWith(
$source, 
$targetPath, 
$recipients, 
new OTPv2Algorithm(),
$expiresIn );

if (!$response->succeeded()){
	// Something went wrong...
	echo . $response->status();
	die();
}

// Success - The encrypted file should be accessible at $targetPath.

```

See the `EncryptFile.php` file for more details and available options when encrypting.

### Decrypting a File

Encrypted files  can be decrypted via `DecryptFile.run` or `DecryptFile.runWith`. 


```php

$filename = "sample.txt.xqf";
$sourcePath = __DIR__ . "/" . $filename;


// Create a file "upload" using a local file.
 $source = File::uploaded([
 	"name" => $filename,
 	"tmp_name" => $sourcePath,
 	"error" => UPLOAD_ERR_OK,
 	"size" => filesize( $targetPath )
 ]);
 
 // This is where our decrypted file will be saved in the filesystem.
// If this is not provided, the result will returned and not saved.
$targetPath =  __DIR__ . "/Sample-encrypted.txt.xqf";


 $response = DecryptFile::with($sdk)->runWith( $source, $targetPath );
 
 if (!$response->succeeded()){
	// Something went wrong...
	echo . $response->status();
	die();
}

// Success - The decrypted file should be accessible at $targetPath.

```

### Granting and Revoking Message Access

There may be cases where a user needs to grant or revoke access to a previously sent message. This can be achieved via `GrantKeyAccess` and `RevokeKeyAccess` respectively:

```php

// The token of the previously existing message.
$token = "MY_MESSAGE_TOKEN"
// Add jim and joe to the recipient list.
$emailsToAdd = array("jim@email.com","joe@email.com");

// Grant access to jim@email.com. This will only work if the currently
// active profile is the same one that sent the original message.
$response = GrantKeyAccess::with($sdk)->runWith($token, $emailsToAdd );

 if (!$response->succeeded()){
	// Something went wrong...
	echo . $response->status();
	die();
}

// Success - Jim and Joe will now be able to read the original message.
```


### Revoking a Message

```php
 // Revoke all access to key.
$response = RevokeKey::with($sdk)->runWith( $encryptedObject->token );

 if (!$response->succeeded()){
	// Something went wrong...
	echo . $response->status();
	die();
}

// Success - Message has been revoked.
```

### Deleting a User

Authenticated users can be deleted via `DeleteUser.run` or `DeleteUser.runWith`. In order to continue use after deleting a user, it will be necessary to switch to another active user profile (if one is available), or request access for a new user.

```php

// Delete the active user.
$response = DeleteUser::with($sdk)->run();

if (!$response->succeeded()){
	// Something went wrong...
	echo . $response->status();
	die();
}

// Success - User has been successfully deleted.
```