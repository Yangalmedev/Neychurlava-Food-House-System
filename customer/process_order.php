<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/session.php';

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data || json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success'=>false,'message'=>'Invalid request data.']); exit();
}

$items           = $data['items']              ?? [];
$fulfillment     = $data['fulfillment_method'] ?? '';
$total           = (float)($data['total']      ?? 0);
$special         = trim($data['special_instructions'] ?? '');
$delivery_address= trim($data['delivery_address']     ?? '');
$customer_phone  = trim($data['customer_phone']       ?? '');
$pickup_name     = trim($data['pickup_name']           ?? '');

// Use logged-in customer_id if available, otherwise NULL (guest)
$customer_id = isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : null;

// Pre-fill phone from customer profile if not provided
if ($customer_id && $customer_phone === '') {
    $cp = $conn->query("SELECT phone FROM Customer WHERE customer_id=$customer_id")->fetch_row();
    if ($cp) $customer_phone = $cp[0];
}

if (empty($items)) {
    echo json_encode(['success'=>false,'message'=>'Cart is empty.']); exit();
}
if (!in_array($fulfillment,['Delivery','Takeout'])) {
    echo json_encode(['success'=>false,'message'=>'Invalid fulfillment method.']); exit();
}
if ($fulfillment==='Delivery' && ($delivery_address==='' || $customer_phone==='')) {
    echo json_encode(['success'=>false,'message'=>'Delivery address and phone are required.']); exit();
}
if ($fulfillment==='Takeout' && $pickup_name==='') {
    echo json_encode(['success'=>false,'message'=>'Pickup name is required.']); exit();
}

// Enforce payment method strictly server-side
$payment_method = ($fulfillment==='Delivery') ? 'Cash on Delivery' : 'Cash at Counter';
$order_type     = ($fulfillment==='Delivery') ? 'Delivery' : 'Take-out';

// System staff for online orders
$sys = $conn->query("SELECT staff_id FROM Staff WHERE role_id=1 LIMIT 1")->fetch_row();
$system_staff_id = $sys ? (int)$sys[0] : 1;

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("
        INSERT INTO `Order`
        (customer_id, staff_id, order_type, fulfillment_method, order_status,
         order_date, total_amount, payment_method, pickup_name, customer_phone, special_instructions)
        VALUES (?, ?, ?, ?, 'Pending', NOW(), ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssdssss",
        $customer_id, $system_staff_id, $order_type, $fulfillment,
        $total, $payment_method, $pickup_name, $customer_phone, $special
    );
    $stmt->execute();
    $order_id = $conn->insert_id;
    $stmt->close();

    $stmt2 = $conn->prepare(
        "INSERT INTO Order_Item (order_id,item_id,quantity,unit_price,subtotal) VALUES (?,?,?,?,?)"
    );
    foreach ($items as $it) {
        $iid=$it['id']; $qty=$it['qty']; $pr=$it['price']; $sub=$qty*$pr;
        $stmt2->bind_param("iiidd",$order_id,$iid,$qty,$pr,$sub);
        $stmt2->execute();
    }
    $stmt2->close();

    if ($fulfillment==='Delivery') {
        $stmt3 = $conn->prepare(
            "INSERT INTO Delivery (order_id,delivery_address,contact_number,delivery_status) VALUES (?,?,?,'Pending')"
        );
        $stmt3->bind_param("iss",$order_id,$delivery_address,$customer_phone);
        $stmt3->execute(); $stmt3->close();
    }

    $conn->commit();
    echo json_encode(['success'=>true,'order_id'=>$order_id]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
