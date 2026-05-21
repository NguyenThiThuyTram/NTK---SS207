# FULL SYSTEM TEST & DEPLOYMENT REPORT
## NTK Fashion — https://ntkfashion.me

**Ngày kiểm thử:** 21/05/2026  
**Người kiểm thử:** Senior QA Engineer (AI Analysis)  
**Môi trường triển khai:** AWS Learner Lab + Docker + Cloudflare Tunnel  
**Domain:** `https://ntkfashion.me`  
**Stack:** PHP 8.x / MySQL / Nginx / Docker / Cloudflare  

---

## 1. TỔNG QUAN HỆ THỐNG

NTK Fashion là một website thương mại điện tử bán quần áo thời trang. Hệ thống được xây dựng theo mô hình **PHP thuần (không dùng framework)** + **MySQL** + **Nginx** đóng gói bằng **Docker**, deploy trên AWS EC2 thông qua **Cloudflare Tunnel**.

### Kiến trúc hệ thống:
```
[Người dùng] → [Cloudflare CDN] → [Cloudflared Tunnel] → [Nginx] → [PHP-FPM] → [MySQL]
```

### Công nghệ sử dụng:
| Thành phần | Công nghệ |
|---|---|
| Frontend | HTML, CSS thuần, jQuery 3.6.0, Font Awesome 6 |
| Backend | PHP 8.x (PDO + MySQL) |
| Database | MySQL |
| Web Server | Nginx Alpine |
| Container | Docker Compose |
| Tunnel | Cloudflare Tunnel (cloudflared) |
| Payment | PayOS API |
| AI Chatbot | Google Gemini API (gemini-2.5-flash) |
| Email | PHPMailer + Gmail SMTP |
| Địa chỉ | esgoo.net API |

---

## 2. TẤT CẢ TÍNH NĂNG PHÁT HIỆN TỪ SOURCE CODE

### 2.1 Tính năng Frontend (Khách hàng)

| STT | Tính năng | File chính |
|---|---|---|
| 1 | Trang chủ: New Arrivals + Best Sellers + Category | `index.php` |
| 2 | Danh sách sản phẩm + lọc theo danh mục | `product.php` |
| 3 | Chi tiết sản phẩm: chọn màu/size, xem ảnh, thêm giỏ | `product_detail.php` |
| 4 | Tìm kiếm sản phẩm | `search.php` |
| 5 | Giỏ hàng: xem, sửa số lượng, xóa, chọn thanh toán | `cart.php` + `ajax_cart.php` |
| 6 | Thanh toán 3 bước: thông tin → ship → thanh toán | `checkout.php` |
| 7 | Thanh toán COD / Online PayOS QR | `controllers/process_checkout.php` |
| 8 | Trang thành công đặt hàng + QR PayOS polling | `order_success.php` |
| 9 | Danh sách yêu thích | `wishlist.php` + `ajax_wishlist.php` |
| 10 | Mã giảm giá (coupon) | `api/check_coupon.php` + `ajax_cart.php` |
| 11 | AI Chatbot tư vấn (Gemini) | `api_chatbot.php` |
| 12 | Đăng ký + xác thực OTP qua Email | `views/register.php` + `controllers/registerController.php` |
| 13 | Đăng nhập / Đăng xuất | `views/login.php` + `controllers/loginController.php` |
| 14 | Dark Mode toggle | `includes/header.php` |
| 15 | Khuyến mãi | `promotion.php` |
| 16 | Trang chính sách (đổi trả, vận chuyển, thanh toán) | `return-policy.php`, `shipping-policy.php`, etc. |
| 17 | Hướng dẫn mua hàng | `guide.php` |

### 2.2 Tính năng Dashboard Người dùng (views/user/)

| STT | Tính năng | File |
|---|---|---|
| 1 | Hồ sơ cá nhân | `profile_form.php` |
| 2 | Quản lý địa chỉ giao hàng | `address_list.php` |
| 3 | Đổi mật khẩu | `change_password.php` |
| 4 | Lịch sử đơn hàng + theo dõi trạng thái | `orders.php` |
| 5 | Chi tiết đơn hàng | `order_detail.php` |
| 6 | Hủy đơn hàng | `controllers/cancel_order.php` |
| 7 | Xác nhận đã nhận hàng | `controllers/mark_received.php` |
| 8 | Trả hàng / Hoàn tiền vào ví | `controllers/return_order.php` |
| 9 | Ví hoàn tiền (wallet) | `wallet.php` |
| 10 | Kho voucher cá nhân | `coupon.php` + `all_coupons.php` |
| 11 | Thông báo | `notifications.php` |
| 12 | Cài đặt thông báo | `notification_settings.php` |
| 13 | Ngân hàng/Thanh toán | `bank_form.php` |

