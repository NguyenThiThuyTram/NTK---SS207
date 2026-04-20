<?php
// ────────────────────────────────────────────────────
// address_list.php – Quản lý nhiều địa chỉ
// Chạy trong ngữ cảnh dashboard.php ($conn, $_SESSION)
// ────────────────────────────────────────────────────
$uid = $_SESSION['user_id'];

// ── Xử lý các action POST/GET ──────────────────────
$action = $_REQUEST['addr_action'] ?? '';
$addr_errors  = [];
$addr_success = '';

// ── Helper: set default (chỉ 1 địa chỉ được mặc định) ──
function setDefault(PDO $conn, string $uid, int $id): void {
    $conn->prepare("UPDATE user_addresses SET is_default=0 WHERE user_id=:uid")->execute([':uid'=>$uid]);
    $conn->prepare("UPDATE user_addresses SET is_default=1 WHERE address_id=:id AND user_id=:uid")->execute([':id'=>$id, ':uid'=>$uid]);
}

// ── ACTION: Đặt mặc định ──
if ($action === 'set_default' && isset($_GET['addr_id'])) {
    setDefault($conn, $uid, (int)$_GET['addr_id']);
    header('Location: dashboard.php?view=diachi&ok=default');
    exit;
}

// ── ACTION: Xóa ──
if ($action === 'delete' && isset($_GET['addr_id'])) {
    $del_id = (int)$_GET['addr_id'];
    // Đếm tổng
    $cnt = $conn->prepare("SELECT COUNT(*) FROM user_addresses WHERE user_id=:uid");
    $cnt->execute([':uid'=>$uid]);
    if ((int)$cnt->fetchColumn() <= 1) {
        $addr_errors[] = 'Bạn phải giữ ít nhất 1 địa chỉ.';
    } else {
        // Nếu xóa địa chỉ mặc định thì set cái khác làm mặc định
        $chk = $conn->prepare("SELECT is_default FROM user_addresses WHERE address_id=:id AND user_id=:uid");
        $chk->execute([':id'=>$del_id, ':uid'=>$uid]);
        $was_default = (bool)($chk->fetchColumn());

        $conn->prepare("DELETE FROM user_addresses WHERE address_id=:id AND user_id=:uid")->execute([':id'=>$del_id, ':uid'=>$uid]);

        if ($was_default) {
            $first = $conn->prepare("SELECT address_id FROM user_addresses WHERE user_id=:uid ORDER BY created_at ASC LIMIT 1");
            $first->execute([':uid'=>$uid]);
            $new_default = $first->fetchColumn();
            if ($new_default) setDefault($conn, $uid, (int)$new_default);
        }
        header('Location: dashboard.php?view=diachi&ok=deleted');
        exit;
    }
}

// ── ACTION: Thêm mới ──
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rn  = trim($_POST['recipient_name'] ?? '');
    $ph  = preg_replace('/\s+/', '', trim($_POST['phone'] ?? ''));
    $st  = trim($_POST['street']   ?? '');
    $wa  = trim($_POST['ward']     ?? '');
    $di  = trim($_POST['district'] ?? '');
    $pr  = trim($_POST['province'] ?? '');
    $no  = trim($_POST['note']     ?? '');
    $isd = isset($_POST['is_default']) ? 1 : 0;

    if (!$rn) $addr_errors[] = 'Họ tên người nhận không được trống.';
    if (!preg_match('/^(0|\+84)[0-9]{8,10}$/', $ph)) $addr_errors[] = 'Số điện thoại không hợp lệ.';
    if (!$st) $addr_errors[] = 'Địa chỉ cụ thể không được trống.';
    if (!$di) $addr_errors[] = 'Vui lòng chọn Quận/Huyện.';
    if (!$pr) $addr_errors[] = 'Vui lòng chọn Tỉnh/Thành phố.';

    if (empty($addr_errors)) {
        if ($isd) {
            $conn->prepare("UPDATE user_addresses SET is_default=0 WHERE user_id=:uid")->execute([':uid'=>$uid]);
        }
        // Nếu chưa có địa chỉ nào, tự động set default
        $has = $conn->prepare("SELECT COUNT(*) FROM user_addresses WHERE user_id=:uid");
        $has->execute([':uid'=>$uid]);
        if ((int)$has->fetchColumn() === 0) $isd = 1;

        $ins = $conn->prepare("INSERT INTO user_addresses (user_id,recipient_name,phone,street,ward,district,province,note,is_default) VALUES (:uid,:rn,:ph,:st,:wa,:di,:pr,:no,:isd)");
        $ins->execute([':uid'=>$uid,':rn'=>$rn,':ph'=>$ph,':st'=>$st,':wa'=>$wa,':di'=>$di,':pr'=>$pr,':no'=>$no ?: null,':isd'=>$isd]);
        header('Location: dashboard.php?view=diachi&ok=added');
        exit;
    }
    // Giữ form mở với tab add
    $open_tab = 'add';
}

