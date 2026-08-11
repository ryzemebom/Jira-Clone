// board.js - Frontend application controller with dynamic i18n support and authentication

let allIssues = [];
let activeView = 'board'; // 'board', 'backlog', or 'settings'
let currentLang = localStorage.getItem('jira_lang') || 'pt-br';

const translations = {
    'pt-br': {
        'menu-board': 'Quadro Kanban',
        'menu-backlog': 'Backlog',
        'menu-settings': 'Configurações',
        'title-board': 'Quadro Kanban',
        'title-backlog': 'Backlog',
        'title-settings': 'Configurações Globais',
        'subtitle-sprint': 'Espaço de Trabalho • Sprint Ativa',
        'search-placeholder': 'Pesquisar tarefas...',
        'btn-create': 'Criar Tarefa',
        'col-todo': 'A FAZER',
        'col-inprogress': 'EM PROGRESSO',
        'col-review': 'EM REVISÃO',
        'col-done': 'CONCLUÍDO',
        'backlog-details': 'Detalhes da Tarefa',
        'backlog-assignee': 'Responsável & Status',
        'settings-title': 'Configurações Globais',
        'settings-lang-label': 'Idioma do Sistema',
        'settings-save-btn': 'Salvar Configurações',
        'modal-create-title': 'Criar Tarefa',
        'field-title': 'Título',
        'field-desc': 'Descrição',
        'field-priority': 'Prioridade',
        'field-assignee': 'Responsável',
        'field-points': 'Story Points',
        'field-tag': 'Etiqueta',
        'field-status': 'Status',
        'priority-low': 'Baixa',
        'priority-medium': 'Média',
        'priority-high': 'Alta',
        'label-task': 'Tarefa',
        'label-bug': 'Bug',
        'label-feature': 'Funcionalidade',
        'status-todo': 'A Fazer',
        'status-inprogress': 'Em Progresso',
        'status-review': 'Em Revisão',
        'status-done': 'Concluído',
        'create-title-placeholder': 'Descreva a tarefa',
        'create-desc-placeholder': 'Adicione detalhes...',
        'create-assignee-placeholder': 'Nome do responsável',
        'btn-cancel': 'Cancelar',
        'btn-create-submit': 'Criar',
        'btn-delete': 'Excluir',
        'btn-save': 'Salvar Alterações',
        'field-subtasks': 'Subtarefas',
        'add-subtask-placeholder': 'Adicionar subtarefa...',
        'btn-add': 'Adicionar',
        'field-comments': 'Comentários',
        'add-comment-placeholder': 'Adicionar comentário...',
        'btn-send': 'Enviar',
        'confirm-delete': 'Tem certeza que deseja excluir esta tarefa?',
        'no-issues': 'Nenhuma tarefa encontrada.',
        'loading-comments': 'Carregando comentários...',
        'no-comments': 'Sem comentários ainda.',
        'error-comments': 'Erro ao carregar comentários.',
        'loading-subtasks': 'Carregando subtarefas...',
        'no-subtasks': 'Sem subtarefas ainda.',
        'error-subtasks': 'Erro ao carregar subtarefas.'
    },
    'en': {
        'menu-board': 'Kanban Board',
        'menu-backlog': 'Backlog',
        'menu-settings': 'Settings',
        'title-board': 'Kanban Board',
        'title-backlog': 'Backlog',
        'title-settings': 'Global Settings',
        'subtitle-sprint': 'Team Space • Active Sprint',
        'search-placeholder': 'Search issues...',
        'btn-create': 'Create Issue',
        'col-todo': 'TO DO',
        'col-inprogress': 'IN PROGRESS',
        'col-review': 'IN REVIEW',
        'col-done': 'DONE',
        'backlog-details': 'Issue Details',
        'backlog-assignee': 'Assignee & Status',
        'settings-title': 'Global Settings',
        'settings-lang-label': 'System Language',
        'settings-save-btn': 'Save Settings',
        'modal-create-title': 'Create Issue',
        'field-title': 'Title',
        'field-desc': 'Description',
        'field-priority': 'Priority',
        'field-assignee': 'Assignee',
        'field-points': 'Story Points',
        'field-tag': 'Label',
        'field-status': 'Status',
        'priority-low': 'Low',
        'priority-medium': 'Medium',
        'priority-high': 'High',
        'label-task': 'Task',
        'label-bug': 'Bug',
        'label-feature': 'Feature',
        'status-todo': 'To Do',
        'status-inprogress': 'In Progress',
        'status-review': 'In Review',
        'status-done': 'Done',
        'create-title-placeholder': 'Describe the task',
        'create-desc-placeholder': 'Add details...',
        'create-assignee-placeholder': 'Assignee name',
        'btn-cancel': 'Cancel',
        'btn-create-submit': 'Create',
        'btn-delete': 'Delete',
        'btn-save': 'Save Changes',
        'field-subtasks': 'Subtasks',
        'add-subtask-placeholder': 'Add subtask...',
        'btn-add': 'Add',
        'field-comments': 'Comments',
        'add-comment-placeholder': 'Add comment...',
        'btn-send': 'Send',
        'confirm-delete': 'Are you sure you want to delete this issue?',
        'no-issues': 'No issues found.',
        'loading-comments': 'Loading comments...',
        'no-comments': 'No comments yet.',
        'error-comments': 'Error loading comments.',
        'loading-subtasks': 'Loading subtasks...',
        'no-subtasks': 'No subtasks yet.',
        'error-subtasks': 'Error loading subtasks.'
    }
};

