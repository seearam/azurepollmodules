<?php
// index.php
$dataFile = __DIR__ . '/data.json';

// If first run and no JSON, create sample
if (!file_exists($dataFile)) {
    $default = [
        "modules" => [
            ["id"=>1,"name"=>"Compute","question"=>"Which Compute service do you prefer?","options"=>[
                ["id"=>"c1","text"=>"Azure VMs","votes"=>5],
                ["id"=>"c2","text"=>"App Service","votes"=>3],
                ["id"=>"c3","text"=>"Functions","votes"=>2]
            ]],
            ["id"=>2,"name"=>"Storage","question"=>"Which Storage option do you use most?","options"=>[
                ["id"=>"s1","text"=>"Blob Storage","votes"=>6],
                ["id"=>"s2","text"=>"File Storage","votes"=>1],
                ["id"=>"s3","text"=>"Queue Storage","votes"=>0]
            ]],
            ["id"=>3,"name"=>"Networking","question"=>"Favorite Networking feature?","options"=>[
                ["id"=>"n1","text"=>"VNet","votes"=>4],
                ["id"=>"n2","text"=>"Load Balancer","votes"=>2],
                ["id"=>"n3","text"=>"Front Door","votes"=>1]
            ]]
        ]
    ];
    file_put_contents($dataFile, json_encode($default, JSON_PRETTY_PRINT));
}

$data = json_decode(file_get_contents($dataFile), true);
if (!$data) $data = ["modules"=>[]];

// helper to escape
function h($str){return htmlspecialchars($str,ENT_QUOTES);}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Azure Polls</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body{font-family:sans-serif;background:#f3f4f6;padding:20px}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px}
.card{background:#fff;padding:14px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.06);cursor:pointer;display:flex;flex-direction:column;gap:8px;min-height:220px}
.card .title{font-weight:600}
.card .question{font-size:.95rem;color:#666}
.chart-wrap{flex:1;display:flex;justify-content:center;align-items:center}
.btn{background:#2563eb;color:#fff;border:none;padding:8px 12px;border-radius:8px;cursor:pointer}
.footer{display:flex;justify-content:space-between;align-items:center;font-size:.9rem;color:#333}
.modal-bg{position:fixed;inset:0;background:rgba(0,0,0,.45);display:none;align-items:center;justify-content:center;z-index:50}
.modal{background:#fff;padding:18px;border-radius:10px;width:100%;max-width:480px;box-shadow:0 8px 30px rgba(0,0,0,.3)}
.option{display:flex;align-items:center;gap:8px;padding:8px;border:1px solid #e5e7eb;border-radius:6px;margin-top:6px}
.close{float:right;font-size:20px;background:none;border:none;cursor:pointer}
</style>
</head>
<body>
<h1>Azure Module Polls</h1>
<div class="grid" id="cards"></div>

<!-- Modal -->
<div class="modal-bg" id="modalBg">
  <div class="modal">
    <button class="close" id="closeModal">&times;</button>
    <h2 id="modalTitle"></h2>
    <p id="modalQ" style="color:#666"></p>
    <div id="options"></div>
    <div style="margin-top:12px;text-align:right">
      <button class="btn" id="voteBtn">Vote</button>
    </div>
  </div>
</div>

<script>
const data = <?php echo json_encode($data); ?>;
const cards = document.getElementById('cards');
const modalBg = document.getElementById('modalBg');
const modalTitle = document.getElementById('modalTitle');
const modalQ = document.getElementById('modalQ');
const optionsDiv = document.getElementById('options');
const voteBtn = document.getElementById('voteBtn');
const closeModal = document.getElementById('closeModal');
let activeModule = null;
const charts = {};

function totalVotes(m){return m.options.reduce((s,o)=>s+(o.votes||0),0);}

function renderCards(){
  cards.innerHTML='';
  data.modules.forEach(m=>{
    const card=document.createElement('div');
    card.className='card';
    card.innerHTML=`<div class="title">${m.name}</div>
      <div class="question">${m.question}</div>
      <div class="chart-wrap"><canvas id="ch-${m.id}" width="200" height="120"></canvas></div>
      <div class="footer"><div>Total: ${totalVotes(m)}</div><button class="btn">Poll</button></div>`;
    card.querySelector('.btn').onclick=e=>openModal(m);
    card.onclick=e=>{if(e.target.tagName!=='BUTTON')openModal(m);};
    cards.appendChild(card);
    drawChart(m);
  });
}
function drawChart(m){
  const ctx=document.getElementById('ch-'+m.id).getContext('2d');
  const labels=m.options.map(o=>o.text);
  const votes=m.options.map(o=>o.votes);
  if(charts[m.id]){charts[m.id].data.datasets[0].data=votes;charts[m.id].update();return;}
  charts[m.id]=new Chart(ctx,{type:'doughnut',data:{labels,datasets:[{data:votes}]},options:{plugins:{legend:{display:false}},maintainAspectRatio:false}});
}

function openModal(m){
  activeModule=m;
  modalTitle.textContent=m.name;
  modalQ.textContent=m.question;
  optionsDiv.innerHTML='';
  m.options.forEach(o=>{
    const opt=document.createElement('label');
    opt.className='option';
    opt.innerHTML=`<input type="radio" name="pollOpt" value="${o.id}"> ${o.text} (${o.votes})`;
    optionsDiv.appendChild(opt);
  });
  modalBg.style.display='flex';
}
function closeModalFn(){modalBg.style.display='none';activeModule=null;}
closeModal.onclick=closeModalFn;
modalBg.onclick=e=>{if(e.target===modalBg)closeModalFn();};

voteBtn.onclick=async()=>{
  if(!activeModule)return;
  const sel=document.querySelector('input[name="pollOpt"]:checked');
  if(!sel){alert("Select an option");return;}
  const fd=new FormData();
  fd.append('moduleId',activeModule.id);
  fd.append('optionId',sel.value);
  const r=await fetch('vote.php',{method:'POST',body:fd});
  const j=await r.json();
  if(!j.success){alert(j.message);return;}
  // update local data & refresh
  data.modules=j.data.modules;
  renderCards();
  closeModalFn();
};

renderCards();
</script>
</body>
</html>