// ── ACTION: Sửa ──
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $eid = (int)($_POST['edit_id'] ?? 0);
    $rn  = trim($_POST['recipient_name'] ?? '');
    $ph  = preg_replace('/\s+/', '', trim($_POST['phone'] ?? ''));
    $st  = trim($_POST['street']   ?? '');
    $wa  = trim($_POST['ward']     ?? '');
    $di  = trim($_POST['district'] ?? '');
    $pr  = trim($_POST['province'] ?? '');
    $no  = trim($_POST['note']     ?? '');
    $isd = isset($_POST['is_default']) ? 1 : 0;

    if (!$rn) $addr_errors[] = 'Họ tên người nhận không được trống.';
    if (!preg_match('/^(0|\+84)[0-9]{8,10}$/', $ph)) $addr_errors[] = 'Số điện thoại không hợp lệ.';
    if (!$st) $addr_errors[] = 'Địa chỉ cụ thể không được trống.';
    if (!$di) $addr_errors[] = 'Vui lòng chọn Quận/Huyện.';
    if (!$pr) $addr_errors[] = 'Vui lòng chọn Tỉnh/Thành phố.';

    if (empty($addr_errors)) {
        if ($isd) {
            $conn->prepare("UPDATE user_addresses SET is_default=0 WHERE user_id=:uid")->execute([':uid'=>$uid]);
        }
        $upd = $conn->prepare("UPDATE user_addresses SET recipient_name=:rn,phone=:ph,street=:st,ward=:wa,district=:di,province=:pr,note=:no,is_default=:isd WHERE address_id=:eid AND user_id=:uid");
        $upd->execute([':rn'=>$rn,':ph'=>$ph,':st'=>$st,':wa'=>$wa,':di'=>$di,':pr'=>$pr,':no'=>$no ?: null,':isd'=>$isd,':eid'=>$eid,':uid'=>$uid]);
        header('Location: dashboard.php?view=diachi&ok=edited');
        exit;
    }
    $open_tab = 'edit';
    $edit_id_open = $eid;
}

// ── Lấy danh sách địa chỉ ──
$stmt_list = $conn->prepare("SELECT * FROM user_addresses WHERE user_id=:uid ORDER BY is_default DESC, created_at DESC");
$stmt_list->execute([':uid'=>$uid]);
$address_list = $stmt_list->fetchAll(PDO::FETCH_ASSOC);

// ── Thông báo success từ redirect ──
$ok_msg = match($_GET['ok'] ?? '') {
    'added'   => 'Đã thêm địa chỉ mới thành công!',
    'edited'  => 'Đã cập nhật địa chỉ thành công!',
    'deleted' => 'Đã xóa địa chỉ.',
    'default' => 'Đã đặt địa chỉ mặc định.',
    default   => ''
};
?>

<style>
/* ══════════════════════════════════════════════════════
   ADDRESS MANAGER – NTK Fashion
   Multi-address card layout with add/edit form drawer
══════════════════════════════════════════════════════ */

