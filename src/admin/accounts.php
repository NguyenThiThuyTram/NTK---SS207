<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

// 1. Xử lý tìm kiếm, lọc vai trò, lọc trạng thái
$search        = $_GET['search'] ?? '';
$role_filter   = $_GET['role']   ?? 'all';
$status_filter = $_GET['status'] ?? 'all';

$whereClauses = ["1=1"];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(u.fullname LIKE ? OR u.email LIKE ? OR u.phonenumber LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}
if ($role_filter !== 'all') {
    $whereClauses[] = "u.role = ?";
    $params[] = $role_filter;
}
if ($status_filter !== 'all') {
    $whereClauses[] = "u.status = ?";
    $params[] = $status_filter;
}

$whereSql = implode(" AND ", $whereClauses);

// 2. Truy vấn
$query = "
    SELECT u.*,
           COUNT(o.order_id) as total_orders,
           IFNULL(SUM(o.final_price), 0) as total_spent
    FROM users u
    LEFT JOIN orders o ON u.user_id = o.user_id
    WHERE $whereSql
    GROUP BY u.user_id
    ORDER BY u.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tổng số tài khoản (không lọc)
$total_users = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();

$admin_current_page = 'accounts.php';
include __DIR__ . '/../includes/admin_sidebar.php';
?>

