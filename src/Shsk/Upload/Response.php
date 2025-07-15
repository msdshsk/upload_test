<?php

namespace Shsk\Upload;

class Response
{
    /**
     * 成功レスポンスを作成
     */
    public function success(array $data = []): array
    {
        return [
            'success' => true,
            'data' => $data,
            'error' => null,
        ];
    }

    /**
     * エラーレスポンスを作成
     */
    public function error(string $message, int $code = 400): array
    {
        return [
            'success' => false,
            'data' => null,
            'error' => [
                'message' => $message,
                'code' => $code,
            ],
        ];
    }

    /**
     * レスポンスをJSONで出力
     */
    public function output(array $response): void
    {
        header('Content-Type: application/json');
        echo json_encode($response);
    }

    /**
     * 成功レスポンスを直接出力
     */
    public function outputSuccess(array $data = []): void
    {
        $this->output($this->success($data));
    }

    /**
     * エラーレスポンスを直接出力
     */
    public function outputError(string $message, int $code = 400): void
    {
        $this->output($this->error($message, $code));
    }
} 