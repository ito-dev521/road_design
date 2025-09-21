// 道路詳細設計管理システム - メインJavaScript

class RoadDesignApp {
    constructor() {
        this.currentUser = null;
        this.currentProject = null;
        this.tasks = [];
        this.users = [];
        this.phases = []; // フェーズ情報を動的に管理
        this.init();
    }

    async init() {
        try {
            // 認証チェック
            await this.checkAuth();

                        // イベントリスナー設定
            this.setupEventListeners();

            // 初期データ読み込み（フェーズ情報を含む）
            await this.loadInitialData();

            // 念のため、もう一度ユーザー情報を更新
            setTimeout(() => {
                if (this.currentUser) {
                    this.updateUserInfo();
                }
            }, 100);

        } catch (error) {
            this.redirectToLogin();
        }
    }

    async checkAuth() {
        try {
            const response = await this.apiCall('GET', 'check_auth');

            if (!response.success) {
                throw new Error('Not authenticated');
            }

            this.currentUser = response.user;

            this.updateUserInfo();
        } catch (error) {
            this.redirectToLogin();
            throw error;
        }
    }

    updateUserInfo() {
        // DOM要素の存在チェック
        const userNameElement = document.getElementById('userName');
        const roleElement = document.getElementById('userRole');
        const adminBtn = document.getElementById('adminBtn');

        if (!userNameElement || !roleElement || !adminBtn) {
            return;
        }

        userNameElement.textContent = this.currentUser.name;

        const roleNames = {
            'manager': '管理者',
            'technical': '技術者',
            'general': '一般'
        };
        roleElement.textContent = roleNames[this.currentUser.role] || this.currentUser.role;

        // 管理者権限がある場合、管理画面ボタンを表示
        if (this.currentUser && this.currentUser.role === 'manager') {

            // 管理画面ボタンを表示
            adminBtn.style.display = 'inline-block';
            adminBtn.style.visibility = 'visible';

            // 直接アクセスボタンも表示
            const directAdminBtn = document.getElementById('directAdminBtn');
            if (directAdminBtn) {
                directAdminBtn.style.display = 'inline-block';
                directAdminBtn.style.visibility = 'visible';
            }

            // イベントリスナーが既に設定されていないことを確認
            if (!adminBtn.hasAttribute('data-listener-added')) {
                adminBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    window.location.href = 'admin.html';
                });
                adminBtn.setAttribute('data-listener-added', 'true');
            }

