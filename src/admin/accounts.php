<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

// 1. Xử lý tìm kiếm và lọc vai trò
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? 'all';

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

$whereSql = implode(" AND ", $whereClauses);

// 2. Truy vấn lấy danh sách User kèm thống kê (Đã có GROUP BY để tránh nhân đôi data)
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

$admin_current_page = 'accounts.php';
include __DIR__ . '/../includes/admin_sidebar.php';
?>

<style>
    .user-wrapper { padding: 30px; background: #fdfdfb; min-height: 100vh; font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; }
    .page-title { font-size: 28px; font-weight: 700; color: #1a1a1a; margin-bottom: 5px; }
    .toolbar { display: flex; gap: 15px; margin: 30px 0; align-items: center; }
    .search-box { position: relative; flex: 1; }
    .search-box i { position: absolute; left: 15px; top: 12px; color: #aaa; }
    .search-input { width: 100%; padding: 10px 15px 10px 40px; border: 1px solid #eee; border-radius: 8px; outline: none; }
    .btn-add-user { background: #2f1c00; color: #fff; padding: 12px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 8px; }
    .user-table-card { background: #fff; border: 1px solid #eee; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
    .user-table { width: 100%; border-collapse: collapse; }
    .user-table th { background: #fafafa; padding: 15px 20px; text-align: left; font-size: 11px; color: #999; text-transform: uppercase; border-bottom: 1px solid #eee; }
    .user-table td { padding: 18px 20px; border-bottom: 1px solid #f9f9f9; vertical-align: middle; font-size: 14px; }
    .id-badge { background: #f4f1ee; padding: 4px 8px; border-radius: 4px; font-weight: 700; color: #666; font-size: 12px; }
    .role-badge { padding: 5px 12px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; white-space: nowrap; display: inline-block; width: 100px; text-align: center; }
    .role-customer { background: #f5f5f5; color: #888; }
    .role-admin { background: #2f1c00; color: #fff; }
</style>

<div class="user-wrapper">
    <h1 class="page-title">Quản lý tài khoản</h1>
    <p style="color: #888;">Quản lý tất cả người dùng trong hệ thống</p>

    <div class="toolbar">
        <form method="GET" class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="search" class="search-input" placeholder="Tìm theo tên, email hoặc số điện thoại..." value="<?= htmlspecialchars($search) ?>">
        </form>
        
        <form method="GET">
            <select name="role" class="search-input" style="width: 150px;" onchange="this.form.submit()">
                <option value="all">Tất cả</option>
                <option value="0" <?= $role_filter === '0' ? 'selected' : '' ?>>Khách hàng</option>
                <option value="1" <?= $role_filter === '1' ? 'selected' : '' ?>>Admin</option>
            </select>
        </form>

        <a href="add_account.php" class="btn-add-user"><i class="fa-solid fa-user-plus"></i> THÊM TÀI KHOẢN</a>
    </div>

    <div class="user-table-card">
        <table class="user-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Họ tên</th>
                    <th>Email / SĐT</th>
                    <th>Vai trò</th>
                    <th>Ngày tham gia</th>
                    <th style="text-align: center;">Số đơn</th>
                    <th>Tổng chi tiêu</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><span class="id-badge"><?= $u['user_id'] ?></span></td>
                    <td><strong style="color: #333;"><?= htmlspecialchars($u['fullname']) ?></strong></td>
                    <td>
                        <div style="color: #333; font-size: 14px;"><?= htmlspecialchars($u['email']) ?></div>
                        <div style="color: #aaa; font-size: 12px;"><?= htmlspecialchars($u['phonenumber']) ?></div>
                    </td>
                    <td>
                        <span class="role-badge <?= $u['role'] == 1 ? 'role-admin' : 'role-customer' ?>">
                            <?= $u['role'] == 1 ? 'Admin' : 'Khách hàng' ?>
                        </span>
                    </td>
                    <td style="color: #666;"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                    <td style="text-align: center; font-weight: 600;"><?= $u['total_orders'] ?></td>
                    <td style="font-weight: 700; color: #2f1c00;"><?= number_format($u['total_spent'], 0, ',', '.') ?>đ</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>