<style>
    .page-title    { font-size: 21px; font-weight: 700; color: #111111; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
    .page-subtitle { font-size: 13px; color: #999; margin-bottom: 0; }

    /* Toolbar */
    .toolbar { display: flex; gap: 12px; margin: 22px 0; align-items: center; flex-wrap: wrap; }
    .search-wrap { position: relative; flex: 1; min-width: 200px; }
    .search-wrap i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #aaa; font-size: 13px; pointer-events: none; }
    .search-input {
        width: 100%; padding: 10px 14px 10px 38px;
        border: 1px solid #e5e5e5; border-radius: 8px;
        font-size: 13.5px; outline: none; color: #111; background: #fff;
        transition: border-color 0.2s;
    }
    .search-input:focus { border-color: #2f1c00; }
    .filter-select {
        padding: 10px 34px 10px 14px;
        border: 1px solid #e5e5e5; border-radius: 8px;
        font-size: 13.5px; color: #333; background: #fff;
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'><polyline points='6 9 12 15 18 9'/></svg>");
        background-repeat: no-repeat; background-position: right 10px center;
        -webkit-appearance: none; appearance: none;
        outline: none; cursor: pointer; transition: border-color 0.2s; min-width: 150px;
    }
    .filter-select:focus { border-color: #2f1c00; }
    .btn-add-user {
        background: #2f1c00; color: #fff; padding: 10px 18px;
        border-radius: 8px; text-decoration: none; font-size: 13.5px;
        font-weight: 600; display: inline-flex; align-items: center; gap: 8px;
        white-space: nowrap; transition: background 0.2s;
    }
    .btn-add-user:hover { background: #1a0f00; }

    /* Table */
    .section-card { background: #fff; border-radius: 10px; border: 1px solid #e5e5e5; overflow: hidden; }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table thead th {
        padding: 14px 20px; font-size: 11.5px; font-weight: 700;
        color: #999; text-transform: uppercase; letter-spacing: 0.8px;
        text-align: left; background: #fafaf8; border-bottom: 1px solid #e5e5e5; white-space: nowrap;
    }
    .data-table tbody td {
        padding: 14px 20px; font-size: 13.5px; color: #333;
        border-bottom: 1px solid #f5f1eb; vertical-align: middle;
    }
    .data-table tbody tr:last-child td { border-bottom: none; }
    .data-table tbody tr:hover { background: #fafaf8; }

    .user-name  { font-weight: 400; color: #111; font-size: 13.5px; }
    .user-email { font-size: 12px; color: #aaa; margin-top: 2px; }
    .id-badge   { background: #f4f1ee; padding: 3px 7px; border-radius: 4px; font-weight: 600; color: #666; font-size: 12px; }

    .role-badge {
        padding: 4px 10px; border-radius: 4px; font-size: 11px;
        font-weight: 700; text-transform: uppercase; white-space: nowrap; display: inline-block; text-align: center;
    }
    .role-customer { background: #f5f5f5; color: #888; }
    .role-admin    { background: #2f1c00; color: #fff; }

    .status-badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; white-space: nowrap;
    }
    .status-badge::before {
        content: ''; width: 6px; height: 6px;
        border-radius: 50%; background: currentColor; opacity: 0.75;
    }
    .status-active { background: #eafaf1; color: #27ae60; }
    .status-locked { background: #fdf0ef; color: #e74c3c; }

    .action-btns { display: flex; gap: 6px; align-items: center; }
    .btn-icon {
        width: 30px; height: 30px; border-radius: 6px;
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; text-decoration: none; border: none; cursor: pointer;
        transition: all 0.2s; background: #f5f1eb; color: #555;
    }
    .btn-icon.lock:hover   { background: #c0392b; color: #fff; }
    .btn-icon.unlock:hover { background: #27ae60; color: #fff; }

    .alert-msg { padding: 13px 18px; border-radius: 8px; margin-top: 16px; font-size: 13.5px; display: flex; align-items: center; gap: 10px; }
    .alert-success { background: #eafaf1; color: #1e8449; border-left: 4px solid #27ae60; }
    .alert-warning { background: #fdf0ef; color: #c0392b; border-left: 4px solid #e74c3c; }
</style>

<div class="page-title">Quản lý tài khoản</div>
<p class="page-subtitle"><?= $total_users ?> tài khoản trong hệ thống</p>

<?php if (($_GET['msg'] ?? '') === 'locked'): ?>
<div class="alert-msg alert-warning"><i class="fa-solid fa-lock"></i> Tài khoản đã bị khóa thành công.</div>
<?php elseif (($_GET['msg'] ?? '') === 'unlocked'): ?>
<div class="alert-msg alert-success"><i class="fa-solid fa-lock-open"></i> Tài khoản đã được mở khóa.</div>
<?php endif; ?>

<form method="GET" id="filterForm">
<div class="toolbar">
    <div class="search-wrap">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" name="search" class="search-input"
               placeholder="Tìm theo tên, email hoặc số điện thoại..."
               value="<?= htmlspecialchars($search) ?>"
               oninput="clearTimeout(window._st); window._st = setTimeout(() => document.getElementById('filterForm').submit(), 500)">
    </div>
    <select name="role" class="filter-select" onchange="document.getElementById('filterForm').submit()">
        <option value="all" <?= $role_filter === 'all' ? 'selected' : '' ?>>Tất cả vai trò</option>
        <option value="0"   <?= $role_filter === '0'   ? 'selected' : '' ?>>Khách hàng</option>
        <option value="1"   <?= $role_filter === '1'   ? 'selected' : '' ?>>Admin</option>
    </select>
    <select name="status" class="filter-select" onchange="document.getElementById('filterForm').submit()">
        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>Tất cả trạng thái</option>
        <option value="1"   <?= $status_filter === '1'   ? 'selected' : '' ?>>Hoạt động</option>
        <option value="0"   <?= $status_filter === '0'   ? 'selected' : '' ?>>Đã khóa</option>
    </select>
    <a href="add_account.php" class="btn-add-user">
        <i class="fa-solid fa-user-plus"></i> Thêm tài khoản
    </a>
</div>
</form>

<div class="section-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Họ tên</th>
                <th>Email / SĐT</th>
                <th>Vai trò</th>
                <th>Trạng thái</th>
                <th>Tổng chi tiêu</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><span class="id-badge"><?= htmlspecialchars($u['user_id']) ?></span></td>
                <td><div class="user-name"><?= htmlspecialchars($u['fullname']) ?></div></td>
                <td>
                    <div style="color:#333; font-size:13.5px;"><?= htmlspecialchars($u['email']) ?></div>
                    <div class="user-email"><?= htmlspecialchars($u['phonenumber']) ?></div>
                </td>
                <td>
                    <span class="role-badge <?= $u['role'] == 1 ? 'role-admin' : 'role-customer' ?>">
                        <?= $u['role'] == 1 ? 'Admin' : 'Khách hàng' ?>
                    </span>
                </td>
                <td>
                    <?php if ($u['status'] == 1): ?>
                        <span class="status-badge status-active">Hoạt động</span>
                    <?php else: ?>
                        <span class="status-badge status-locked">Đã khóa</span>
                    <?php endif; ?>
                </td>
                <td style="font-weight:600; color:#2f1c00;"><?= number_format($u['total_spent'], 0, ',', '.') ?>đ</td>
                <td>
                    <div class="action-btns">
                        <!-- Nút Chi tiết tài khoản -->
                        <a href="account_detail.php?id=<?= urlencode($u['user_id']) ?>" class="btn-icon" title="Chi tiết tài khoản" style="background:#e8f4fd; color:#2980b9;">
                            <i class="fa-regular fa-eye"></i>
                        </a>

                        <?php if ($u['status'] == 1): ?>
                        <form method="POST" action="toggle_user_status.php"
                              onsubmit="return confirm('Bạn có chắc muốn khóa tài khoản <?= htmlspecialchars(addslashes($u['fullname'])) ?>?')">
                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($u['user_id']) ?>">
                            <input type="hidden" name="action" value="lock">
                            <button type="submit" class="btn-icon lock" title="Khóa tài khoản">
                                <i class="fa-solid fa-lock"></i>
                            </button>
                        </form>
                        <?php else: ?>
                        <form method="POST" action="toggle_user_status.php"
                              onsubmit="return confirm('Mở khóa tài khoản <?= htmlspecialchars(addslashes($u['fullname'])) ?>?')">
                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($u['user_id']) ?>">
                            <input type="hidden" name="action" value="unlock">
                            <button type="submit" class="btn-icon unlock" title="Mở khóa tài khoản">
                                <i class="fa-solid fa-lock-open"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</div><!-- /.admin-content -->
</main>
</body>
</html>