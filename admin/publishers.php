<?php
session_start();
require_once __DIR__ . '/../config/database.php';

checkAdmin();

$page_title = 'Kelola Penerbit';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = sanitize($_POST['name'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $website = sanitize($_POST['website'] ?? '');

        if (empty($name)) {
            $error = 'Nama penerbit harus diisi!';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO publishers (name, address, phone, email, website) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $address, $phone, $email, $website]);
                $message = 'Penerbit berhasil ditambahkan!';
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $website = sanitize($_POST['website'] ?? '');

        if ($id <= 0 || empty($name)) {
            $error = 'Data tidak valid!';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE publishers SET name = ?, address = ?, phone = ?, email = ?, website = ? WHERE id = ?");
                $stmt->execute([$name, $address, $phone, $email, $website, $id]);
                $message = 'Penerbit berhasil diperbarui!';
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM publishers WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'Penerbit berhasil dihapus!';
            } catch (PDOException $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
}

try {
    $publishers = $pdo->query("SELECT * FROM publishers ORDER BY name")->fetchAll();
} catch (PDOException $e) {
    $error = $e->getMessage();
    $publishers = [];
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
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Kelola Penerbit</h1>
                    </div>
                    <div class="flex items-center gap-4">
                        <button class="btn btn-primary flex items-center gap-2" onclick="openAddModal()"><i class="ph ph-plus-circle text-xl"></i> Tambah Penerbit</button>
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

            <!-- Publishers Table -->
            <div class="card overflow-hidden">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Penerbit</th>
                                <th>Email</th>
                                <th>Telepon</th>
                                <th>Website</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($publishers as $publisher): ?>
                            <tr>
                                <td class="font-500"><?php echo htmlspecialchars($publisher['name']); ?></td>
                                <td>
                                    <a href="mailto:<?php echo htmlspecialchars($publisher['email']); ?>" class="text-blue-600 hover:underline">
                                        <?php echo htmlspecialchars($publisher['email'] ?? '-'); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($publisher['phone'] ?? '-'); ?></td>
                                <td>
                                    <?php if ($publisher['website']): ?>
                                    <a href="<?php echo htmlspecialchars($publisher['website']); ?>" target="_blank" class="text-blue-600 hover:underline text-sm">
                                        Kunjungi
                                    </a>
                                    <?php else: ?>
                                    <span class="text-gray-500">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm" onclick="editPublisher(<?php echo htmlspecialchars(json_encode($publisher)); ?>)"><i class="ph ph-pencil-simple text-lg"></i></button>
                                    <button class="btn btn-sm btn-danger" onclick="deletePublisher(<?php echo $publisher['id']; ?>)"><i class="ph ph-trash text-lg"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

<!-- Add/Edit Modal -->
<div id="publisherModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4" id="modalTitle">Tambah Penerbit Baru</h2>

        <form id="publisherForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="publisherId">

            <div class="form-group">
                <label>Nama Penerbit *</label>
                <input type="text" name="name" id="name" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="email">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label>Telepon</label>
                    <input type="text" name="phone" id="phone">
                </div>
                <div class="form-group">
                    <label>Website</label>
                    <input type="url" name="website" id="website">
                </div>
            </div>

            <div class="form-group">
                <label>Alamat</label>
                <textarea name="address" id="address" style="height: 100px;"></textarea>
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
        <p class="text-gray-600 dark:text-gray-400 mb-6">Apakah Anda yakin ingin menghapus penerbit ini? Pastikan tidak ada buku yang menggunakan penerbit ini.</p>

        <form id="deleteForm" method="POST" class="flex gap-4">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="deletePublisherId">
            <button type="submit" class="btn btn-danger flex-1">Hapus</button>
            <button type="button" class="btn btn-secondary flex-1" onclick="closeDeleteModal()">Batal</button>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Tambah Penerbit Baru';
    document.getElementById('formAction').value = 'add';
    document.getElementById('publisherForm').reset();
    document.getElementById('publisherId').value = '';
    document.getElementById('publisherModal').style.display = 'block';
}

function editPublisher(publisher) {
    document.getElementById('modalTitle').textContent = 'Edit Penerbit';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('publisherId').value = publisher.id;
    document.getElementById('name').value = publisher.name;
    document.getElementById('email').value = publisher.email || '';
    document.getElementById('phone').value = publisher.phone || '';
    document.getElementById('website').value = publisher.website || '';
    document.getElementById('address').value = publisher.address || '';
    document.getElementById('publisherModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('publisherModal').style.display = 'none';
}

function deletePublisher(id) {
    document.getElementById('deletePublisherId').value = id;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

window.onclick = function(event) {
    let modal = document.getElementById('publisherModal');
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
