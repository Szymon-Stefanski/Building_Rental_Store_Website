<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $productId = $_POST['product_id'];
        $productName = $_POST['product_name'];
        $productPrice = (float)$_POST['product_price'];
        $quantity = (int)$_POST['quantity'];
        $productImage = $_POST['product_image'];

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $productFound = false;

        foreach ($_SESSION['cart'] as $index => $item) {
            if ($item['id'] === $productId) {
                $_SESSION['cart'][$index]['quantity'] += $quantity;
                $productFound = true;
                break;
            }
        }

        if (!$productFound) {
            $_SESSION['cart'][] = [
                'id' => $productId,
                'name' => $productName,
                'price' => $productPrice,
                'quantity' => $quantity,
                'image' => $productImage,
            ];
        }



        $totalQuantity = 0;
        $totalPrice = 0;

        foreach ($_SESSION['cart'] as $item) {
            $totalQuantity += $item['quantity'];
            $totalPrice += $item['quantity'] * $item['price'];
        }

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode([
                'totalQuantity' => $totalQuantity,
                'totalPrice' => number_format($totalPrice, 2, ',', ' ')
            ]);
            exit;
        }


        if (isset($_SERVER['HTTP_REFERER'])) {
            $redirectUrl = $_SERVER['HTTP_REFERER'];
        } else {
            $redirectUrl = '../index.php';
        }

        header("Location: $redirectUrl");
        exit;
    }
}

?>
