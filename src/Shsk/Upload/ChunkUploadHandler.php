<?php

namespace Shsk\Upload;

use Shsk\Http\Request\PutRequestParser;
use Shsk\Upload\FileManager;
use Shsk\Upload\Config;
use Shsk\Upload\Response;
use Shsk\Upload\Logger;
use Shsk\Upload\Validator;
use Shsk\Upload\Exception\UploadException;

class ChunkUploadHandler
{
    private FileManager $fileManager;
    private Config $config;
    private Response $response;
    private Logger $logger;
    private Validator $validator;

    public function __construct(Config $config = null, Logger $logger = null)
    {
        $this->config = $config ?? new Config();
        $this->fileManager = new FileManager($this->config);
        $this->response = new Response();
        $this->logger = $logger ?? new Logger();
        $this->validator = new Validator($this->config);
    }

    /**
     * GETリクエストでUUIDを生成
     */
    public function handleGetRequest(): array
    {
        $uuid = $this->generateUuid();
        $this->logger->info('Generated UUID for new upload session', ['uuid' => $uuid]);
        return $this->response->success(['uuid' => $uuid]);
    }

    /**
     * PUTリクエストでチャンクまたはファイル完了を処理
     */
    public function handlePutRequest(): array
    {
        try {
            $parser = new PutRequestParser();
            $parser->parse('php://input', true);

            if (!isset($_POST['uuid'])) {
                $this->logger->warning('PUT request without UUID');
                return $this->response->error('UUID is required');
            }

            $uuid = $_POST['uuid'];
            $this->validator->validateUuid($uuid);

            // チャンクのアップロード
            if (isset($_FILES['data']) && $this->isUploadedFile($_FILES['data']['tmp_name'])) {
                return $this->handleChunkUpload($uuid);
            }

            // ファイルの結合
            if (isset($_POST['name'])) {
                return $this->handleFileCompletion($uuid, $_POST['name']);
            }

            $this->logger->warning('Invalid PUT request', ['uuid' => $uuid]);
            return $this->response->error('Invalid request');

        } catch (UploadException $e) {
            $this->logger->error('Upload error: ' . $e->getMessage(), ['uuid' => $_POST['uuid'] ?? 'unknown']);
            return $this->response->error($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error: ' . $e->getMessage(), ['uuid' => $_POST['uuid'] ?? 'unknown']);
            return $this->response->error('Internal server error');
        }
    }

    /**
     * チャンクアップロードの処理
     */
    private function handleChunkUpload(string $uuid): array
    {
        if (!isset($_POST['index'])) {
            $this->logger->warning('Chunk upload without index', ['uuid' => $uuid]);
            return $this->response->error('Index is required');
        }

        $index = $this->validator->validateChunkIndex($_POST['index']);
        $fileData = $_FILES['data'];

        $this->logger->debug('Processing chunk upload', [
            'uuid' => $uuid,
            'index' => $index,
            'size' => $fileData['size']
        ]);

        // バリデーション
        $this->validator->validateChunkData($fileData);

        $chunkPath = $this->fileManager->saveChunk($uuid, $index, $fileData['tmp_name']);
        
        $this->logger->info('Chunk saved successfully', ['uuid' => $uuid, 'index' => $index, 'path' => $chunkPath]);
        return $this->response->success(['chunk_saved' => true, 'path' => $chunkPath]);
    }

    /**
     * ファイル完了の処理
     */
    private function handleFileCompletion(string $uuid, string $fileName): array
    {
        $this->logger->info('Starting file completion', ['uuid' => $uuid, 'fileName' => $fileName]);
        
        // ファイル名のバリデーション
        $this->validator->validateFileName($fileName);
        
        $joinedFile = $this->fileManager->joinChunks($uuid, $fileName);
        
        $this->logger->info('File completed successfully', ['uuid' => $uuid, 'fileName' => $fileName, 'path' => $joinedFile]);
        
        return $this->response->success([
            'file_completed' => true,
            'path' => $joinedFile
        ]);
    }



    /**
     * UUIDの生成
     */
    private function generateUuid(): string
    {
        return uniqid('', true);
    }

    /**
     * アップロードファイルの確認
     */
    private function isUploadedFile(string $tmpFile): bool
    {
        return PutRequestParser::isUploadedFile($tmpFile);
    }
} 