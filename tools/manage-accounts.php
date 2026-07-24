<?php
include_once '../auth_check.php';
include '../db.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" href="../Imag3s/baguio-logo-gov.webp" type="image/png">
    <link rel="stylesheet" href="/CSS/tools.css">
    <title>City Council | Manage Accounts</title>
</head>
<body>
    <!-- For subfolder pages (table.php, events.php, etc.) -->
<div id="page-loader">
    <div class="loader-wrapper">
        <img src="../Imag3s/baguio-logo-gov.png" alt="BCH" class="loader-logo">
        
        <div class="loader-circle"></div>
    </div>
</div>
    <nav>
        <img src="/Imag3s/baguio-logo-gov.png" alt="logo">
        <h1>Sangguniang Panlungsod</h1>
        <div class="nav-actions">
            <button id="Dashboard" onclick="window.location.href='/admin/admin.php'">Home</button>
            <button class="btn-nav logout" onclick="window.location.href='/logout.php'">Logout</button>
        </div>
    </nav>

    <main>
        <!-- Success Alert for Create/Update -->
        <?php if(isset($_GET['success'])): ?>
            <div id="success-alert" style="background: #dcfce7; color: #166534; padding: 12px; border-radius: 8px; margin-bottom: 15px; font-weight: 600; text-align: center; border: 1px solid #bbf7d0;">
                <?php echo ($_GET['success'] == 'updated') ? 'Account updated successfully!' : 'Account created successfully!'; ?>
            </div>
            <script>setTimeout(() => { document.getElementById('success-alert').style.display = 'none'; }, 3000);</script>
        <?php endif; ?>

        <div id="container1">
            <div class="table-header">
                <h1>User Accounts</h1>
                <button id="add" onclick="document.getElementById('modal-create').showModal()">+ Create Account</button>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID Number</th>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $conn->query("SELECT * FROM users ORDER BY id DESC");

                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $id = $row['id'];
                                $e_id = htmlspecialchars($row['employee_id']);
                                $name = htmlspecialchars($row['full_name'] ?? 'N/A');
                                $pass = htmlspecialchars($row['passkey']);
                                $role = htmlspecialchars($row['role']);
                                $status = $row['status'] ?? 'active';

                                $statusBadge = ($status == 'active') ? '<span class="badge active">● Active</span>' : '<span class="badge archived">○ Archived</span>';

                                $is_active = ($status == 'active');
                                $toggleText = $is_active ? 'Archive' : 'Restore';
                                $toggleIcon = $is_active ? '🔒' : '🔓';
                                $toggleClassName = $is_active ? 'btn-archive-red' : 'btn-restore-green';

                                echo "<tr>
                                    <td style='font-weight: 600; color: #64748b;'>{$e_id}</td>
                                    <td style='font-weight: 600; color: #1e293b;'>{$name}</td>
                                    <td><span class='role-tag'>" . ucfirst($role) . "</span></td>
                                    <td>{$statusBadge}</td>
                                    <td>
                                        <div class='action-group'>
                                            <button class='btn-action btn-edit-blue' onclick='openEditModal($id, \"$name\", \"$e_id\", \"$pass\", \"$role\")' title='Edit User'>
                                                <span>📝</span> Edit
                                            </button>
                                            <button class='btn-action {$toggleClassName}' onclick='window.location.href=\"toggle_status.php?id=$id\"' title='Change Status'>
                                                <span>{$toggleIcon}</span> {$toggleText}
                                            </button>
                                        </div>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center; padding: 40px; color: #94a3b8;'>No accounts found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal for Creating Accounts -->
	<dialog id="modal-create">
    <form action="create_user.php" method="POST">
        <h2>Create New Account</h2>
        <label>Full Name</label>
        <input type="text" name="full_name" required>
        
        <label>ID Number</label>
        <input type="text" name="employee_id" required> <!-- MUST match PHP -->
        
        <label>Temporary Passkey</label>
        <input type="password" name="passkey" required>
        
        <label>Role</label>
        <select name="role">
            <option value="user">User</option>
            <option value="admin">Admin</option>
        </select>
            <div class="modal-actions">
                <button type="submit" class="btn-save">Save Account</button>
                <button type="button" class="btn-cancel" onclick="document.getElementById('modal-create').close()">Cancel</button>
            </div>
        </form>
    </dialog>

    <!-- Modal for Editing Accounts -->
<dialog id="modal-edit">
    <form action="update_user.php" method="POST">
        <h2>Edit Account</h2>
        <input type="hidden" name="user_id" id="edit-user-id">

        <label>Full Name</label>
        <input type="text" name="full_name" id="edit-full-name" required>

        <label>ID Number</label>
        <input type="text" name="employee_id" id="edit-employee-id" required>

        <label>Passkey / Security Code</label>
        <div style="display: flex; gap: 10px;">
            <input type="text" name="passkey" id="edit-passkey" required style="flex: 1;">
            <button type="button" onclick="generateSecurityCode()" style="background:#64748b; color:white; border:none; padding:5px 10px; border-radius:5px; font-size:12px; cursor:pointer;">New Code</button>
        </div>
        <small style="color: #64748b;">Enter a code (e.g. BCH-123456) to force a password reset.</small>

        <label>Role</label>
        <select name="role" id="edit-role">
            <option value="user">User</option>
            <option value="admin">Admin</option>
        </select>

        <div class="modal-actions">
            <button type="submit" class="btn-save">Update Changes</button>
            <button type="button" class="btn-cancel" onclick="document.getElementById('modal-edit').close()">Cancel</button>
        </div>
    </form>
</dialog>

    <script src="/scripts/main.js"></script>
    
    <script>
function openEditModal(id, name, eid, pass, role) {
    document.getElementById('edit-user-id').value = id;
    document.getElementById('edit-full-name').value = name;
    document.getElementById('edit-employee-id').value = eid;
    document.getElementById('edit-passkey').value = pass;
    document.getElementById('edit-role').value = role;
    
    document.getElementById('modal-edit').showModal();
}

// Optional: Helper to generate a unique BCH Security Code
function generateSecurityCode() {
    const code = "BCH-" + Math.floor(100000 + Math.random() * 900000);
    document.getElementById('edit-passkey').value = code;
}
    </script>
</body>
</html>