### 2.3 Tính năng Admin Panel (admin/)

| STT | Tính năng | File |
|---|---|---|
| 1 | Dashboard thống kê | `admin/dashboard.php` |
| 2 | Quản lý sản phẩm | `admin/products.php`, `add_product.php`, `product_detail.php` |
| 3 | Quản lý danh mục | `admin/categories.php`, `add_category.php`, `category_detail.php` |
| 4 | Quản lý đơn hàng | `admin/orders.php`, `order_detail.php` |
| 5 | Quản lý tài khoản người dùng | `admin/accounts.php`, `account_detail.php`, `add_account.php` |
| 6 | Quản lý mã giảm giá | `admin/coupons.php`, `add_coupon.php`, `update_coupon.php`, `view_coupon.php` |
| 7 | Quản lý kho hàng | `admin/inventory.php` |

### 2.4 API Endpoints

| Endpoint | Chức năng |
|---|---|
| `GET /api/health.php` | Kiểm tra trạng thái server + DB |
| `POST /api/check_coupon.php` | Kiểm tra mã giảm giá |
| `GET/POST /api/check_payment_status.php` | Kiểm tra trạng thái thanh toán PayOS |
| `POST /api/webhook_payos.php` | Webhook nhận callback từ PayOS |
| `POST /api/save_coupon_session.php` | Lưu coupon vào session |
| `POST /ajax_cart.php` | Xử lý tất cả thao tác giỏ hàng |
| `POST /ajax_wishlist.php` | Thêm/xóa sản phẩm yêu thích |
| `POST /ajax_remove_wishlist.php` | Xóa sản phẩm khỏi wishlist |
| `POST /api_chatbot.php` | Chatbot AI tư vấn |

---

## 3. TÍNH NĂNG HOẠT ĐỘNG ĐÚNG SAU KHI DEPLOY

| STT | Tính năng | Kết quả kiểm tra |
|---|---|---|
| ✅ 1 | Trang chủ tải được, hiển thị sản phẩm New Arrivals và Best Sellers | Hoạt động |
| ✅ 2 | Kết nối Database ổn định | API Health: `{"status":"ok","database":"connected"}` |
| ✅ 3 | Hiển thị danh mục sản phẩm (Shop by Category) | Hoạt động |
| ✅ 4 | Trang chi tiết sản phẩm tải được thông tin cơ bản | Hoạt động |
| ✅ 5 | Cloudflare Tunnel hoạt động (HTTPS) | Hoạt động |
| ✅ 6 | Static assets (CSS, JS) load từ relative path | Hoạt động |
| ✅ 7 | Chính sách trang (return, shipping, payment) tải được | Hoạt động |
| ✅ 8 | Footer render đúng | Hoạt động |
| ✅ 9 | Dark Mode toggle hoạt động (LocalStorage) | Hoạt động |
| ✅ 10 | PayOS đã từng hoạt động thành công (debug log cho thấy code: `00`) | Từng hoạt động |

---

## 4. CÁC LỖI (BUGS) PHÁT HIỆN

---

### Bug 1 — GIÁ HIỂN THỊ "0đ" CHO SẢN PHẨM BEST SELLERS TRÊN TRANG CHỦ

**Feature:** Trang chủ - Best Sellers  
**Description:** Sản phẩm Best Sellers hiển thị giá "0đ" thay vì giá thực tế  

**Related code:**
- File: `src/index.php`, dòng 25–34
- Query SQL: `SELECT p.*, v.original_price, v.sale_price FROM products p LEFT JOIN product_variants v ON p.product_id = v.product_id WHERE p.status = 1 GROUP BY p.product_id ORDER BY p.sold_count DESC LIMIT 4`
- Dòng 88: `echo number_format($item['original_price'], 0, ',', '.'); ?>`

**Steps to reproduce:**
1. Vào trang chủ `https://ntkfashion.me`
2. Xem phần "Best Sellers"
3. Quan sát giá của sản phẩm "Áo Polo Chiết Eo Tay Bồng" (PL03)

**Expected result (từ code):** Hiển thị đúng giá `original_price` hoặc `sale_price` của sản phẩm  
**Actual result (deployed):** Hiển thị "0đ" — Sản phẩm PL03 cho thấy `<p class="price">0đ</p>`

**Possible cause (QUAN TRỌNG):**  
Câu lệnh SQL dùng `GROUP BY p.product_id` nhưng `original_price` và `sale_price` thuộc bảng `product_variants`. Khi MySQL ở chế độ `ONLY_FULL_GROUP_BY` (mặc định trên MySQL 5.7+), việc chọn `v.original_price` mà không có hàm tổng hợp (MIN, MAX...) trong GROUP BY gây ra kết quả **không xác định** — có thể trả về giá trị `NULL` hoặc `0`. Trên môi trường deploy (AWS + Docker MySQL), mode strict có thể khác với môi trường local.

