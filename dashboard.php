<?php
session_start();

if(!isset($_SESSION['admin'])){
    die('Login required');
}
?>

<html>
<head>
<title>Dashboard</title>
</head>
<body>
<?php include 'header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body{
    font-family:Arial;
    background:#f4f4f4;
}
.cards{
    display:flex;
    gap:20px;
    margin-bottom:30px;
}
.card{
    flex:1;
    background:white;
    padding:20px;
    border-radius:10px;
    box-shadow:0 0 8px rgba(0,0,0,0.1);
}
canvas{
    background:white;
    padding:20px;
    border-radius:10px;
}
</style>
</head>
<body>
<div class="content" >

<h1>Dashboard</h1>

<div class="cards">

<div class="card">
<h3>Total Revenue</h3>
<h2 id="rev">0</h2>
</div>

<div class="card">
<h3>Total Orders</h3>
<h2 id="ord">0</h2>
</div>

</div>

<canvas id="salesChart"></canvas>

<script>
fetch('api/summary.php')
.then(response => response.json())
.then(data => {

document.getElementById('rev').innerText = '₹' + (data.revenue || 0);
document.getElementById('ord').innerText = data.orders || 0;

new Chart(document.getElementById('salesChart'), {
type: 'bar',
data: {
labels: ['Revenue', 'Orders'],
datasets: [{
label: 'Dashboard Summary',
data: [data.revenue || 0, data.orders || 0]
}]
}
});

});
</script>
<div>
</body>
</html>
