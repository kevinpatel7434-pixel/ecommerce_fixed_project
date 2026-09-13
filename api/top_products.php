
<?php require '../db.php';
$q=$conn->query("SELECT p.name,SUM(oi.quantity) qty FROM order_items oi JOIN products p ON p.id=oi.product_id GROUP BY p.id");
$labels=[];$values=[];
while($r=$q->fetch_assoc()){ $labels[]=$r['name']; $values[]=$r['qty']; }
echo json_encode(['labels'=>$labels,'values'=>$values]);
?>