**Severity: HIGH** — Hiển thị giá 0đ gây mất uy tín và có thể dẫn đến lỗi khi người dùng đặt hàng.

---

### Bug 2 — TRANG CHI TIẾT SẢN PHẨM KHÔNG HIỆN GIÁ, MÀU SẮC, SIZE KHI `variants` RỖNG

**Feature:** Trang chi tiết sản phẩm (product_detail.php)  
**Description:** Sản phẩm "Áo Polo Chiết Eo Tay Bồng" (PL03) trên deployed không hiển thị giá, màu sắc, size — tất cả đều rỗng

**Related code:**
- File: `src/product_detail.php`, dòng 28–33
- Query: `SELECT * FROM product_variants WHERE product_id = :id AND is_active = 1`
- Dòng 319: `var allVariants = [];` (rỗng trên deployed)

**Steps to reproduce:**
1. Vào `https://ntkfashion.me/product_detail.php?id=PL03`
2. Quan sát phần giá, màu sắc, kích cỡ

**Expected result:** Hiển thị giá, nút chọn màu/size, tồn kho  
**Actual result:**
- Phần giá trống hoàn toàn: `<span class="detail-sale-price" id="detail-sale-price"></span>`
- Không có nút màu sắc, không có nút size
- Hiển thị "0 sản phẩm có sẵn"
- `allVariants = []` trong JavaScript

**Possible cause:**  
Có hai khả năng:  
1. Cột `is_active` của tất cả variants sản phẩm PL03 trong DB đang là `0` (inactive) — có thể do admin vô tình tắt.  
2. Sản phẩm không có variants được nhập trong DB của môi trường deployed.

**Severity: CRITICAL** — Người dùng không thể thêm sản phẩm vào giỏ hàng khi không có variant nào hiển thị.

---

### Bug 3 — API KEY GEMINI BỊ LỌ (LEAKED) — CHATBOT AI KHÔNG HOẠT ĐỘNG

**Feature:** AI Chatbot tư vấn  
**Description:** API Key Google Gemini bị đánh dấu là "leaked" (bị lộ), chatbot trả lỗi 403

**Related code:**
- File: `src/api_chatbot.php`, dòng 20: `$apiKey = 'AIzaSyBXwQjYyVqnzidwLHyOHA27xcjbtB6hWxI';`
- File: `src/gemini_error_log.txt` ghi nhận lỗi:
```
2026-04-29 14:13:12 - {"error":{"code":403,"message":"Your API key was reported as leaked. Please use another API key.","status":"PERMISSION_DENIED"}}
```

**Steps to reproduce:**
1. Vào trang chủ `https://ntkfashion.me`
2. Click nút chat 💬 góc dưới phải
3. Nhập bất kỳ câu hỏi nào và gửi

**Expected result:** AI trả lời tư vấn thời trang  
**Actual result:** Chatbot trả về thông báo "Dạ nhân viên AI của NTK đang bận chút xíu" hoặc lỗi kết nối

**Possible cause:**  
API key hard-coded thẳng trong file PHP (không dùng `.env`), khi push lên GitHub public, Google tự động phát hiện và vô hiệu hóa key. Đây là **lỗi bảo mật nghiêm trọng** — API key bị expose trong source code.

**Severity: CRITICAL** — Tính năng chatbot hoàn toàn mất chức năng. Cũng là rủi ro bảo mật cao khi expose API key.

---

### Bug 4 — MẬT KHẨU ĐƯỢC MÃ HÓA BẰNG MD5 (BẢO MẬT YẾU)

**Feature:** Đăng ký / Đăng nhập  
**Description:** Mật khẩu người dùng được hash bằng MD5 — thuật toán đã lỗi thời và không an toàn

**Related code:**
- File: `src/controllers/loginController.php`, dòng 7: `$password = md5($_POST['password']);`
- File: `src/controllers/registerController.php`, dòng 17: `$password = md5($_POST['password']);`

**Steps to reproduce:**
1. Đăng ký tài khoản mới
2. Kiểm tra DB — cột `password` lưu chuỗi MD5 32 ký tự

**Expected result:** Mật khẩu được hash bằng `password_hash()` (bcrypt/argon2)  
**Actual result:** Mật khẩu hash bằng MD5 — dễ bị crack qua rainbow table

**Possible cause:**  
Lựa chọn thiết kế sai — MD5 không phù hợp cho hashing mật khẩu vì nó nhanh và không có salt tích hợp.

