// 管理画面クラス
class AdminPanel {
    constructor() {
        this.currentUser = null;
        this.currentTab = 'users';
        this.phases = [];
        this.templates = [];
        this.manuals = [];
        this.init();
    }

    async init() {
        try {
            // 認証チェック（存在すれば表示用に使用。失敗しても続行）
            try {
                await this.checkAuth();
            } catch (e) {
                // 未ログインでも管理画面は利用可能とする
                this.currentUser = { name: 'ゲスト', role: 'guest' };
                this.updateUserInfo();
            }

            // イベントリスナー設定
            this.setupEventListeners();

            // 初期データ読み込み
            await this.loadInitialData();

            // ユーザー管理を初期表示
            this.showTab('users');
            await this.loadUsers();

        } catch (error) {
            console.error('Admin panel initialization error:', error);
            this.showAlert('管理画面の初期化に失敗しました。', 'error');
            // ここではページ遷移しない（未ログインでも続行）
        }
    }

    async checkAuth() {
        const response = await this.apiCall('GET', 'check_auth');
        if (response && response.success && response.user) {
            this.currentUser = response.user;
        } else {
            throw new Error('Not authenticated');
        }
        this.updateUserInfo();
    }

    updateUserInfo() {
        const name = (this.currentUser && this.currentUser.name) ? this.currentUser.name : 'ゲスト';
        document.getElementById('userName').textContent = name;
        const roleElement = document.getElementById('userRole');
        const role = (this.currentUser && this.currentUser.role) ? this.currentUser.role : 'guest';
        const roleNames = { manager: '管理者', technical: '技術者', general: '一般', guest: 'ゲスト' };
        roleElement.textContent = roleNames[role] || role;
    }

