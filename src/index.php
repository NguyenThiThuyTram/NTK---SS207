<?php
// 1. Phải kết nối Database đầu tiên để có biến $conn
require_once 'config/database.php';

// 2. Gọi header
require_once 'includes/header.php';

// --- PHẦN LOGIC LẤY DỮ LIỆU ---

// Lấy 4 sản phẩm mới nhất (New Arrivals)
$sql_new = "SELECT p.*, v.original_price, v.sale_price 
            FROM products p 
            LEFT JOIN product_variants v ON p.product_id = v.product_id 
            WHERE p.status = 1
            GROUP BY p.product_id
            ORDER BY p.product_id DESC 
            LIMIT 4";
$stmt_new = $conn->prepare($sql_new);
$stmt_new->execute();
$new_arrivals = $stmt_new->fetchAll(PDO::FETCH_ASSOC);

// Lấy 4 sản phẩm bán chạy nhất (Best Sellers)
$sql_best = "SELECT p.*, v.original_price, v.sale_price 
             FROM products p 
             LEFT JOIN product_variants v ON p.product_id = v.product_id 
             WHERE p.status = 1
             GROUP BY p.product_id
             ORDER BY p.sold_count DESC 
             LIMIT 4";
$stmt_best = $conn->prepare($sql_best);
$stmt_best->execute();
$best_sellers = $stmt_best->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="banner">
    <div class="banner-content" style="text-align: center; padding: 100px 0;">
        </div>
</div>

<div class="section main-section-card">
    <div class="section-header">
        <p>MỚI RA MẮT</p>
        <h2>New Arrivals</h2>
    </div>
    <div class="product-grid">
        <?php foreach ($new_arrivals as $item): ?>
            <div class="product-card">
                <a href="product_detail.php?id=<?php echo $item['product_id']; ?>">
                    <div class="img-wrapper">
                        <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>">
                    </div>
                    <div class="product-info">
                        <h3 class="product-name"><?php echo $item['name']; ?></h3>
                        <div class="product-meta">
                            <span class="product-stars">★★★★★</span>
                            <span class="product-sold">| Đã bán <?php echo $item['sold_count']; ?></span>
                        </div>
                        <p class="price"><?php echo number_format($item['original_price'], 0, ',', '.'); ?>đ</p>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="section bg-be main-section-card">
    <div class="section-header">
        <p>ĐƯỢC YÊU THÍCH NHẤT</p>
        <h2>Best Sellers</h2>
    </div>
    <div class="product-grid">
        <?php foreach ($best_sellers as $item): ?>
            <div class="product-card">
                <a href="product_detail.php?id=<?php echo $item['product_id']; ?>">
                    <div class="img-wrapper">
                        <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>">
                        <span class="badge-hot" style="position:absolute; top:10px; right:10px; background: #a6825c; color:#fff; padding:2px 10px; font-size:12px;">HOT</span>
                    </div>
                    <div class="product-info">
                        <h3 class="product-name"><?php echo $item['name']; ?></h3>
                        <div class="product-meta">
                            <span class="product-stars">★★★★★</span>
                            <span class="product-sold">| Đã bán <?php echo $item['sold_count']; ?></span>
                        </div>
                        <p class="price"><?php echo number_format($item['original_price'], 0, ',', '.'); ?>đ</p>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="section main-section-card">
    <div class="section-header">
        <p>DANH MỤC</p>
        <h2>Shop by Category</h2>
    </div>
    <div class="category-grid">
        <a href="product.php?cat=CAT01" class="category-card">
            <img src="https://down-vn.img.susercontent.com/file/vn-11134207-7ra0g-m7ne96vcjmiu46" alt="Áo thun">
            <div class="category-overlay">Áo thun</div>
        </a>
        <a href="product.php?cat=CAT02" class="category-card">
            <img src="https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mitmevxbal1j0b" alt="Áo khoác">
            <div class="category-overlay">Áo khoác</div>
        </a>
        <a href="product.php?cat=CAT03" class="category-card">
            <img src="https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mg6a54vwzvv002" alt="Hoodie">
            <div class="category-overlay">Hoodie</div>
        </a>
        <a href="product.php?cat=CAT04" class="category-card">
            <img src="https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mfxc3xhxoop9ed" alt="Quần">
            <div class="category-overlay">Quần</div>
        </a>
        <a href="product.php?cat=CAT05" class="category-card">
            <img src="https://down-vn.img.susercontent.com/file/vn-11134207-820l4-me8igxycndhj04" alt="Áo sơ mi">
            <div class="category-overlay">Áo sơ mi</div>
        </a>
    </div>
</div>

