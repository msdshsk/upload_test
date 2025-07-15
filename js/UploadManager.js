/**
 * チャンクアップロードマネージャー
 */
class UploadManager {
    constructor(config = {}) {
        this.config = {
            endpoint: 'upload.php',
            chunkSize: 1024 * 1024, // 1MB
            maxConcurrentUploads: 3,
            retryCount: 3,
            retryDelay: 1000,
            ...config
        };
        
        this.activeUploads = new Map();
        this.uploadQueue = [];
        this.isProcessing = false;
    }

    /**
     * ファイルアップロードを開始
     */
    async uploadFile(file, progressCallback = null) {
        if (!file || file.size === 0) {
            throw new Error('Invalid file');
        }

        try {
            const uuid = await this.generateUuid();
            const chunks = this.createChunks(file, this.config.chunkSize);
            
            const uploadTask = {
                uuid,
                file,
                chunks,
                progressCallback,
                uploadedChunks: 0,
                totalChunks: chunks.length,
                status: 'pending'
            };

            this.activeUploads.set(uuid, uploadTask);
            this.uploadQueue.push(uploadTask);

            if (!this.isProcessing) {
                this.processQueue();
            }

            return uuid;

        } catch (error) {
            console.error('Upload failed:', error);
            throw error;
        }
    }

    /**
     * UUIDの生成
     */
    async generateUuid() {
        const response = await fetch(this.config.endpoint, {
            method: 'GET',
            cache: 'no-cache'
        });

        if (!response.ok) {
            throw new Error('Failed to generate UUID');
        }

        const data = await response.json();
        
        // レスポンス形式に応じたUUID取得
        if (data.success && data.data && data.data.uuid) {
            return data.data.uuid;
        } else if (data.uuid) {
            return data.uuid;
        } else {
            throw new Error('No UUID found in response');
        }
    }

    /**
     * ファイルをチャンクに分割
     */
    createChunks(file, chunkSize) {
        const chunks = [];
        let start = 0;
        let index = 0;

        while (start < file.size) {
            const end = Math.min(start + chunkSize, file.size);
            const chunk = file.slice(start, end);
            
            chunks.push({
                index: index++,
                data: chunk,
                size: chunk.size,
                start,
                end
            });

            start = end;
        }

        return chunks;
    }

    /**
     * アップロードキューを処理
     */
    async processQueue() {
        if (this.isProcessing || this.uploadQueue.length === 0) {
            return;
        }

        this.isProcessing = true;

        try {
            const concurrentUploads = [];
            
            while (this.uploadQueue.length > 0 && concurrentUploads.length < this.config.maxConcurrentUploads) {
                const uploadTask = this.uploadQueue.shift();
                concurrentUploads.push(this.processUpload(uploadTask));
            }

            await Promise.all(concurrentUploads);
            
            if (this.uploadQueue.length > 0) {
                await this.processQueue();
            }

        } finally {
            this.isProcessing = false;
        }
    }

    /**
     * 個別のアップロード処理
     */
    async processUpload(uploadTask) {
        try {
            uploadTask.status = 'uploading';
            
            // チャンクを並列でアップロード
            const chunkPromises = uploadTask.chunks.map(chunk => 
                this.uploadChunk(uploadTask.uuid, chunk, uploadTask)
            );

            await Promise.all(chunkPromises);

            // ファイル完了を通知
            await this.completeUpload(uploadTask.uuid, uploadTask.file.name);
            
            uploadTask.status = 'completed';
            console.log(`Upload completed: ${uploadTask.file.name}`);

        } catch (error) {
            uploadTask.status = 'failed';
            console.error('Upload failed:', error);
            throw error;
        }
    }

    /**
     * チャンクをアップロード
     */
    async uploadChunk(uuid, chunk, uploadTask) {
        let retryCount = 0;

        while (retryCount < this.config.retryCount) {
            try {
                const formData = new FormData();
                formData.append('uuid', uuid);
                formData.append('index', chunk.index.toString());
                formData.append('data', chunk.data, uploadTask.file.name);

                const response = await fetch(this.config.endpoint, {
                    method: 'PUT',
                    body: formData,
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();
                
                if (result.success) {
                    uploadTask.uploadedChunks++;
                    
                    // プログレスコールバックを呼び出し
                    if (uploadTask.progressCallback) {
                        uploadTask.progressCallback(
                            uuid,
                            uploadTask.file.name,
                            uploadTask.uploadedChunks,
                            uploadTask.totalChunks
                        );
                    }
                    
                    return result;
                } else {
                    const errorMessage = result.error?.message || result.message || 'Upload failed';
                    throw new Error(errorMessage);
                }

            } catch (error) {
                retryCount++;
                
                if (retryCount >= this.config.retryCount) {
                    throw error;
                }

                // リトライ前の待機
                await new Promise(resolve => setTimeout(resolve, this.config.retryDelay));
            }
        }
    }

    /**
     * アップロード完了を通知
     */
    async completeUpload(uuid, fileName) {
        const formData = new FormData();
        formData.append('uuid', uuid);
        formData.append('name', fileName);

        const response = await fetch(this.config.endpoint, {
            method: 'PUT',
            body: formData,
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        
        if (!result.success) {
            const errorMessage = result.error?.message || result.message || 'Upload completion failed';
            throw new Error(errorMessage);
        }

        return result;
    }

    /**
     * アップロード状況を取得
     */
    getUploadStatus(uuid) {
        return this.activeUploads.get(uuid);
    }

    /**
     * アップロードをキャンセル
     */
    cancelUpload(uuid) {
        const uploadTask = this.activeUploads.get(uuid);
        if (uploadTask) {
            uploadTask.status = 'cancelled';
            this.activeUploads.delete(uuid);
        }
    }

    /**
     * すべてのアップロードをキャンセル
     */
    cancelAll() {
        this.activeUploads.clear();
        this.uploadQueue.length = 0;
    }
} 