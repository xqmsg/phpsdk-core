<?php

require_once __DIR__ . "/../src/AutoLoader.php";

use com\xqmsg\sdk\v2\enums\NotificationType;
use com\xqmsg\sdk\v2\services\AddKey;
use com\xqmsg\sdk\v2\services\CheckKeyExpiration;
use com\xqmsg\sdk\v2\services\DelegateAccess;
use com\xqmsg\sdk\v2\services\GetUserSettings;
use com\xqmsg\sdk\v2\services\GrantKeyAccess;
use com\xqmsg\sdk\v2\services\MergeTokens;
use com\xqmsg\sdk\v2\services\RequestAccess;
use com\xqmsg\sdk\v2\services\RevokeKey;
use com\xqmsg\sdk\v2\services\DeleteUser;
use com\xqmsg\sdk\v2\services\RetrieveKey;
use com\xqmsg\sdk\v2\services\RevokeKeyAccess;
use com\xqmsg\sdk\v2\services\UpdateUserSettings;
use com\xqmsg\sdk\v2\services\ValidateAccessRequest;
use PHPUnit\Framework\TestCase;
use com\xqmsg\sdk\v2\services\RequestAliasAccess;
use com\xqmsg\sdk\v2\XQSDK;

class WorkflowTest extends TestCase {
    public function testAuthorizeAlias(): void
    {

        try {
            // Request access for an alias.
            $sdk = new XQSDK();
            $primary = 'testing-'.bin2hex(random_bytes ( 6 )) . '@xqmsg.com';
            $response = RequestAliasAccess::with($sdk)->run( [
                'user' => $primary ] );

            if ($response->succeeded()) {

                // Was the active profile set successfully?
                $savedProfile = $sdk->getCache()->getActiveProfile();
                self::assertEquals($primary, $savedProfile);

                /// Add a key to the server.
                $secret = "XQ-KEY_TEST-" . bin2hex(random_bytes ( 20 ));
                $response = AddKey::with($sdk)->runWith(
                    $secret,
                    $primary,
                    4 ,
                    AddKey::TYPE_EMAIL,
                    ['subject' => 'My Unit Test'] );

                self::assertTrue( $response->succeeded() , $response->status() );
                $token = $response->raw();
                self::assertGreaterThanOrEqual( 43, strlen($token) , "An invalid token was retrieved.");

                // Confirm that we can retrieve the key.
                $response = RetrieveKey::with($sdk)->runWith($token);
                self::assertTrue( $response->succeeded(), "Unable to read key with primary profile: " . $response->status()  );
                self::assertEquals($secret, $response->raw(), "An invalid secret was retrieved.");

                // Check the key expiration.
                $response = CheckKeyExpiration::with($sdk)->runWith($token);
                self::assertTrue( $response->succeeded(), "Unable to check key expiration date." );
                self::assertGreaterThan(0, $response->json()->remaining, "Key expiration date is invalid: " . $response->status()  );

                {
                    // Create a second user to test other features.
                    $secondary = 'test-'.bin2hex(random_bytes ( 8 ));
                    $response = RequestAliasAccess::with($sdk)->runWith( $secondary  );
                    self::assertTrue( $response->succeeded(), "Failed to get aliased user access: " . $response->status()  );

                    // Get the actual address of the secondary user. We will neeed this
                    // in order to grant an aliased user access.
                    // With a normal login, this is not necessary, simply use the same value used for
                    // requesting access.
                    $response = GetUserSettings::with($sdk)->run();
                    self::assertTrue($response->succeeded(), "Failed to get aliased user settings: " . $response->status() );
                    $targetUser = $response->json()->user;

                    // Confirm that secondary user cannot read previously created key..
                    $response = RetrieveKey::with($sdk)->runWith($token);
                    self::assertFalse( $response->succeeded(), "Was able to erroneously get key with secondary profile" );

                    // Grant secondary user access to previous token.
                    self::assertTrue( $sdk->switchProfile($primary), "Failed to switch to primary profile");
                    $response = GrantKeyAccess::with($sdk)->runWith($token, array($targetUser));
                    self::assertTrue( $response->succeeded(), "Failed to grant key access to secondary profile: " . $response->status()  );

                    // Check that secondary user is now able to read key.
                    self::assertTrue($sdk->switchProfile($secondary), "Failed to switch to secondary profile: " . $response->status()  );
                    $response = RetrieveKey::with($sdk)->runWith($token);
                    self::assertTrue( $response->succeeded(), "Failed to get key with secondary profile: " . $response->status()  );

                    // Delete the secondary user
                    $response = DeleteUser::with($sdk)->run();
                    self::assertTrue( $response->succeeded(), "Failed to delete secondary profile: " . $response->status()  );

                    // Revoke the previously granted key access from the (now deleted) secondary user.
                    $sdk->switchProfile($primary);
                    $response = RevokeKeyAccess::with($sdk)->runWith($token, array($targetUser));
                    self::assertTrue( $response->succeeded(), "Failed to revoke key access: " . $response->status()  );

                }

                // Revoke all access to key.
                $response = RevokeKey::with($sdk)->runWith($token);
                self::assertTrue( $response->succeeded() );

                // Test that access was correctly revoked.
                $response = RetrieveKey::with($sdk)->runWith($token);
                self::assertFalse( $response->succeeded() );

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

    public function testAuthorize(): void
    {
        $this->markTestSkipped('This test can only be run on enterprise installations in test mode.');

        try {
            // Request access for an alias.
            $sdk = new XQSDK();
            $primary = 'test-'.bin2hex(random_bytes ( 6 )) . '@xqmsg.com';
            $response = RequestAccess::with($sdk)->run( [
                'user' => $primary,
                'newsletter' => false,
                'notifications' => NotificationType::None,
                'quiet' => true
            ] );

            self::assertTrue($response->succeeded(), "Failed to request access.");

            // Exchange the code and token for a new access token.
            $json = $response->json();
            $response = ValidateAccessRequest::with($sdk)->run([ ValidateAccessRequest::PIN => $json->code ]);
            self::assertTrue($response->succeeded(), "Failed to validate access request with PIN");

            {
                // Was the active profile set successfully?
                $savedProfile = $sdk->getCache()->getActiveProfile();
                self::assertEquals($primary, $savedProfile);

                /// Add a key to the server.
                $secret = "XQ-KEY_TEST-" . bin2hex(random_bytes ( 20 ));
                $response = AddKey::with($sdk)->runWith(
                    $secret,
                    $primary,
                    4 ,
                    AddKey::TYPE_EMAIL,
                    ['subject' => 'My Unit Test'] );

                self::assertTrue( $response->succeeded() );
                $token = $response->raw();
                self::assertGreaterThanOrEqual( 43, strlen($token) , "An invalid token was retrieved.");

                // Confirm that we can retrieve the key.
                $response = RetrieveKey::with($sdk)->runWith($token);
                self::assertTrue( $response->succeeded(), "Unable to read key with primary profile." );
                self::assertEquals($secret, $response->raw(), "An invalid secret was retrieved.");

                // Check the key expiration.
                $response = CheckKeyExpiration::with($sdk)->runWith($token);
                self::assertTrue( $response->succeeded(), "Unable to check key expiration date." );
                self::assertGreaterThan(0, $response->json()->remaining, "Key expiration date is invalid." );

                // Test access delegation. The token received here can be used
                // to access XQ services with the same user profile, but only for an extremely limited
                // amount of time.
                $response = DelegateAccess::with($sdk)->run();
                self::assertTrue($response->succeeded(), "Failed to delegate authorization: " . $response->status());
                $delegatedToken = $response->raw();
                $response = RetrieveKey::with($sdk)->runWith($token, $delegatedToken);
                self::assertTrue( $response->succeeded(), "Unable to read key with primary profile: " . $response->status() );
                self::assertEquals($secret, $response->raw(), "An invalid secret was retrieved.");

                // A simple test here to confirm that we can update settings ( although we won't actually
                // change anything.
                $response = UpdateUserSettings::with($sdk)->runWith(false,  NotificationType::None);
                self::assertTrue($response->succeeded(), "Failed to update user settings: " . $response->status() );

                // Combine all existing tokens and attempt to retrieve the same key with the result.
                // Calling run without any tokens will automatically attempt to merge all available profile tokens.
                $response = MergeTokens::with($sdk)->run();
                self::assertTrue( $response->succeeded(), "Failed to merge tokens: " . $response->status() );
                $response = RetrieveKey::with($sdk)->runWith($token, $response->json()->token);
                self::assertTrue( $response->succeeded(), "Failed to get key with merged token: " . $response->status() );


                // Create a second user to test other features.
                $secondary = 'test-'.bin2hex(random_bytes ( 8 ));
                $response = RequestAliasAccess::with($sdk)->runWith( $secondary  );
                self::assertTrue( $response->succeeded(), "Failed to get aliased user access: " . $response->status() );

                // Get the actual address of the secondary user. We will neeed this
                // in order to grant an aliased user access.
                // With a normal login, this is not necessary, simply use the same value used for
                // requesting access.
                $response = GetUserSettings::with($sdk)->run();
                self::assertTrue($response->succeeded(), "Failed to get aliased user settings: " . $response->status());
                $targetUser = $response->json()->user;

                // Confirm that secondary user cannot read previously created key..
                $response = RetrieveKey::with($sdk)->runWith($token);
                self::assertFalse( $response->succeeded(), "Was able to erroneously get key with secondary profile: " . $response->status() );

                // Grant secondary user access to previous token.
                self::assertTrue( $sdk->switchProfile($primary), "Failed to switch to primary profile: " . $response->status());
                $response = GrantKeyAccess::with($sdk)->runWith($token, array($targetUser));
                self::assertTrue( $response->succeeded(), "Failed to grant key access to secondary profile: " . $response->status() );

                // Check that secondary user is now able to read key.
                self::assertTrue($sdk->switchProfile($secondary), "Failed to switch to secondary profile: " . $response->status() );
                $response = RetrieveKey::with($sdk)->runWith($token);
                self::assertTrue( $response->succeeded(), "Failed to get key with secondary profile: " . $response->status() );


                // Delete the secondary user
                $response = DeleteUser::with($sdk)->run();
                self::assertTrue( $response->succeeded(), "Failed to delete secondary profile: " . $response->status() );

                // Revoke the previously granted key access from the (now deleted) secondary user.
                $sdk->switchProfile($primary);
                $response = RevokeKeyAccess::with($sdk)->runWith($token, array($targetUser));
                self::assertTrue( $response->succeeded(), "Failed to revoke key access: " . $response->status() );

            }

            // Revoke all access to key.
            $response = RevokeKey::with($sdk)->runWith($token);
            self::assertTrue( $response->succeeded() , $response->status() );

            // Test that access was correctly revoked.
            $response = RetrieveKey::with($sdk)->runWith($token);
            self::assertFalse( $response->succeeded() , $response->status()  );

            // Delete user
            $response = DeleteUser::with($sdk)->run();
            self::assertTrue( $response->succeeded(), $response->status()  );
            self::assertIsNotObject( $sdk->getCache()->getActiveProfile());
        }
        catch (Exception $e){
            self::fail($e->getMessage());
        }
    }
}