**Severity: CRITICAL (Bảo mật)** — Trong trường hợp DB bị lộ, toàn bộ mật khẩu người dùng có thể bị phá giải trong thời gian ngắn.

---

### Bug 5 — CREDENTIALS NHẠY CẢM HARD-CODED TRONG SOURCE CODE

**Feature:** Toàn bộ hệ thống  
**Description:** Nhiều credentials bí mật được hard-code trực tiếp trong source code

**Related code:**

| File | Secret bị lộ |
|---|---|
| `src/api_chatbot.php`, dòng 20 | Google Gemini API Key: `AIzaSyBXwQjYyVqnzidwLHyOHA27xcjbtB6hWxI` |
| `src/controllers/process_checkout.php`, dòng 250–252 | PayOS Client ID, API Key, Checksum Key |
| `src/api/webhook_payos.php`, dòng 5 | PayOS Checksum Key |
| `src/controllers/registerController.php`, dòng 47–48 | Gmail SMTP Username + App Password |
| `.env.example`, dòng 7 | Cloudflare Tunnel Token thật (không phải example!) |

**Expected result:** Tất cả credentials được lưu trong biến môi trường (`.env`) và không commit lên Git  
**Actual result:** Credentials hard-coded trong nhiều file PHP

**Possible cause:**  
Thiếu kiến thức về quản lý secrets trong phát triển phần mềm; không thiết lập `.gitignore` đúng cách cho file `.env` thật.

**Severity: CRITICAL (Bảo mật)** — Rủi ro bị tấn công, lạm dụng API, mất chi phí cloud.

---

### Bug 6 — PHÍ SHIP KHÔNG NHẤT QUÁN GIỮA FRONTEND VÀ BACKEND

**Feature:** Thanh toán (Checkout)  
**Description:** Phí vận chuyển được định nghĩa ở hai nơi với hai giá trị khác nhau

**Related code:**
- File: `src/checkout.php`, dòng 53: `$shipping_fee = 30000; // Phí ship mặc định của Bee`
- File: `src/checkout.php`, dòng 62: `$shipping_fee = 35000; // Cố định 35k như yêu cầu`
- File: `src/cart.php`, dòng 219 (JavaScript): `var SHIPPING_FEE = 35000;`
- File: `src/controllers/process_checkout.php`, dòng 95: `$shipping_fee = 35000;`

**Steps to reproduce:**
1. Thêm sản phẩm vào giỏ hàng và vào trang checkout
2. Quan sát phí vận chuyển hiển thị

**Expected result:** Phí ship 35.000đ (theo yêu cầu) được hiển thị nhất quán  
**Actual result:** `$shipping_fee` bị gán **hai lần** trong `checkout.php` — giá trị đầu 30.000đ bị ghi đè bởi 35.000đ. Mặc dù kết quả cuối đúng (35k), nhưng code có "dead code" gây nhầm lẫn và có thể dẫn đến lỗi khi bảo trì.

**Possible cause:**  
Copy-paste code không dọn dẹp — hai phiên bản phí ship khác nhau được hợp nhất nhưng không xóa phần cũ.

**Severity: Medium** — Hiện tại không gây lỗi, nhưng tăng nguy cơ bug trong tương lai.

---

### Bug 7 — TÍNH TOÁN COUPON KHÔNG NHẤT QUÁN GIỮA CÁC ĐIỂM

**Feature:** Mã giảm giá (Coupon)  
**Description:** Logic tính coupon discount khác nhau ở `checkout.php`, `api/check_coupon.php`, và `controllers/process_checkout.php`

**Related code:**
- `checkout.php`, dòng 100: `$calc = $subtotal * (floatval($cp['discount_value']) / 100);` — tính trên `subtotal`
- `api/check_coupon.php`, dòng 53: `$discount_amount = $order_total * ...` — tính trên `order_total` (subtotal, không bao gồm ship)
- `controllers/process_checkout.php`, dòng 121: `$calc = $total_price * ...` — tính trên `total_price` (bao gồm cả ship!)

**Steps to reproduce:**
1. Áp dụng mã giảm giá % (ví dụ: 10%) tại trang checkout
2. So sánh số tiền giảm hiển thị với số tiền giảm thực tế khi đặt hàng

**Expected result:** Số tiền giảm giá nhất quán giữa hiển thị frontend và xử lý backend  
**Actual result:** Backend (`process_checkout.php`) tính coupon dựa trên `total_price` (bao gồm phí ship 35k), trong khi frontend hiển thị dựa trên `subtotal` — gây sai lệch số tiền giảm

**Possible cause:**  
Code được viết bởi nhiều người khác nhau (comments đề cập "Bee", "Khải") mà không có đặc tả chung.

