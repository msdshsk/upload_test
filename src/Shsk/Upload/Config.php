<?php

namespace Shsk\Upload;

class Config
{
    private array $config = [
        'upload_dir' => './uploads/',
        'temp_dir' => './temp/',
        'max_chunk_size' => 1024 * 1024 * 10, // 10MB
        'max_file_size' => 1024 * 1024 * 100, // 100MB
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip'],
        'chunk_prefix' => 'chunk_',
        'auto_create_dirs' => true,
    ];

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
        
        // パスを正規化（末尾のスラッシュを統一）
        $this->config['upload_dir'] = rtrim($this->config['upload_dir'], '/') . '/';
        $this->config['temp_dir'] = rtrim($this->config['temp_dir'], '/') . '/';
    }

    public function getUploadDir(): string
    {
        return $this->config['upload_dir'];
    }

    public function getMaxChunkSize(): int
    {
        return $this->config['max_chunk_size'];
    }

    public function getMaxFileSize(): int
    {
        return $this->config['max_file_size'];
    }

    public function getAllowedExtensions(): array
    {
        return $this->config['allowed_extensions'];
    }

    public function getChunkPrefix(): string
    {
        return $this->config['chunk_prefix'];
    }

    public function getTempDir(): string
    {
        return $this->config['temp_dir'];
    }

    public function getAutoCreateDirs(): bool
    {
        return $this->config['auto_create_dirs'];
    }

    public function isAllowedExtension(string $extension): bool
    {
        return in_array(strtolower($extension), $this->getAllowedExtensions());
    }

    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $this->config[$key] = $value;
    }
} 