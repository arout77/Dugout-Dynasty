<?php

namespace Core\Cache;

class FileCacheDriver implements CacheInterface
{
    protected string $cachePath = __DIR__ . '/../../storage/cache/app/';

    /**
     * @param string $key
     * @param $default
     * @return mixed
     */
    public function get( string $key, $default = null )
    {
        $path = $this->cachePath . md5( $key );
        if ( !file_exists( $path ) ) {
            return $default;
        }

        $data = unserialize( file_get_contents( $path ) );
        if ( time() > $data['expires'] ) {
            unlink( $path );
            return $default;
        }
        return $data['value'];
    }

    /**
     * @param string $key
     * @param $value
     * @param int $minutes
     */
    public function put( string $key, $value, int $minutes ): void
    {
        if ( !is_dir( $this->cachePath ) ) {
            mkdir( $this->cachePath, 0777, true );
        }
        $data = [
            'value'   => $value,
            'expires' => time() + ( $minutes * 60 ),
        ];
        file_put_contents( $this->cachePath . md5( $key ), serialize( $data ) );
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function has( string $key ): bool
    {
        return $this->get( $key ) !== null;
    }

    /**
     * @param string $key
     */
    public function forget( string $key ): void
    {
        $path = $this->cachePath . md5( $key );
        if ( file_exists( $path ) ) {
            unlink( $path );
        }
    }

    public function flush(): bool
    {
        $files = glob( $this->cachePath . '*' );
        foreach ( $files as $file ) {
            if ( is_file( $file ) ) {
                unlink( $file );
            }
        }
        return true;
    }
}