document.addEventListener('DOMContentLoaded', () => {
    applyLanguage(currentLang);
    setupAuthListeners();

    // Check if the board interface is present before loading issues
    if (document.getElementById('board-search')) {
        loadIssues();
        setupEventListeners();
    }
});

// Setup Login & Registration event listeners
function setupAuthListeners() {
    const toRegister = document.getElementById('switch-to-register');
    const toLogin = document.getElementById('switch-to-login');
    const loginCard = document.getElementById('login-container');
    const registerCard = document.getElementById('register-container');

    if (toRegister && toLogin) {
        toRegister.addEventListener('click', (e) => {
            e.preventDefault();
            loginCard.style.display = 'none';
            registerCard.style.display = 'block';
        });

        toLogin.addEventListener('click', (e) => {
            e.preventDefault();
            registerCard.style.display = 'none';
            loginCard.style.display = 'block';
        });
    }

    const formLogin = document.getElementById('form-login');
    if (formLogin) {
        formLogin.addEventListener('submit', async (e) => {
            e.preventDefault();
            const errBox = document.getElementById('login-error');
            errBox.style.display = 'none';

            const data = {
                username: document.getElementById('login-username').value,
                password: document.getElementById('login-password').value
            };

            try {
                const res = await fetch('auth.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                if (res.ok && result.success) {
                    window.location.reload();
                } else {
                    errBox.innerText = result.error || 'Login falhou / Login failed';
                    errBox.style.display = 'block';
                }
            } catch (err) {
                console.error(err);
            }
        });
    }

    const formRegister = document.getElementById('form-register');
    if (formRegister) {
        formRegister.addEventListener('submit', async (e) => {
            e.preventDefault();
            const errBox = document.getElementById('register-error');
            errBox.style.display = 'none';

            const data = {
                username: document.getElementById('register-username').value,
                password: document.getElementById('register-password').value
            };

            try {
                const res = await fetch('auth.php?action=register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                if (res.ok && result.success) {
                    window.location.reload();
                } else {
                    errBox.innerText = result.error || 'Registro falhou / Registration failed';
                    errBox.style.display = 'block';
                }
            } catch (err) {
                console.error(err);
            }
        });
    }
}

// Update UI elements based on selected language keys
function applyLanguage(lang) {
    currentLang = lang;
    localStorage.setItem('jira_lang', lang);
    const settingsSelect = document.getElementById('settings-language');
    if (settingsSelect) settingsSelect.value = lang;

    document.querySelectorAll('[data-i18n]').forEach(elem => {
        const key = elem.getAttribute('data-i18n');
        if (translations[lang][key]) {
            elem.innerText = translations[lang][key];
        }
    });

    document.querySelectorAll('[data-i18n-placeholder]').forEach(elem => {
        const key = elem.getAttribute('data-i18n-placeholder');
        if (translations[lang][key]) {
            elem.setAttribute('placeholder', translations[lang][key]);
        }
    });

    document.documentElement.lang = lang === 'pt-br' ? 'pt-BR' : 'en';
}

