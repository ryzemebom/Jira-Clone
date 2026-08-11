<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jira - Espaço de Trabalho</title>
    <link rel="stylesheet" href="style.css">
    <!-- FontAwesome icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <?php if (!$isLoggedIn): ?>
        <!-- Auth Container (Login / Register) -->
        <div class="auth-wrapper">
            <!-- Login Card -->
            <div class="auth-container" id="login-container">
                <div class="auth-header">
                    <i class="fa-brands fa-jira"></i>
                    <h2>Acessar Jira Clone</h2>
                    <p>Entre com suas credenciais de equipe</p>
                </div>
                <div class="error-message-box" id="login-error"></div>
                <form id="form-login">
                    <div class="form-group">
                        <label for="login-username">Nome de Usuário</label>
                        <input type="text" id="login-username" class="form-control" placeholder="Ex: joao_silva" required>
                    </div>
                    <div class="form-group">
                        <label for="login-password">Senha</label>
                        <input type="password" id="login-password" class="form-control" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; margin-top: 10px;">Entrar</button>
                </form>
                <div class="auth-switch">
                    Não tem uma conta? <a href="#" id="switch-to-register">Criar conta</a>
                </div>
            </div>

            <!-- Register Card (Hidden by default) -->
            <div class="auth-container" id="register-container" style="display: none;">
                <div class="auth-header">
                    <i class="fa-brands fa-jira"></i>
                    <h2>Registrar Equipe</h2>
                    <p>Crie uma nova conta de espaço de trabalho</p>
                </div>
                <div class="error-message-box" id="register-error"></div>
                <form id="form-register">
                    <div class="form-group">
                        <label for="register-username">Nome de Usuário</label>
                        <input type="text" id="register-username" class="form-control" placeholder="Ex: joao_silva" required>
                    </div>
                    <div class="form-group">
                        <label for="register-password">Senha</label>
                        <input type="password" id="register-password" class="form-control" placeholder="Crie uma senha forte" required>
                    </div>
                    <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; margin-top: 10px;">Registrar</button>
                </form>
                <div class="auth-switch">
                    Já tem uma conta? <a href="#" id="switch-to-login">Fazer Login</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Main Jira Workspace (Sidebar + Workspace content) -->
        <!-- Sidebar Navigation -->
        <aside>
            <div class="logo">
                <i class="fa-brands fa-jira"></i>
                <span>Jira Clone</span>
            </div>
            
            <div style="background: rgba(255,255,255,0.03); border:1px solid var(--panel-border); padding: 12px; border-radius: 12px; display: flex; align-items: center; gap: 10px; margin-bottom: 24px;">
                <div class="assignee-avatar" style="width: 32px; height: 32px; font-size: 12px; font-weight: 700;">
                    <?= strtoupper(substr($username, 0, 1)) ?>
                </div>
                <div style="overflow: hidden;">
                    <div style="font-size: 13px; font-weight: 600; text-overflow: ellipsis; white-space: nowrap;"><?= htmlspecialchars($username) ?></div>
                    <div style="font-size: 10px; color: var(--text-muted);">Membro da equipe</div>
                </div>
            </div>

            <ul class="nav-links" style="flex-grow: 1;">
                <li class="active" id="nav-board"><a href="#"><i class="fa-solid fa-square-poll-vertical"></i> <span data-i18n="menu-board">Quadro Kanban</span></a></li>
                <li id="nav-backlog"><a href="#"><i class="fa-solid fa-list-check"></i> <span data-i18n="menu-backlog">Backlog</span></a></li>
                <li id="nav-settings"><a href="#"><i class="fa-solid fa-gear"></i> <span data-i18n="menu-settings">Configurações</span></a></li>
            </ul>

            <div style="margin-top: auto; padding-top: 20px;">
                <a href="auth.php?action=logout" class="btn-secondary" style="display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; text-decoration: none; font-size: 13px;">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Sair
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main>
            <header>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <button class="btn-secondary" id="btn-toggle-sidebar" style="display: none; padding: 10px 14px;"><i class="fa-solid fa-bars"></i></button>
                    <div class="project-info">
                        <h1 id="view-title" data-i18n="title-board">Quadro Kanban</h1>
                        <p data-i18n="subtitle-sprint">Espaço de Trabalho • Sprint Ativa</p>
                    </div>
                </div>
                
                <div class="header-actions">
                    <input type="text" id="board-search" class="form-control" placeholder="Pesquisar tarefas..." style="width: 240px; margin-bottom: 0; background: #FAFBFC;" data-i18n-placeholder="search-placeholder">
                    <button class="btn-primary" id="btn-create-issue">
                        <i class="fa-solid fa-plus"></i> <span data-i18n="btn-create">Criar Tarefa</span>
                    </button>
                </div>
            </header>

            <!-- Board View -->
            <div class="board-container" id="board-view">
                <!-- A Fazer / To Do -->
                <div class="board-column" data-status="todo">
                    <div class="column-header">
                        <div class="column-title">
                            <span class="column-indicator todo-indicator"></span>
                            <span data-i18n="col-todo">A FAZER</span>
                            <span class="issue-count" id="count-todo">0</span>
                        </div>
                        <span class="column-points" id="points-todo">0 SP</span>
                    </div>
                    <div class="cards-container" id="container-todo"></div>
                </div>

                <!-- Em Progresso / In Progress -->
                <div class="board-column" data-status="in_progress">
                    <div class="column-header">
                        <div class="column-title">
                            <span class="column-indicator inprogress-indicator"></span>
                            <span data-i18n="col-inprogress">EM PROGRESSO</span>
                            <span class="issue-count" id="count-inprogress">0</span>
                        </div>
                        <span class="column-points" id="points-inprogress">0 SP</span>
                    </div>
                    <div class="cards-container" id="container-in_progress"></div>
                </div>

                <!-- Em Revisão / In Review -->
                <div class="board-column" data-status="review">
                    <div class="column-header">
                        <div class="column-title">
                            <span class="column-indicator review-indicator"></span>
                            <span data-i18n="col-review">EM REVISÃO</span>
                            <span class="issue-count" id="count-review">0</span>
                        </div>
                        <span class="column-points" id="points-review">0 SP</span>
                    </div>
                    <div class="cards-container" id="container-review"></div>
                </div>

                <!-- Concluído / Done -->
                <div class="board-column" data-status="done">
                    <div class="column-header">
                        <div class="column-title">
                            <span class="column-indicator done-indicator"></span>
                            <span data-i18n="col-done">CONCLUÍDO</span>
                            <span class="issue-count" id="count-done">0</span>
                        </div>
                        <span class="column-points" id="points-done">0 SP</span>
                    </div>
                    <div class="cards-container" id="container-done"></div>
                </div>
            </div>

            <!-- Backlog List View (Hidden by default) -->
            <div id="backlog-view" style="display: none; background: var(--panel-bg); border: 1px solid var(--panel-border); border-radius: 16px; padding: 24px; backdrop-filter: blur(12px);">
                <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--panel-border); padding-bottom: 16px; margin-bottom: 16px;">
                    <span style="font-weight: 600; color: var(--text-muted);" data-i18n="backlog-details">Detalhes da Tarefa</span>
                    <span style="font-weight: 600; color: var(--text-muted);" data-i18n="backlog-assignee">Responsável & Status</span>
                </div>
                <div id="backlog-list" style="display: flex; flex-direction: column; gap: 12px;"></div>
            </div>

            <!-- Settings View (Hidden by default) -->
            <div id="settings-view" style="display: none; background: var(--panel-bg); border: 1px solid var(--panel-border); border-radius: 16px; padding: 24px; backdrop-filter: blur(12px); max-width: 600px;">
                <h3 style="margin-bottom: 20px;" data-i18n="settings-title">Configurações globais</h3>
                <div class="form-group">
                    <label for="settings-language" data-i18n="settings-lang-label">Idioma do Sistema</label>
                    <select id="settings-language" class="form-control" style="max-width: 300px;">
                        <option value="pt-br">Português (Brasil)</option>
                        <option value="en">English (United States)</option>
                    </select>
                </div>
                <div style="margin-top: 24px;">
                    <button type="button" class="btn-primary" id="btn-save-settings" data-i18n="settings-save-btn">Salvar Configurações</button>
                </div>
            </div>
        </main>

        <!-- Create Issue Modal -->
        <div class="modal-overlay" id="modal-create">
            <div class="modal-container">
                <div class="modal-header">
                    <h2 data-i18n="modal-create-title">Criar Tarefa</h2>
                    <button class="btn-close" onclick="closeModal('modal-create')">&times;</button>
                </div>
                <form id="form-create-issue">
                    <div class="form-group">
                        <label for="create-title" data-i18n="field-title">Título</label>
                        <input type="text" id="create-title" class="form-control" required placeholder="Descreva a tarefa" data-i18n-placeholder="create-title-placeholder">
                    </div>
                    <div class="form-group">
                        <label for="create-desc" data-i18n="field-desc">Descrição</label>
                        <textarea id="create-desc" class="form-control" placeholder="Adicione detalhes..." data-i18n-placeholder="create-desc-placeholder"></textarea>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label for="create-priority" data-i18n="field-priority">Prioridade</label>
                            <select id="create-priority" class="form-control">
                                <option value="low" data-i18n="priority-low">Baixa</option>
                                <option value="medium" selected data-i18n="priority-medium">Média</option>
                                <option value="high" data-i18n="priority-high">Alta</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="create-assignee" data-i18n="field-assignee">Responsável</label>
                            <input type="text" id="create-assignee" class="form-control" placeholder="Nome do responsável" data-i18n-placeholder="create-assignee-placeholder">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label for="create-storypoints" data-i18n="field-points">Story Points</label>
                            <input type="number" id="create-storypoints" class="form-control" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label for="create-label" data-i18n="field-tag">Etiqueta</label>
                            <select id="create-label" class="form-control">
                                <option value="Tarefa" selected data-i18n="label-task">Tarefa</option>
                                <option value="Bug" data-i18n="label-bug">Bug</option>
                                <option value="Feature" data-i18n="label-feature">Funcionalidade</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="create-status" data-i18n="field-status">Status</label>
                        <select id="create-status" class="form-control">
                            <option value="todo" data-i18n="status-todo">A Fazer</option>
                            <option value="in_progress" data-i18n="status-inprogress">Em Progresso</option>
                            <option value="review" data-i18n="status-review">Em Revisão</option>
                            <option value="done" data-i18n="status-done">Concluído</option>
                        </select>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal('modal-create')" data-i18n="btn-cancel">Cancelar</button>
                        <button type="submit" class="btn-primary" data-i18n="btn-create-submit">Criar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Issue Details & Edit Modal -->
        <div class="modal-overlay" id="modal-details">
            <div class="modal-container" style="max-height: 85vh; overflow-y: auto;">
                <div class="modal-header">
                    <h2 id="details-issue-id">Detalhes da Tarefa</h2>
                    <button class="btn-close" onclick="closeModal('modal-details')">&times;</button>
                </div>
                <form id="form-edit-issue">
                    <input type="hidden" id="edit-id">
                    <div class="form-group">
                        <label for="edit-title" data-i18n="field-title">Título</label>
                        <input type="text" id="edit-title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-desc" data-i18n="field-desc">Descrição</label>
                        <textarea id="edit-desc" class="form-control"></textarea>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label for="edit-priority" data-i18n="field-priority">Prioridade</label>
                            <select id="edit-priority" class="form-control">
                                <option value="low" data-i18n="priority-low">Baixa</option>
                                <option value="medium" data-i18n="priority-medium">Média</option>
                                <option value="high" data-i18n="priority-high">Alta</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit-assignee" data-i18n="field-assignee">Responsável</label>
                            <input type="text" id="edit-assignee" class="form-control">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label for="edit-storypoints" data-i18n="field-points">Story Points</label>
                            <input type="number" id="edit-storypoints" class="form-control" min="0">
                        </div>
                        <div class="form-group">
                            <label for="edit-label" data-i18n="field-tag">Etiqueta</label>
                            <select id="edit-label" class="form-control">
                                <option value="Tarefa" data-i18n="label-task">Tarefa</option>
                                <option value="Bug" data-i18n="label-bug">Bug</option>
                                <option value="Feature" data-i18n="label-feature">Funcionalidade</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="edit-status" data-i18n="field-status">Status</label>
                        <select id="edit-status" class="form-control">
                            <option value="todo" data-i18n="status-todo">A Fazer</option>
                            <option value="in_progress" data-i18n="status-inprogress">Em Progresso</option>
                            <option value="review" data-i18n="status-review">Em Revisão</option>
                            <option value="done" data-i18n="status-done">Concluído</option>
                        </select>
                    </div>

                    <!-- Subtasks Checklist Section -->
                    <div class="subtasks-section">
                        <label style="font-weight: 600; color: #fff;" data-i18n="field-subtasks">Subtarefas</label>
                        <div class="progress-container" style="margin-top: 8px;">
                            <div class="progress-bar" id="subtask-progress"></div>
                        </div>
                        <div id="subtasks-list"></div>
                        <div style="display: flex; gap: 10px; margin-top: 12px;">
                            <input type="text" id="new-subtask-title" class="form-control" placeholder="Adicionar subtarefa..." style="margin-bottom:0;" data-i18n-placeholder="add-subtask-placeholder">
                            <button type="button" class="btn-secondary" id="btn-add-subtask" style="padding: 10px 16px;" data-i18n="btn-add">Adicionar</button>
                        </div>
                    </div>
                    
                    <!-- Comments inside card details -->
                    <div class="comments-section">
                        <label style="font-weight: 600; color: #fff;" data-i18n="field-comments">Comentários</label>
                        <div class="comments-list" id="comments-list"></div>
                        <div style="display: flex; gap: 10px; margin-top: 12px;">
                            <input type="text" id="new-comment-text" class="form-control" placeholder="Adicionar comentário..." style="margin-bottom:0;" data-i18n-placeholder="add-comment-placeholder">
                            <button type="button" class="btn-secondary" id="btn-add-comment" style="padding: 10px 16px;" data-i18n="btn-send">Enviar</button>
                        </div>
                    </div>

                    <div class="modal-actions" style="margin-top: 32px;">
                        <button type="button" class="btn-danger" id="btn-delete-issue" style="margin-right: auto;" data-i18n="btn-delete">Excluir</button>
                        <button type="button" class="btn-secondary" onclick="closeModal('modal-details')" data-i18n="btn-cancel">Cancelar</button>
                        <button type="submit" class="btn-primary" data-i18n="btn-save">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script src="board.js"></script>
</body>
</html>
