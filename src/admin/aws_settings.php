<?php
require_once 'auth_check.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/aws_rekognition.php';

// ── XỬ LÝ FORM SUBMIT ──────────────────────────────────────────────
$message = '';
$message_type = '';
$test_result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_credentials') {
        $accessKey    = $_POST['aws_access_key_id'] ?? '';
        $secretKey    = $_POST['aws_secret_access_key'] ?? '';
        $sessionToken = $_POST['aws_session_token'] ?? '';
        $region       = $_POST['aws_region'] ?? 'us-east-1';

        if (empty($accessKey) || empty($secretKey)) {
            $message = 'Vui lòng nhập đầy đủ Access Key ID và Secret Access Key!';
            $message_type = 'error';
        } else {
            $saved = AwsRekognition::saveCredentials($accessKey, $secretKey, $sessionToken, $region);
            if ($saved) {
                $message = '✅ Đã lưu AWS Credentials thành công! Có hiệu lực ngay lập tức (không cần restart).';
                $message_type = 'success';
            } else {
                $message = '❌ Lỗi khi ghi file. Kiểm tra quyền ghi thư mục config/.';
                $message_type = 'error';
            }
        }
    }

    if ($action === 'paste_block') {
        // Parse AWS CLI credential block
        $raw = $_POST['credential_block'] ?? '';
        
        $accessKey = ''; $secretKey = ''; $sessionToken = '';
        
        if (preg_match('/aws_access_key_id\s*=\s*(\S+)/i', $raw, $m)) {
            $accessKey = $m[1];
        }
        if (preg_match('/aws_secret_access_key\s*=\s*(\S+)/i', $raw, $m)) {
            $secretKey = $m[1];
        }
        if (preg_match('/aws_session_token\s*=\s*(\S+)/i', $raw, $m)) {
            $sessionToken = $m[1];
        }

        if (!empty($accessKey) && !empty($secretKey)) {
            // Default Learner Lab Region is us-east-1
            $region = 'us-east-1';
            if (preg_match('/(?:aws_region|region)\s*=\s*(\S+)/i', $raw, $m)) {
                $region = $m[1];
            }

            $saved = AwsRekognition::saveCredentials($accessKey, $secretKey, $sessionToken, $region);
            if ($saved) {
                $message = '✅ Đã tự động nhận diện và lưu credentials từ block paste! Có hiệu lực ngay.';
                $message_type = 'success';
            } else {
                $message = '❌ Lỗi khi ghi file.';
                $message_type = 'error';
            }
        } else {
            $message = '❌ Không nhận diện được credentials. Hãy paste đúng block từ AWS Details.';
            $message_type = 'error';
        }
    }

    if ($action === 'test_connection') {
        $rekognition = new AwsRekognition();
        if ($rekognition->isConfigured()) {
            // Test với ảnh 1x1 pixel
            $testImage = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==');
            $result = $rekognition->detectLabels($testImage, 3, 50.0);

            if (isset($result['error'])) {
                $test_result = ['status' => 'error', 'detail' => json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)];
            } else {
                $test_result = ['status' => 'success', 'detail' => json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)];
            }
        } else {
            $test_result = ['status' => 'error', 'detail' => 'AWS chưa được cấu hình. Hãy lưu credentials trước.'];
        }
    }
}

// ── LẤY THÔNG TIN HIỆN TẠI ──────────────────────────────────────────
$credInfo = AwsRekognition::getCredentialInfo();

include __DIR__ . '/../includes/admin_sidebar.php';
?>