            // ボタンの視覚的なフィードバックを追加
            adminBtn.style.cursor = 'pointer';
            adminBtn.title = '管理画面を開く';

        } else {
            adminBtn.style.display = 'none';
            adminBtn.style.visibility = 'hidden';

            const directAdminBtn = document.getElementById('directAdminBtn');
            if (directAdminBtn) {
                directAdminBtn.style.display = 'none';
                directAdminBtn.style.visibility = 'hidden';
            }
        }
    }

    setupEventListeners() {
        // ログアウト
        const logoutBtn = document.getElementById('logoutBtn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', this.logout.bind(this));
        }

        // 管理画面ボタン（右上）
        const debugBtn = document.getElementById('debugBtn');
        if (debugBtn) {
            debugBtn.addEventListener('click', (e) => {
                e.preventDefault();
                window.location.href = 'admin.html';
            });
        }

        // 直接アクセスボタン
        const directAdminBtn = document.getElementById('directAdminBtn');
        if (directAdminBtn) {
            directAdminBtn.addEventListener('click', (e) => {
                e.preventDefault();
                window.location.href = 'admin.html';
            });
        }

        // 新規プロジェクト
        document.getElementById('newProjectBtn').addEventListener('click', this.showNewProjectModal.bind(this));
        document.getElementById('closeNewProjectModal').addEventListener('click', this.hideNewProjectModal.bind(this));
        document.getElementById('cancelNewProject').addEventListener('click', this.hideNewProjectModal.bind(this));
        document.getElementById('newProjectForm').addEventListener('submit', this.createProject.bind(this));

        // プロジェクト選択
        document.getElementById('projectSelect').addEventListener('change', this.selectProject.bind(this));

        // タスクモーダル
        document.getElementById('closeTaskModal').addEventListener('click', this.hideTaskModal.bind(this));
        document.getElementById('cancelTaskEdit').addEventListener('click', this.hideTaskModal.bind(this));
        document.getElementById('saveTaskChanges').addEventListener('click', this.saveTaskChanges.bind(this));

        // フィルター
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', this.filterTasks.bind(this));
        });

        // モーダル外クリックで閉じる機能を完全に無効化
        // ユーザーが意図しない操作でモーダルが閉じることを防ぐ
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            // 背景クリックを無効化（モーダル内の要素は除外）
            modal.addEventListener('click', (e) => {
                // モーダル内の要素がクリックされた場合は何もしない
                if (e.target.closest('.modal')) {
                    return;
                }
                // 背景がクリックされた場合のみ無効化
                e.preventDefault();
                e.stopPropagation();
                return false;
            }, true);
        });
    }

    async loadInitialData() {
        this.showLoading(true);
        try {
            // フェーズ情報、プロジェクト一覧、ユーザー一覧を並行取得
            const [phasesResponse, projectsResponse, usersResponse] = await Promise.all([
                this.apiCall('GET', 'phases'),
                this.apiCall('GET', 'projects'),
                this.apiCall('GET', 'users')
            ]);

            if (phasesResponse.success) {
                this.phases = phasesResponse.phases || [];
                this.renderPhaseContainers();
                this.renderPhaseFilters();
            }

            if (projectsResponse.success) {
                this.populateProjectSelect(projectsResponse.projects);
            }

            if (usersResponse.success) {
                this.users = usersResponse.users;
                this.populateUserSelects();
            }
        } catch (error) {
            this.showAlert('初期データの読み込みに失敗しました。', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    populateProjectSelect(projects) {
        const select = document.getElementById('projectSelect');
        select.innerHTML = '<option value="">プロジェクトを選択してください</option>';

        projects.forEach(project => {
            const option = document.createElement('option');
            option.value = project.id;
            // プロジェクトコードをプロジェクト名の前に付ける（シンプル表示）
            const displayName = project.project_code
                ? `${project.project_code} ${project.name}`
                : project.name;
            option.textContent = displayName;
            select.appendChild(option);
        });
    }

    populateUserSelects() {
        const assigneeSelect = document.getElementById('taskAssigneeSelect');
        assigneeSelect.innerHTML = '<option value="">未割当</option>';

        this.users.forEach(user => {
            const option = document.createElement('option');
            option.value = user.id;
            option.textContent = user.name;
            assigneeSelect.appendChild(option);
        });
    }

    renderPhaseContainers() {
        const taskSection = document.getElementById('taskSection');
        const phaseContainerSection = taskSection.querySelector('.phase-container-section') ||
                                    taskSection.appendChild(document.createElement('div'));

        // 既存のフェーズコンテナをクリア
        phaseContainerSection.innerHTML = '';
        phaseContainerSection.className = 'phase-container-section';

        // フェーズコンテナを動的に生成
        this.phases.forEach((phase, index) => {
            const phaseContainer = document.createElement('div');
            phaseContainer.className = 'phase-container';
            phaseContainer.dataset.phase = phase.phase_name;

            // フェーズ番号に基づいてアイコンを設定
            const icons = ['📊', '⚙️', '📐', '🔧', '📋', '✅'];
            const icon = icons[index] || '📋';

            phaseContainer.innerHTML = `
                <div class="phase-header">
                    <h3>${icon} ${phase.phase_name}</h3>
                    <div class="phase-progress">
                        <span class="phase-progress-text" id="${phase.phase_name.replace('フェーズ', 'phase')}Progress">0/0</span>
                    </div>
                </div>
                <div class="task-grid" id="${phase.phase_name.replace('フェーズ', 'phase')}Tasks">
                    <!-- タスクはJavaScriptで動的生成 -->
                </div>
            `;

            phaseContainerSection.appendChild(phaseContainer);
        });
    }

    renderPhaseFilters() {
        const filterContainer = document.querySelector('.task-filters');
        if (!filterContainer) return;

        // 既存のフィルターボタンをクリア（「すべて」ボタンは残す）
        const existingButtons = filterContainer.querySelectorAll('.filter-btn:not([data-phase="all"])');
        existingButtons.forEach(button => button.remove());

        // 「すべて」ボタンの後にフェーズフィルターボタンを追加
        const allButton = filterContainer.querySelector('[data-phase="all"]');

        this.phases.forEach(phase => {
            const filterButton = document.createElement('button');
            filterButton.className = 'filter-btn';
            filterButton.dataset.phase = phase.phase_name;
            filterButton.textContent = phase.phase_name;
            filterButton.addEventListener('click', this.filterTasks.bind(this));
            filterContainer.appendChild(filterButton);
        });
    }

    async selectProject(event) {
        const projectId = event.target.value;
        
        if (!projectId) {
            this.hideProjectInfo();
            return;
        }

        this.showLoading(true);
        try {
            const response = await this.apiCall('GET', `projects/${projectId}`);
            if (response.success) {
                this.currentProject = response.project;
                this.tasks = response.tasks || [];
                
                this.showProjectInfo();
                this.updateProjectDisplay();
                this.renderTasks();
            } else {
                throw new Error(response.message);
            }
        } catch (error) {
            this.showAlert('プロジェクトの読み込みに失敗しました。', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    showProjectInfo() {
        document.getElementById('projectInfo').style.display = 'block';
        document.getElementById('taskSection').style.display = 'block';
    }

    hideProjectInfo() {
        document.getElementById('projectInfo').style.display = 'none';
        document.getElementById('taskSection').style.display = 'none';
    }

    updateProjectDisplay() {
        if (!this.currentProject) return;

        // プロジェクト基本情報
        // プロジェクトコードをプロジェクト名の前に付ける
        const displayName = this.currentProject.project_code
            ? `${this.currentProject.project_code} ${this.currentProject.name}`
            : this.currentProject.name;
        document.getElementById('projectName').textContent = displayName;
        document.getElementById('projectClient').textContent = this.currentProject.client_name || '発注者未設定';
        
        const period = this.formatPeriod(this.currentProject.start_date, this.currentProject.target_end_date);
        document.getElementById('projectPeriod').textContent = period;
        
        const statusElement = document.getElementById('projectStatus');
        statusElement.textContent = this.getStatusText(this.currentProject.status);
        statusElement.className = `project-status ${this.currentProject.status}`;

        // 統計情報
        const stats = this.calculateStats();
        document.getElementById('totalTasks').textContent = stats.total;
        document.getElementById('completedTasks').textContent = stats.completed;
        document.getElementById('inProgressTasks').textContent = stats.inProgress;
        document.getElementById('overdueTasks').textContent = stats.overdue;

        // 全体進捗
        const progressPercentage = stats.total > 0 ? Math.round((stats.completed / stats.total) * 100) : 0;
        document.getElementById('progressFill').style.width = `${progressPercentage}%`;
        document.getElementById('progressText').textContent = `${progressPercentage}%`;

        // フェーズ別進捗
        this.updatePhaseProgress();
    }

    calculateStats() {
        const stats = {
            total: this.tasks.length,
            completed: 0,
            inProgress: 0,
            overdue: 0
        };

        const today = new Date();
        this.tasks.forEach(task => {
            if (task.status === 'completed') {
                stats.completed++;
            } else if (task.status === 'in_progress') {
                stats.inProgress++;
                if (task.planned_date && new Date(task.planned_date) < today) {
                    stats.overdue++;
                }
            } else if (task.planned_date && new Date(task.planned_date) < today) {
                stats.overdue++;
            }
        });

        return stats;
    }

    updatePhaseProgress() {
        // データベースから取得したフェーズ情報を使用
        this.phases.forEach(phase => {
            const phaseTasks = this.tasks.filter(task => task.phase_name === phase.phase_name);
            const completedTasks = phaseTasks.filter(task => task.status === 'completed');

            const progressElement = document.getElementById(`${phase.phase_name.replace('フェーズ', 'phase')}Progress`);
            if (progressElement) {
                progressElement.textContent = `${completedTasks.length}/${phaseTasks.length}`;
            }
        });
    }

    renderTasks() {
        // データベースから取得したフェーズ情報を使用
        this.phases.forEach(phase => {
            const container = document.getElementById(`${phase.phase_name.replace('フェーズ', 'phase')}Tasks`);
            if (!container) return;

            container.innerHTML = '';

            const phaseTasks = this.tasks.filter(task => task.phase_name === phase.phase_name);
            phaseTasks.forEach(task => {
                const taskElement = this.createTaskElement(task);
                container.appendChild(taskElement);
            });
        });
    }

    createTaskElement(task) {
        const taskCard = document.createElement('div');
        taskCard.className = `task-card ${task.status === 'completed' ? 'completed' : ''}`;
        taskCard.addEventListener('click', () => this.showTaskModal(task));

        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.className = 'task-checkbox';
        checkbox.checked = task.status === 'completed';
        checkbox.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleTaskStatus(task, checkbox.checked);
        });

        const badges = [];
        if (task.is_technical_work) {
            badges.push('<span class="task-badge technical">技術者</span>');
        }
        if (task.has_manual) {
            badges.push('<span class="task-badge manual">○</span>');
        }

        const assigneeName = task.assigned_to_name || '未割当';
        
        taskCard.innerHTML = `
            <div class="task-header">
                <div class="task-title ${task.status === 'completed' ? 'completed' : ''}">${task.task_name}</div>
            </div>
            <div class="task-meta-badges">
                ${badges.join('')}
            </div>
            <div class="task-status">
                <span class="status-badge ${task.status}">${this.getStatusText(task.status)}</span>
                <span class="task-assignee">${assigneeName}</span>
            </div>
        `;

        // チェックボックスを先頭に挿入
        taskCard.querySelector('.task-header').insertBefore(checkbox, taskCard.querySelector('.task-title'));

        return taskCard;
    }

    async toggleTaskStatus(task, isCompleted) {
        const newStatus = isCompleted ? 'completed' : 'not_started';
        
        try {
            const response = await this.apiCall('PUT', 'tasks/status', {
                task_id: task.id,
                status: newStatus
            });

            if (response.success) {
                task.status = newStatus;
                this.updateProjectDisplay();
                this.renderTasks();
                this.showAlert('タスクの状態を更新しました。', 'success');
            } else {
                throw new Error(response.message);
            }
        } catch (error) {
            this.showAlert('タスクの状態更新に失敗しました。', 'error');
        }
    }

    showTaskModal(task) {
        document.getElementById('taskModalTitle').textContent = task.task_name;
        document.getElementById('taskStatusSelect').value = task.status;
        document.getElementById('taskAssigneeSelect').value = task.assigned_to || '';
        document.getElementById('taskPlannedDate').value = task.planned_date || '';
        document.getElementById('taskTechnical').textContent = task.is_technical_work ? '○' : '-';
        document.getElementById('taskManual').textContent = task.has_manual ? '○' : '-';
        document.getElementById('taskNotesInput').value = task.notes || '';

        document.getElementById('taskModal').dataset.taskId = task.id;
        document.getElementById('taskModal').classList.add('active');
    }

    hideTaskModal() {
        document.getElementById('taskModal').classList.remove('active');
    }

    async saveTaskChanges() {
        const taskId = document.getElementById('taskModal').dataset.taskId;
        const status = document.getElementById('taskStatusSelect').value;
        const assignedTo = document.getElementById('taskAssigneeSelect').value || null;
        const plannedDate = document.getElementById('taskPlannedDate').value || null;
        const notes = document.getElementById('taskNotesInput').value.trim();

        try {
            const response = await this.apiCall('PUT', 'tasks/update', {
                task_id: taskId,
                status: status,
                assigned_to: assignedTo,
                planned_date: plannedDate,
                notes: notes
            });

            if (response.success) {
                // ローカルタスクデータ更新
                const taskIndex = this.tasks.findIndex(t => t.id == taskId);
                if (taskIndex !== -1) {
                    this.tasks[taskIndex].status = status;
                    this.tasks[taskIndex].assigned_to = assignedTo;
                    this.tasks[taskIndex].planned_date = plannedDate;
                    this.tasks[taskIndex].notes = notes;
                    
                    // 担当者名更新
                    if (assignedTo) {
                        const user = this.users.find(u => u.id == assignedTo);
                        this.tasks[taskIndex].assigned_to_name = user ? user.name : null;
                    } else {
                        this.tasks[taskIndex].assigned_to_name = null;
                    }
                }

                this.hideTaskModal();
                this.updateProjectDisplay();
                this.renderTasks();
                this.showAlert('タスクを更新しました。', 'success');
            } else {
                throw new Error(response.message);
            }
        } catch (error) {
            this.showAlert('タスクの更新に失敗しました。', 'error');
        }
    }

    filterTasks(event) {
        const phase = event.target.dataset.phase;

        // フィルターボタンのアクティブ状態更新
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');

        // フェーズコンテナの表示/非表示
        document.querySelectorAll('.phase-container').forEach(container => {
            const containerPhase = container.dataset.phase;
            if (phase === 'all' || phase === containerPhase) {
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
            }
        });
    }

    showNewProjectModal() {
        document.getElementById('newProjectModal').classList.add('active');
        // 開始日を今日に設定
        document.getElementById('startDateInput').value = new Date().toISOString().split('T')[0];
    }

    hideNewProjectModal() {
        document.getElementById('newProjectModal').classList.remove('active');
        document.getElementById('newProjectForm').reset();
    }

    async createProject(event) {
        event.preventDefault();
        
        const formData = {
            name: document.getElementById('projectNameInput').value,
            project_code: document.getElementById('projectCodeInput').value,
            client_name: document.getElementById('clientNameInput').value,
            description: document.getElementById('descriptionInput').value,
            start_date: document.getElementById('startDateInput').value,
            target_end_date: document.getElementById('endDateInput').value
        };

        this.showLoading(true);
        try {
            const response = await this.apiCall('POST', 'projects', formData);
            
            if (response.success) {
                this.hideNewProjectModal();
                this.showAlert('プロジェクトを作成しました。', 'success');
                
                // プロジェクト一覧を再取得
                const projectsResponse = await this.apiCall('GET', 'projects');
                if (projectsResponse.success) {
                    this.populateProjectSelect(projectsResponse.projects);
                    // 作成したプロジェクトを選択
                    document.getElementById('projectSelect').value = response.project_id;
                    document.getElementById('projectSelect').dispatchEvent(new Event('change'));
                }
            } else {
                throw new Error(response.message);
            }
        } catch (error) {
            this.showAlert('プロジェクトの作成に失敗しました。', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async logout() {
        try {
            await this.apiCall('POST', 'logout');
        } catch (error) {
            // ログアウトエラーは無視
        } finally {
            this.redirectToLogin();
        }
    }

    redirectToLogin() {
        window.location.href = 'login.html';
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
        // 簡易アラート表示（将来的にはtoast UIに置き換え可能）
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.textContent = message;
        alertDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 6px;
            color: white;
            font-weight: 500;
            z-index: 3000;
            max-width: 300px;
        `;

        if (type === 'success') {
            alertDiv.style.backgroundColor = '#28a745';
        } else if (type === 'error') {
            alertDiv.style.backgroundColor = '#dc3545';
        } else {
            alertDiv.style.backgroundColor = '#007bff';
        }

        document.body.appendChild(alertDiv);

        setTimeout(() => {
            alertDiv.remove();
        }, 3000);
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

    formatPeriod(startDate, endDate) {
        const formatDate = (dateStr) => {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            return `${date.getFullYear()}/${(date.getMonth() + 1).toString().padStart(2, '0')}/${date.getDate().toString().padStart(2, '0')}`;
        };

        const start = formatDate(startDate);
        const end = formatDate(endDate);
        
        if (start && end) {
            return `${start} 〜 ${end}`;
        } else if (start) {
            return `${start} 〜`;
        } else if (end) {
            return `〜 ${end}`;
        }
        return '期間未設定';
    }

    getStatusText(status) {
        const statusTexts = {
            'planning': '計画中',
            'in_progress': '進行中',
            'completed': '完了',
            'cancelled': 'キャンセル',
            'not_started': '未着手',
            'not_applicable': '対象外'
        };
        return statusTexts[status] || status;
    }

    showDebugInfo() {
        try {
            const adminBtn = document.getElementById('adminBtn');
            const debugBtn = document.getElementById('debugBtn');

            const message = `デバッグ情報:
ユーザー: ${this.currentUser?.name || '未設定'}
権限: ${this.currentUser?.role || '未設定'}
管理ボタン表示: ${adminBtn?.style?.display || '不明'}
管理ボタン存在: ${adminBtn ? 'あり' : 'なし'}`;

            alert(message);

        } catch (error) {
            alert('デバッグ情報取得中にエラーが発生しました: ' + error.message);
        }
    }
}

// アプリケーション初期化
document.addEventListener('DOMContentLoaded', function() {
    try {
        window.roadDesignApp = new RoadDesignApp();
    } catch (error) {
        alert('アプリケーションの初期化に失敗しました: ' + error.message);
    }
});

// フォールバック: loadイベントでも初期化を試す
window.addEventListener('load', function() {
    if (!window.roadDesignApp) {
        try {
            window.roadDesignApp = new RoadDesignApp();
        } catch (error) {
            // 初期化エラーは無視
        }
    }
});

// グローバルデバッグ関数（ブラウザコンソールから呼び出し可能）
window.debugRoadDesign = function() {
    if (window.roadDesignApp) {
        window.roadDesignApp.showDebugInfo();
    } else {
        alert('RoadDesignAppが初期化されていません。ページをリロードしてください。');
    }
};

// 管理画面に直接遷移する関数
window.goToAdmin = function() {
    window.location.href = 'admin.html';
};

// 管理画面ボタンのテスト関数
window.testAdminButton = function() {
    const adminBtn = document.getElementById('adminBtn');

    if (!adminBtn) {
        return;
    }

    adminBtn.click();
};

// コンソールで利用可能な簡単なテスト関数
window.testButtons = function() {
    const buttons = ['debugBtn', 'adminBtn', 'logoutBtn', 'newProjectBtn'];
    buttons.forEach(id => {
        const btn = document.getElementById(id);
        // ボタンの存在確認のみ
    });
};

// 即時実行関数でグローバル関数が確実に定義されることを保証
(function() {
    // グローバル関数が定義されていることを確認
    if (typeof window.debugRoadDesign !== 'function') {
        window.debugRoadDesign = function() {
            alert('デバッグ機能が利用できません。ページをリロードしてください。');
        };
    }

    if (typeof window.goToAdmin !== 'function') {
        window.goToAdmin = function() {
            window.location.href = 'admin.html';
        };
    }

    if (typeof window.testAdminButton !== 'function') {
        window.testAdminButton = function() {
            const adminBtn = document.getElementById('adminBtn');
            if (adminBtn) {
                adminBtn.click();
            } else {
                alert('管理画面ボタンが見つかりません。');
            }
        };
    }
})();