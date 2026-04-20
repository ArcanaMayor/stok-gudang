<?php
session_start();
require_once __DIR__ . '/../config/database.php';

checkAdmin();

$page_title = 'Kelola Penulis';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = sanitize($_POST['name'] ?? '');
        $bio = sanitize($_POST['bio'] ?? '');
        $birth_date = $_POST['birth_date'] ?? null;
        $nationality = sanitize($_POST['nationality'] ?? '');

        if (empty($name)) {
            $error = 'Nama penulis harus diisi!';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO authors (name, bio, birth_date, nationality) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $bio, $birth_date, $nationality]);
                $message = 'Penulis berhasil ditambahkan!';
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $bio = sanitize($_POST['bio'] ?? '');
        $birth_date = $_POST['birth_date'] ?? null;
        $nationality = sanitize($_POST['nationality'] ?? '');

        if ($id <= 0 || empty($name)) {
            $error = 'Data tidak valid!';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE authors SET name = ?, bio = ?, birth_date = ?, nationality = ? WHERE id = ?");
                $stmt->execute([$name, $bio, $birth_date, $nationality, $id]);
                $message = 'Penulis berhasil diperbarui!';
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM authors WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'Penulis berhasil dihapus!';
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
}

try {
    $authors = $pdo->query("SELECT * FROM authors ORDER BY name")->fetchAll();
} catch (PDOException $e) {
    $error = $e->getMessage();
    $authors = [];
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
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Kelola Penulis</h1>
                    </div>
                    <div class="flex items-center gap-4">
                        <button class="btn btn-primary flex items-center gap-2" onclick="openAddModal()"><i class="ph ph-plus-circle text-xl"></i> Tambah Penulis</button>
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

            <!-- Authors Table -->
            <div class="card overflow-hidden">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Penulis</th>
                                <th>Tanggal Lahir</th>
                                <th>Kebangsaan</th>
                                <th>Biografi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($authors as $author): ?>
                            <tr>
                                <td class="font-500"><?php echo htmlspecialchars($author['name']); ?></td>
                                <td><?php echo formatDate($author['birth_date']); ?></td>
                                <td><?php echo htmlspecialchars($author['nationality'] ?? '-'); ?></td>
                                <td>
                                    <p class="text-gray-600 dark:text-gray-400 text-sm"><?php echo htmlspecialchars(substr($author['bio'] ?? '', 0, 50)); ?></p>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm" onclick="editAuthor(<?php echo htmlspecialchars(json_encode($author)); ?>)"><i class="ph ph-pencil-simple text-lg"></i></button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteAuthor(<?php echo $author['id']; ?>)"><i class="ph ph-trash text-lg"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

<!-- Add/Edit Modal -->
<div id="authorModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4" id="modalTitle">Tambah Penulis Baru</h2>

        <form id="authorForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="authorId">

            <div class="form-group">
                <label>Nama Penulis *</label>
                <input type="text" name="name" id="name" required>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label>Tanggal Lahir</label>
                    <input type="date" name="birth_date" id="birth_date">
                </div>
                <div class="form-group">
                    <label>Kebangsaan</label>
                    <input type="text" name="nationality" id="nationality" placeholder="Contoh: Indonesia">
                </div>
            </div>

            <div class="form-group">
                <label>Biografi</label>
                <textarea name="bio" id="bio" style="height: 120px;"></textarea>
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
        <p class="text-gray-600 dark:text-gray-400 mb-6">Apakah Anda yakin ingin menghapus penulis ini? Pastikan tidak ada buku yang menggunakan penulis ini.</p>

        <form id="deleteForm" method="POST" class="flex gap-4">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="deleteAuthorId">
            <button type="submit" class="btn btn-danger flex-1">Hapus</button>
            <button type="button" class="btn btn-secondary flex-1" onclick="closeDeleteModal()">Batal</button>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Penulis Baru';
    document.getElementById('formAction').value = 'add';
    document.getElementById('authorForm').reset();
    document.getElementById('authorId').value = '';
    document.getElementById('authorModal').style.display = 'block';
}

function editAuthor(author) {
    document.getElementById('modalTitle').textContent = 'Edit Penulis';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('authorId').value = author.id;
    document.getElementById('name').value = author.name;
    document.getElementById('birth_date').value = author.birth_date || '';
    document.getElementById('nationality').value = author.nationality || '';
    document.getElementById('bio').value = author.bio || '';
    document.getElementById('authorModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('authorModal').style.display = 'none';
}

function deleteAuthor(id) {
    document.getElementById('deleteAuthorId').value = id;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

window.onclick = function(event) {
    let modal = document.getElementById('authorModal');
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
