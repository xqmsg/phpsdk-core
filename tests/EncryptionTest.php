<?php

require_once __DIR__ . "/../src/AutoLoader.php";

use com\xqmsg\sdk\v2\algorithms\AESAlgorithm;
use com\xqmsg\sdk\v2\algorithms\OTPv2Algorithm;
use com\xqmsg\sdk\v2\models\File;
use com\xqmsg\sdk\v2\services\Decrypt;
use com\xqmsg\sdk\v2\services\DecryptFile;
use com\xqmsg\sdk\v2\services\Encrypt;
use com\xqmsg\sdk\v2\services\EncryptFile;
use com\xqmsg\sdk\v2\services\RevokeKey;
use com\xqmsg\sdk\v2\services\DeleteUser;
use PHPUnit\Framework\TestCase;
use com\xqmsg\sdk\v2\services\RequestAliasAccess;
use com\xqmsg\sdk\v2\XQSDK;


class EncryptionTest extends TestCase
{
    public function testTextEncryption(): void
    {

        try {
            // Request access for an alias.
            $sdk = new XQSDK();

            $primary = 'test-'.bin2hex(random_bytes ( 6 ));
            $response = RequestAliasAccess::with($sdk)->run( [
                'user' => $primary ] );

            if ($response->succeeded()) {

                // Was the active profile set successfully?
                $savedProfile = $sdk->getCache()->getActiveProfile();
                self::assertEquals($primary, $savedProfile);

                /// Add a key to the server.
                $plainText = "Hello World";

                // OTP Encrypt without any metadata and a 4-hour read time.
                $response = Encrypt::with($sdk)->runWith( $plainText,  $primary,  new OTPv2Algorithm() , 4, ['subject' => 'Test to ' . $primary] );

                self::assertTrue( $response->succeeded() , $response->status() );
                $encryptedObject = $response->json();
                self::assertNotNull( $encryptedObject->data, "No encrypted data found."  );
                self::assertNotNull( $encryptedObject->secret, "No secret found."  );
                self::assertNotNull( $encryptedObject->token, "No token data found."  );

                $url = $sdk->encodeLink($encryptedObject->data, $encryptedObject->token);
                self::assertIsString($url, "Failed to encode URL");

                // Confirm that we can decode the data.
                $response = Decrypt::with($sdk)->run( (array) $encryptedObject );
                self::assertTrue( $response->succeeded() , $response->status() );
                self::assertEquals($plainText, $response->raw());
                ///////////////////

                // AES Encrypt without any metadata and a 4-hour read time.
                $response = Encrypt::with($sdk)->runWith( $plainText,  $primary,  new AESAlgorithm() , 4, ['subject' => 'Test to ' . $primary] );

                self::assertTrue( $response->succeeded() , $response->status() );
                $encryptedObject = $response->json();
                self::assertNotNull( $encryptedObject->data, "No encrypted data found."  );
                self::assertNotNull( $encryptedObject->secret, "No secret found."  );
                self::assertNotNull( $encryptedObject->token, "No token data found."  );

                $url = $sdk->encodeLink($encryptedObject->data, $encryptedObject->token);

                $restored = $sdk->decodeLink($url);

                self::assertIsString($restored->data, "Failed to restore organization tag from URL.");
                self::assertIsObject($restored, "Failed to recover encrypted url data.");
                // Confirm that we can decode the data.
                $response = Decrypt::with($sdk)->run( (array) $restored );
                self::assertTrue( $response->succeeded() , $response->status() );
                self::assertEquals($plainText, $response->raw());
                ///////////////////

                // Revoke all access to key.
                $response = RevokeKey::with($sdk)->runWith( $encryptedObject->token );
                self::assertTrue( $response->succeeded() );

                // Delete user
                $response = DeleteUser::with($sdk)->run();
                self::assertTrue( $response->succeeded() );
                self::assertIsNotObject( $sdk->getCache()->getActiveProfile());
            }
            else {
                self::fail($response->status());
            }
        }
        catch (Exception $e){
            self::fail($e->getMessage());
        }
    }

    public function testFileEncryption(): void
    {

        try {

            // Request access for an alias.
            $sdk = new XQSDK();
            $primary = 'test-'.bin2hex(random_bytes ( 6 ));
            $response = RequestAliasAccess::with($sdk)->run( [
                'user' => $primary ] );

            if ($response->succeeded()) {

                // Was the active profile set successfully?
                $savedProfile = $sdk->getCache()->getActiveProfile();
                self::assertEquals($primary, $savedProfile);

                /// Test file.
                $sourcePath = __DIR__ . DIRECTORY_SEPARATOR . "Sample.txt";
                $source = File::uploaded([
                    "name" => "Sample.txt",
                    "tmp_name" => $sourcePath,
                    "type" => mime_content_type($sourcePath),
                    "error" => UPLOAD_ERR_OK,
                    "size" => filesize( $sourcePath )
                ]);

                $targetPath =  __DIR__ . DIRECTORY_SEPARATOR . "Sample-encrypted.txt.xqf";
                if (file_exists($targetPath)) {
                    unlink($targetPath);
                }

                $response = EncryptFile::with($sdk)->runWith($source, $targetPath, $primary, null,4 );

                self::assertTrue( $response->succeeded() , $response->status() );
                $encryptedContent = $response->raw();
                self::assertNotEquals( '', $encryptedContent,  "No encrypted data found."  );
                self::assertFileExists($targetPath);

                $target = File::uploaded([
                    "name" => "Sample-encrypted.txt.xqf",
                    "tmp_name" => $targetPath,
                    "error" => UPLOAD_ERR_OK,
                    "size" => filesize( $targetPath )
                ]);

                $outputPath =  __DIR__ . DIRECTORY_SEPARATOR . "Sample-decrypted.txt";

                // Confirm that we can decrypt the file.
                $response = DecryptFile::with($sdk)->runWith( $target, $outputPath );
                self::assertTrue( $response->succeeded() , $response->status() );
                self::assertFileEquals(
                    $outputPath, $sourcePath, "Decrypted content does not match original.");
                ///////////////////

                // Delete user
                $response = DeleteUser::with($sdk)->run();
                self::assertTrue( $response->succeeded() );
                self::assertIsNotObject( $sdk->getCache()->getActiveProfile());
            }
            else {
                self::fail($response->status());
            }
        }
        catch (Exception $e){
            self::fail($e->getMessage());
        }
    }

}