// Check response helper to redirect to logout if unauthorized
function checkResponseStatus(res) {
    if (res.status === 401) {
        window.location.href = 'auth.php?action=logout';
        throw new Error('Session expired');
    }
    return res;
}

// Load all issues from the backend
async function loadIssues() {
    try {
        const response = await fetch('api.php');
        checkResponseStatus(response);
        allIssues = await response.json();
        renderView();
    } catch (err) {
        console.error('Error loading issues:', err);
    }
}

// Render active view: Board, Backlog, or Settings
function renderView() {
    const searchQuery = document.getElementById('board-search').value.toLowerCase();
    const filteredIssues = allIssues.filter(issue => {
        return (issue.title.toLowerCase().includes(searchQuery) || 
                (issue.description && issue.description.toLowerCase().includes(searchQuery)) ||
                (issue.assignee && issue.assignee.toLowerCase().includes(searchQuery)) ||
                (issue.label && issue.label.toLowerCase().includes(searchQuery)));
    });

    if (activeView === 'board') {
        renderBoard(filteredIssues);
    } else if (activeView === 'backlog') {
        renderBacklog(filteredIssues);
    }
}

// Render cards inside the Kanban Board columns
function renderBoard(issues) {
    const containers = {
        todo: document.getElementById('container-todo'),
        in_progress: document.getElementById('container-in_progress'),
        review: document.getElementById('container-review'),
        done: document.getElementById('container-done')
    };

    Object.values(containers).forEach(c => c.innerHTML = '');

    const counts = { todo: 0, in_progress: 0, review: 0, done: 0 };
    const points = { todo: 0, in_progress: 0, review: 0, done: 0 };

    issues.forEach(issue => {
        const status = issue.status;
        if (containers[status]) {
            counts[status]++;
            points[status] += parseInt(issue.story_points || 0);
            const card = createCardElement(issue);
            containers[status].appendChild(card);
        }
    });

    document.getElementById('count-todo').innerText = counts.todo;
    document.getElementById('count-inprogress').innerText = counts.in_progress;
    document.getElementById('count-review').innerText = counts.review;
    document.getElementById('count-done').innerText = counts.done;

    document.getElementById('points-todo').innerText = `${points.todo} SP`;
    document.getElementById('points-inprogress').innerText = `${points.in_progress} SP`;
    document.getElementById('points-review').innerText = `${points.review} SP`;
    document.getElementById('points-done').innerText = `${points.done} SP`;
}

// Create an issue card element
function createCardElement(issue) {
    const card = document.createElement('div');
    card.className = `issue-card priority-${issue.priority}`;
    card.draggable = true;
    card.dataset.id = issue.id;

    const notAssignedText = currentLang === 'pt-br' ? 'Não atribuído' : 'Unassigned';
    const displayAssignee = issue.assignee || notAssignedText;
    const initial = (displayAssignee !== notAssignedText) ? displayAssignee.charAt(0).toUpperCase() : '?';

    let displayLabel = issue.label || 'Task';
    if (currentLang === 'pt-br') {
        if (displayLabel === 'Task') displayLabel = 'Tarefa';
        if (displayLabel === 'Feature') displayLabel = 'Funcionalidade';
    } else {
        if (displayLabel === 'Tarefa') displayLabel = 'Task';
        if (displayLabel === 'Funcionalidade') displayLabel = 'Feature';
    }

    let displayPriority = issue.priority;
    if (currentLang === 'pt-br') {
        if (displayPriority === 'low') displayPriority = 'baixa';
        if (displayPriority === 'medium') displayPriority = 'média';
        if (displayPriority === 'high') displayPriority = 'alta';
    }

    card.innerHTML = `
        <div class="card-tags">
            <span class="label-badge ${displayLabel.toLowerCase() === 'funcionalidade' ? 'feature' : (displayLabel.toLowerCase() === 'tarefa' ? 'task' : displayLabel.toLowerCase())}">${escapeHTML(displayLabel)}</span>
        </div>
        <div class="card-title">${escapeHTML(issue.title)}</div>
        <div class="card-desc">${escapeHTML(issue.description || (currentLang === 'pt-br' ? 'Sem descrição' : 'No description'))}</div>
        <div class="card-footer">
            <span class="priority-badge ${issue.priority}">${displayPriority}</span>
            <div style="display:flex; align-items:center; gap:8px;">
                <span class="story-badge">${issue.story_points || 0}</span>
                <div class="assignee">
                    <div class="assignee-avatar">${initial}</div>
                </div>
            </div>
        </div>
    `;

    card.addEventListener('click', (e) => {
        if (e.target.closest('.assignee-avatar') === null) {
            openDetailsModal(issue);
        }
    });

    card.addEventListener('dragstart', handleDragStart);
    card.addEventListener('dragend', handleDragEnd);

    return card;
}

