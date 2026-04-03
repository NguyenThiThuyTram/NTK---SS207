//TRANG CHỦ - HOME
// Danh sách sản phẩm Best Sellers
const bestProducts = [
    { name: "Áo Thun Baby Tee", price: "179.000đ", img: "https://down-vn.img.susercontent.com/file/vn-11134207-81ztc-mmiex4xgmw3od4@resize_w900_nl.webp", star: 4.8, sold: 234 },
    { name: "Áo Sơ Mi Babydoll", price: "279.000đ", img: "https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mcswuusxcjvx85@resize_w900_nl.webp", star: 4.9, sold: 189 },
    { name: "Quần Jeans Ống Rộng", price: "239.000đ", img: "https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mh9x7bhmtb7vea@resize_w900_nl.webp", star: 5.0, sold: 112 },
    { name: "Áo Khoác Denim", price: "459.000đ", img: "https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mgvn5j1lf1u00c.webp", star: 4.7, sold: 456 },
    { name: "Áo Jeans Dáng Lửng", price: "229.000đ", img: "https://down-vn.img.susercontent.com/file/vn-11134207-7ras8-mbc529lr4zbsf1@resize_w900_nl.webp", star: 4.8, sold: 356 },
    { name: "Chân Váy Dài Xếp Ly", price: "299.000đ", img: "https://down-vn.img.susercontent.com/file/vn-11134207-81ztc-mmk19huu99u711@resize_w900_nl.webp", star: 4.9, sold: 486 }
];

// Danh sách sản phẩm New Arrivals (Dựa trên ảnh Bee gửi)
const newProducts = [
    { name: "Áo Sơ Mi Cổ Sen", price: "189.000đ", img: "https://down-vn.img.susercontent.com/file/vn-11134207-820l4-miwu7ska4tfr42@resize_w900_nl.webp", star: 4.8, sold: 234 },
    { name: "Áo Khoác Da Biker", price: "429.000đ", img: "https://down-vn.img.susercontent.com/file/vn-11134207-81ztc-mm8depmmwd1h56@resize_w900_nl.webp", star: 4.9, sold: 189 },
    { name: "Quần Jeans Đinh Tán", price: "319.000đ", img: "https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mi6p0p4dknbb86@resize_w900_nl.webp", star: 4.7, sold: 155 },
    { name: "Quần Short Kaki", price: "199.000đ", img: "https://down-vn.img.susercontent.com/file/vn-11134207-820l4-mf7s9gg775sdb2@resize_w900_nl.webp", star: 5.0, sold: 312 }
];

// Hàm Render chung
function renderProducts(id, data) {
    const area = document.getElementById(id);
    if (!area) return;
    area.innerHTML = data.map(p => `
        <div class="product-card">
            <img src="${p.img}">
            <div class="product-info">
                <h4>${p.name}</h4>
                <div class="product-meta"><span>★</span>${p.star} • Đã bán ${p.sold}</div>
                <div class="price">${p.price}</div>
            </div>
        </div>
    `).join("");
}

function scrollToProduct(index) {
    const slider = document.getElementById('bestSeller');
    const dots = document.querySelectorAll('#bestSellerDots .dot');
    const cardWidth = slider.querySelector('.product-card').offsetWidth + 20;
    slider.scrollLeft = index * (cardWidth * 2);
    dots.forEach(dot => dot.classList.remove('active'));
    dots[index].classList.add('active');
}

// Chạy hàm cho cả 2 mục
renderProducts("bestSeller", bestProducts);
renderProducts("newArrivals", newProducts);