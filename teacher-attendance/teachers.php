<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>
<?php require_once 'api/db.php'; ?>

<?php
$stmt = $pdo->query("SELECT id, teacher_id, name, department, email, phone, (face_descriptor IS NOT NULL AND face_descriptor != '') as has_face, created_at FROM teachers ORDER BY created_at DESC");
$teachers = $stmt->fetchAll();
?>

<main class="col-12 col-lg-10 ms-auto main-content px-3 px-sm-4 px-xl-5 py-3 py-md-4">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 pt-1 pb-3 mb-4 border-bottom border-secondary border-opacity-10">
        <div>
            <h1 class="h4 fw-bold text-dark mb-1">Teachers Directory</h1>
            <p class="text-muted mb-0 small">Faculty directory registered via Teacher Portal.</p>
        </div>
        <div class="w-100 w-sm-auto">
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2 fw-medium">
                <i class="bi bi-people-fill me-1"></i> Total Teachers: <?= count($teachers) ?>
            </span>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="d-md-none px-3 py-2 bg-light border-bottom text-muted" style="font-size: 0.75rem;">
            <i class="bi bi-arrows-expand me-1 text-primary"></i> Scroll horizontally to view directory
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="table-light text-muted">
                        <tr>
                            <th class="ps-3 ps-sm-4">Teacher ID</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Email</th>
                            <th>Face Status</th>
                            <th class="pe-4 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="teachers-list">
                        <?php if (count($teachers) > 0): ?>
                            <?php foreach ($teachers as $teacher): ?>
                                <tr>
                                    <td class="ps-4 fw-semibold text-dark"><?= htmlspecialchars($teacher['teacher_id']) ?></td>
                                    <td><?= htmlspecialchars($teacher['name']) ?></td>
                                    <td><?= htmlspecialchars($teacher['department']) ?></td>
                                    <td><?= htmlspecialchars($teacher['email'] ?: '-') ?></td>
                                    <td>
                                        <?php if (!empty($teacher['has_face'])): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-2"><i class="bi bi-check-circle me-1"></i> Enrolled</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-3 py-2"><i class="bi bi-clock me-1"></i> Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <button onclick="deleteTeacher(<?= $teacher['id'] ?>, this)" class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No teachers found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    async function deleteTeacher(id, button) {
        if (confirm("Are you sure you want to delete this teacher? All their attendance records will also be deleted. This action cannot be undone.")) {
            try {
                const formData = new FormData();
                formData.append('id', id);
                
                const response = await fetch('api/delete_teacher.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    const tr = button.closest('tr');
                    if (tr) {
                        tr.remove();
                    }
                } else {
                    alert(result.message);
                }
            } catch (error) {
                alert("Error deleting teacher.");
            }
        }
    }
    </script>
</main>

<?php require_once 'includes/footer.php'; ?>