**Severity: High** — Người dùng có thể thấy số tiền giảm giá khác với số thực tế bị trừ.

---

### Bug 8 — REDIRECT SAU ĐĂNG NHẬP CÓ THỂ BỊ LỖI OPEN REDIRECT

**Feature:** Đăng nhập  
**Description:** Tham số `redirect_to` trong form đăng nhập không được kiểm tra đủ, có thể bị khai thác để chuyển hướng sang trang độc hại

**Related code:**
- File: `src/controllers/loginController.php`, dòng 10: `$redirect_to = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : '../index.php';`
- File: `src/views/login.php`, dòng 100–114: Đọc `$_GET['redirect']` hoặc `$_SERVER['HTTP_REFERER']`

**Expected result:** Chỉ cho phép redirect đến các URL nội bộ của website  
**Actual result:** Không có validation whitelist — attacker có thể craft URL như `/views/login.php?redirect=https://evil.com` và sau khi đăng nhập, user bị redirect ra ngoài

**Possible cause:**  
Không filter redirect URL theo domain nội bộ.

**Severity: Medium (Bảo mật)** — Phishing attack vector.

---

### Bug 9 — SEARCHBAR TRONG HEADER KHÔNG CÓ ELEMENT `searchBar`

**Feature:** Chức năng tìm kiếm (Search toggle)  
**Description:** Hàm `toggleSearch()` trong `header.php` tham chiếu đến `document.getElementById("searchBar")` nhưng element này không tồn tại trong header

**Related code:**
- File: `src/includes/header.php`, dòng 188–192:
```javascript
function toggleSearch() {
    const sb = document.getElementById("searchBar");
    if(sb) sb.style.display = (sb.style.display === "none") ? "block" : "none";
}
```

**Steps to reproduce:**
1. Vào bất kỳ trang nào của website (trừ dashboard)
2. Click icon kính lúp 🔍 trên header

**Expected result:** Hiện thanh tìm kiếm dropdown  
**Actual result:** Không có gì xảy ra — `getElementById("searchBar")` trả về `null` vì element không tồn tại trong header chính (chỉ có trong `views/user/dashboard.php`)

**Possible cause:**  
Search bar được implement trong `dashboard.php` nhưng logic toggle được copy sang `header.php` mà không đưa HTML element searchBar vào.

**Severity: High** — Tính năng tìm kiếm trên header bị vô hiệu hoàn toàn với người dùng thông thường (không phải trang dashboard).

---

### Bug 10 — USER_ID ĐƯỢC SINH NGẪU NHIÊN (RAND) — CÓ THỂ TRÙNG LẶP

**Feature:** Đăng ký tài khoản  
**Description:** User ID được tạo bằng `'U' . rand(1000, 9999)` — chỉ có 9000 giá trị khả dụng

**Related code:**
- File: `src/controllers/registerController.php`, dòng 19: `$user_id = 'U' . rand(1000, 9999);`

**Expected result:** User ID duy nhất và không thể đoán được  
**Actual result:** Với ~9000 khả năng, xác suất trùng lặp tăng theo Birthday Paradox — khoảng 50% khi có ~130 users, 99.9% khi có ~4000 users

**Possible cause:**  
Không dùng `uniqid()`, UUID, hoặc AUTO_INCREMENT.

**Severity: High** — Khi có đủ người dùng, đăng ký mới sẽ thất bại do trùng Primary Key.

---

### Bug 11 — LOGIC HỦY ĐƠN HOÀN TIỀN BỊ TÍNH 2 LẦN

**Feature:** Hủy đơn hàng + hoàn tiền ví  
**Description:** Trong `cancel_order.php`, logic tính `refund_amount` cộng cả `final_price` (đã trừ tiền ví) lẫn `wallet_used_amount` — dẫn đến hoàn tiền ví 2 lần

**Related code:**
- File: `src/controllers/cancel_order.php`, dòng 33–37:
```php
$refund_amount = 0;
if ($order['payment_status'] == 1) { 
    $refund_amount += floatval($order['final_price']); // final_price đã trừ wallet rồi
}
$refund_amount += floatval($order['wallet_used_amount']); // + wallet nữa → sai!
```

**Steps to reproduce:**
1. Tạo đơn hàng sử dụng cả ví nội bộ lẫn PayOS
2. Thanh toán thành công (payment_status = 1)
3. Hủy đơn hàng

**Expected result:** Hoàn lại đúng `final_price + wallet_used_amount` cho người dùng  
**Actual result:** Hoàn lại `final_price + wallet_used_amount + wallet_used_amount` — hoàn tiền ví bị tính 2 lần (vì `final_price` = `total - coupon - wallet`, nhưng code lại cộng thêm `wallet_used_amount`)

