<?php
session_start();
require_once '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = md5($_POST['password']); 

    // Nhận cái link "trí nhớ" từ thẻ input ẩn lúc nãy
    $redirect_to = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : '../index.php';

    try {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $user = null;
        if ($stmt->rowCount() > 0) {
            $potential_user = $stmt->fetch(PDO::FETCH_ASSOC);
            $plain_password = $_POST['password'] ?? '';
            $authenticated = false;

            if (strlen($potential_user['password']) === 32) {
                // Legacy MD5 Check
                if ($potential_user['password'] === md5($plain_password)) {
                    $authenticated = true;
                }
            } else {
                // Modern BCRYPT Check
                if (password_verify($plain_password, $potential_user['password'])) {
                    $authenticated = true;
                }
            }

            if ($authenticated) {
                $user = $potential_user;
            }
        }

        if ($user) {

            // KIỂM TRA OTP
            if ($user['is_verified'] == 0) {
                $_SESSION['login_error'] = "Tài khoản chưa xác thực OTP. Vui lòng kiểm tra Email!";
                header("Location: ../views/login.php");
                exit();
            }

            // LƯU SESSION
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role'] = $user['role'];
            if ($user['role'] == 1) {
                $_SESSION['admin_logged_in'] = true;
            }

            // Task 2: Auto-Execute Post-Login Add to Cart
            if (isset($_SESSION['pending_cart_action'])) {
                $pending = $_SESSION['pending_cart_action'];
                $p_variant_id = $pending['variant_id'];
                $p_qty = $pending['quantity'];
                $p_user_id = $_SESSION['user_id'];

                try {
                    // Check stock
                    $st_v = $conn->prepare("SELECT stock FROM product_variants WHERE variant_id = :vid AND is_active = 1");
                    $st_v->execute(['vid' => $p_variant_id]);
                    $variant_info = $st_v->fetch(PDO::FETCH_ASSOC);

                    if ($variant_info && $variant_info['stock'] >= 1) {
                        if ($p_qty > $variant_info['stock']) {
                            $p_qty = $variant_info['stock'];
                        }

                        // Check if item already in user's cart
                        $st_c = $conn->prepare("SELECT cart_id, quantity FROM cart WHERE user_id = :uid AND variant_id = :vid");
                        $st_c->execute(['uid' => $p_user_id, 'vid' => $p_variant_id]);
                        $existing_cart = $st_c->fetch(PDO::FETCH_ASSOC);

                        if ($existing_cart) {
                            $new_qty = min($existing_cart['quantity'] + $p_qty, $variant_info['stock']);
                            $upd_c = $conn->prepare("UPDATE cart SET quantity = :qty WHERE cart_id = :cid");
                            $upd_c->execute(['qty' => $new_qty, 'cid' => $existing_cart['cart_id']]);
                        } else {
                            // Generate new cart ID
                            $stMax = $conn->prepare("SELECT MAX(CAST(SUBSTRING(cart_id, 2) AS UNSIGNED)) FROM cart");
                            $stMax->execute();
                            $maxNum = intval($stMax->fetchColumn()) + 1;
                            $new_cart_id = 'C' . str_pad($maxNum, 4, '0', STR_PAD_LEFT);

                            // Insert new cart item
                            $ins_c = $conn->prepare("INSERT INTO cart (cart_id, user_id, variant_id, quantity, is_selected) VALUES (:cid, :uid, :vid, :qty, 1)");
                            $ins_c->execute([
                                'cid' => $new_cart_id,
                                'uid' => $p_user_id,
                                'vid' => $p_variant_id,
                                'qty' => $p_qty
                            ]);
                        }

                        // Set success message for UI toast/alert
                        $_SESSION['cart_success_msg'] = "Product has been successfully added to your cart after login.";
                    }
                } catch (PDOException $e) {
                    // Fail silently or log error
                }

                $redirect_to = $pending['return_url'];
                unset($_SESSION['pending_cart_action']);
            } elseif (isset($_SESSION['redirect_url'])) {
                // Task 1: Check if the redirect URL exists
                $redirect_to = $_SESSION['redirect_url'];
                unset($_SESSION['redirect_url']);
            } elseif ($user['role'] == 1 && ($redirect_to === '../index.php' || strpos($redirect_to, 'login.php') !== false)) {
                // If it's an admin and they don't have a specific page redirection request, redirect to admin home page
                $redirect_to = '../admin/index.php';
            }
            
            // LUỒNG LOGIC: Báo thành công và văng về ĐÚNG TRANG CŨ
            echo "<script>
                    alert('Đăng nhập thành công!');
                    window.location.href = '" . $redirect_to . "';
                  </script>";
            exit();

        } else {
            $_SESSION['login_error'] = "Email hoặc mật khẩu không chính xác!";
            header("Location: ../views/login.php");
            exit();
        }
    } catch(PDOException $e) {
        $_SESSION['login_error'] = "Lỗi hệ thống: " . $e->getMessage();
        header("Location: ../views/login.php");
        exit();
    }
}
?>