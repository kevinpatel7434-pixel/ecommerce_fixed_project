
<html>
<head>
<title>Dashboard</title>
</head>
<body class="admin-body">
<?php include 'header.php'; ?>

<main class="admin-main" >

<div class="admin-page-heading"><div><span class="admin-eyebrow">Admin workspace</span><h1>Reports</h1><p>See which products are driving your store.</p></div><a class="admin-outline-button" href="admin_page.php"><i class="fa fa-arrow-left"></i> Overview</a></div>
<section class="admin-panel admin-chart-panel"><div class="admin-panel-heading"><div><span class="admin-eyebrow">Product performance</span><h2>Top products</h2></div></div><canvas id='top'></canvas></section>
<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>
<script>
fetch('api/top_products.php').then(r=>r.json()).then(d=>{
 new Chart(document.getElementById('top'),{
   type:'bar',
   data:{labels:d.labels, datasets:[{data:d.values}]}
 });
});
</script>
 </main>

