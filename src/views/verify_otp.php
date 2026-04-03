<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận OTP - NTK</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            /* Dùng font chữ không chân hiện đại giống các trang login/register */
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f3f4f6; /* Nền xám cực nhạt để làm nổi bật khung trắng */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .otp-container {
            background-color: #ffffff;
            width: 100%;
            max-width: 420px;
            padding: 40px 30px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05); /* Đổ bóng sang trọng */
            text-align: center;
        }
        .otp-container h2 {
            color: #111827;
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .otp-container p {
            color: #6b7280;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .otp-container p b {
            color: #111827;
            font-weight: 600;
        }
        .otp-inputs {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            gap: 15px;
        }
        .otp-inputs input {
            width: 60px;
            height: 60px;
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            border: 1.5px solid #d1d5db;
            border-radius: 12px;
            background-color: #fff;
            color: #111827;
            transition: all 0.2s ease;
        }
        /* Hiệu ứng khi click vào ô nhập */
        .otp-inputs input:focus {
            border-color: #000000;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
        }
        .btn-verify {
            width: 100%;
            background-color: #000000;
            color: #ffffff;
            font-size: 16px;
            font-weight: 600;
            padding: 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-bottom: 25px;
        }
        .btn-verify:hover {
            background-color: #333333;
        }
        .resend-text {
            font-size: 14px;
            color: #6b7280;
        }
        .resend-text a {
            color: #000000;
            font-weight: 600;
            text-decoration: none;
        }
        .resend-text a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="otp-container">
        <h2>Nhập mã xác nhận</h2>
        <p>Mã xác minh của bạn sẽ được gửi qua email<br><b><?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?></b></p>
        
        <form action="../controllers/verifyController.php" method="POST">
            <input type="hidden" name="email" value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>">
            
            <div class="otp-inputs">
                <input type="text" name="otp[]" maxlength="1" required autofocus autocomplete="off">
                <input type="text" name="otp[]" maxlength="1" required autocomplete="off">
                <input type="text" name="otp[]" maxlength="1" required autocomplete="off">
                <input type="text" name="otp[]" maxlength="1" required autocomplete="off">
            </div>
            
            <button type="submit" class="btn-verify">Xác nhận</button>
        </form>

        <div class="resend-text">
            Chưa nhận được mã? <a href="#" onclick="alert('Mã OTP đã được gửi lại vào email của bạn!'); return false;">Gửi lại</a>
        </div>
    </div>

    <script>
        const inputs = document.querySelectorAll('.otp-inputs input');
        
        inputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                // Nếu người dùng dán (paste) 4 số vào 1 ô, tự động chia ra 4 ô
                if(e.target.value.length > 1) {
                    let pastedData = e.target.value.split('').slice(0, 4);
                    inputs.forEach((inp, i) => {
                        if(pastedData[i]) inp.value = pastedData[i];
                    });
                    inputs[3].focus(); // Nhảy tới ô cuối
                    return;
                }
                
                // Gõ 1 số xong tự nhảy sang ô tiếp theo
                if (input.value.length === 1 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });

            // Bấm Backspace (Xóa) tự lùi về ô trước đó
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && input.value === '' && index > 0) {
                    inputs[index - 1].focus();
                }
            });
        });
    </script>

</body>
</html>
