<?php
// ajax_update_cart.php
require_once 'db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['product_id'] ?? null;
    $action = $_POST['action'] ?? null; 
    
    if ($productId && is_numeric($productId) && in_array($action, ['increase', 'decrease'])) {
        
        // Проверяваме дали количката съществува в сесията
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        if (isset($_SESSION['cart'][$productId])) {
            if ($action === 'increase') {
                $_SESSION['cart'][$productId]++;
            } elseif ($action === 'decrease') {
                $_SESSION['cart'][$productId]--;
                
                // Ако количеството падне до 0 (или по-малко), премахваме продукта изцяло
                if ($_SESSION['cart'][$productId] <= 0) {
                    unset($_SESSION['cart'][$productId]);
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Количеството е обновено',
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
            'message' => 'Невалидни данни'
        ]);
    }
}
?>