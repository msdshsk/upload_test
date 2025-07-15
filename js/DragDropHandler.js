/**
 * ドラッグ&ドロップハンドラー
 */
class DragDropHandler {
    constructor(dropZoneId, config = {}) {
        this.dropZone = document.getElementById(dropZoneId);
        this.config = {
            allowedTypes: ['*'],
            maxFileSize: 100 * 1024 * 1024, // 100MB
            maxFiles: 10,
            ...config
        };
        
        this.dragCounter = 0;
        this.onDropCallback = null;
        this.onErrorCallback = null;
        
        if (!this.dropZone) {
            throw new Error(`Drop zone with id '${dropZoneId}' not found`);
        }
        
        this.init();
    }

    /**
     * イベントリスナーを初期化
     */
    init() {
        this.dropZone.addEventListener('dragenter', (e) => this.handleDragEnter(e));
        this.dropZone.addEventListener('dragover', (e) => this.handleDragOver(e));
        this.dropZone.addEventListener('dragleave', (e) => this.handleDragLeave(e));
        this.dropZone.addEventListener('drop', (e) => this.handleDrop(e));
        
        // クリックでファイル選択
        this.dropZone.addEventListener('click', () => this.openFileDialog());
    }

    /**
     * ドラッグエンター処理
     */
    handleDragEnter(e) {
        e.preventDefault();
        e.stopPropagation();
        
        this.dragCounter++;
        this.dropZone.classList.add('drag-over');
    }

    /**
     * ドラッグオーバー処理
     */
    handleDragOver(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // ドラッグ効果を設定
        e.dataTransfer.dropEffect = 'copy';
    }

    /**
     * ドラッグリーブ処理
     */
    handleDragLeave(e) {
        e.preventDefault();
        e.stopPropagation();
        
        this.dragCounter--;
        
        if (this.dragCounter === 0) {
            this.dropZone.classList.remove('drag-over');
        }
    }

    /**
     * ドロップ処理
     */
    handleDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        
        this.dragCounter = 0;
        this.dropZone.classList.remove('drag-over');
        
        const files = Array.from(e.dataTransfer.files);
        this.processFiles(files);
    }

    /**
     * ファイルダイアログを開く
     */
    openFileDialog() {
        const input = document.createElement('input');
        input.type = 'file';
        input.multiple = true;
        input.accept = this.getAcceptString();
        
        input.addEventListener('change', (e) => {
            const files = Array.from(e.target.files);
            this.processFiles(files);
        });
        
        input.click();
    }

    /**
     * ファイルを処理
     */
    processFiles(files) {
        if (files.length === 0) {
            return;
        }

        // ファイル数チェック
        if (files.length > this.config.maxFiles) {
            this.handleError(`最大${this.config.maxFiles}個のファイルまでアップロードできます。`);
            return;
        }

        const validFiles = [];
        const errors = [];

        for (const file of files) {
            const validation = this.validateFile(file);
            if (validation.valid) {
                validFiles.push(file);
            } else {
                errors.push(`${file.name}: ${validation.error}`);
            }
        }

        // エラーがある場合は表示
        if (errors.length > 0) {
            this.handleError(errors.join('\n'));
        }

        // 有効なファイルがある場合は処理
        if (validFiles.length > 0 && this.onDropCallback) {
            this.onDropCallback(validFiles);
        }
    }

    /**
     * ファイルをバリデーション
     */
    validateFile(file) {
        // ファイルサイズチェック
        if (file.size > this.config.maxFileSize) {
            return {
                valid: false,
                error: `ファイルサイズが上限を超えています (${this.formatFileSize(this.config.maxFileSize)})`
            };
        }

        // ファイルタイプチェック
        if (!this.isAllowedType(file)) {
            return {
                valid: false,
                error: 'このファイルタイプはサポートされていません'
            };
        }

        return { valid: true };
    }

    /**
     * ファイルタイプが許可されているかチェック
     */
    isAllowedType(file) {
        if (this.config.allowedTypes.includes('*')) {
            return true;
        }

        const extension = file.name.split('.').pop().toLowerCase();
        const mimeType = file.type;

        return this.config.allowedTypes.some(type => {
            if (type.startsWith('.')) {
                return type.slice(1).toLowerCase() === extension;
            }
            return mimeType.includes(type);
        });
    }

    /**
     * acceptアトリビュート用の文字列を取得
     */
    getAcceptString() {
        if (this.config.allowedTypes.includes('*')) {
            return '*/*';
        }

        return this.config.allowedTypes.map(type => {
            if (type.startsWith('.')) {
                return type;
            }
            return type + '/*';
        }).join(',');
    }

    /**
     * ファイルサイズをフォーマット
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * エラーハンドリング
     */
    handleError(message) {
        if (this.onErrorCallback) {
            this.onErrorCallback(message);
        } else {
            console.error('DragDropHandler Error:', message);
            alert(message);
        }
    }

    /**
     * ドロップコールバックを設定
     */
    setOnDropCallback(callback) {
        this.onDropCallback = callback;
    }

    /**
     * エラーコールバックを設定
     */
    setOnErrorCallback(callback) {
        this.onErrorCallback = callback;
    }

    /**
     * 設定を更新
     */
    updateConfig(newConfig) {
        this.config = { ...this.config, ...newConfig };
    }

    /**
     * 破棄
     */
    destroy() {
        this.dropZone.removeEventListener('dragenter', this.handleDragEnter);
        this.dropZone.removeEventListener('dragover', this.handleDragOver);
        this.dropZone.removeEventListener('dragleave', this.handleDragLeave);
        this.dropZone.removeEventListener('drop', this.handleDrop);
        this.dropZone.removeEventListener('click', this.openFileDialog);
        
        this.dropZone.classList.remove('drag-over');
    }
} 