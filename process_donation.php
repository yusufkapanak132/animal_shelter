<?php

require_once 'db_connect.php';

require_once('vendor/autoload.php'); 


\Stripe\Stripe::setApiKey('sk_test_51T7Av4CVAtbeeoyBeMx8nTRufntO8rLDJgnGZwvbIR2YlEQhgNQvQheCwyjRzLy1jZzFaaTSIwAfJyNAPOFUV9LW00pPyc75Ob');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'] ?? 0;
    $fullName = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    
    
    if ($amount <= 0 || empty($fullName) || empty($email)) {
        die('Моля, попълнете всички полета и въведете валидна сума!');
    }
    
    try {
        
        $stmt = $pdo->prepare("INSERT INTO donations (user_id, amount, full_name, email, payment_method, status) VALUES (?, ?, ?, ?, 'Stripe', 'Изчаква се')");
        $userId = (function_exists('isLoggedIn') && isLoggedIn()) ? $_SESSION['user_id'] : null;
        $stmt->execute([$userId, $amount, $fullName, $email]);
        $donationId = $pdo->lastInsertId();
        $baseUrl = 'http://nadejda.free.nf';
        
        $checkout_session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'customer_email' => $email,
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $amount * 100, 
                    'product_data' => [
                        'name' => 'Дарение за приют "Надежда"',
                        'description' => 'Дарение от ' . $fullName,
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',

            'success_url' => $baseUrl . '/donation_success.php?amount=' . $amount . '&donation_id=' . $donationId,

            'cancel_url' => $baseUrl . '/donation.php',
        ]);
        
        header("HTTP/1.1 303 See Other");
        header("Location: " . $checkout_session->url);
        exit;
        
    } catch (Exception $e) {
        die('Грешка при обработка на дарението: ' . $e->getMessage());
    }
}
?>
