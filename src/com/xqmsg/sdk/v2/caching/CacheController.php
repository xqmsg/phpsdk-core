<?php namespace com\xqmsg\sdk\v2\caching;

use com\xqmsg\sdk\v2\exceptions\StatusCodeException;

/**
 * Interface CacheController
 * @package com\xqmsg\sdk\v2\caching
 */
interface CacheController {

    /**
     * @param string $user
     * @param string $token
     * @return CacheController
     */
    public function addDashboardPreauth(string $user, string $token) : CacheController;

    /**
     * @param string $user
     */
    public function clearDashboardPreauth(string $user) : void;

    /**
     * @param string $user
     * @param string $token
     * @return CacheController
     */
    public function addXQPreauth(string $user, string $token) : CacheController;

    /**
     * @param string $user
     */
    public function clearXQPreauth(string $user) : void;

    /**
     * @param string $user
     * @param string $token
     * @return CacheController
     */
    public function addDashboardAccess(string $user, string $token) : CacheController;

    /**
     * @param string $user
     */
    public function clearDashboardAccess(string $user) : void;

    /**
     * @param string $user
     * @param string $token
     * @return CacheController
     */
    public function addXQAccess(string $user, string $token) : CacheController;

    /**
     * @param string $user
     */
    public function clearXQAccess(string $user) : void;

    /**
     * @param string $user
     * @return string|null
     */
    public function getDashboardPreauth(string $user) : ?string;

    /**
     * @param string $user
     * @return string|null
     */
    public function getXQPreauth(string $user) : ?string;

    /**
     * @param string $user
     * @param bool $required
     * @return string|null
     * @throws StatusCodeException
     */
    public function getDashboardAccess(string $user, bool $required = false ) : ?string ;

    /**
     * @param string $user
     * @param bool $required
     * @return string|null
     * @throws StatusCodeException
     */
    public function getXQAccess(string $user, bool $required = false ) : ?string ;

    /**
     * @param string $user
     * @return bool
     */
    public function hasProfile(string $user) : bool;

    /**
     * @param string $user
     */
    public function setActiveProfile(string $user) : void;

    /**
     * @param bool $required
     * @return string|null
     * @throws StatusCodeException
     */
    public function getActiveProfile(bool $required = false ) : ?string;

    /**
     *
     */
    public function clearAllProfiles(): void;

    /**
     * @return array
     */
    public function listProfiles(): array;

    /**
     * @param string $user
     */
    public function clearProfile(string $user) : void;
}