# TÀI LIỆU BÀN GIAO DỰ ÁN NTK FASHION 🚀

Tài liệu này hướng dẫn chi tiết cách tiếp nhận, vận hành và quản lý dự án **NTK Fashion** đang được triển khai (deploy) trên hệ thống máy chủ **AWS Learner Lab**.

---

## 1. THÔNG TIN TÀI KHOẢN (Cần lưu trữ kỹ)

Người bàn giao cần cung cấp cho bạn các thông tin sau:
1. **Tài khoản AWS Learner Lab:** (Email & Mật khẩu đăng nhập vào portal học tập)
2. **Tài khoản Cloudflare:** (Quản lý tên miền `ntkfashion.me` và Đường hầm kết nối)
3. **Tài khoản Github:** (Chứa Source Code dự án)
4. **Tài khoản Admin Website:** (Username/Password để đăng nhập vào trang quản trị web)

---

## 2. ĐẶC ĐIỂM QUAN TRỌNG CỦA AWS LEARNER LAB (BẮT BUỘC ĐỌC)

⚠️ **LƯU Ý CỰC KỲ QUAN TRỌNG:**
Khác với máy chủ doanh nghiệp chạy 24/24, AWS Learner Lab là môi trường sinh viên nên **máy chủ sẽ TỰ ĐỘNG TẮT sau mỗi phiên làm việc (khoảng 4 tiếng)**. 
👉 Do đó, nếu bạn thấy website bỗng nhiên báo lỗi **521 Web server is down**, đừng hoảng hốt! Đó là do máy chủ đã tự tắt, bạn chỉ cần vào bật lại theo Hướng dẫn ở Mục 3 dưới đây.

---

## 3. CÁCH BẬT LẠI WEBSITE KHI BỊ TẮT (HOẶC KHI CẦN CHẤM ĐIỂM/DEMO)

Mỗi khi muốn mở website lên cho giáo viên chấm hoặc khách xem, bạn làm đúng theo thứ tự sau:

**Bước 1: Khởi động máy chủ ảo (EC2)**
1. Đăng nhập vào trang web **AWS Learner Lab**.
2. Bấm vào nút **Start Lab** (đợi có chữ Ready / dấu tích xanh).
3. Bấm vào chữ **AWS (màu xanh lá cây/xanh dương)** để nhảy sang trang quản trị chính thức của AWS Console.

**Bước 2: Mở cửa sổ dòng lệnh (Terminal)**
1. Trên giao diện AWS Console, gõ vào thanh tìm kiếm chữ **EC2** và chọn nó.
2. Bấm vào mục **Instances (running)** để xem danh sách máy chủ.
3. Tích chọn vào con máy chủ của bạn -> Bấm nút **Connect** (Kết nối) ở góc trên bên phải.
4. Ở màn hình tiếp theo, cuộn xuống bấm nút **Connect** màu cam để mở màn hình đen (Terminal).

**Bước 3: Gõ lệnh bật Website**
Trên màn hình đen, bạn gõ lần lượt 2 lệnh sau (nhớ ấn Enter sau mỗi dòng):

```bash
cd NTK---SS207
docker compose up -d
```

🎉 **Xong!** Bạn đợi khoảng 5-10 giây để Docker khởi động. Sau đó bạn (và tất cả mọi người) có thể truy cập bình thường vào:
*   Trang khách hàng: `https://ntkfashion.me`
*   Trang quản trị: `https://admin.ntkfashion.me` (hoặc `https://ntkfashion.me/admin`)

---

## 4. CÁCH CẬP NHẬT GIAO DIỆN / TÍNH NĂNG MỚI (UPDATE CODE)

Nếu đội Dev (người code) vừa sửa lỗi trên máy tính cá nhân và đã tải code mới lên Github, bạn là người vận hành chỉ cần làm thao tác sau trên máy chủ (Terminal) để cập nhật cho website thực tế:

```bash
# 1. Di chuyển vào thư mục dự án
cd NTK---SS207

# 2. Tải code mới nhất từ Github về
git pull origin main

# 3. Khởi động lại hệ thống để nhận code mới
docker compose up -d --build
```

---

## 5. BẮT BỆNH CƠ BẢN (TROUBLESHOOTING)

Nếu website chạy không đúng ý muốn, bạn dùng các câu lệnh này để xem "nhật ký" (Log) hoạt động của các phòng ban:

*   **Xem toàn bộ các bộ phận có đang bật (Up) không:**
    `docker compose ps`

*   **Xem lỗi của code PHP (Nơi xử lý giỏ hàng, kết nối Database):**
    `docker compose logs --tail 50 php`

*   **Xem lỗi của Nginx (Nơi đón khách):**
    `docker compose logs --tail 50 nginx`

*   **Tắt hoàn toàn hệ thống để dọn dẹp:**
    `docker compose down`
