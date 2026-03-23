<?php 
require_once 'config.php'; 
include 'header.php'; 
?>

<style>
    /* CSS Cục bộ cho trang Chính sách */
    .policy-container {
        max-width: 1000px;
        margin: 40px auto;
        padding: 0 20px;
    }
    
    .page-title {
        text-align: center;
        color: #333;
        font-size: 28px;
        margin-bottom: 40px;
        text-transform: uppercase;
        position: relative;
        padding-bottom: 15px;
    }
    .page-title::after {
        content: '';
        position: absolute;
        bottom: 0; left: 50%; transform: translateX(-50%);
        width: 60px; height: 3px;
        background: var(--accent-pink, #ff4757);
    }

    /* Policy Grid */
    .policy-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 30px;
    }

    .policy-card {
        background: #fff;
        border: 1px solid #eee;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.03);
        transition: 0.3s;
        display: flex;
        gap: 20px;
        align-items: flex-start;
    }
    .policy-card:hover {
        border-color: var(--primary-pink, #ffc0cb);
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(233, 30, 99, 0.1);
    }

    .policy-icon {
        flex-shrink: 0;
        width: 60px; height: 60px;
        background: #fff0f5;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        color: var(--accent-pink, #ff4757);
        font-size: 24px;
    }

    .policy-content h3 {
        color: var(--accent-pink, #ff4757);
        margin-bottom: 10px;
        font-size: 20px;
        text-transform: uppercase;
    }
    .policy-content p {
        color: #555;
        line-height: 1.6;
        margin-bottom: 10px;
        font-size: 15px;
    }
    .policy-content ul {
        list-style-type: disc;
        padding-left: 20px;
        color: #555;
        margin-top: 5px;
    }
    .policy-content ul li {
        margin-bottom: 5px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .policy-card { flex-direction: column; text-align: center; }
        .policy-icon { margin: 0 auto 15px; }
        .policy-content ul { list-style-type: none; padding: 0; }
    }
</style>

<div class="policy-container">
    <h1 class="page-title">Chính Sách & Quy Định</h1>

    <div class="policy-grid">
        
        <div class="policy-card">
            <div class="policy-icon"><i class="fa-solid fa-shirt"></i></div>
            <div class="policy-content">
                <h3>1. Chính sách Thuê Đồ</h3>
                <p>Để đảm bảo quyền lợi cho cả hai bên, khách hàng vui lòng tuân thủ:</p>
                <ul>
                    <li>Phí thuê thêm ngày: 10% giá trị sản phẩm/ngày.</li>
                    <li>Khách hàng cần đặt cọc (Tiền mặt hoặc CCCD) khi nhận đồ. Tiền cọc sẽ được hoàn trả 100% khi trả đồ nguyên vẹn.</li>
                </ul>
            </div>
        </div>

        <div class="policy-card">
            <div class="policy-icon"><i class="fa-solid fa-rotate"></i></div>
            <div class="policy-content">
                <h3>2. Chính sách Đổi / Trả</h3>
                <p>QHTN Fashion hỗ trợ đổi trả linh hoạt trong các trường hợp:</p>
                <ul>
                    <li>Đổi size/mẫu miễn phí trong vòng <strong>24h</strong> sau khi nhận hàng nếu chưa sử dụng.</li>
                    <li>Sản phẩm bị lỗi do nhà cung cấp (rách, bẩn, hỏng khóa...) được đổi mới ngay lập tức.</li>
                    <li>Không hỗ trợ trả hàng với lý do "không thích" hoặc đã qua sử dụng.</li>
                </ul>
            </div>
        </div>

        <div class="policy-card">
            <div class="policy-icon"><i class="fa-solid fa-wallet"></i></div>
            <div class="policy-content">
                <h3>3. Phương thức Thanh toán</h3>
                <p>Chúng tôi chấp nhận các hình thức thanh toán sau:</p>
                <ul>
                    <li>Tiền mặt trực tiếp tại cửa hàng hoặc khi nhận hàng (COD).</li>
                    <li>Chuyển khoản ngân hàng (Vietcombank, Techcombank...).</li>
                    <li>Ví điện tử: MoMo, ZaloPay.</li>
                    <li>Nội dung chuyển khoản: <strong>Tên Khách Hàng + SĐT</strong>.</li>
                </ul>
            </div>
        </div>

        <div class="policy-card">
            <div class="policy-icon"><i class="fa-solid fa-truck-fast"></i></div>
            <div class="policy-content">
                <h3>4. Chính sách Giao vận</h3>
                <p>QHTN Fashion hợp tác với các đơn vị vận chuyển uy tín:</p>
                <ul>
                    <li><strong>Nội thành:</strong> Giao siêu tốc trong 2h-4h. Freeship đơn > 500k.</li>
                    <li><strong>Ngoại thành/Tỉnh:</strong> Giao hàng từ 2-4 ngày làm việc.</li>
                    <li>Khách hàng chịu phí ship 2 chiều nếu đổi trả không do lỗi cửa hàng.</li>
                </ul>
            </div>
        </div>

        <div class="policy-card">
            <div class="policy-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
            <div class="policy-content">
                <h3>5. Chính sách Đồng kiểm</h3>
                <p>Chúng tôi khuyến khích khách hàng kiểm tra kỹ sản phẩm:</p>
                <ul>
                    <li>Khách hàng <strong>ĐƯỢC PHÉP</strong> mở gói hàng kiểm tra số lượng, mẫu mã trước khi thanh toán.</li>
                    <li>Vui lòng quay video clip khi mở hàng để làm bằng chứng nếu có khiếu nại về sau.</li>
                    <li>Kiểm tra kỹ tình trạng vải, đường chỉ, khóa kéo ngay khi nhận.</li>
                </ul>
            </div>
        </div>

        <div class="policy-card">
            <div class="policy-icon"><i class="fa-solid fa-user-shield"></i></div>
            <div class="policy-content">
                <h3>6. Bảo mật thông tin</h3>
                <p>Cam kết bảo vệ quyền riêng tư của khách hàng:</p>
                <ul>
                    <li>Thông tin cá nhân (SĐT, Địa chỉ, CCCD) chỉ được dùng cho mục đích thuê đồ và giao hàng.</li>
                    <li>Tuyệt đối không chia sẻ thông tin cho bên thứ 3.</li>
                    <li>Hệ thống bảo mật dữ liệu an toàn, mã hóa mật khẩu người dùng.</li>
                </ul>
            </div>
        </div>

    </div>
</div>

<?php include 'footer.php'; ?>