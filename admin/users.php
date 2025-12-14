<?php
require_once '../config/session.php';
require_once '../models/User.php';
require_once '../models/MedicalRecord.php';

requireAdmin();

$userModel = new User();
$recordModel = new MedicalRecord();

$error = '';
$success = '';

// Обработка удаления пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    $user_id_to_delete = intval($_POST['user_id'] ?? 0);
    
    if ($user_id_to_delete > 0 && $user_id_to_delete != $_SESSION['user_id']) {
        if ($userModel->deleteUser($user_id_to_delete, $_SESSION['user_id'])) {
            $success = 'Пользователь успешно удален. Его записи сохранены.';
        } else {
            $error = 'Ошибка при удалении пользователя';
        }
    } else {
        $error = 'Нельзя удалить самого себя';
    }
}

// Обработка обновления пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user') {
    $user_id_to_update = intval($_POST['user_id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';
    
    if ($user_id_to_update > 0 && !empty($username) && !empty($email)) {
        if ($userModel->updateUser($user_id_to_update, $username, $email, $role)) {
            $success = 'Пользователь успешно обновлен';
        } else {
            $error = 'Ошибка при обновлении пользователя';
        }
    } else {
        $error = 'Заполните все поля';
    }
}

// Получаем всех пользователей
$users = $userModel->getAllUsers();

$pageTitle = "Управление пользователями";
require_once '../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1>Управление пользователями</h1>
        <a href="dashboard.php" class="btn btn-secondary">← Назад к панели</a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <div class="admin-section">
        <h2>Список пользователей</h2>
        
        <?php if (empty($users)): ?>
            <div class="empty-state">
                <p>Пользователи не найдены</p>
            </div>
        <?php else: ?>
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Имя пользователя</th>
                            <th>Email</th>
                            <th>Роль</th>
                            <th>Записей</th>
                            <th>Дата регистрации</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="role-badge <?php echo $user['role'] === 'admin' ? 'role-admin' : 'role-user'; ?>">
                                        <?php echo $user['role'] === 'admin' ? 'Администратор' : 'Пользователь'; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="user_records.php?user_id=<?php echo $user['id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <?php echo $user['record_count']; ?> записей
                                    </a>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                                <td class="action-buttons">
                                    <a href="user_records.php?user_id=<?php echo $user['id']; ?>" 
                                       class="btn btn-sm btn-primary">Записи</a>
                                    <button type="button" 
                                            class="btn btn-sm btn-secondary" 
                                            onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                        Редактировать
                                    </button>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" style="display: inline;" 
                                              onsubmit="return confirm('Вы уверены, что хотите удалить пользователя <?php echo htmlspecialchars($user['username']); ?>? Его записи будут сохранены.');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Удалить</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">Текущий пользователь</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Модальное окно для редактирования пользователя -->
<div id="editUserModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Редактировать пользователя</h2>
            <span class="modal-close" onclick="closeEditModal()">&times;</span>
        </div>
        <form method="POST" id="editUserForm">
            <input type="hidden" name="action" value="update_user">
            <input type="hidden" name="user_id" id="edit_user_id">
            
            <div class="form-group">
                <label for="edit_username">Имя пользователя:</label>
                <input type="text" id="edit_username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="edit_email">Email:</label>
                <input type="email" id="edit_email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="edit_role">Роль:</label>
                <select id="edit_role" name="role" required>
                    <option value="user">Пользователь</option>
                    <option value="admin">Администратор</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Сохранить</button>
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Отмена</button>
            </div>
        </form>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('editUserModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editUserModal').style.display = 'none';
}

// Закрытие модального окна при клике вне его
window.onclick = function(event) {
    const modal = document.getElementById('editUserModal');
    if (event.target == modal) {
        closeEditModal();
    }
}
</script>

<style>
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    justify-content: center;
    align-items: center;
}

.modal-content {
    background-color: var(--card-bg);
    padding: 2rem;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    border-bottom: 2px solid var(--border-color);
    padding-bottom: 1rem;
}

.modal-close {
    font-size: 2rem;
    cursor: pointer;
    color: var(--text-light);
}

.modal-close:hover {
    color: var(--text-color);
}

.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
}

.role-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.875rem;
    font-weight: 600;
}

.role-admin {
    background-color: var(--admin-color);
    color: white;
}

.role-user {
    background-color: var(--secondary-color);
    color: white;
}

.text-muted {
    color: var(--text-light);
    font-size: 0.875rem;
}
</style>

<?php require_once '../includes/footer.php'; ?>