// Render Backlog List View
function renderBacklog(issues) {
    const listContainer = document.getElementById('backlog-list');
    listContainer.innerHTML = '';

    if (issues.length === 0) {
        listContainer.innerHTML = `<div style="text-align: center; color: var(--text-muted); padding: 20px;">${translations[currentLang]['no-issues']}</div>`;
        return;
    }

    issues.forEach(issue => {
        let displayLabel = issue.label || 'Task';
        if (currentLang === 'pt-br') {
            if (displayLabel === 'Task') displayLabel = 'Tarefa';
            if (displayLabel === 'Feature') displayLabel = 'Funcionalidade';
        }

        let displayStatus = issue.status.replace('_', ' ');
        if (currentLang === 'pt-br') {
            if (issue.status === 'todo') displayStatus = 'A Fazer';
            if (issue.status === 'in_progress') displayStatus = 'Em Progresso';
            if (issue.status === 'review') displayStatus = 'Em Revisão';
            if (issue.status === 'done') displayStatus = 'Concluído';
        }

        const item = document.createElement('div');
        item.style.cssText = `
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--card-bg);
            border: 1px solid var(--panel-border);
            border-radius: 12px;
            padding: 16px;
            cursor: pointer;
            transition: background 0.2s ease;
        `;
        item.addEventListener('mouseenter', () => item.style.background = 'var(--card-hover)');
        item.addEventListener('mouseleave', () => item.style.background = 'var(--card-bg)');
        
        item.innerHTML = `
            <div>
                <div style="display:flex; align-items:center; gap:8px; margin-bottom: 4px;">
                    <span class="label-badge ${displayLabel.toLowerCase() === 'funcionalidade' ? 'feature' : (displayLabel.toLowerCase() === 'tarefa' ? 'task' : displayLabel.toLowerCase())}" style="font-size:8px; padding:1px 4px;">${escapeHTML(displayLabel)}</span>
                    <div style="font-weight: 600;">${escapeHTML(issue.title)}</div>
                </div>
                <span class="priority-badge ${issue.priority}" style="font-size: 9px; padding: 1px 6px;">${issue.priority}</span>
                <span style="font-size: 11px; margin-left: 8px; color: var(--text-muted);">${issue.story_points || 0} SP</span>
            </div>
            <div style="display: flex; align-items: center; gap: 20px;">
                <span style="color: var(--text-muted); font-size: 13px;">${escapeHTML(issue.assignee)}</span>
                <span style="
                    font-size: 11px;
                    font-weight: 600;
                    padding: 4px 10px;
                    border-radius: 6px;
                    text-transform: uppercase;
                    background: rgba(255,255,255,0.08);
                    color: #fff;
                ">${displayStatus}</span>
            </div>
        `;
        
        item.addEventListener('click', () => openDetailsModal(issue));
        listContainer.appendChild(item);
    });
}

// Drag & Drop handlers
let draggedCard = null;

function handleDragStart(e) {
    draggedCard = this;
    this.style.opacity = '0.5';
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', this.dataset.id);
}

function handleDragEnd(e) {
    this.style.opacity = '1';
    document.querySelectorAll('.board-column').forEach(col => col.classList.remove('drag-over'));
}

