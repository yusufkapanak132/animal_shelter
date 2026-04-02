<?php
require_once 'db_connect.php';

$amount = isset($_GET['amount']) ? htmlspecialchars($_GET['amount']) : '0';
$donationId = isset($_GET['donation_id']) ? intval($_GET['donation_id']) : 0;

// Тук ъпдейтваме статуса на дарението
if ($donationId > 0) {
    $stmt = $pdo->prepare("UPDATE donations SET status = 'Завършено' WHERE id = ?");
    $stmt->execute([$donationId]);
}
?>
<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Успешно Дарение - Приют Надежда</title>
        <link rel="icon" type="image/png" href="assets/logo/paw-solid-full.png">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta http-equiv="refresh" content="20;url=index.php">
    <style>
        .success-container {
            max-width: 600px;
            margin: 100px auto;
            text-align: center;
            padding: 40px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        .success-icon {
            font-size: 80px;
            color: #4CAF50;
            margin-bottom: 20px;
            animation: scaleIn 0.5s ease-in-out;
        }
        @keyframes scaleIn {
            0% { transform: scale(0); }
            100% { transform: scale(1); }
        }
        .redirect-text {
            margin-top: 30px;
            color: #888;
            font-size: 0.9rem;
        }
    </style>
</head>
<body style="background-color: #f5f5f5;">

    <div class="success-container fade-in">
        <i class="fas fa-check-circle success-icon"></i>
        <h1 style="color: #333; margin-bottom: 15px;">Огромно Благодаря!</h1>
        <p style="font-size: 1.2rem; color: #555; margin-bottom: 20px;">
            Вашето дарение от <strong><?php echo $amount; ?> €.</strong> беше прието успешно.
        </p>
        <p style="color: #666; line-height: 1.6;">
            Тези средства ще бъдат използвани директно за храна, лекарства и грижа за нашите спасени животни. Вие току-що променихте нечий живот към по-добро! 
            
        </p>
        <br>
        <img src="assets/logo/paw-solid-full.png" alt="Надежда logo" class="logo-img-donation">
        
        <div class="redirect-text">
            Ще бъдете автоматично пренасочени към началната страница след <span id="countdown">20</span> секунди...<br>
            <a href="index.php" class="btn btn-primary" style="margin-top: 15px; display: inline-block;">Върни се сега</a>
        </div>
    </div>

    <script>
        // JS таймер за визуално отброяване на секундите
        let timeLeft = 30;
        const countdownEl = document.getElementById('countdown');
        
        const timerId = setInterval(() => {
            timeLeft--;
            countdownEl.textContent = timeLeft;
            if (timeLeft <= 0) {
                clearInterval(timerId);
            }
        }, 1000);
    </script>
</body>
</html>