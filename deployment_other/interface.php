<?php
$d    = file_exists('deployed.json') ? json_decode(file_get_contents('deployed.json'), true) : [];
$addr = htmlspecialchars($d['address'] ?? '');
?><!DOCTYPE html>
<html><head>
<meta charset="UTF-8"><title>KB42</title>
<script src="https://cdn.jsdelivr.net/npm/ethers@6.13.1/dist/ethers.umd.min.js"></script>
<style>
*    { box-sizing: border-box; }
body { font-family: monospace; background: #1a1a1a; color: #ccc; padding: 16px; font-size: 13px; }
input  { background: #252525; border: 1px solid #404040; color: #fff; padding: 4px 8px; font-family: monospace; font-size: 12px; }
button { padding: 4px 10px; cursor: pointer; font-family: monospace; font-size: 12px; border: 1px solid; }
.r { background: #0d2a44; color: #5ba3d4; border-color: #1a5a8a; }
.w { background: #2a1500; color: #e8893a; border-color: #5a3000; }
.o { background: #2a0d0d; color: #e05252; border-color: #6a2020; }
.row { display: flex; align-items: center; gap: 6px; margin: 3px 0; flex-wrap: wrap; }
h4   { color: #555; font-size: 10px; letter-spacing: 2px; text-transform: uppercase; margin: 12px 0 5px; }
#log { padding: 6px 10px; background: #111; border: 1px solid #2a2a2a; margin: 8px 0; word-break: break-all; min-height: 22px; }
</style>
</head><body>

<div class="row" style="margin-bottom:8px">
  <b style="color:#e8b84b">KB42</b>
  <input id="ca" value="<?=$addr?>" size="46" placeholder="0x contract address">
  <button class="r" onclick="loadContract()">Load</button>
  <button class="w" onclick="connectWallet()" id="wb">Connect MetaMask</button>
  <span id="ws" style="color:#555;font-size:11px"></span>
</div>

<div id="log" style="color:#555">—</div>

<h4>Read</h4>
<div class="row">
  <button class="r" onclick="r('name')">name</button>
  <button class="r" onclick="r('symbol')">symbol</button>
  <button class="r" onclick="r('decimals')">decimals</button>
  <button class="r" onclick="r('totalSupply',true)">totalSupply</button>
  <button class="r" onclick="r('owner')">owner</button>
</div>
<div class="row">
  <button class="r" onclick="r('balanceOf',true,['r_bal'])">balanceOf</button>
  <input id="r_bal" placeholder="address" size="44">
</div>
<div class="row">
  <button class="r" onclick="r('allowance',true,['r_al_o','r_al_s'])">allowance</button>
  <input id="r_al_o" placeholder="owner"   size="20">
  <input id="r_al_s" placeholder="spender" size="20">
</div>

<h4>Write</h4>
<div class="row">
  <button class="w" onclick="w('transfer',['w_tr_to'],['w_tr_amt'])">transfer</button>
  <input id="w_tr_to"  placeholder="to"    size="44">
  <input id="w_tr_amt" placeholder="KB42"  size="8" type="number" step="0.01">
</div>
<div class="row">
  <button class="w" onclick="w('approve',['w_ap_sp'],['w_ap_amt'])">approve</button>
  <input id="w_ap_sp"  placeholder="spender" size="44">
  <input id="w_ap_amt" placeholder="KB42"    size="8" type="number" step="0.01">
</div>
<div class="row">
  <button class="w" onclick="w('transferFrom',['w_tf_fr','w_tf_to'],['w_tf_amt'])">transferFrom</button>
  <input id="w_tf_fr"  placeholder="from" size="20">
  <input id="w_tf_to"  placeholder="to"   size="20">
  <input id="w_tf_amt" placeholder="KB42" size="8" type="number" step="0.01">
</div>
<div class="row">
  <button class="w" onclick="w('burn',[],['w_bn_amt'])">burn</button>
  <input id="w_bn_amt" placeholder="KB42" size="8" type="number" step="0.01">
</div>

<h4>Owner only</h4>
<div class="row">
  <button class="o" onclick="w('mint',['o_mt_to'],['o_mt_amt'])">mint</button>
  <input id="o_mt_to"  placeholder="to"   size="44">
  <input id="o_mt_amt" placeholder="KB42" size="8" type="number" step="0.01">
</div>

<script>
const FUJI_RPC = 'https://api.avax-test.network/ext/bc/C/rpc';
const DEC = 2;
const ABI = [
  {name:'name',             inputs:[],                                                                       outputs:[{type:'string'}],  stateMutability:'view',      type:'function'},
  {name:'symbol',           inputs:[],                                                                       outputs:[{type:'string'}],  stateMutability:'view',      type:'function'},
  {name:'decimals',         inputs:[],                                                                       outputs:[{type:'uint8'}],   stateMutability:'pure',      type:'function'},
  {name:'totalSupply',      inputs:[],                                                                       outputs:[{type:'uint256'}], stateMutability:'view',      type:'function'},
  {name:'owner',            inputs:[],                                                                       outputs:[{type:'address'}], stateMutability:'view',      type:'function'},
  {name:'balanceOf',        inputs:[{name:'account',type:'address'}],                                        outputs:[{type:'uint256'}], stateMutability:'view',      type:'function'},
  {name:'allowance',        inputs:[{name:'owner',type:'address'},{name:'spender',type:'address'}],           outputs:[{type:'uint256'}], stateMutability:'view',      type:'function'},
  {name:'transfer',         inputs:[{name:'to',type:'address'},{name:'amount',type:'uint256'}],               outputs:[{type:'bool'}],    stateMutability:'nonpayable',type:'function'},
  {name:'approve',          inputs:[{name:'spender',type:'address'},{name:'amount',type:'uint256'}],          outputs:[{type:'bool'}],    stateMutability:'nonpayable',type:'function'},
  {name:'transferFrom',     inputs:[{name:'from',type:'address'},{name:'to',type:'address'},{name:'amount',type:'uint256'}], outputs:[{type:'bool'}], stateMutability:'nonpayable',type:'function'},
  {name:'burn',             inputs:[{name:'amount',type:'uint256'}],                                         outputs:[],                 stateMutability:'nonpayable',type:'function'},
  {name:'mint',             inputs:[{name:'to',type:'address'},{name:'amount',type:'uint256'}],               outputs:[],                 stateMutability:'nonpayable',type:'function'},
  // Custom errors — decoded for readable messages
  {name:'ERC20InsufficientBalance',  inputs:[{name:'sender',type:'address'},{name:'balance',type:'uint256'},{name:'needed',type:'uint256'}],   type:'error'},
  {name:'ERC20InsufficientAllowance',inputs:[{name:'spender',type:'address'},{name:'allowance',type:'uint256'},{name:'needed',type:'uint256'}], type:'error'},
  {name:'ERC20InvalidSender',        inputs:[{name:'sender',type:'address'}],   type:'error'},
  {name:'ERC20InvalidReceiver',      inputs:[{name:'receiver',type:'address'}], type:'error'},
  {name:'ERC20InvalidApprover',      inputs:[{name:'approver',type:'address'}], type:'error'},
  {name:'ERC20InvalidSpender',       inputs:[{name:'spender',type:'address'}],  type:'error'},
  {name:'OwnableUnauthorizedAccount',inputs:[{name:'account',type:'address'}],  type:'error'},
];

let provider = new ethers.JsonRpcProvider(FUJI_RPC);
let signer   = null;
let contract = null;

function log(msg, color) {
  const el = document.getElementById('log');
  el.textContent = msg;
  el.style.color  = color || '#8fbcbb';
}

function loadContract() {
  const a = document.getElementById('ca').value.trim();
  if (!ethers.isAddress(a)) { log('Invalid address', '#e05252'); return; }
  contract = new ethers.Contract(a, ABI, signer || provider);
  log('Loaded: ' + a);
}

const FUJI = {
  chainId:         '0xa869',
  chainName:       'Avalanche Fuji Testnet',
  nativeCurrency:  { name: 'AVAX', symbol: 'AVAX', decimals: 18 },
  rpcUrls:         ['https://api.avax-test.network/ext/bc/C/rpc'],
  blockExplorerUrls:['https://testnet.avascan.info/']
};

async function connectWallet() {
  if (!window.ethereum) { log('MetaMask not found', '#e05252'); return; }
  try {
    // Switch to Fuji (or add it if not present)
    try {
      await window.ethereum.request({ method: 'wallet_switchEthereumChain', params: [{ chainId: FUJI.chainId }] });
    } catch(switchErr) {
      if (switchErr.code === 4902) {
        await window.ethereum.request({ method: 'wallet_addEthereumChain', params: [FUJI] });
      } else { throw switchErr; }
    }
    const wp = new ethers.BrowserProvider(window.ethereum);
    await wp.send('eth_requestAccounts', []);
    signer   = await wp.getSigner();
    provider = wp;
    const a  = await signer.getAddress();
    document.getElementById('ws').textContent = a.slice(0,6) + '…' + a.slice(-4);
    document.getElementById('wb').textContent = 'Fuji ✓';
    loadContract();
  } catch(e) { log(e.message, '#e05252'); }
}

async function r(fn, isAmt, ids) {
  if (!contract) { log('Load a contract first', '#e05252'); return; }
  try {
    const args = (ids||[]).map(id => document.getElementById(id).value.trim());
    const res  = await contract[fn](...args);
    const raw  = res.toString();
    const hint = (isAmt && typeof res === 'bigint') ? '  (' + Number(res)/10**DEC + ' KB42)' : '';
    log(fn + ' → ' + raw + hint);
  } catch(e) { log(e.reason || e.message, '#e05252'); }
}

function decodeError(e) {
  if (e.data) {
    try {
      const decoded = contract.interface.parseError(e.data);
      if (!decoded) return null;
      const n = decoded.name;
      const a = decoded.args;
      const kb = v => Number(v) / 10**DEC + ' KB42';
      if (n === 'ERC20InsufficientBalance')
        return 'Solde insuffisant — vous avez ' + kb(a.balance) + ', besoin de ' + kb(a.needed);
      if (n === 'ERC20InsufficientAllowance')
        return 'Allowance insuffisante — accordée ' + kb(a.allowance) + ', besoin de ' + kb(a.needed);
      if (n === 'OwnableUnauthorizedAccount')
        return 'Vous n\'êtes pas le propriétaire du contrat';
      if (n === 'ERC20InvalidReceiver' || n === 'ERC20InvalidSender')
        return 'Adresse invalide : ' + a[0];
      return n + ' — ' + [...a].join(', ');
    } catch {}
  }
  return null;
}

async function w(fn, addrIds, amtIds) {
  if (!signer)   { log('Connect MetaMask first', '#e05252'); return; }
  if (!contract) { log('Load a contract first',  '#e05252'); return; }
  try {
    const args = [
      ...(addrIds||[]).map(id => document.getElementById(id).value.trim()),
      ...(amtIds||[]).map(id => Math.round(parseFloat(document.getElementById(id).value) * 10**DEC))
    ];
    log('Sending…');
    const tx = await contract.connect(signer)[fn](...args);
    log('Pending: ' + tx.hash);
    await tx.wait();
    log('✓ ' + tx.hash, '#4ec94e');
  } catch(e) { log(decodeError(e) || e.reason || e.message, '#e05252'); }
}

window.onload = async () => {
  // Pre-load contract with public RPC (reads work without MetaMask)
  const a = document.getElementById('ca').value.trim();
  if (ethers.isAddress(a)) {
    contract = new ethers.Contract(a, ABI, provider);
    log('Contract loaded. Connect MetaMask for write functions.');
  }
  // Auto-connect MetaMask if already authorized
  if (window.ethereum) {
    const accs = await window.ethereum.request({ method: 'eth_accounts' });
    if (accs.length) connectWallet();
  }
};
</script>
</body></html>