// Event Listeners Setup for Kanban Board
function setupEventListeners() {
    document.getElementById('board-search').addEventListener('input', renderView);

    // Toggle sidebar on mobile
    const toggleBtn = document.getElementById('btn-toggle-sidebar');
    const sidebar = document.querySelector('aside');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });
    }

    document.getElementById('btn-create-issue').addEventListener('click', () => {
        openModal('modal-create');
    });

    document.getElementById('form-create-issue').addEventListener('submit', handleCreateIssue);
    document.getElementById('form-edit-issue').addEventListener('submit', handleEditIssue);
    document.getElementById('btn-delete-issue').addEventListener('click', handleDeleteIssue);
    document.getElementById('btn-add-comment').addEventListener('click', handleAddComment);
    document.getElementById('btn-add-subtask').addEventListener('click', handleAddSubtask);

    document.getElementById('btn-save-settings').addEventListener('click', () => {
        const selectedLang = document.getElementById('settings-language').value;
        applyLanguage(selectedLang);
        alert(selectedLang === 'pt-br' ? 'Configurações salvas com sucesso!' : 'Settings saved successfully!');
        document.getElementById('nav-board').click();
    });

    document.getElementById('nav-board').addEventListener('click', (e) => {
        e.preventDefault();
        activeView = 'board';
        document.getElementById('nav-board').classList.add('active');
        document.getElementById('nav-backlog').classList.remove('active');
        document.getElementById('nav-settings').classList.remove('active');
        
        document.getElementById('board-view').style.display = 'grid';
        document.getElementById('backlog-view').style.display = 'none';
        document.getElementById('settings-view').style.display = 'none';
        
        document.getElementById('view-title').innerText = translations[currentLang]['title-board'];
        if (sidebar.classList.contains('active')) sidebar.classList.remove('active');
        renderView();
    });

    document.getElementById('nav-backlog').addEventListener('click', (e) => {
        e.preventDefault();
        activeView = 'backlog';
        document.getElementById('nav-backlog').classList.add('active');
        document.getElementById('nav-board').classList.remove('active');
        document.getElementById('nav-settings').classList.remove('active');
        
        document.getElementById('board-view').style.display = 'none';
        document.getElementById('backlog-view').style.display = 'block';
        document.getElementById('settings-view').style.display = 'none';
        
        document.getElementById('view-title').innerText = translations[currentLang]['title-backlog'];
        if (sidebar.classList.contains('active')) sidebar.classList.remove('active');
        renderView();
    });

    document.getElementById('nav-settings').addEventListener('click', (e) => {
        e.preventDefault();
        activeView = 'settings';
        document.getElementById('nav-settings').classList.add('active');
        document.getElementById('nav-board').classList.remove('active');
        document.getElementById('nav-backlog').classList.remove('active');
        
        document.getElementById('board-view').style.display = 'none';
        document.getElementById('backlog-view').style.display = 'none';
        document.getElementById('settings-view').style.display = 'block';
        
        document.getElementById('view-title').innerText = translations[currentLang]['title-settings'];
        if (sidebar.classList.contains('active')) sidebar.classList.remove('active');
    });

    const columns = document.querySelectorAll('.board-column');
    columns.forEach(column => {
        column.addEventListener('dragover', (e) => {
            e.preventDefault();
            column.classList.add('drag-over');
        });

        column.addEventListener('dragleave', () => {
            column.classList.remove('drag-over');
        });

        column.addEventListener('drop', async (e) => {
            e.preventDefault();
            const id = e.dataTransfer.getData('text/plain');
            const targetStatus = column.dataset.status;

            if (id && targetStatus) {
                const issue = allIssues.find(i => i.id == id);
                if (issue && issue.status !== targetStatus) {
                    issue.status = targetStatus;
                    renderView();

                    try {
                        const res = await fetch('api.php', {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id: id, status: targetStatus, status_only: true })
                        });
                        checkResponseStatus(res);
                    } catch (err) {
                        console.error('Failed to update issue status:', err);
                    }
                }
            }
            column.classList.remove('drag-over');
        });
    });
}

