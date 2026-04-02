<?php
// ajax_add_to_cart.php
require_once 'db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['product_id'] ?? null;
    
    if ($productId && is_numeric($productId)) {
        $success = addToCartAjax($productId);
        
        if ($success) {
            // Вземане на информация за продукта
            $stmt = $pdo->prepare("SELECT name, price, image_url FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'message' => 'Продуктът е добавен в количката!',
                'cart_count' => getCartCount(),
                'product_name' => $product['name'] ?? 'Продукт',
                'product_price' => $product['price'] ?? 0,
                'product_image' => $product['image_url'] ?? ''
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Грешка при добавяне в количката'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Невалиден продукт'
        ]);
    }
}
?>