/**
 * プログレスバーマネージャー
 */
class ProgressManager {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.progressBars = new Map();
        
        if (!this.container) {
            throw new Error(`Container with id '${containerId}' not found`);
        }
    }

    /**
     * プログレスバーを作成
     */
    createProgressBar(uuid, fileName) {
        if (this.progressBars.has(uuid)) {
            return this.progressBars.get(uuid);
        }

        const progressBar = new ProgressBar(uuid, fileName);
        this.container.appendChild(progressBar.element);
        this.progressBars.set(uuid, progressBar);
        
        return progressBar;
    }

    /**
     * プログレスを更新
     */
    updateProgress(uuid, fileName, current, total) {
        let progressBar = this.progressBars.get(uuid);
        
        if (!progressBar) {
            progressBar = this.createProgressBar(uuid, fileName);
        }

        progressBar.update(current, total);
    }

    /**
     * プログレスバーを削除
     */
    removeProgressBar(uuid) {
        const progressBar = this.progressBars.get(uuid);
        if (progressBar) {
            progressBar.remove();
            this.progressBars.delete(uuid);
        }
    }

    /**
     * すべてのプログレスバーをクリア
     */
    clearAll() {
        this.progressBars.forEach(progressBar => progressBar.remove());
        this.progressBars.clear();
    }
}

/**
 * 個別のプログレスバー
 */
class ProgressBar {
    constructor(uuid, fileName) {
        this.uuid = uuid;
        this.fileName = fileName;
        this.current = 0;
        this.total = 0;
        
        this.createElement();
    }

    /**
     * プログレスバー要素を作成
     */
    createElement() {
        this.element = document.createElement('div');
        this.element.className = 'progress-item';
        this.element.dataset.uuid = this.uuid;

        // プログレスバーの構造を作成
        this.element.innerHTML = `
            <div class="progress-header">
                <span class="progress-filename">${this.fileName}</span>
                <span class="progress-percentage">0%</span>
                <button class="progress-cancel" data-uuid="${this.uuid}">×</button>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: 0%"></div>
            </div>
            <div class="progress-info">
                <span class="progress-current">0</span> / <span class="progress-total">0</span> chunks
            </div>
        `;

        // 要素の参照を保存
        this.filenameElement = this.element.querySelector('.progress-filename');
        this.percentageElement = this.element.querySelector('.progress-percentage');
        this.fillElement = this.element.querySelector('.progress-fill');
        this.currentElement = this.element.querySelector('.progress-current');
        this.totalElement = this.element.querySelector('.progress-total');
        this.cancelButton = this.element.querySelector('.progress-cancel');

        // キャンセルボタンのイベントリスナー
        this.cancelButton.addEventListener('click', () => {
            this.onCancel();
        });
    }

    /**
     * プログレスを更新
     */
    update(current, total) {
        this.current = current;
        this.total = total;

        const percentage = total > 0 ? Math.round((current / total) * 100) : 0;
        
        this.percentageElement.textContent = `${percentage}%`;
        this.fillElement.style.width = `${percentage}%`;
        this.currentElement.textContent = current;
        this.totalElement.textContent = total;

        // 完了時のスタイル変更
        if (current >= total && total > 0) {
            this.element.classList.add('completed');
            this.cancelButton.style.display = 'none';
            
            // 3秒後に自動削除
            setTimeout(() => {
                this.remove();
            }, 3000);
        }
    }

    /**
     * エラー状態を表示
     */
    showError(message) {
        this.element.classList.add('error');
        this.percentageElement.textContent = 'エラー';
        this.fillElement.style.width = '0%';
        
        // エラーメッセージを表示
        const errorElement = document.createElement('div');
        errorElement.className = 'progress-error';
        errorElement.textContent = message;
        this.element.appendChild(errorElement);
    }

    /**
     * キャンセル処理
     */
    onCancel() {
        if (this.onCancelCallback) {
            this.onCancelCallback(this.uuid);
        }
        this.remove();
    }

    /**
     * キャンセルコールバックを設定
     */
    setCancelCallback(callback) {
        this.onCancelCallback = callback;
    }

    /**
     * プログレスバーを削除
     */
    remove() {
        if (this.element && this.element.parentNode) {
            this.element.parentNode.removeChild(this.element);
        }
    }
} 