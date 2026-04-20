<?php
session_start();
require_once __DIR__ . '/../config/database.php';

checkAdmin();

$page_title = 'Kelola Pengguna';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'edit_status') {
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] === 'active' ? 'active' : 'inactive';

        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
                $stmt->execute([$status, $id]);
                $message = 'Status pengguna berhasil diperbarui!';
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0 && $id !== $_SESSION['user_id']) {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'Pengguna berhasil dihapus!';
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        } else {
            $error = 'Tidak dapat menghapus pengguna ini!';
        }
    }
}

try {
    $users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) {
    $error = $e->getMessage();
    $users = [];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="flex">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 ml-64 min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Top Navbar -->
        <nav class="navbar sticky top-0 z-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center gap-4">
                        <button class="lg:hidden" onclick="toggleSidebar()">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Kelola Pengguna</h1>
                    </div>
                    <div class="flex items-center gap-4">
                    </div>
                </div>
            </div>
        </nav>

        <!-- Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <?php if ($message): ?>
            <div class="alert alert-success mb-6">
                <i class="ph-fill ph-check-circle text-xl"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert alert-danger mb-6">
                <i class="ph-fill ph-warning-circle text-xl"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>

            <!-- Users Table -->
            <div class="card overflow-hidden">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Terdaftar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center text-primary font-bold text-sm shrink-0">
                                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <p class="font-500"><?php echo htmlspecialchars($user['full_name']); ?></p>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php if ($user['role'] === 'admin'): ?>
                                        <span class="badge badge-info">Admin</span>
                                    <?php else: ?>
                                        <span class="badge badge-primary">User</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="edit_status">
                                        <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                        <select name="status" onchange="this.form.submit()" class="px-3 py-1 rounded border text-sm">
                                            <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Aktif</option>
                                            <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Nonaktif</option>
                                        </select>
                                    </form>
                                </td>
                                <td><?php echo formatDate($user['created_at']); ?></td>
                                <td class="text-center">
                                    <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                    <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>)"><i class="ph ph-trash text-lg"></i></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

<!-- Delete Confirmation -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeDeleteModal()">&times;</span>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Konfirmasi Hapus</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-6">Apakah Anda yakin ingin menghapus pengguna ini? Semua data terkait akan dihapus permanen.</p>

        <form id="deleteForm" method="POST" class="flex gap-4">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="deleteUserId">
            <button type="submit" class="btn btn-danger flex-1">Hapus</button>
            <button type="button" class="btn btn-secondary flex-1" onclick="closeDeleteModal()">Batal</button>
        </form>
    </div>
</div>

<script>
function deleteUser(id) {
    document.getElementById('deleteUserId').value = id;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

window.onclick = function(event) {
    let deleteModal = document.getElementById('deleteModal');
    if (event.target === deleteModal) {
        deleteModal.style.display = 'none';
    }
}
</script>

        </div>

        <!-- Footer -->
        <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>
</body>
</html>