<style>
    * { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; box-sizing: border-box; }
    
    .page-header { margin-bottom: 28px; }
    .page-title { font-size: 21px; font-weight: 700; color: #111; text-transform: uppercase; letter-spacing: 0.5px; }
    .page-subtitle { font-size: 13px; color: #999; margin-top: 4px; }

    .aws-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }

    .aws-card {
        background: #fff;
        border: 1px solid #e5e5e5;
        border-radius: 10px;
        padding: 24px;
    }

    .aws-card-title {
        font-size: 14px; font-weight: 700; color: #111;
        text-transform: uppercase; letter-spacing: 0.8px;
        margin-bottom: 20px; padding-bottom: 12px;
        border-bottom: 1px solid #e5e5e5;
        display: flex; align-items: center; gap: 8px;
    }

    .aws-card-full { grid-column: 1 / -1; }

    /* Status Bar */
    .status-bar {
        display: flex; flex-wrap: wrap; gap: 20px; align-items: center;
        padding: 18px 24px;
        background: #fafaf8; border: 1px solid #e5e5e5; border-radius: 10px;
        margin-bottom: 24px;
    }
    .status-item { display: flex; align-items: center; gap: 8px; font-size: 13px; }
    .status-dot { width: 10px; height: 10px; border-radius: 50%; }
    .status-dot.green { background: #27ae60; }
    .status-dot.red { background: #e74c3c; }
    .status-dot.orange { background: #f39c12; }
    .status-label { color: #666; }
    .status-value { font-weight: 600; color: #111; }

    /* Form */
    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-size: 12px; font-weight: 600; color: #555; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
    .form-input {
        width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 6px;
        font-size: 13px; font-family: 'Courier New', monospace;
        transition: border-color 0.2s;
    }
    .form-input:focus { outline: none; border-color: #a6825c; }
    
    .form-textarea {
        width: 100%; min-height: 140px; padding: 12px 14px;
        border: 1px solid #ddd; border-radius: 6px;
        font-size: 12px; font-family: 'Courier New', monospace;
        resize: vertical; transition: border-color 0.2s;
    }
    .form-textarea:focus { outline: none; border-color: #a6825c; }

    .form-hint { font-size: 11px; color: #999; margin-top: 4px; }

    /* Buttons */
    .btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 10px 20px; border: none; border-radius: 6px;
        font-size: 13px; font-weight: 600; cursor: pointer;
        transition: all 0.2s;
    }
    .btn-primary { background: #2f1c00; color: #fff; }
    .btn-primary:hover { background: #a6825c; }
    .btn-test { background: #f5f1eb; color: #2f1c00; }
    .btn-test:hover { background: #ece8e1; }
    .btn-paste { background: #27ae60; color: #fff; }
    .btn-paste:hover { background: #219a52; }

    .btn-row { display: flex; gap: 10px; margin-top: 20px; }

    /* Alert */
    .alert {
        padding: 14px 18px; border-radius: 8px; margin-bottom: 20px;
        font-size: 13px; font-weight: 500;
    }
    .alert-success { background: #eafaf1; color: #1a7a42; border: 1px solid #a3d9b1; }
    .alert-error { background: #fdf0ef; color: #c0392b; border: 1px solid #f5c6cb; }

    /* Test Result */
    .test-box {
        margin-top: 16px; padding: 16px; border-radius: 8px;
        font-size: 12px; font-family: 'Courier New', monospace;
        max-height: 300px; overflow-y: auto;
    }
    .test-success { background: #eafaf1; border: 1px solid #a3d9b1; }
    .test-error { background: #fdf0ef; border: 1px solid #f5c6cb; }

    /* Quick Paste Highlight */
    .quick-paste-box {
        background: linear-gradient(135deg, #f8f6f0, #fff8e9);
        border: 2px dashed #d4a853;
    }
    .quick-paste-box .aws-card-title { color: #7a5000; border-bottom-color: #d4a853; }

    /* Dark mode overrides */
    body.dark-mode .aws-card { background: #1e1e1e; border-color: #2a2a2a; }
    body.dark-mode .aws-card-title { color: #fff; border-bottom-color: #2a2a2a; }
    body.dark-mode .status-bar { background: #1a1a1a; border-color: #2a2a2a; }
    body.dark-mode .status-label { color: #888; }
    body.dark-mode .status-value { color: #fff; }
    body.dark-mode .form-label { color: #aaa; }
    body.dark-mode .form-input,
    body.dark-mode .form-textarea { background: #252525; border-color: #333; color: #fff; }
    body.dark-mode .form-input:focus,
    body.dark-mode .form-textarea:focus { border-color: #a6825c; }
    body.dark-mode .form-hint { color: #666; }
    body.dark-mode .alert-success { background: #1c3d27; color: #2ecc71; border-color: #2ecc71; }
    body.dark-mode .alert-error { background: #3d1a1a; color: #ff4d4d; border-color: #c0392b; }
    body.dark-mode .quick-paste-box { background: linear-gradient(135deg, #2a2515, #1e1e1e); border-color: #a6825c; }
    body.dark-mode .quick-paste-box .aws-card-title { color: #e5c199; border-bottom-color: #a6825c; }
</style>

<div class="page-header">
    <div class="page-title"><i class="fa-brands fa-aws"></i> AWS Rekognition</div>
    <div class="page-subtitle">Quản lý credentials cho tìm kiếm ảnh & kiểm duyệt nội dung</div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<!-- Status Bar -->
<div class="status-bar">
    <div class="status-item">
        <div class="status-dot <?= $credInfo['source'] !== 'none' ? 'green' : 'red' ?>"></div>
        <span class="status-label">Trạng thái:</span>
        <span class="status-value"><?= htmlspecialchars($credInfo['source_label']) ?></span>
    </div>
    <?php if ($credInfo['source'] !== 'none'): ?>
        <div class="status-item">
            <i class="fa-solid fa-key" style="color:#a6825c;"></i>
            <span class="status-label">Key:</span>
            <span class="status-value"><?= htmlspecialchars($credInfo['key_preview']) ?></span>
        </div>
        <div class="status-item">
            <i class="fa-solid fa-globe" style="color:#3498db;"></i>
            <span class="status-label">Region:</span>
            <span class="status-value"><?= htmlspecialchars($credInfo['region']) ?></span>
        </div>
        <div class="status-item">
            <div class="status-dot <?= $credInfo['has_token'] ? 'green' : 'orange' ?>"></div>
            <span class="status-label">Session Token:</span>
            <span class="status-value"><?= $credInfo['has_token'] ? 'Có' : 'Không' ?></span>
        </div>
        <?php if ($credInfo['updated_at'] !== 'N/A'): ?>
            <div class="status-item">
                <i class="fa-solid fa-clock" style="color:#999;"></i>
                <span class="status-label">Cập nhật:</span>
                <span class="status-value"><?= htmlspecialchars($credInfo['updated_at']) ?></span>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="aws-grid">
    <!-- ⚡ PASTE NHANH (Main Feature) -->
    <div class="aws-card aws-card-full quick-paste-box">
        <div class="aws-card-title">
            <i class="fa-solid fa-bolt"></i> Cập nhật nhanh — Paste từ AWS Academy
        </div>
        <p style="font-size:13px; color:#666; margin-bottom:14px; line-height:1.6;">
            Vào <b>AWS Academy → Start Lab → AWS Details</b>, copy toàn bộ block credentials rồi paste vào ô bên dưới. 
            Hệ thống sẽ <b>tự động nhận diện</b> Access Key, Secret Key và Session Token.
        </p>
        <form method="POST">
            <input type="hidden" name="action" value="paste_block">
            <div class="form-group">
                <label class="form-label">Paste credentials block tại đây</label>
                <textarea class="form-textarea" name="credential_block" placeholder="[default]
aws_access_key_id=ASIAXXXXXXXXXXX
aws_secret_access_key=xxxxxxxxxxxxxxxx
aws_session_token=FwoGZXIvYXdzExxxxxx..."></textarea>
                <div class="form-hint">Hệ thống tự động parse — chỉ cần paste nguyên block, không cần tách từng trường.</div>
            </div>
            <button type="submit" class="btn btn-paste"><i class="fa-solid fa-wand-magic-sparkles"></i> Tự động nhận diện & Lưu</button>
        </form>
    </div>

    <!-- Nhập thủ công -->
    <div class="aws-card">
        <div class="aws-card-title">
            <i class="fa-solid fa-pen-to-square"></i> Nhập thủ công
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="save_credentials">
            <div class="form-group">
                <label class="form-label">Access Key ID <span style="color:#e74c3c;">*</span></label>
                <input type="text" name="aws_access_key_id" class="form-input" placeholder="ASIA..." autocomplete="off">
            </div>
            <div class="form-group">
                <label class="form-label">Secret Access Key <span style="color:#e74c3c;">*</span></label>
                <input type="password" name="aws_secret_access_key" class="form-input" placeholder="••••••••" autocomplete="off">
            </div>
            <div class="form-group">
                <label class="form-label">Session Token <span style="color:#999;">(Learner Lab)</span></label>
                <textarea class="form-textarea" name="aws_session_token" placeholder="FwoGZXIvYXdz..." style="min-height:80px;"></textarea>
                <div class="form-hint">Bắt buộc nếu sử dụng AWS Academy Learner Lab</div>
            </div>
            <div class="form-group">
                <label class="form-label">Region</label>
                <select name="aws_region" class="form-input" style="font-family:inherit;">
                    <option value="us-east-1" selected>us-east-1 (N. Virginia)</option>
                    <option value="us-west-2">us-west-2 (Oregon)</option>
                    <option value="ap-southeast-1">ap-southeast-1 (Singapore)</option>
                    <option value="ap-northeast-1">ap-northeast-1 (Tokyo)</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Lưu Credentials</button>
        </form>
    </div>

    <!-- Test kết nối -->
    <div class="aws-card">
        <div class="aws-card-title">
            <i class="fa-solid fa-flask-vial"></i> Kiểm tra kết nối
        </div>
        <p style="font-size:13px; color:#666; margin-bottom:16px; line-height:1.5;">
            Gửi 1 ảnh test nhỏ tới AWS Rekognition để kiểm tra credentials có hoạt động không.
        </p>
        <form method="POST">
            <input type="hidden" name="action" value="test_connection">
            <button type="submit" class="btn btn-test"><i class="fa-solid fa-satellite-dish"></i> Test DetectLabels API</button>
        </form>

        <?php if ($test_result): ?>
            <div class="test-box test-<?= $test_result['status'] ?>">
                <strong><?= $test_result['status'] === 'success' ? '✅ Kết nối thành công!' : '❌ Kết nối thất bại!' ?></strong>
                <pre style="margin-top:10px; white-space:pre-wrap; word-break:break-all;"><?= htmlspecialchars($test_result['detail']) ?></pre>
                <?php if ($test_result['status'] === 'error'): ?>
                    <div style="margin-top:12px; font-family:inherit; font-size:12px;">
                        <b>Giải pháp:</b>
                        <ul style="margin-top:6px; padding-left:18px;">
                            <li>Session Token hết hạn → Vào AWS Academy, <b>Start Lab</b> lại, paste block mới</li>
                            <li>Access Key sai → Kiểm tra lại chính xác từ AWS Details</li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

</main>
</body>
</html>
