<?php
header('Content-Type: application/json');
require_once '../config.php';

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!isset($input['message'])) {
    echo json_encode(['success' => false, 'message' => 'No message provided']);
    exit;
}

$userMessage = trim($input['message']);
$conversationId = isset($input['conversationId']) ? $input['conversationId'] : 'conv-' . time();

/**
 * 1. CẤU HÌNH GROQ API
 * Lấy API key từ https://console.groq.com/keys
 */
$groqApiKey = getenv('GROQ_API_KEY');
$groqApiUrl = 'https://api.groq.com/openai/v1/chat/completions';

/**
 * 2. RAG (Retrieval-Augmented Generation): Truy xuất dữ liệu từ Database
 */
$context = "";
$products = [];
$quickReplies = [];

try {
    // Truy xuất thông tin sản phẩm cơ bản nếu có từ khóa khớp
    $keywords = explode(' ', strtolower($userMessage));
    $searchConditions = [];
    $params = [];
    
    // Bỏ qua các từ quá ngắn như "có", "là", "gì"...
    $stopWords = ['có', 'là', 'gì', 'cho', 'tôi', 'xem']; 
    $filteredKeywords = array_filter($keywords, function($w) use ($stopWords) {
        return strlen($w) > 2 && !in_array($w, $stopWords);
    });
    
    foreach ($filteredKeywords as $key => $word) {
        $paramKey = ":word" . md5($word . $key); // Tránh trùng tên param
        $searchConditions[] = "(name LIKE $paramKey OR description LIKE $paramKey)";
        $params[$paramKey] = '%' . $word . '%';
    }
    
    if (!empty($searchConditions)) {
        $whereClause = implode(' OR ', $searchConditions);
        $stmt = $conn->prepare("SELECT id, name, price, description, category_id, image FROM products WHERE $whereClause LIMIT 5");
        $stmt->execute($params);
        $matchedProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($matchedProducts) {
            $context .= "🔍 SẢN PHẨM ĐANG CÓ BÁN TẠI CỬA HÀNG:\n";
            foreach ($matchedProducts as $prod) {
                $desc = strip_tags(substr($prod['description'], 0, 100)) . '...';
                $context .= "- ID: {$prod['id']} | Tên: {$prod['name']} | Danh mục ID: {$prod['category_id']} | Giá thuê: " . number_format($prod['price']) . " VND/ngày | Mô tả ngắn: $desc\n";
                
                // Trả về UI để render Card sản phẩm
                $products[] = [
                    "name" => $prod['name'],
                    "price" => (int)$prod['price'],
                    "image" => "img/" . basename($prod['image']),
                    "url" => "product_detail.php?id=" . $prod['id']
                ];
            }
        } else {
            $context .= "Không tìm thấy sản phẩm cụ thể nào khớp với yêu cầu tại cửa hàng. Hãy hướng dẫn chung hoặc gợi ý danh mục.\n";
        }
    } else {
        $context .= "Người dùng đang hỏi chung chung, hãy tư vấn nhiệt tình các danh mục: Áo dài, Váy đi biển, Váy thiết kế, Giày, Phụ kiện.\n";
    }

    // Nếu người dùng hỏi về chính sách, thêm context chính sách
    if (strpos(strtolower($userMessage), 'chính sách') !== false || strpos(strtolower($userMessage), 'thuê') !== false || strpos(strtolower($userMessage), 'phí') !== false) {
        $context .= "\n📜 CHÍNH SÁCH CỬA HÀNG:\n";
        $context .= "- Khách hàng Premium được giảm giá 10% và miễn phí ship.\n";
        $context .= "- Giá niêm yết là giá thuê tính theo 1 ngày (24 giờ).\n";
        $context .= "- Phí cọc thường là 50% đến 100% giá trị sản phẩm (được hoàn lại khi trả đồ nguyên vẹn).\n";
    }

} catch (Exception $e) {
    // Lỗi DB thì vẫn tiếp tục mà không có context sản phẩm
    error_log("RAG DB Error: " . $e->getMessage());
}

/**
 * 3. GIAO TIẾP VỚI GROQ LLM
 */
$systemPrompt = "Bạn là Concierge - Trợ lý AI cao cấp của QHTN Fashion Rental.
Quy tắc hành xử:
1. LUÔN LUÔN trả lời ngắn gọn, tinh tế, lịch sự, chuẩn mực bằng tiếng Việt (không dài dòng quá 4 câu).
2. Tích hợp emoji sang trọng (✨, 🎀, 👗, 💎) một cách tinh tế.
3. Nếu người dùng hỏi về sản phẩm, hãy dựa TRỰC TIẾP vào CONTEXT dưới đây để trả lời.
4. NGHÊM CẤM sử dụng các từ ngữ kỹ thuật như 'database', 'cơ sở dữ liệu', 'hệ thống', 'trong danh sách'. Thay vào đó, nếu có sản phẩm, hãy nói 'hiện tại cửa hàng chúng tôi có...', 'trong bộ sưu tập của QHTN có...'. Mời khách nhấn vào thẻ sản phẩm để xem.
5. Nếu người dùng hỏi ngoài phạm vi thời trang/cửa hàng, hãy khéo léo từ chối và hướng về dịch vụ của QHTN Fashion Rental.

[CONTEXT BẮT ĐẦU]
$context
[CONTEXT KẾT THÚC]";

$data = [
    'model' => 'llama-3.1-8b-instant', 
    'messages' => [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userMessage]
    ],
    'temperature' => 0.6, // Sáng tạo vừa phải để đảm bảo tính chính xác cho bot bán hàng
    'max_tokens' => 300 // Rút ngắn token vì yêu cầu trả lời gọn gàng
];

$ch = curl_init($groqApiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $groqApiKey
]);

// Set timeout để bot không treo quá lâu
curl_setopt($ch, CURLOPT_TIMEOUT, 6);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$aiResponseText = "";

if ($httpCode === 200 && $response) {
    $responseData = json_decode($response, true);
    if (isset($responseData['choices'][0]['message']['content'])) {
        $aiResponseText = trim($responseData['choices'][0]['message']['content']);
    }
} else {
    // Lỗi gọi API hoặc thiếu Key
    $errorMsg = $response ? "Lỗi từ Groq API ($httpCode)" : "Timeout/Không thể kết nối API";
    $aiResponseText = "Xin lỗi, hiện tại dịch vụ AI đang bận hoặc chưa cấu hình API. ($errorMsg). \n\nBạn có thể tham khảo một số gợi ý dưới đây nhé!";
    // Log ra để Dev biết
    error_log("Groq API Error: " . print_r($response, true));
}

/**
 * 4. TẠO QUICK REPLIES ĐỘNG
 */
if (count($products) > 0) {
    $quickReplies = [
        ["text" => "Tư vấn chọn size", "value" => "Cách chọn size cho sản phẩm này"],
        ["text" => "Chính sách thuê đồ", "value" => "Chính sách giá và cọc như thế nào?"]
    ];
} else {
    $quickReplies = [
        ["text" => "Áo dài cách tân", "value" => "Có áo dài cách tân không?"],
        ["text" => "Váy đi tiệc", "value" => "Tôi muốn tìm váy đi tiệc/đi biển"],
        ["text" => "Liên hệ CSKH", "value" => "Cho tôi thông tin liên hệ"]
    ];
}

echo json_encode([
    'success' => true,
    'conversationId' => $conversationId,
    'aiMessage' => [
        'id' => 'msg-ai-' . uniqid(),
        'text' => $aiResponseText,
        'products' => $products,
        'quickReplies' => $quickReplies,
        'timestamp' => date('c')
    ]
]);
