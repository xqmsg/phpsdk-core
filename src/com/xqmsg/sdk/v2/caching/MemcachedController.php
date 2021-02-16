<?php namespace com\xqmsg\sdk\v2\caching;


use com\xqmsg\sdk\v2\util\StatusCodes;
use Config;
use Memcached;
use com\xqmsg\sdk\v2\exceptions\StatusCodeException;

class MemcachedController implements CacheController {

    private const DASHBOARD_PREFIX = 'dash/';
    private const XQ_PREFIX = 'xq/';
    private const EXCHANGE_PREFIX = 'exchange/';
    private const ACTIVE_PROFILE_KEY = 'active_profile';
    private const PROFILE_LIST_KEY = 'available_profiles';

    private Memcached $cache;

    /**
     * MemcachedController constructor.
     */
    public function __construct()
    {
        $this->cache = new Memcached();
        $this->cache->addServer(Config::CACHE_SERVER_URL, Config::CACHE_SERVER_PORT );
    }

    /**
     * @inheritDoc
     */
    public function addDashboardPreauth(string $user, string $token) : CacheController {
        $this->cache->add(self::EXCHANGE_PREFIX.self::DASHBOARD_PREFIX.$user, $token );
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function clearDashboardPreauth(string $user) : void {
        $this->cache->delete(self::EXCHANGE_PREFIX.self::DASHBOARD_PREFIX.$user );
    }

    /**
     * @inheritDoc
     */
    public function addXQPreauth(string $user, string $token) : CacheController {
        $this->cache->add(self::EXCHANGE_PREFIX.self::XQ_PREFIX.$user, $token );
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function clearXQPreauth(string $user) : void {
        $this->cache->delete(self::EXCHANGE_PREFIX.self::XQ_PREFIX.$user);
    }

    /**
     * @inheritDoc
     */
    public function addDashboardAccess(string $user, string $token) : CacheController {
        $this->cache->set(self::DASHBOARD_PREFIX.$user, $token );
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function clearDashboardAccess(string $user) : void {
        $this->cache->delete(self::DASHBOARD_PREFIX.$user );
    }

    /**
     * @inheritDoc
     */
    public function addXQAccess(string $user, string $token) : CacheController {
        $this->cache->set(self::XQ_PREFIX.$user, $token );
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function clearXQAccess(string $user) : void {
        $this->cache->delete(self::XQ_PREFIX.$user );
    }

    /**
     * @inheritDoc
     */
    public function getDashboardPreauth(string $user) : ?string {
        $res = $this->cache->get(self::EXCHANGE_PREFIX.self::DASHBOARD_PREFIX.$user);
        return (!$res ) ? null : $res;
    }

    /**
     * @inheritDoc
     */
    public function getXQPreauth(string $user) : ?string {
        $res = $this->cache->get(self::EXCHANGE_PREFIX.self::XQ_PREFIX.$user);
        return (!$res ) ? null : $res;
    }

    /**
     * @inheritDoc
     */
    public function getDashboardAccess(string $user, bool $required = false ) : ?string {
        $res = $this->cache->get(self::DASHBOARD_PREFIX.$user);
        if ($required && !$res ) {
            throw new StatusCodeException(
                "No access token available for selected profile.",
                StatusCodes::HTTP_UNAUTHORIZED
            );
        }
        return (!$res ) ? null : $res;
    }

    /**
     * @inheritDoc
     */
    public function getXQAccess(string $user, bool $required = false ) : ?string {
        $res = $this->cache->get(self::XQ_PREFIX.$user);
        if ($required && !$res ) {
            throw new StatusCodeException(
                "No access token available for selected profile.",
                StatusCodes::HTTP_UNAUTHORIZED
            );
        }
        return (!$res ) ? null : $res;
    }

    /**
     * @inheritDoc
     */
    public function hasProfile(string $user) : bool {
        if ( $list = $this->cache->get(self::PROFILE_LIST_KEY ) ) {
            return in_array($user, $list, true);
        }
        return false;
    }

    /**
     * @inheritDoc
     */
    public function setActiveProfile(string $user) : void  {
        if ( $list = $this->cache->get(self::PROFILE_LIST_KEY ) ) {
            $list[] = $user;
            $this->cache->set(self::PROFILE_LIST_KEY, $list );
        }
        else {
            $this->cache->set(self::PROFILE_LIST_KEY, array($user) );
        }
        $this->cache->set(self::ACTIVE_PROFILE_KEY, $user );
    }

    /**
     * @inheritDoc
     */
    public function getActiveProfile(bool $required = false ) : ?string {
        $res = $this->cache->get( self::ACTIVE_PROFILE_KEY );
        if ($required && !$res ) {
            throw new StatusCodeException(
                "You do not have an active profile set.",
                StatusCodes::HTTP_UNAUTHORIZED
            );
        }
        return (!$res ) ? null : $res;
    }

    /**
     * @inheritDoc
     */
    public function clearAllProfiles(): void
    {
        if ( $list = $this->cache->get(self::PROFILE_LIST_KEY ) ) {
            foreach ($list as $user) {
                $this->cache->delete(self::XQ_PREFIX . $user );
                $this->cache->delete(self::DASHBOARD_PREFIX . $user );
                $this->cache->delete(self::EXCHANGE_PREFIX.self::DASHBOARD_PREFIX . $user );
                $this->cache->delete(self::EXCHANGE_PREFIX.self::XQ_PREFIX. $user );
            }
        }
        $this->cache->delete( self::ACTIVE_PROFILE_KEY );
        $this->cache->delete( self::PROFILE_LIST_KEY );
    }

    /**
     * @inheritDoc
     */
    public function listProfiles(): array {
        if ( $list = $this->cache->get(self::PROFILE_LIST_KEY ) ) {
            return $list;
        }
        return [];
    }

    /**
     * @inheritDoc
     */
    public function clearProfile(string $user) : void {

        $this->cache->delete(self::XQ_PREFIX . $user );
        $this->cache->delete(self::DASHBOARD_PREFIX . $user );
        $this->cache->delete(self::EXCHANGE_PREFIX.self::DASHBOARD_PREFIX . $user );
        $this->cache->delete(self::EXCHANGE_PREFIX.self::XQ_PREFIX. $user );

        if ( $list = $this->cache->get(self::PROFILE_LIST_KEY ) ) {
            $list = array_diff($list, [$user]);
            $this->cache->set(self::PROFILE_LIST_KEY, $list );
            if (!empty($list)) {
                $this->setActiveProfile(current($list));
            }
        }
    }
}