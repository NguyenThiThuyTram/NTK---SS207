<style>
    /* CSS dành riêng cho phần Cài đặt thông báo */
    .settings-list {
        display: flex;
        flex-direction: column;
    }
    .setting-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 0;
        border-bottom: 1px solid var(--border-color);
    }
    .setting-item:last-child {
        border-bottom: none; /* Dòng cuối không cần kẻ viền dưới */
    }
    .setting-info h4 {
        font-size: 15px;
        font-weight: 500;
        color: var(--text-main);
        margin-bottom: 3px;
    }
    .setting-info p {
        font-size: 13px;
        color: var(--text-muted);
        margin: 0;
    }
    /* Style cho checkbox to và rõ hơn */
    .setting-checkbox {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: var(--primary); /* Đổi màu checkbox khi tick sang màu nâu của theme */
    }
</style>

<div class="content-header">
    <h2>Cài đặt thông báo</h2>
</div>

<div class="settings-list">
    <div class="setting-item">
        <div class="setting-info">
            <h4>Cập nhật đơn hàng</h4>
            <p>Nhận thông báo về trạng thái đơn hàng</p>
        </div>
        <input type="checkbox" class="setting-checkbox" checked>
    </div>

    <div class="setting-item">
        <div class="setting-info">
            <h4>Khuyến mãi</h4>
            <p>Nhận thông báo về các chương trình khuyến mãi</p>
        </div>
        <input type="checkbox" class="setting-checkbox" checked>
    </div>

    <div class="setting-item">
        <div class="setting-info">
            <h4>Sản phẩm mới</h4>
            <p>Nhận thông báo về sản phẩm mới</p>
        </div>
        <input type="checkbox" class="setting-checkbox">
    </div>
</div>