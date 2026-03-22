$(document).ready(function() {
    // Sử dụng Event Delegation để bắt sự kiện cho cả những nút được sinh ra sau này
    // Yêu cầu: Nút bấm trong HTML phải có class="add-to-cart-btn" và data-id="ID_SP"
    $(document).on('click', '.add-to-cart-btn', function(e) {
        e.preventDefault();
        
        var pid = $(this).data('id'); // Lấy ID sản phẩm
        var toastBox = $('#toast-box'); // Lấy hộp thông báo

        $.ajax({
            url: 'add_to_cart_ajax.php',
            type: 'POST',
            dataType: 'json',
            data: { id: pid },
            success: function(response) {
                if(response.status === 'login_required') {
                    alert(response.message);
                    window.location.href = 'login.php';
                }
                else if(response.status === 'success') {
                    // 1. Cập nhật số lượng trên Header
                    var cartCountEl = $('#cart-count');
                    if (cartCountEl.length) {
                        // Nếu thẻ span đã có -> cập nhật số text
                        cartCountEl.text(response.total_count);
                    } else {
                        // Nếu chưa có (giỏ rỗng) -> Thêm thẻ span vào icon giỏ hàng
                        // Lưu ý: Cần đảm bảo selector cha của icon giỏ hàng là chính xác (ví dụ .cart-icon-header a)
                        $('.cart-icon-header a').append('<span class="cart-count" id="cart-count">' + response.total_count + '</span>');
                    }

                    // 2. Hiện Toast thông báo đẹp mắt (thay vì alert)
                    $('#toast-msg').text(response.message);
                    toastBox.addClass('show');
                    
                    // Tự ẩn sau 2.5 giây
                    setTimeout(function() {
                        toastBox.removeClass('show');
                    }, 2500);
                } 
                else {
                    alert('Lỗi: ' + response.message);
                }
            },
            error: function() {
                alert('Lỗi hệ thống, vui lòng thử lại sau.');
            }
        });
    });

    function formatCurrency(value) {
        return Number(value).toLocaleString('vi-VN') + 'đ';
    }

    function syncPriceRange(rangeMin, rangeMax, source, $label) {
        var minVal = Number(rangeMin.value);
        var maxVal = Number(rangeMax.value);

        if (minVal > maxVal) {
            if (source === rangeMin) {
                rangeMax.value = minVal;
                maxVal = minVal;
            } else {
                rangeMin.value = maxVal;
                minVal = maxVal;
            }
        }

        if ($label && $label.length) {
            $label.text(formatCurrency(minVal) + ' - ' + formatCurrency(maxVal));
        }
    }

    function debounce(fn, wait) {
        var timer = null;
        return function() {
            var args = arguments;
            var context = this;
            clearTimeout(timer);
            timer = setTimeout(function() {
                fn.apply(context, args);
            }, wait);
        };
    }

    $('.auto-filter-form').each(function() {
        var $form = $(this);
        var $priceMin = $form.find('input[name="min_price"]');
        var $priceMax = $form.find('input[name="max_price"]');
        var $priceLabel = $form.find('#price_range_label');
        var $instantInputs = $form.find('input[type="radio"]');

        var submitForm = function() {
            $form.trigger('submit');
        };

        var submitDebounced = debounce(submitForm, 280);

        if ($priceMin.length && $priceMax.length) {
            syncPriceRange($priceMin.get(0), $priceMax.get(0), $priceMin.get(0), $priceLabel);

            $priceMin.on('input change', function() {
                syncPriceRange($priceMin.get(0), $priceMax.get(0), $priceMin.get(0), $priceLabel);
                submitDebounced();
            });

            $priceMax.on('input change', function() {
                syncPriceRange($priceMin.get(0), $priceMax.get(0), $priceMax.get(0), $priceLabel);
                submitDebounced();
            });
        }

        $instantInputs.on('change', function() {
            submitForm();
        });
    });
});