function openModal(id) {
    document.getElementById(id).classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

async function openDetailsModal(issue) {
    document.getElementById('details-issue-id').innerText = `Issue #${issue.id}`;
    document.getElementById('edit-id').value = issue.id;
    document.getElementById('edit-title').value = issue.title;
    document.getElementById('edit-desc').value = issue.description || '';
    document.getElementById('edit-priority').value = issue.priority;
    document.getElementById('edit-assignee').value = issue.assignee;
    document.getElementById('edit-status').value = issue.status;
    document.getElementById('edit-storypoints').value = issue.story_points || 0;
    
    let normalizedLabel = issue.label || 'Task';
    if (normalizedLabel === 'Task') normalizedLabel = 'Tarefa';
    if (normalizedLabel === 'Feature') normalizedLabel = 'Feature'; 
    document.getElementById('edit-label').value = normalizedLabel;
    
    document.getElementById('new-subtask-title').value = '';
    document.getElementById('new-comment-text').value = '';

    await loadSubtasks(issue.id);
    await loadComments(issue.id);
    
    openModal('modal-details');
}

// Subtasks CRUD Frontend Functions
async function loadSubtasks(issueId) {
    const list = document.getElementById('subtasks-list');
    list.innerHTML = `<span style="font-size:12px; color:var(--text-muted)">${translations[currentLang]['loading-subtasks']}</span>`;
    
    try {
        const res = await fetch(`api.php?action=subtasks&issue_id=${issueId}`);
        checkResponseStatus(res);
        const subtasks = await res.json();
        list.innerHTML = '';
        
        let completedCount = 0;
        
        if (subtasks.length === 0) {
            list.innerHTML = `<span style="font-size:12px; color:var(--text-muted)">${translations[currentLang]['no-subtasks']}</span>`;
            updateProgressBar(0);
            return;
        }
        
        subtasks.forEach(subtask => {
            if (subtask.completed == 1) completedCount++;
            
            const item = document.createElement('div');
            item.className = `subtask-item ${subtask.completed == 1 ? 'completed' : ''}`;
            item.innerHTML = `
                <input type="checkbox" class="subtask-checkbox" ${subtask.completed == 1 ? 'checked' : ''}>
                <span>${escapeHTML(subtask.title)}</span>
                <button type="button" class="btn-delete-subtask"><i class="fa-solid fa-trash"></i></button>
            `;
            
            item.querySelector('.subtask-checkbox').addEventListener('change', async (e) => {
                const isChecked = e.target.checked ? 1 : 0;
                try {
                    const res = await fetch('api.php?action=toggle_subtask', {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: subtask.id, completed: isChecked })
                    });
                    checkResponseStatus(res);
                    loadSubtasks(issueId);
                } catch (err) {
                    console.error('Failed to toggle subtask:', err);
                }
            });
            
            item.querySelector('.btn-delete-subtask').addEventListener('click', async () => {
                try {
                    const res = await fetch(`api.php?action=delete_subtask&id=${subtask.id}`, {
                        method: 'DELETE'
                    });
                    checkResponseStatus(res);
                    loadSubtasks(issueId);
                } catch (err) {
                    console.error('Failed to delete subtask:', err);
                }
            });

            list.appendChild(item);
        });
        
        const percentage = Math.round((completedCount / subtasks.length) * 100);
        updateProgressBar(percentage);
        
    } catch (err) {
        list.innerHTML = `<span style="color:var(--danger)">${translations[currentLang]['error-subtasks']}</span>`;
    }
}

function updateProgressBar(percentage) {
    document.getElementById('subtask-progress').style.width = `${percentage}%`;
}

async function handleAddSubtask() {
    const issueId = document.getElementById('edit-id').value;
    const title = document.getElementById('new-subtask-title').value.trim();
    
    if (!title) return;
    
    try {
        const response = await fetch('api.php?action=create_subtask', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                issue_id: issueId,
                title: title
            })
        });
        checkResponseStatus(response);
        
        if (response.ok) {
            document.getElementById('new-subtask-title').value = '';
            loadSubtasks(issueId);
        }
    } catch (err) {
        console.error('Error adding subtask:', err);
    }
}

