<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$dataFile = __DIR__ . "/data.json";
$data = json_decode(file_get_contents($dataFile), true);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Azure Poll App</title>
<style>
body {font-family: Arial, sans-serif; background:#f4f4f4; padding:20px;}
h1 {text-align:center;}
.grid {display:grid; grid-template-columns: repeat(3, 1fr); gap:20px;}
.card {background:white;padding:15px;border-radius:10px;text-align:center;
       box-shadow:0 2px 6px rgba(0,0,0,0.1);}
button {margin-top:10px;padding:8px 16px;cursor:pointer;}
.modal {display:none;position:fixed;top:0;left:0;width:100%;height:100%;
        background:rgba(0,0,0,0.5);}
.modal-content {background:white;margin:10% auto;padding:20px;border-radius:8px;width:300px;}
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<h1>Azure Poll App</h1>
<div class="grid" id="pollGrid"></div>

<div class="modal" id="pollModal">
  <div class="modal-content">
    <h3 id="pollTitle"></h3>
    <div id="options"></div>
    <button onclick="closeModal()">Close</button>
  </div>
</div>

<script>
const modules = <?php echo json_encode($data['modules']); ?>;
const grid = document.getElementById('pollGrid');
let currentModule = null;
let charts = {};

function renderGrid() {
  grid.innerHTML = "";
  modules.forEach(mod => {
    const card = document.createElement('div');
    card.className = 'card';
    card.innerHTML = `
      <h3>${mod.name}</h3>
      <canvas id="chart${mod.id}" width="200" height="200"></canvas><br>
      <button onclick="openModal(${mod.id})">Poll</button>
    `;
    grid.appendChild(card);
    setTimeout(()=>drawChart(mod), 0); // draw after DOM append
  });
}

function drawChart(mod){
  const ctx = document.getElementById(`chart${mod.id}`);
  const labels = mod.options.map(o=>o.text);
  const votes = mod.options.map(o=>o.votes);
  const colors = ["#36A2EB","#FF6384","#FFCE56","#4BC0C0","#9966FF","#FF9F40"];
  if(charts[mod.id]) charts[mod.id].destroy();
  charts[mod.id] = new Chart(ctx, {
    type:'doughnut',
    data:{ 
      labels:labels,
      datasets:[{ 
        label:'Votes',
        data:votes,
        backgroundColor: colors.slice(0, labels.length),
        borderWidth:1
      }]
    },
    options:{
      plugins:{
        legend:{ position:'bottom' }
      }
    }
  });
}

function openModal(id){
  currentModule = modules.find(m=>m.id===id);
  document.getElementById('pollTitle').innerText = currentModule.question;
  const optDiv = document.getElementById('options');
  optDiv.innerHTML = currentModule.options.map(o =>
    `<button onclick="vote('${o.id}')">${o.text}</button>`
  ).join('<br>');
  document.getElementById('pollModal').style.display='block';
}

function closeModal(){ document.getElementById('pollModal').style.display='none'; }

function vote(optionId){
  const votedKey = `voted_${currentModule.id}`;
  if(localStorage.getItem(votedKey)){
    alert("You already voted for this module.");
    return;
  }
  fetch('vote.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({moduleId:currentModule.id, optionId:optionId})
  })
  .then(r=>r.json())
  .then(updated=>{
    localStorage.setItem(votedKey,'true');
    const modIndex = modules.findIndex(m=>m.id===updated.id);
    modules[modIndex] = updated;
    drawChart(updated);
    closeModal();
  })
  .catch(err=>console.error(err));
}

renderGrid();
</script>
</body>
</html>
