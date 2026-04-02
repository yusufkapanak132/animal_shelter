<?php
// ajax_remove_from_cart.php
require_once 'db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['product_id'] ?? null;
    
    if ($productId && is_numeric($productId)) {
        $success = removeFromCartAjax($productId);
        
        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Продуктът е премахнат от количката',
                'cart_count' => getCartCount(),
                'cart_total' => getCartTotalPrice()
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Продуктът не е намерен в количката'
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