**Possible cause:**  
Hiểu nhầm logic `final_price` — `final_price` đã là số tiền sau khi trừ ví, nên không cần cộng thêm `wallet_used_amount`.

**Severity: High** — Người dùng nhận được tiền hoàn nhiều hơn thực tế → thiệt hại tài chính cho shop.

---

### Bug 12 — `<main>` TAG BỊ LỒNG ĐÔI TRONG PRODUCT_DETAIL.PHP

**Feature:** Trang chi tiết sản phẩm  
**Description:** `product_detail.php` tạo ra 2 thẻ `<main>` lồng nhau

**Related code:**
- File: `src/includes/header.php`, dòng 160: `<main class="main-content">`
- File: `src/product_detail.php`, dòng 67: `<main class="container">`

**Steps to reproduce:**
1. Vào `https://ntkfashion.me/product_detail.php?id=T10`
2. Kiểm tra HTML source code

**Expected result:** Chỉ có 1 thẻ `<main>` theo chuẩn HTML5  
**Actual result:** 2 thẻ `<main>` lồng nhau — vi phạm chuẩn HTML5 (mỗi trang chỉ được có 1 `<main>`)

**Possible cause:**  
`header.php` mở thẻ `<main>` nhưng `product_detail.php` tự mở thêm thẻ `<main>` nữa.

**Severity: Low (SEO/Accessibility)** — Gây ảnh hưởng đến SEO và accessibility, nhưng không làm vỡ giao diện.

---

### Bug 13 — `checkout.php` CÓ DIV LỒNG TRÙNG

**Feature:** Trang thanh toán  
**Description:** Trang checkout có 2 thẻ mở `.checkout-page` liên tiếp không đóng đúng

**Related code:**
- File: `src/checkout.php`, dòng 370–371:
```html
<div class="checkout-page">
    <div class="checkout-page">  <!-- Lồng trùng! -->
```

**Possible cause:**  
Lỗi copy-paste khi ghép code.

**Severity: Low** — Có thể gây lỗi CSS layout.

---

### Bug 14 — `update_price_and_stock` THAM CHIẾU `stock-info` KHÔNG TỒN TẠI

**Feature:** Trang chi tiết sản phẩm  
**Description:** Hàm JavaScript `updatePriceAndStock()` gọi `document.getElementById('stock-info')` nhưng element trong HTML dùng class `stock-info` không phải id

**Related code:**
- File: `src/product_detail.php`, dòng 265–266 (JS): `document.getElementById('stock-info').textContent = ...`
- File: `src/product_detail.php`, dòng 161 (HTML): `<span class="stock-info">...</span>` — dùng class, không có id!

**Expected result:** Thông tin tồn kho cập nhật khi chọn màu/size  
**Actual result:** `getElementById('stock-info')` trả về `null` → JavaScript lỗi → không cập nhật được tồn kho

**Possible cause:**  
Không nhất quán giữa selector JS và HTML attribute.

**Severity: High** — Người dùng không thấy thông tin tồn kho khi đổi màu/size sản phẩm.

---

## 5. VẤN ĐỀ ĐẶC THÙ TRIỂN KHAI (DEPLOYMENT-SPECIFIC ISSUES)

| STT | Vấn đề | Nguyên nhân |
|---|---|---|
| 1 | **AWS Learner Lab tự tắt sau 4 tiếng** | Giới hạn của môi trường học tập — không phải production |
| 2 | **Không có `.env` thật trên server** — Credentials lấy từ `docker-compose.yml` trực tiếp | Không tách biệt config và code |
| 3 | **Cloudflare Tunnel Token trong `.env.example` là token thật** | Nên là placeholder, không phải giá trị thật |
| 4 | **Không có reverse proxy cho HTTPS termination** — Cloudflare Tunnel xử lý luôn | Phụ thuộc 100% vào Cloudflare |
| 5 | **MySQL không có biến môi trường thêm** — `docker-compose.yml` thiếu service `mysql`, sử dụng external DB | Không có container MySQL trong compose chính |
| 6 | **Không có volume mount cho MySQL data** trong `docker-compose.yml` | Nếu container restart, data có thể mất |
| 7 | **CI/CD runner trên EC2** — nếu máy EC2 tắt, CI/CD pipeline không chạy được | Phụ thuộc AWS session |

---

## 6. TÍNH NĂNG CÓ TRONG CODE NHƯNG KHÔNG HOẠT ĐỘNG SAU DEPLOY