<style>
    /* ============================================================
        CSS FIX ĐỒNG BỘ DARKMODE TRANG CHỦ NTK FASHION
    ============================================================ */
    .banner {
        background-color: #f4f4f4; /* Nền mặc định Lightmode */
        transition: background-color 0.3s ease;
    }

    /* Ép đồng bộ nền tối sâu tuyệt đối khi bật Dark Mode */
    body.dark-mode, 
    body.dark-mode .main-content, 
    body.dark-mode .banner, 
    body.dark-mode .main-section-card {
        background-color: #121212 !important;
    }

    body.dark-mode .bg-be {
        background-color: #1a1a1a !important; /* Biến vùng Best Seller thành xám đen sang trọng */
    }

    body.dark-mode .section-header h2, 
    body.dark-mode .section-header p {
        color: #ffffff !important;
    }

    /* Các css phụ cho Chatbox */
    #ntk-chat-toggle { 
        position: fixed; bottom: 20px; right: 20px; 
        background: #2f1c00; 
        color: #ffffff; 
        border: none; border-radius: 50%; 
        width: 60px; height: 60px; 
        font-size: 24px; cursor: pointer; 
        box-shadow: 0 4px 10px rgba(0,0,0,0.2); 
        z-index: 9998; 
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
    }
    
    #ntk-chatbox { 
        position: fixed; bottom: 90px; right: 20px; 
        width: 320px; 
        background: #ffffff; 
        border: 1px solid #e5e5e5; 
        border-radius: 12px; 
        box-shadow: 0 5px 20px rgba(0,0,0,0.1); 
        display: none; flex-direction: column; 
        z-index: 9999; overflow: hidden; 
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; 
        color: #111111; 
    }
    
    body.dark-mode #ntk-chatbox, 
    body.dark-mode #ntk-chat-messages,
    body.dark-mode #ntk-chat-input-area {
        background: #1e1e1e !important;
        border-color: #333 !important;
        color: #fff !important;
    }
    
    #ntk-chat-header { 
        background: #2f1c00; 
        color: #ffffff; 
        padding: 15px; 
        font-weight: bold; cursor: pointer; 
        display: flex; justify-content: space-between; 
        border-bottom: 1px solid #e5e5e5;
    }
    
    #ntk-chat-messages { 
        height: 320px; overflow-y: auto; 
        padding: 15px; 
        background: #ffffff; 
        display: flex; flex-direction: column; gap: 12px; 
    }
    
    #ntk-chat-input-area { 
        display: flex; 
        border-top: 1px solid #e5e5e5; 
        padding: 12px; 
        background: #ffffff; 
    }
    
    #ntk-chat-input { 
        flex: 1; padding: 10px 15px; 
        border: 1px solid #e5e5e5; 
        border-radius: 20px; outline: none; 
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        color: #111111;
        background-color: #ffffff;
    }

    body.dark-mode #ntk-chat-input {
        background-color: #252525 !important;
        border-color: #444 !important;
        color: #fff !important;
    }
    
    #ntk-send-btn { 
        background: #2f1c00; 
        color: #ffffff; border: none; 
        padding: 8px 18px; margin-left: 8px; 
        border-radius: 20px; cursor: pointer; 
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        font-weight: bold;
    }
    
    .msg-user { 
        background: #f5f1eb; 
        color: #111111; 
        padding: 10px 14px; 
        border-radius: 15px 15px 0 15px; 
        align-self: flex-end; max-width: 80%; 
        font-size: 14px; line-height: 1.4;
    }
    body.dark-mode .msg-user {
        background: #332211 !important;
        color: #fff !important;
    }
    
    .msg-bot { 
        background: #ffffff; 
        border: 1px solid #e5e5e5; 
        color: #111111; 
        padding: 10px 14px; 
        border-radius: 15px 15px 15px 0; 
        align-self: flex-start; max-width: 80%; 
        font-size: 14px; line-height: 1.4;
    }
    body.dark-mode .msg-bot {
        background: #252525 !important;
        border-color: #444 !important;
        color: #eee !important;
    }
</style>

<button id="ntk-chat-toggle" onclick="toggleChat()">💬</button>

<div id="ntk-chatbox">
    <div id="ntk-chat-header" onclick="toggleChat()">
        <span>Nhân viên AI Tư Vấn</span>
        <span>✖</span>
    </div>
    <div id="ntk-chat-messages">
        <div class="msg-bot">Dạ chào anh/chị, em là nhân viên AI của shop NTK. Mình đang tìm đồ như thế nào để em tư vấn cho ạ? </div>
    </div>
    <div id="ntk-chat-input-area">
        <input type="text" id="ntk-chat-input" placeholder="Hãy nhập câu hỏi của bạn nhé..." onkeypress="if(event.key==='Enter') sendMessage()">
        <button id="ntk-send-btn" onclick="sendMessage()">Gửi</button>
    </div>
</div>

<script>
    function toggleChat() {
        const chatbox = document.getElementById('ntk-chatbox');
        chatbox.style.display = (chatbox.style.display === 'flex') ? 'none' : 'flex';
    }

    async function sendMessage() {
        const input = document.getElementById('ntk-chat-input');
        const msgText = input.value.trim();
        if (!msgText) return;

        const messagesDiv = document.getElementById('ntk-chat-messages');
        
        messagesDiv.innerHTML += `<div class="msg-user">${msgText}</div>`;
        input.value = '';
        messagesDiv.scrollTop = messagesDiv.scrollHeight;

        const typingId = "typing-" + Date.now();
        messagesDiv.innerHTML += `<div class="msg-bot" id="${typingId}">Nhân viên AI đang tìm câu trả lời...</div>`;
        messagesDiv.scrollTop = messagesDiv.scrollHeight;

        try {
            const response = await fetch('api_chatbot.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: msgText })
            });
            const data = await response.json();
            
            document.getElementById(typingId).remove();
            messagesDiv.innerHTML += `<div class="msg-bot">${data.reply.replace(/\n/g, '<br>')}</div>`;
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        } catch (error) {
            document.getElementById(typingId).innerHTML = "Lỗi kết nối rồi đại ca ơi!";
        }
    }
</script>

<?php
require_once 'includes/footer.php';
?>
