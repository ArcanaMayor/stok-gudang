<?php
session_start();
require_once __DIR__ . '/../config/database.php';

checkAdmin();

$page_title = 'Kelola Kategori';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $icon = sanitize($_POST['icon'] ?? '📚');

        if (empty($name)) {
            $error = 'Nama kategori harus diisi!';
        } else {
            try {
                $slug = slugify($name);
                $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, icon) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $slug, $description, $icon]);
                $message = 'Kategori berhasil ditambahkan!';
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $icon = sanitize($_POST['icon'] ?? '📚');

        if ($id <= 0 || empty($name)) {
            $error = 'Data tidak valid!';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, icon = ? WHERE id = ?");
                $stmt->execute([$name, $description, $icon, $id]);
                $message = 'Kategori berhasil diperbarui!';
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'Kategori berhasil dihapus!';
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
}

try {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
} catch (PDOException $e) {
    $error = $e->getMessage();
    $categories = [];
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
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Kelola Kategori</h1>
                    </div>
                    <div class="flex items-center gap-4">
                        <button class="btn btn-primary flex items-center gap-2" onclick="openAddModal()"><i class="ph ph-plus-circle text-xl"></i> Tambah Kategori</button>
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

            <!-- Categories Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($categories as $category): ?>
                <div class="card p-6 hover:shadow-lg transition">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-4xl"><?php echo $category['icon']; ?></div>
                        <div class="flex gap-2">
                            <button class="btn btn-sm" onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)"><i class="ph ph-pencil-simple text-lg"></i></button>
                            <button class="btn btn-sm btn-danger" onclick="deleteCategory(<?php echo $category['id']; ?>)"><i class="ph ph-trash text-lg"></i></button>
                        </div>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2"><?php echo htmlspecialchars($category['name']); ?></h3>
                    <p class="text-gray-600 dark:text-gray-400 text-sm"><?php echo htmlspecialchars(substr($category['description'] ?? '', 0, 100)); ?></p>
                </div>
                <?php endforeach; ?>
            </div>

<!-- Add/Edit Modal -->
<div id="categoryModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4" id="modalTitle">Tambah Kategori Baru</h2>

        <form id="categoryForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="categoryId">

            <div class="form-group">
                <label>Nama Kategori *</label>
                <input type="text" name="name" id="name" required>
            </div>

            <div class="form-group">
                <label>Icon</label>
                <input type="text" name="icon" id="icon" placeholder="Contoh: 📚 🎓 📖">
            </div>

            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="description" id="description" style="height: 100px;"></textarea>
            </div>

            <div class="flex gap-4 pt-4">
                <button type="submit" class="btn btn-primary flex-1">Simpan</button>
                <button type="button" class="btn btn-secondary flex-1" onclick="closeModal()">Batal</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeDeleteModal()">&times;</span>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Konfirmasi Hapus</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-6">Apakah Anda yakin ingin menghapus kategori ini? Pastikan tidak ada buku yang menggunakan kategori ini.</p>

        <form id="deleteForm" method="POST" class="flex gap-4">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="deleteCategoryId">
            <button type="submit" class="btn btn-danger flex-1">Hapus</button>
            <button type="button" class="btn btn-secondary flex-1" onclick="closeDeleteModal()">Batal</button>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Kategori Baru';
    document.getElementById('formAction').value = 'add';
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryModal').style.display = 'block';
}

function editCategory(category) {
    document.getElementById('modalTitle').textContent = 'Edit Kategori';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('categoryId').value = category.id;
    document.getElementById('name').value = category.name;
    document.getElementById('icon').value = category.icon || '📚';
    document.getElementById('description').value = category.description || '';
    document.getElementById('categoryModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('categoryModal').style.display = 'none';
}

function deleteCategory(id) {
    document.getElementById('deleteCategoryId').value = id;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

window.onclick = function(event) {
    let modal = document.getElementById('categoryModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
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