| STT | Tính năng | Trạng thái | Lý do |
|---|---|---|---|
| 1 | AI Chatbot Gemini | ❌ Không hoạt động | API Key bị lộ và bị Google revoke |
| 2 | Tìm kiếm qua header icon | ❌ Không hoạt động | Element `searchBar` không có trong HTML của các trang thường |
| 3 | Hiển thị tồn kho theo màu/size | ❌ Không hoạt động | `getElementById('stock-info')` → null |
| 4 | Gửi email OTP | ⚠️ Chưa xác nhận | Phụ thuộc vào SMTP Gmail có còn hoạt động không |
| 5 | "Quên mật khẩu" | ❌ Không implement | Link `<a href="#">Quên mật khẩu?</a>` không dẫn đến đâu |
| 6 | "Mua lại" sau đơn hoàn thành | ❌ Không implement | Button chỉ có HTML, không có action |
| 7 | Tìm kiếm đơn hàng trong dashboard | ❌ Không implement | Input tìm kiếm có nhưng không có event handler |
| 8 | Social links (Facebook, Instagram...) | ❌ Không implement | Tất cả link `href="#"` |
| 9 | Mã GPKD thật | ⚠️ Placeholder | Footer hiển thị `GPKD: 0123456789` — số giả |

---

## 7. PHÂN TÍCH NGUYÊN NHÂN GỐC RỄ

### 7.1 Quản lý Credentials (Vấn đề BẢO MẬT quan trọng nhất)
**Nguyên nhân:** Nhóm phát triển chưa có kinh nghiệm với **Secret Management**. Tất cả API keys (Gemini, PayOS, SMTP Gmail, Cloudflare) đều được đặt trực tiếp trong file PHP. Khi push lên GitHub, Google tự động quét và revoke Gemini API key.

**Hậu quả:**
- Chatbot AI bị vô hiệu hóa hoàn toàn
- PayOS keys bị lộ (bất kỳ ai có source code có thể lạm dụng)
- Gmail credentials bị lộ (có thể bị spam)

### 7.2 Database Schema / Dữ liệu
**Nguyên nhân:** Sản phẩm PL03 (Best Seller đứng đầu) không có variants `is_active = 1` trên môi trường deployed. Nguyên nhân có thể là:
- Import SQL chưa đầy đủ
- Admin vô tình deactivate variants
- Schema version trên deployed khác với local

**Hậu quả:** Giá hiển thị 0đ, không thể mua sản phẩm.

### 7.3 Code chất lượng
**Nguyên nhân:** Dự án được phát triển nhóm (đề cập "Bee", "Khải") mà không có:
- Code review
- Consistent coding standards  
- Unit tests
- Centralized configuration management

**Hậu quả:** Logic không nhất quán (phí ship, coupon), dead code, HTML không chuẩn.

### 7.4 Kiến trúc bảo mật
**Nguyên nhân:** Thiếu kiến thức bảo mật cơ bản:
- MD5 cho password hashing
- Không có CSRF protection trong forms
- Open redirect vulnerability
- SQL credentials không được escape đúng trong một số điểm

---

## 8. ĐỀ XUẤT SỬA LỖI CỤ THỂ

### 8.1 Khẩn cấp — Bảo mật (Cần làm ngay)

**[a] Thay thế Gemini API Key mới và đưa vào biến môi trường:**
```php
// src/api_chatbot.php — TRƯỚC (SAI)
$apiKey = 'AIzaSyBXwQjYyVqnzidwLHyOHA27xcjbtB6hWxI';

// SAU (ĐÚNG) — Thêm vào .env
GEMINI_API_KEY=your_new_key_here
// Trong code:
$apiKey = getenv('GEMINI_API_KEY') ?: '';
```

**[b] Đưa PayOS credentials vào `.env`:**
```php
// process_checkout.php — SAU
$PAYOS_CLIENT_ID = getenv('PAYOS_CLIENT_ID');
$PAYOS_API_KEY = getenv('PAYOS_API_KEY');
$PAYOS_CHECKSUM_KEY = getenv('PAYOS_CHECKSUM_KEY');
```

**[c] Đưa Gmail SMTP vào `.env`:**
```php
// registerController.php — SAU
$mail->Username = getenv('SMTP_USER');
$mail->Password = getenv('SMTP_PASS');
```

**[d] Thay MD5 bằng `password_hash()`:**
```php
// registerController.php — SAU
$password = password_hash($_POST['password'], PASSWORD_BCRYPT);

// loginController.php — SAU
$user = ...; // lấy user theo email trước
if (password_verify($_POST['password'], $user['password'])) { ... }
```

### 8.2 Quan trọng — Logic