async function loadComments(issueId) {
    const list = document.getElementById('comments-list');
    list.innerHTML = `<span style="font-size:12px; color:var(--text-muted)">${translations[currentLang]['loading-comments']}</span>`;
    
    try {
        const res = await fetch(`api.php?action=comments&issue_id=${issueId}`);
        checkResponseStatus(res);
        const comments = await res.json();
        list.innerHTML = '';
        
        if (comments.length === 0) {
            list.innerHTML = `<span style="font-size:12px; color:var(--text-muted)">${translations[currentLang]['no-comments']}</span>`;
            return;
        }
        
        comments.forEach(comment => {
            const item = document.createElement('div');
            item.className = 'comment-item';
            
            let displayAuthor = comment.author;
            if (currentLang === 'pt-br' && displayAuthor === 'Anonymous') displayAuthor = 'Anônimo';
            if (currentLang === 'en' && displayAuthor === 'Anônimo') displayAuthor = 'Anonymous';

            item.innerHTML = `
                <div class="comment-meta">
                    <strong>${escapeHTML(displayAuthor)}</strong>
                    <span>${comment.created_at}</span>
                </div>
                <div class="comment-text">${escapeHTML(comment.comment_text)}</div>
            `;
            list.appendChild(item);
        });
    } catch (err) {
        list.innerHTML = `<span style="color:var(--danger)">${translations[currentLang]['error-comments']}</span>`;
    }
}

// API Submit handlers
async function handleCreateIssue(e) {
    e.preventDefault();
    const data = {
        title: document.getElementById('create-title').value,
        description: document.getElementById('create-desc').value,
        priority: document.getElementById('create-priority').value,
        assignee: document.getElementById('create-assignee').value || (currentLang === 'pt-br' ? 'Não atribuído' : 'Unassigned'),
        status: document.getElementById('create-status').value,
        story_points: document.getElementById('create-storypoints').value || 0,
        label: document.getElementById('create-label').value
    };

    try {
        const response = await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        checkResponseStatus(response);
        
        if (response.ok) {
            closeModal('modal-create');
            document.getElementById('form-create-issue').reset();
            loadIssues();
        }
    } catch (err) {
        console.error('Error creating issue:', err);
    }
}

async function handleEditIssue(e) {
    e.preventDefault();
    const data = {
        id: document.getElementById('edit-id').value,
        title: document.getElementById('edit-title').value,
        description: document.getElementById('edit-desc').value,
        priority: document.getElementById('edit-priority').value,
        assignee: document.getElementById('edit-assignee').value || (currentLang === 'pt-br' ? 'Não atribuído' : 'Unassigned'),
        status: document.getElementById('edit-status').value,
        story_points: document.getElementById('edit-storypoints').value || 0,
        label: document.getElementById('edit-label').value
    };

    try {
        const response = await fetch('api.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        checkResponseStatus(response);
        
        if (response.ok) {
            closeModal('modal-details');
            loadIssues();
        }
    } catch (err) {
        console.error('Error updating issue:', err);
    }
}

async function handleDeleteIssue() {
    const id = document.getElementById('edit-id').value;
    if (confirm(translations[currentLang]['confirm-delete'])) {
        try {
            const response = await fetch(`api.php?id=${id}`, {
                method: 'DELETE'
            });
            checkResponseStatus(response);
            
            if (response.ok) {
                closeModal('modal-details');
                loadIssues();
            }
        } catch (err) {
            console.error('Error deleting issue:', err);
        }
    }
}

async function handleAddComment() {
    const issueId = document.getElementById('edit-id').value;
    const text = document.getElementById('new-comment-text').value.trim();
    
    if (!text) return;
    
    try {
        const response = await fetch('api.php?action=comment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                issue_id: issueId,
                author: currentLang === 'pt-br' ? 'Gerente' : 'Manager',
                comment_text: text
            })
        });
        checkResponseStatus(response);
        
        if (response.ok) {
            document.getElementById('new-comment-text').value = '';
            loadComments(issueId);
        }
    } catch (err) {
        console.error('Error adding comment:', err);
    }
}

// Helpers
function escapeHTML(str) {
    if (!str) return '';
    return str.replace(/[&<>'"]/g, 
        tag => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#39;',
            '"': '&quot;'
        }[tag] || tag)
    );
}
