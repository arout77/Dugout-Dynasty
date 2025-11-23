<?php

namespace Core\Cache;

interface CacheInterface
{
    /**
     * @param string $key
     * @param $default
     */
    public function get( string $key, $default = null );
    /**
     * @param string $key
     * @param $value
     * @param int $minutes
     */
    public function put( string $key, $value, int $minutes ): void;
    public function has( string $key ): bool;
    public function forget( string $key ): void;
    public function flush(): bool;
}
