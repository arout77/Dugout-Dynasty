<?php

namespace Core;

class FileUploader
{
    protected string $uploadDir;
    protected array $allowedMimes = [];
    protected int $maxSize; // in bytes
    protected array $errors        = [];
    protected array $uploadedFiles = [];

    /**
     * @param string $uploadDir
     */
    public function __construct( string $uploadDir = 'storage/uploads/' )
    {
        $this->uploadDir = rtrim( $uploadDir, '/' ) . '/';
        $this->maxSize   = 2 * 1024 * 1024;
    }

    /**
     * @param array $mimes
     * @return mixed
     */
    public function setAllowedMimes( array $mimes ): self
    {
        $this->allowedMimes = $mimes;
        return $this;
    }

    /**
     * @param int $bytes
     * @return mixed
     */
    public function setMaxSize( int $bytes ): self
    {
        $this->maxSize = $bytes;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * @return mixed
     */
    public function getUploadedFiles(): array {
        return $this->uploadedFiles;
    }

    /**
     * @param string $fileInputName
     */
    public function handle( string $fileInputName ): bool
    {
        if ( empty( $_FILES[$fileInputName] ) )
        {
            $this->errors[] = "No files were uploaded for '{$fileInputName}'.";
            return false;
        }

        $files = $this->normalizeFiles( $_FILES[$fileInputName] );

        foreach ( $files as $file )
        {
            if ( $this->validate( $file ) )
            {
                $this->move( $file );
            }
        }

        return empty( $this->errors );
    }

    /**
     * @param array $file
     */
    protected function validate( array $file ): bool
    {
        // Now using a more reliable MIME type check
        $finfo    = new \finfo( FILEINFO_MIME_TYPE );
        $mimeType = $finfo->file( $file['tmp_name'] );

        if ( $file['error'] !== UPLOAD_ERR_OK )
        {
            $this->errors[$file['name']][] = 'An error occurred during upload.';
            return false;
        }
        if ( $file['size'] > $this->maxSize )
        {
            $this->errors[$file['name']][] = 'File is too large.';
            return false;
        }
        if ( !empty( $this->allowedMimes ) && !in_array( $mimeType, $this->allowedMimes ) )
        {
            $this->errors[$file['name']][] = 'Invalid file type.';
            return false;
        }
        return true;
    }

    /**
     * @param array $file
     */
    protected function move( array $file ): void
    {
        $extension   = pathinfo( $file['name'], PATHINFO_EXTENSION );
        $newFilename = uniqid( '', true ) . '.' . $extension;
        $destination = $this->uploadDir . $newFilename;

        if ( move_uploaded_file( $file['tmp_name'], $destination ) )
        {
            $this->uploadedFiles[] = $newFilename;
        }
        else
        {
            $this->errors[$file['name']][] = 'Failed to move uploaded file.';
        }
    }

    /**
     * @param array $files
     * @return mixed
     */
    protected function normalizeFiles( array $files ): array {
        $normalized = [];
        if ( is_array( $files['name'] ) )
        {
            foreach ( $files['name'] as $index => $name )
            {
                $normalized[] = [
                    'name'     => $name,
                    'type'     => $files['type'][$index],
                    'tmp_name' => $files['tmp_name'][$index],
                    'error'    => $files['error'][$index],
                    'size'     => $files['size'][$index],
                ];
            }
        }
        else
        {
            $normalized[] = $files;
        }
        return $normalized;
    }
}