.am-wrap { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; color: #111; }

/* ── Page header ── */
.am-header { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 28px; padding-bottom: 18px; border-bottom: 1px solid #e5e5e5; }
.am-header h2 { margin: 0 0 4px; font-size: 20px; font-weight: 700; }
.am-header p  { margin: 0; font-size: 13px; color: #777; }

/* ── Alert ── */
.am-alert { display: flex; align-items: flex-start; gap: 10px; padding: 11px 16px; border-radius: 7px; font-size: 13.5px; margin-bottom: 18px; }
.am-alert.success { background: #f0faf3; border: 1px solid #b7e4c7; color: #1b5e35; }
.am-alert.error   { background: #fff5f5; border: 1px solid #fca5a5; color: #991b1b; }
.am-alert ul { margin: 4px 0 0; padding-left: 16px; }

/* ── Address cards grid ── */
.am-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 28px; }
@media (max-width: 700px) { .am-cards { grid-template-columns: 1fr; } }

.am-card {
    background: #fff;
    border: 1.5px solid #e5e5e5;
    border-radius: 10px;
    padding: 18px 20px;
    position: relative;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.am-card:hover { border-color: #ccc; box-shadow: 0 3px 12px rgba(0,0,0,0.06); }
.am-card.is-default { border-color: #2f1c00; background: #fdfaf6; }

/* ── Default badge ── */
.badge-default {
    display: inline-flex; align-items: center; gap: 4px;
    background: #2f1c00; color: #fff;
    font-size: 10.5px; font-weight: 600;
    padding: 3px 8px; border-radius: 20px;
    margin-bottom: 10px;
}

/* ── Card content ── */
.am-card-name  { font-weight: 700; font-size: 15px; margin-bottom: 3px; }
.am-card-phone { font-size: 13.5px; color: #555; margin-bottom: 8px; }
.am-card-addr  { font-size: 13px; color: #444; line-height: 1.55; }

/* ── Card actions ── */
.am-card-actions { display: flex; gap: 8px; margin-top: 14px; padding-top: 12px; border-top: 1px solid #f0f0f0; flex-wrap: wrap; }
.am-btn { 
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 12px; border-radius: 5px; font-size: 12.5px;
    font-family: inherit; font-weight: 500; cursor: pointer;
    border: 1.5px solid transparent; transition: all 0.18s;
    text-decoration: none;
}
.am-btn-edit     { border-color: #d0d0d0; color: #333; background: #fff; }
.am-btn-edit:hover { border-color: #2f1c00; color: #2f1c00; }
.am-btn-delete   { border-color: #fca5a5; color: #c0392b; background: #fff; }
.am-btn-delete:hover { background: #fff5f5; }
.am-btn-default  { border-color: #2f1c00; color: #2f1c00; background: #fff; }
.am-btn-default:hover { background: #2f1c00; color: #fff; }
.am-btn-primary  { background: #2f1c00; color: #fff; border-color: #2f1c00; }
.am-btn-primary:hover { background: #4a2e00; }

/* ── Add new button ── */
.am-add-btn {
    display: flex; align-items: center; gap: 10px;
    width: 100%; padding: 15px 20px;
    border: 1.5px dashed #ccc; border-radius: 10px;
    background: #fafafa; color: #555;
    font-size: 14px; font-family: inherit;
    cursor: pointer; text-align: left;
    transition: all 0.2s; margin-bottom: 20px;
}
.am-add-btn:hover { border-color: #2f1c00; color: #2f1c00; background: #fdfaf6; }
.am-add-btn i { font-size: 16px; }

/* ── Form drawer ── */
.am-drawer {
    background: #fff;
    border: 1.5px solid #e5e5e5;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 20px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.06);
}
.am-drawer-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 20px;
    background: #fdfaf6; border-bottom: 1px solid #e5e5e5;
    font-weight: 600; font-size: 14px; color: #2f1c00;
}
.am-drawer-body { padding: 24px 24px 20px; }
.am-drawer-close { background: none; border: none; font-size: 20px; color: #999; cursor: pointer; line-height: 1; }
.am-drawer-close:hover { color: #333; }

/* ── Form fields ── */
.am-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.am-grid-2 .full { grid-column: 1 / -1; }
@media (max-width: 600px) { .am-grid-2 { grid-template-columns: 1fr; } .am-grid-2 .full { grid-column: 1; } }

.am-field { display: flex; flex-direction: column; gap: 5px; }
.am-field label { font-size: 11.5px; font-weight: 700; color: #555; text-transform: uppercase; letter-spacing: 0.6px; }
.am-field label .req { color: #e53e3e; margin-left: 2px; }

.am-input-wrap { position: relative; }
.am-input-wrap i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #bbb; font-size: 13px; pointer-events: none; }
.am-input-wrap.textarea-wrap i { top: 13px; transform: none; }

.am-wrap input[type=text],
.am-wrap input[type=tel],
.am-wrap select,
.am-wrap textarea {
    width: 100%; box-sizing: border-box;
    padding: 10px 12px 10px 36px;
    font-size: 13.5px; font-family: inherit; color: #111;
    background: #fafafa; border: 1.5px solid #e0e0e0; border-radius: 7px;
    outline: none; -webkit-appearance: none; appearance: none;
    transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
}
.am-wrap select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23999' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 10px center;
    padding-right: 30px; cursor: pointer;
}
.am-wrap textarea { resize: vertical; min-height: 72px; padding-top: 10px; }
.am-wrap input:focus,.am-wrap select:focus,.am-wrap textarea:focus {
    border-color: #2f1c00; background: #fff; box-shadow: 0 0 0 3px rgba(47,28,0,0.07);
}
.am-wrap select:disabled { opacity: 0.45; cursor: not-allowed; }

/* ── Checkbox default ── */
.am-check-row { display: flex; align-items: center; gap: 10px; padding: 12px 0 4px; cursor: pointer; font-size: 13.5px; color: #333; }
.am-check-row input[type=checkbox] { width: 17px; height: 17px; accent-color: #2f1c00; cursor: pointer; }

/* ── Error inline ── */
.am-field-err { font-size: 11.5px; color: #e53e3e; display: none; }
.am-field-err.show { display: block; }
.am-input-err { border-color: #e53e3e !important; background: #fff5f5 !important; }

/* ── Submit row ── */
.am-submit-row { display: flex; align-items: center; gap: 14px; margin-top: 20px; padding-top: 16px; border-top: 1px solid #f0f0f0; }
.am-save-btn {
    display: inline-flex; align-items: center; gap: 7px;
    background: #2f1c00; color: #fff; border: none;
    padding: 12px 26px; border-radius: 7px; font-size: 14px;
    font-weight: 600; font-family: inherit; cursor: pointer;
    box-shadow: 0 3px 10px rgba(47,28,0,0.18);
    transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
}
.am-save-btn:hover { background: #4a2e00; transform: translateY(-1px); box-shadow: 0 5px 16px rgba(47,28,0,0.22); }
.am-cancel-btn { background: none; border: 1.5px solid #ddd; padding: 11px 20px; border-radius: 7px; font-size: 13.5px; font-family: inherit; cursor: pointer; color: #555; transition: all 0.18s; }
.am-cancel-btn:hover { border-color: #999; color: #333; }

/* ── Empty state ── */
.am-empty { text-align: center; padding: 50px 20px; color: #bbb; }
.am-empty i { font-size: 48px; margin-bottom: 14px; display: block; }
.am-empty p { font-size: 15px; }
</style>

<?php
// ── Dữ liệu cho form sửa ──
$edit_data = null;
if (!empty($edit_id_open)) {
    $ep = $conn->prepare("SELECT * FROM user_addresses WHERE address_id=:id AND user_id=:uid");
    $ep->execute([':id'=>$edit_id_open, ':uid'=>$uid]);
    $edit_data = $ep->fetch(PDO::FETCH_ASSOC);
}

// ── Location config ──
// Không dùng mock data nữa, sẽ fetch từ esgoo.net qua JS
$location_json = json_encode([]);
?>

<div class="am-wrap">

    <!-- ── Section header ── -->
    <div class="am-header">
        <div>
            <h2><i class="fa-solid fa-location-dot" style="color:#2f1c00;margin-right:8px;"></i>Địa chỉ nhận hàng</h2>
            <p>Quản lý các địa chỉ giao hàng của bạn</p>
        </div>
        <button class="am-btn am-btn-primary" onclick="openDrawer('add')">
            <i class="fa-solid fa-plus"></i> Thêm địa chỉ mới
        </button>
    </div>

    <!-- ── Alerts ── -->
    <?php if ($ok_msg): ?>
    <div class="am-alert success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($ok_msg) ?></div>
    <?php endif; ?>
    <?php if (!empty($addr_errors)): ?>
    <div class="am-alert error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <div><strong>Có lỗi:</strong><ul><?php foreach($addr_errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
    </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════
         DRAWER: THÊM MỚI
    ══════════════════════════════════════════════ -->
    <div class="am-drawer" id="drawer-add" style="display:<?= (($open_tab??'') === 'add') ? 'block' : 'none' ?>;">
        <div class="am-drawer-header">
            <span><i class="fa-solid fa-location-dot"></i> &nbsp;Thêm địa chỉ mới</span>
            <button class="am-drawer-close" onclick="closeDrawer('add')" type="button">×</button>
        </div>
        <div class="am-drawer-body">
            <?= renderAddressForm('add', null, $location_json) ?>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════
         DANH SÁCH ĐỊA CHỈ
    ══════════════════════════════════════════════ -->
    <?php if (empty($address_list)): ?>
        <div class="am-empty">
            <i class="fa-solid fa-map-location-dot"></i>
            <p>Bạn chưa có địa chỉ nào. Hãy thêm địa chỉ đầu tiên!</p>
        </div>
    <?php else: ?>
    <div class="am-cards">
        <?php foreach ($address_list as $a): ?>
        <div class="am-card <?= $a['is_default'] ? 'is-default' : '' ?>">

            <?php if ($a['is_default']): ?>
            <div class="badge-default"><i class="fa-solid fa-check"></i> Mặc định</div>
            <?php endif; ?>

            <div class="am-card-name"><?= htmlspecialchars($a['recipient_name']) ?></div>
            <div class="am-card-phone"><i class="fa-solid fa-phone" style="color:#aaa;margin-right:5px;font-size:11px;"></i><?= htmlspecialchars($a['phone']) ?></div>
            <div class="am-card-addr">
                <?= htmlspecialchars($a['street']) ?>
                <?php if ($a['ward'])     echo ', ' . htmlspecialchars($a['ward']); ?>
                <?php if ($a['district']) echo ', ' . htmlspecialchars($a['district']); ?>
                <?php if ($a['province']) echo ', ' . htmlspecialchars($a['province']); ?>
            </div>
            <?php if (!empty($a['note'])): ?>
            <div style="font-size:12px;color:#999;margin-top:6px;font-style:italic;">📝 <?= htmlspecialchars($a['note']) ?></div>
            <?php endif; ?>

            <div class="am-card-actions">
                <button class="am-btn am-btn-edit" onclick="openEditDrawer(<?= $a['address_id'] ?>)" type="button">
                    <i class="fa-regular fa-pen-to-square"></i> Sửa
                </button>
                <?php if (!$a['is_default']): ?>
                <a href="dashboard.php?view=diachi&addr_action=set_default&addr_id=<?= $a['address_id'] ?>"
                   class="am-btn am-btn-default"
                   onclick="return confirm('Đặt đây làm địa chỉ mặc định?')">
                    <i class="fa-regular fa-star"></i> Mặc định
                </a>
                <?php endif; ?>
                <a href="dashboard.php?view=diachi&addr_action=delete&addr_id=<?= $a['address_id'] ?>"
                   class="am-btn am-btn-delete"
                   onclick="return confirm('Xóa địa chỉ này?')">
                    <i class="fa-regular fa-trash-can"></i> Xóa
                </a>
            </div>

        </div>

        <!-- ── Drawer sửa (inline, ẩn) ── -->
        <div class="am-drawer" id="drawer-edit-<?= $a['address_id'] ?>"
             style="grid-column: 1/-1; display:<?= (!empty($edit_id_open) && $edit_id_open == $a['address_id']) ? 'block':'none' ?>;">
            <div class="am-drawer-header" style="grid-column:1/-1;">
                <span><i class="fa-regular fa-pen-to-square"></i> &nbsp;Sửa địa chỉ</span>
                <button class="am-drawer-close" onclick="closeEditDrawer(<?= $a['address_id'] ?>)" type="button">×</button>
            </div>
            <div class="am-drawer-body">
                <?= renderAddressForm('edit', $a, $location_json) ?>
            </div>
        </div>

        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div><!-- /.am-wrap -->

<?php
// ── Helper: render form ──────────────────────────────────────────────────────
function renderAddressForm(string $mode, ?array $a, string $loc_json): string {
    $eid   = $a['address_id'] ?? 0;
    $fid   = $mode === 'edit' ? "ef_{$eid}" : 'af';
    $rn    = htmlspecialchars($a['recipient_name'] ?? '');
    $ph    = htmlspecialchars($a['phone']          ?? '');
    $st    = htmlspecialchars($a['street']         ?? '');
    $wa    = htmlspecialchars($a['ward']           ?? '');
    $di    = htmlspecialchars($a['district']       ?? '');
    $pr    = htmlspecialchars($a['province']       ?? '');
    $no    = htmlspecialchars($a['note']           ?? '');
    $isd   = !empty($a['is_default']);
    $action_val = $mode;
    ob_start();
    ?>
    <form method="POST" id="form-<?= $fid ?>" onsubmit="return validateAmForm('<?= $fid ?>')">
        <input type="hidden" name="addr_action" value="<?= $action_val ?>">
        <?php if ($mode === 'edit'): ?>
        <input type="hidden" name="edit_id" value="<?= $eid ?>">
        <?php endif; ?>

        <div class="am-grid-2">
            <div class="am-field full">
                <label>Họ và tên người nhận <span class="req">*</span></label>
                <div class="am-input-wrap"><i class="fa-regular fa-user"></i>
                    <input type="text" id="<?= $fid ?>_rn" name="recipient_name" value="<?= $rn ?>" placeholder="Nguyễn Thị Kim..." autocomplete="name">
                </div>
                <span class="am-field-err" id="<?= $fid ?>_rn_err">Vui lòng nhập họ tên.</span>
            </div>

            <div class="am-field full">
                <label>Số điện thoại <span class="req">*</span></label>
                <div class="am-input-wrap"><i class="fa-solid fa-phone"></i>
                    <input type="tel" id="<?= $fid ?>_ph" name="phone" value="<?= $ph ?>" placeholder="0901 234 567" autocomplete="tel">
                </div>
                <span class="am-field-err" id="<?= $fid ?>_ph_err">SĐT không hợp lệ (VD: 0901234567).</span>
            </div>

            <div class="am-field full"><hr style="border:none;border-top:1px dashed #eee;margin:2px 0 6px;"></div>

            <div class="am-field full">
                <label>Địa chỉ cụ thể <span class="req">*</span></label>
                <div class="am-input-wrap"><i class="fa-solid fa-house"></i>
                    <input type="text" id="<?= $fid ?>_st" name="street" value="<?= $st ?>" placeholder="Số nhà, tên đường, hẻm...">
                </div>
                <span class="am-field-err" id="<?= $fid ?>_st_err">Vui lòng nhập địa chỉ cụ thể.</span>
            </div>

            <div class="am-field">
                <label>Tỉnh / Thành phố <span class="req">*</span></label>
                <div class="am-input-wrap"><i class="fa-solid fa-city"></i>
                    <select id="<?= $fid ?>_pr" name="province" onchange="amCascade('<?= $fid ?>')">
                        <option value="">-- Chọn Tỉnh/TP --</option>
                    </select>
                </div>
                <span class="am-field-err" id="<?= $fid ?>_pr_err">Vui lòng chọn Tỉnh/TP.</span>
            </div>

            <div class="am-field">
                <label>Quận / Huyện <span class="req">*</span></label>
                <div class="am-input-wrap"><i class="fa-solid fa-map-pin"></i>
                    <select id="<?= $fid ?>_di" name="district" disabled onchange="amCascadeWard('<?= $fid ?>')">
                        <option value="">-- Chọn Quận/Huyện --</option>
                    </select>
                </div>
                <span class="am-field-err" id="<?= $fid ?>_di_err">Vui lòng chọn Quận/Huyện.</span>
            </div>

            <div class="am-field full">
                <label>Phường / Xã</label>
                <div class="am-input-wrap"><i class="fa-solid fa-map"></i>
                    <select id="<?= $fid ?>_wa" name="ward" disabled>
                        <option value="">-- Chọn Phường/Xã --</option>
                    </select>
                </div>
            </div>

            <div class="am-field full">
                <label>Ghi chú <span style="font-weight:400;color:#bbb;font-size:10px;">(tuỳ chọn)</span></label>
                <div class="am-input-wrap textarea-wrap"><i class="fa-regular fa-note-sticky"></i>
                    <textarea id="<?= $fid ?>_no" name="note" placeholder="Giao giờ hành chính, gọi trước khi giao..."><?= $no ?></textarea>
                </div>
            </div>
        </div>

        <label class="am-check-row">
            <input type="checkbox" name="is_default" value="1" <?= $isd ? 'checked' : '' ?>
                   <?= ($isd && $mode==='edit') ? 'disabled title="Đây đã là địa chỉ mặc định"' : '' ?>>
            <span>
                <i class="fa-regular fa-star" style="color:#2f1c00;margin-right:4px;"></i>
                Đặt làm địa chỉ mặc định khi thanh toán
            </span>
        </label>

        <div class="am-submit-row">
            <button type="submit" class="am-save-btn">
                <i class="fa-solid fa-floppy-disk"></i>
                <?= $mode === 'edit' ? 'Cập nhật địa chỉ' : 'Lưu địa chỉ' ?>
            </button>
            <button type="button" class="am-cancel-btn"
                onclick="<?= $mode==='edit' ? "closeEditDrawer({$eid})" : "closeDrawer('add')" ?>">
                Huỷ
            </button>
        </div>

        <!-- data cho JS cascade -->
        <script>
        (function(){
            const fid = '<?= $fid ?>';
            const savedPr = <?= json_encode($pr) ?>;
            const savedDi = <?= json_encode($di) ?>;
            const savedWa = <?= json_encode($wa) ?>;

            const selPr = document.getElementById(fid+'_pr');
            const selDi = document.getElementById(fid+'_di');
            const selWa = document.getElementById(fid+'_wa');

            function fill(sel, opts, placeholder, selected){
                sel.innerHTML = `<option value="">${placeholder}</option>`;
                opts.forEach(o => {
                    const el = document.createElement('option');
                    el.value = o.name;
                    el.dataset.id = o.id;
                    el.textContent = o.name;
                    if(o.name === selected) el.selected = true;
                    sel.appendChild(el);
                });
            }

            // Gọi API Tỉnh
            function initPr(){
                fetch('https://esgoo.net/api-tinhthanh/1/0.htm')
                    .then(r=>r.json())
                    .then(d => {
                        if(d.error===0) {
                            fill(selPr, d.data, '-- Chọn Tỉnh/TP --', savedPr);
                            if(savedPr) {
                                // Tìm ID của tỉnh đã lưu để load quận
                                const opt = Array.from(selPr.options).find(o => o.value === savedPr);
                                if(opt && opt.dataset.id) initDi(opt.dataset.id, savedDi);
                            }
                        }
                    });
            }

            // Gọi API Quận
            function initDi(pid, selVal){
                selDi.disabled = false;
                fetch(`https://esgoo.net/api-tinhthanh/2/${pid}.htm`)
                    .then(r=>r.json())
                    .then(d => {
                        if(d.error===0) {
                            fill(selDi, d.data, '-- Chọn Quận/Huyện --', selVal);
                            if(selVal) {
                                const opt = Array.from(selDi.options).find(o => o.value === selVal);
                                if(opt && opt.dataset.id) initWa(opt.dataset.id, savedWa);
                            }
                        }
                    });
            }

            // Gọi API Phường
            function initWa(did, selVal){
                selWa.disabled = false;
                fetch(`https://esgoo.net/api-tinhthanh/3/${did}.htm`)
                    .then(r=>r.json())
                    .then(d => {
                        if(d.error===0) {
                            fill(selWa, d.data, '-- Chọn Phường/Xã --', selVal);
                        }
                    });
            }

            window.amCascade = function(f){
                if(f!==fid) return;
                selWa.innerHTML='<option value="">-- Chọn Phường/Xã --</option>'; selWa.disabled=true;
                const opt = selPr.options[selPr.selectedIndex];
                if(opt && opt.dataset.id) {
                    initDi(opt.dataset.id, '');
                } else {
                    selDi.innerHTML='<option value="">-- Chọn Quận/Huyện --</option>'; selDi.disabled=true;
                }
            };

            window.amCascadeWard = function(f){
                if(f!==fid) return;
                const opt = selDi.options[selDi.selectedIndex];
                if(opt && opt.dataset.id) {
                    initWa(opt.dataset.id, '');
                } else {
                    selWa.innerHTML='<option value="">-- Chọn Phường/Xã --</option>'; selWa.disabled=true;
                }
            };

            document.addEventListener('DOMContentLoaded', initPr);
            if(document.readyState!=='loading') initPr();
        })();
        </script>
    </form>
    <?php
    return ob_get_clean();
}
?>

<script>
// ── Drawer open/close ──────────────────────────────────────
function openDrawer(name) {
    document.getElementById('drawer-' + name).style.display = 'block';
    document.getElementById('drawer-' + name).scrollIntoView({ behavior: 'smooth', block: 'center' });
}
function closeDrawer(name) {
    document.getElementById('drawer-' + name).style.display = 'none';
}
function openEditDrawer(id) {
    // Đóng tất cả drawer edit khác
    document.querySelectorAll('[id^="drawer-edit-"]').forEach(d => d.style.display = 'none');
    const d = document.getElementById('drawer-edit-' + id);
    if (d) { d.style.display = 'block'; d.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
}
function closeEditDrawer(id) {
    const d = document.getElementById('drawer-edit-' + id);
    if (d) d.style.display = 'none';
}

// ── Client-side validation ─────────────────────────────────
const phoneRx = /^(0|\+84)[0-9]{8,10}$/;
function validateAmForm(fid) {
    let ok = true;
    const set = (id, errId, cond) => {
        const el = document.getElementById(id);
        const er = document.getElementById(errId);
        if (!el || !er) return;
        if (cond) { el.classList.add('am-input-err'); er.classList.add('show'); ok = false; }
        else       { el.classList.remove('am-input-err'); er.classList.remove('show'); }
    };
    set(fid+'_rn', fid+'_rn_err', !document.getElementById(fid+'_rn')?.value.trim());
    set(fid+'_ph', fid+'_ph_err', !phoneRx.test((document.getElementById(fid+'_ph')?.value||'').replace(/\s+/,'')));
    set(fid+'_st', fid+'_st_err', !document.getElementById(fid+'_st')?.value.trim());
    set(fid+'_pr', fid+'_pr_err', !document.getElementById(fid+'_pr')?.value);
    set(fid+'_di', fid+'_di_err', !document.getElementById(fid+'_di')?.value);
    if (!ok) {
        const first = document.querySelector('.am-input-err');
        if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    return ok;
}
</script>