**[e] Sửa SQL giá sản phẩm trong index.php dùng hàm tổng hợp:**
```sql
-- index.php — SAU
SELECT p.*, MIN(v.original_price) as original_price, MIN(v.sale_price) as sale_price 
FROM products p 
LEFT JOIN product_variants v ON p.product_id = v.product_id 
WHERE p.status = 1
GROUP BY p.product_id
ORDER BY p.product_id DESC 
LIMIT 4
```

**[f] Sửa logic hoàn tiền trong cancel_order.php:**
```php
// cancel_order.php — SAU (ĐÚNG)
$refund_amount = 0;
if ($order['payment_status'] == 1) {
    // Hoàn lại toàn bộ final_price (đã trừ wallet rồi)
    $refund_amount = floatval($order['final_price']);
}
// Cộng thêm tiền ví đã dùng (vì final_price không bao gồm phần ví đã trừ)
if (floatval($order['wallet_used_amount']) > 0 && $order['payment_status'] != 1) {
    // Chỉ hoàn ví nếu không đã hoàn qua payment (để tránh đếm 2 lần)
    $refund_amount += floatval($order['wallet_used_amount']);
}
```

**[g] Sửa lỗi getElementById('stock-info') trong product_detail.php:**
```html
<!-- HTML — Thêm id -->
<span class="stock-info" id="stock-info"><?php echo $first_v['stock'] ?? 0; ?> sản phẩm có sẵn</span>
```

**[h] Thêm search bar vào header.php:**
```html
<!-- Thêm vào header.php sau </nav> -->
<div id="searchBar" style="display:none; position:absolute; top:100%; left:0; width:100%; background:#fff; padding:20px; box-shadow:0 4px 10px rgba(0,0,0,0.1); z-index:999;">
    <form action="search.php" method="GET" style="display:flex; max-width:600px; margin:0 auto;">
        <input type="text" name="q" placeholder="Tìm kiếm sản phẩm..." style="flex:1; padding:10px; border:1px solid #ddd; border-radius:4px 0 0 4px; outline:none;">
        <button type="submit" style="padding:10px 20px; background:#2f1c00; color:#fff; border:none; border-radius:0 4px 4px 0; cursor:pointer;">Tìm</button>
    </form>
</div>
```

**[i] Sửa User ID generation:**
```php
// registerController.php — SAU
$user_id = 'U' . strtoupper(substr(uniqid('', true), -8));
// Hoặc dùng UUID nếu muốn chuẩn hơn
```

### 8.3 Cải thiện Code Quality

**[j] Xóa phí ship trùng trong checkout.php** (dòng 53–60)

**[k] Sửa HTML lồng `<main>` trong product_detail.php** — bỏ thẻ `<main class="container">` thay bằng `<div class="container">`

**[l] Implement "Quên mật khẩu" thực sự** hoặc xóa link nếu chưa làm

---

## 9. ĐÁNH GIÁ TỔNG THỂ

### Bảng chấm điểm hệ thống:

| Tiêu chí | Điểm | Nhận xét |
|---|---|---|
| Tính năng core (thêm giỏ, checkout, COD) | 6/10 | Hoạt động nhưng có bugs logic |
| Bảo mật | 2/10 | MD5 password, API key lộ, open redirect |
| Chất lượng code | 4/10 | Dead code, không nhất quán, thiếu tests |
| UX / Giao diện | 7/10 | Thiết kế ổn, nhưng một số tính năng UI bị broken |
| Deploy / DevOps | 6/10 | Docker hoạt động, CI/CD có nhưng phụ thuộc AWS Learner Lab |
| Tính năng nâng cao (AI, PayOS) | 3/10 | Chatbot broken, PayOS keys lộ |
| **Tổng thể** | **4.7/10** | |

### Kết luận:

> ⚠️ **HỆ THỐNG CHƯA SẴN SÀNG CHO PRODUCTION THỰC TẾ**

Website NTK Fashion hoạt động được ở mức cơ bản (xem sản phẩm, đăng nhập, thêm giỏ, checkout COD) nhưng có **nhiều lỗi nghiêm trọng về bảo mật** cần phải khắc phục trước khi đưa vào sử dụng thực tế với khách hàng và tiền thật:

1. **Ưu tiên 1 (Khẩn cấp):** Thay toàn bộ API keys bị lộ, chuyển sang biến môi trường
2. **Ưu tiên 2 (Quan trọng):** Sửa MD5 password sang bcrypt
3. **Ưu tiên 3 (Quan trọng):** Sửa bugs hiển thị giá 0đ và getElementById null
4. **Ưu tiên 4 (Cần thiết):** Sửa logic hoàn tiền khi hủy đơn
5. **Ưu tiên 5 (Cải thiện):** Clean up dead code, implement các tính năng còn thiếu

---

*Báo cáo được tạo ngày 21/05/2026 bởi hệ thống kiểm thử tự động.*