    setupEventListeners() {
        // ログアウト
        document.getElementById('logoutBtn').addEventListener('click', this.logout.bind(this));
        document.getElementById('backToMain').addEventListener('click', () => {
            window.location.href = 'index.html';
        });

        // タブ切り替え
        document.querySelectorAll('.menu-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                const tabName = e.target.dataset.tab;
                this.showTab(tabName);
            });
        });

        // ユーザー管理イベント
        this.setupUserEvents();

        // フェーズ管理イベント
        this.setupPhaseEvents();

        // テンプレート管理イベント
        this.setupTemplateEvents();

        // マニュアル管理イベント
        this.setupManualEvents();

        // モーダル外クリックで閉じる機能を完全に無効化
        // ユーザーが意図しない操作でモーダルが閉じることを防ぐ
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            // 背景クリックを完全に無効化
            modal.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }, true);
            
            // モーダル内のクリックは正常に動作させる
            const modalContent = modal.querySelector('.modal');
            if (modalContent) {
                modalContent.addEventListener('click', (e) => {
                    e.stopPropagation();
                });
            }
        });
    }

    setupUserEvents() {
        // 新規ユーザー追加
        document.getElementById('addUserBtn').addEventListener('click', this.showAddUserModal.bind(this));
        document.getElementById('closeAddUserModal').addEventListener('click', () => this.closeModal('addUserModal'));
        document.getElementById('cancelAddUser').addEventListener('click', () => this.closeModal('addUserModal'));
        document.getElementById('addUserForm').addEventListener('submit', this.addUser.bind(this));

        // ユーザー編集
        document.getElementById('closeEditUserModal').addEventListener('click', () => this.closeModal('editUserModal'));
        document.getElementById('cancelEditUser').addEventListener('click', () => this.closeModal('editUserModal'));
        document.getElementById('editUserForm').addEventListener('submit', this.updateUser.bind(this));
    }

    setupPhaseEvents() {
        // フェーズ管理
        document.getElementById('addPhaseBtn').addEventListener('click', this.showAddPhaseModal.bind(this));
        document.getElementById('closePhaseModal').addEventListener('click', () => this.closeModal('phaseModal'));
        document.getElementById('cancelPhase').addEventListener('click', () => this.closeModal('phaseModal'));
        document.getElementById('phaseForm').addEventListener('submit', this.savePhase.bind(this));
    }

    setupTemplateEvents() {
        // タスクテンプレート管理
        document.getElementById('addTemplateBtn').addEventListener('click', this.showAddTemplateModal.bind(this));
        document.getElementById('closeTemplateModal').addEventListener('click', () => this.closeModal('templateModal'));
        document.getElementById('cancelTemplate').addEventListener('click', () => this.closeModal('templateModal'));
        document.getElementById('templateForm').addEventListener('submit', this.saveTemplate.bind(this));
    }

    setupManualEvents() {
        // マニュアル管理
        document.getElementById('uploadManualBtn').addEventListener('click', this.showUploadManualModal.bind(this));
        document.getElementById('closeManualModal').addEventListener('click', () => this.closeModal('manualModal'));
        document.getElementById('cancelManual').addEventListener('click', () => this.closeModal('manualModal'));
        document.getElementById('manualForm').addEventListener('submit', this.uploadManual.bind(this));

        // ファイル選択時の自動ファイル名設定
        document.getElementById('manualFile').addEventListener('change', this.onManualFileChange.bind(this));
    }

    showTab(tabName) {
        // タブ切り替え
        document.querySelectorAll('.menu-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');

        // コンテンツ切り替え
        document.querySelectorAll('.admin-content').forEach(content => {
            content.style.display = 'none';
        });
        document.getElementById(`${tabName}-tab`).style.display = 'block';

        this.currentTab = tabName;

        // タブに応じたデータ読み込み
        switch (tabName) {
            case 'users':
                this.loadUsers();
                break;
            case 'phases':
                this.loadPhases();
                break;
            case 'templates':
                this.loadTemplates();
                break;
            case 'manuals':
                this.loadManuals();
                break;
        }
    }

    async loadInitialData() {
        // 初期表示の時点ではローディングを外す（ページ全体のブロックを避ける）
        this.showLoading(false);
        try {
            // フェーズ一覧を取得（テンプレート作成時に使用）
            const phasesResponse = await this.apiCall('GET', 'admin/phases');
            if (phasesResponse.success) {
                this.phases = phasesResponse.phases || [];
            }
        } catch (error) {
            console.error('Failed to load initial data:', error);
        } finally {
            // ローディングは初期化完了前に確実に消す
            setTimeout(() => this.showLoading(false), 0);
        }
    }

    // ==================== ユーザー管理 ====================

    async loadUsers() {
        this.showLoading(true);
        try {
            const response = await this.apiCall('GET', 'admin/users');
            if (response.success) {
                this.renderUsers(response.users || []);
            } else {
                throw new Error(response.message);
            }
        } catch (error) {
            console.error('Failed to load users:', error);
            this.showAlert('ユーザー一覧の読み込みに失敗しました。', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    renderUsers(users) {
        const container = document.getElementById('usersList');
        container.innerHTML = '';

        if (users.length === 0) {
            container.innerHTML = '<div class="empty-state">ユーザーが登録されていません。</div>';
            return;
        }

        users.forEach(user => {
            const userElement = this.createUserElement(user);
            container.appendChild(userElement);
        });
    }

    createUserElement(user) {
        const userItem = document.createElement('div');
        userItem.className = 'user-item';

        const roleNames = {
            'manager': '管理者',
            'technical': '技術者',
            'general': '一般スタッフ'
        };

        const statusClass = user.is_active ? 'active' : 'inactive';
        const statusText = user.is_active ? '有効' : '無効';

        userItem.innerHTML = `
            <div class="user-info">
                <div class="user-avatar">${user.name.charAt(0).toUpperCase()}</div>
                <div class="user-details">
                    <h4>${user.name}</h4>
                    <p>${user.email}</p>
                    <p>${roleNames[user.role] || user.role}</p>
                </div>
                <span class="user-status ${statusClass}">${statusText}</span>
            </div>
            <div class="user-actions">
                <button class="btn btn-primary btn-small" onclick="adminPanel.editUser(${user.id})">編集</button>
                <button class="btn btn-danger btn-small" onclick="adminPanel.deleteUser(${user.id}, '${user.name}')">削除</button>
            </div>
        `;

        return userItem;
    }

    showAddUserModal() {
        document.getElementById('addUserForm').reset();
        document.getElementById('addUserModal').classList.add('active');
    }

    async addUser(event) {
        event.preventDefault();

        const formData = {
            email: document.getElementById('userEmail').value.trim(),
            name: document.getElementById('userName').value.trim(),
            role: document.getElementById('userRole').value,
            password: document.getElementById('userPassword').value
        };

        if (!formData.email || !formData.name || !formData.role || !formData.password) {
            this.showAlert('すべての必須項目を入力してください。', 'error');
            return;
        }

        this.showLoading(true);
        try {
            const response = await this.apiCall('POST', 'admin/users', formData);
            if (response.success) {
                this.closeModal('addUserModal');
                this.showAlert('ユーザーを追加しました。', 'success');
                await this.loadUsers();
            } else {
                throw new Error(response.message);
            }
        } catch (error) {
            console.error('Failed to add user:', error);
            this.showAlert('ユーザーの追加に失敗しました。', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async editUser(userId) {
        this.showLoading(true);
        try {
            const response = await this.apiCall('GET', `admin/users/${userId}`);
            if (response.success) {
                const user = response.user;
                document.getElementById('editUserEmail').value = user.email;
                document.getElementById('editUserName').value = user.name;
                document.getElementById('editUserRole').value = user.role;
                document.getElementById('editUserStatus').value = user.is_active ? '1' : '0';

                document.getElementById('editUserModal').dataset.userId = userId;
                document.getElementById('editUserModal').classList.add('active');
            } else {
                throw new Error(response.message);
            }
        } catch (error) {
            console.error('Failed to load user:', error);
            this.showAlert('ユーザーの読み込みに失敗しました。', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async updateUser(event) {
        event.preventDefault();

        const userId = document.getElementById('editUserModal').dataset.userId;
        const formData = {
            name: document.getElementById('editUserName').value.trim(),
            role: document.getElementById('editUserRole').value,
            is_active: document.getElementById('editUserStatus').value === '1'
        };

        this.showLoading(true);
        try {
            const response = await this.apiCall('PUT', `admin/users/${userId}`, formData);
            if (response.success) {
                this.closeModal('editUserModal');
                this.showAlert('ユーザーを更新しました。', 'success');
                await this.loadUsers();
            } else {
                throw new Error(response.message);
            }
        } catch (error) {
            console.error('Failed to update user:', error);
            this.showAlert('ユーザーの更新に失敗しました。', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async deleteUser(userId, userName) {
        if (!confirm(`${userName}さんを削除してもよろしいですか？\nこの操作は取り消せません。`)) {
            return;
        }

        this.showLoading(true);
        try {
            const response = await this.apiCall('DELETE', `admin/users/${userId}`);
            if (response.success) {
                this.showAlert('ユーザーを削除しました。', 'success');
                await this.loadUsers();
            } else {
                throw new Error(response.message);
            }
        } catch (error) {
            console.error('Failed to delete user:', error);
            this.showAlert('ユーザーの削除に失敗しました。', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    // ==================== フェーズ管理 ====================

    async loadPhases() {
        this.showLoading(true);
        try {
            const response = await this.apiCall('GET', 'admin/phases');
            if (response.success) {
                this.phases = response.phases || [];
                this.renderPhases(this.phases);
            } else {
                throw new Error(response.message);
            }
        } catch (error) {
            console.error('Failed to load phases:', error);
            this.showAlert('フェーズ一覧の読み込みに失敗しました。', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    renderPhases(phases) {
        const container = document.getElementById('phasesList');
        container.innerHTML = '';

        if (phases.length === 0) {
            container.innerHTML = '<div class="empty-state">フェーズが登録されていません。</div>';
            return;
        }

        phases.forEach(phase => {
            const phaseElement = this.createPhaseElement(phase);
            container.appendChild(phaseElement);
        });
    }

    createPhaseElement(phase) {
        const phaseItem = document.createElement('div');
        phaseItem.className = 'phase-item';

        phaseItem.innerHTML = `
            <div>
                <div class="phase-header">
                    <h3 class="phase-title">${phase.phase_name}</h3>
                    <div class="user-actions">
                        <button class="btn btn-primary btn-small" onclick="adminPanel.editPhase('${phase.phase_name}')">編集</button>
                        <button class="btn btn-danger btn-small" onclick="adminPanel.deletePhase('${phase.phase_name}', '${phase.phase_name}')">削除</button>
                    </div>
                </div>
                <p class="phase-description">${phase.description || '説明なし'}</p>
            </div>
        `;

        return phaseItem;
    }

    showAddPhaseModal(phaseName = null) {
        const modal = document.getElementById('phaseModal');
        const form = document.getElementById('phaseForm');
        const title = document.getElementById('phaseModalTitle');

        if (phaseName) {
            title.textContent = 'フェーズ編集';
            // 既存データの読み込み
            const phase = this.phases.find(p => p.phase_name === phaseName);
            if (phase) {
                document.getElementById('phaseName').value = phase.phase_name;
                document.getElementById('phaseDescription').value = phase.description || '';
            }
            modal.dataset.editMode = 'true';
            modal.dataset.originalName = phaseName;
        } else {
            title.textContent = '新規フェーズ追加';
            form.reset();
            modal.dataset.editMode = 'false';
            delete modal.dataset.originalName;
        }

        modal.classList.add('active');
    }

    async savePhase(event) {
        event.preventDefault();

        const modal = document.getElementById('phaseModal');
        const isEdit = modal.dataset.editMode === 'true';
        const originalName = modal.dataset.originalName;

        const formData = {
            phase_name: document.getElementById('phaseName').value.trim(),
            description: document.getElementById('phaseDescription').value.trim()
        };

        if (!formData.phase_name) {
            this.showAlert('フェーズ名を入力してください。', 'error');
            return;
        }

        this.showLoading(true);
        try {
            let response;
            if (isEdit) {
                response = await this.apiCall('PUT', `admin/phases/${encodeURIComponent(originalName)}`, formData);
            } else {
                response = await this.apiCall('POST', 'admin/phases', formData);
            }

            if (response.success) {
                this.closeModal('phaseModal');
                const message = isEdit ? 'フェーズを更新しました。' : 'フェーズを追加しました。';
                this.showAlert(message, 'success');
                await this.loadPhases();
            } else {
                throw new Error(response.message);
            }
        } catch (error) {
            console.error('Failed to save phase:', error);
            this.showAlert('フェーズの保存に失敗しました。', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    // ==================== テンプレート管理 ====================

    showAddTemplateModal() {
        const modal = document.getElementById('templateModal');
        const form = document.getElementById('templateForm');
        if (form) form.reset();
        modal.classList.add('active');
        // フェーズ選択肢を更新（必要なら）
        const phaseSelect = document.getElementById('templatePhase');
        if (phaseSelect && this.phases && this.phases.length > 0) {
            phaseSelect.innerHTML = '';
            this.phases.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.phase_name;
                opt.textContent = p.phase_name;
                phaseSelect.appendChild(opt);
            });
        }
    }

    async saveTemplate(event) {
        event.preventDefault();
        // ひとまずプレースホルダー実装（API未実装のため）
        this.closeModal('templateModal');
        this.showAlert('テンプレート保存は準備中です。', 'info');
        // 一覧を再描画（ダミー）
        await this.loadTemplates();
    }

    async loadTemplates() {
        this.showLoading(true);
        try {
            const response = await this.apiCall('GET', 'admin/templates');
            if (response.success) {
                this.renderTemplates(response.templates || []);
            } else {
                throw new Error(response.message);
            }
        } catch (error) {
            console.error('Failed to load templates:', error);
            this.showAlert('テンプレート一覧の読み込みに失敗しました。', 'error');
            this.renderTemplates([]);
        } finally {
            this.showLoading(false);
        }
    }

    renderTemplates(templates) {
        const container = document.getElementById('templatesList');
        if (!container) return;
        container.innerHTML = '';
        if (!templates || templates.length === 0) {
            container.innerHTML = '<div class="empty-state">テンプレートがまだありません。</div>';
            return;
        }
        // 必要になればカード表示を実装
    }

    // ==================== マニュアル管理 ====================

    showUploadManualModal() {
        const modal = document.getElementById('manualModal');
        const form = document.getElementById('manualForm');
        if (form) form.reset();
        modal.classList.add('active');
    }

    async uploadManual(event) {
        event.preventDefault();
        // ひとまずプレースホルダー実装（API未実装のため）
        this.closeModal('manualModal');
        this.showAlert('マニュアルのアップロードは準備中です。', 'info');
        await this.loadManuals();
    }

    onManualFileChange(e) {
        const fileInput = e.target;
        const nameInput = document.getElementById('manualName');
        if (fileInput && fileInput.files && fileInput.files[0] && nameInput) {
            nameInput.value = fileInput.files[0].name;
        }
    }

    async loadManuals() {
        this.showLoading(true);
        try {
            const response = await this.apiCall('GET', 'admin/manuals');
            if (response.success) {
                this.renderManuals(response.manuals || []);
            } else {
                throw new Error(response.message);
            }
        } catch (error) {
            console.error('Failed to load manuals:', error);
            this.showAlert('マニュアル一覧の読み込みに失敗しました。', 'error');
            this.renderManuals([]);
        } finally {
            this.showLoading(false);
        }
    }

    renderManuals(manuals) {
        const container = document.getElementById('manualsList');
        if (!container) return;
        container.innerHTML = '';
        if (!manuals || manuals.length === 0) {
            container.innerHTML = '<div class="empty-state">マニュアルがまだありません。</div>';
            return;
        }
        // 必要になれば一覧表示を実装
    }

    // ==================== ユーティリティ ====================

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            // フォームリセット
            const form = modal.querySelector('form');
            if (form) form.reset();
            // データ属性クリア
            Object.keys(modal.dataset).forEach(key => {
                delete modal.dataset[key];
            });
        }
    }

    showLoading(show) {
        const overlay = document.getElementById('loadingOverlay');
        if (show) {
            overlay.classList.add('active');
        } else {
            overlay.classList.remove('active');
        }
    }

    showAlert(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.textContent = message;

        const container = document.getElementById('alertContainer');
        container.appendChild(alertDiv);

        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }

    async logout() {
        try {
            await this.apiCall('POST', 'logout');
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            window.location.href = 'login.html';
        }
    }

    async apiCall(method, endpoint, data = null) {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        if (data) {
            options.body = JSON.stringify(data);
        }

        const response = await fetch(`api.php?path=${endpoint}`, options);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    }
}

// グローバルインスタンス作成
let adminPanel;
document.addEventListener('DOMContentLoaded', function() {
    adminPanel = new AdminPanel();
});
