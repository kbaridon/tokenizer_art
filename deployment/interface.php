<?php
$d    = file_exists('deployed.json') ? json_decode(file_get_contents('deployed.json'), true) : [];
$addr = htmlspecialchars($d['address'] ?? '');
?><!DOCTYPE html>
<html><head>
<meta charset="UTF-8"><title>Castle42 NFT</title>
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
  <b style="color:#e8b84b">Castle42</b>
  <input id="ca" value="<?=$addr?>" size="46" placeholder="0x contract address">
  <button class="r" onclick="loadContract()">Load</button>
  <button class="w" onclick="connectWallet()" id="wb">Connect MetaMask</button>
  <span id="ws" style="color:#555;font-size:11px"></span>
</div>

<div id="log" style="color:#555">—</div>

<h4>Preview</h4>
<div class="row">
  <button class="r" onclick="preview()">Preview NFT</button>
  <input id="p_id" placeholder="tokenId" size="6" type="number" value="0">
</div>
<div id="nft-preview" style="margin:8px 0;display:none">
  <img id="nft-img" style="max-width:300px;border:1px solid #333;display:block;margin-bottom:4px">
  <div id="nft-meta" style="font-size:11px;color:#888"></div>
</div>

<h4>Read</h4>
<div class="row">
  <button class="r" onclick="r('name')">name</button>
  <button class="r" onclick="r('symbol')">symbol</button>
  <button class="r" onclick="r('ARTIST')">artist</button>
  <button class="r" onclick="r('TITLE')">title</button>
  <button class="r" onclick="r('paused')">paused</button>
</div>
<div class="row">
  <button class="r" onclick="r('ownerOf',false,['r_oof'])">ownerOf</button>
  <input id="r_oof" placeholder="tokenId" size="6" type="number">
</div>
<div class="row">
  <button class="r" onclick="r('balanceOf',false,['r_bal'])">balanceOf</button>
  <input id="r_bal" placeholder="address" size="44">
</div>
<div class="row">
  <button class="r" onclick="r('tokenURI',false,['r_uri'])">tokenURI</button>
  <input id="r_uri" placeholder="tokenId" size="6" type="number">
</div>
<div class="row">
  <button class="r" onclick="royaltyInfo()">royaltyInfo</button>
  <input id="r_ri_id"    placeholder="tokenId"   size="6"  type="number">
  <input id="r_ri_price" placeholder="salePrice (wei)" size="20" type="number">
</div>

<h4>Write</h4>
<div class="row">
  <button class="w" onclick="w('burn',['w_bn_id'])">burn</button>
  <input id="w_bn_id" placeholder="tokenId" size="6" type="number">
</div>
<div class="row">
  <button class="w" onclick="w('transferTo',['w_tr_id','w_tr_to'])">transferTo</button>
  <input id="w_tr_id" placeholder="tokenId" size="6" type="number">
  <input id="w_tr_to" placeholder="to address" size="44">
</div>
<div class="row">
  <button class="w" onclick="w('approve',['w_ap_to','w_ap_id'])">approve</button>
  <input id="w_ap_to" placeholder="address" size="44">
  <input id="w_ap_id" placeholder="tokenId" size="6" type="number">
</div>

<h4>Owner only</h4>
<div class="row">
  <button class="o" onclick="w('mint',['o_mt_to'])">mint</button>
  <input id="o_mt_to" placeholder="to address" size="44">
</div>
<div class="row">
  <button class="o" onclick="w('pause')">pause</button>
  <button class="o" onclick="w('unpause')">unpause</button>
</div>
<div class="row">
  <button class="o" onclick="w('setRoyalty',['o_rr','o_rb'])">setRoyalty</button>
  <input id="o_rr" placeholder="recipient address" size="44">
  <input id="o_rb" placeholder="bps (500=5%)" size="12" type="number">
</div>

<script>
const FUJI_RPC = 'https://avalanche-fuji-c-chain-rpc.publicnode.com';
const ABI = [
  {name:'name',            inputs:[],                                                                     outputs:[{type:'string'}],  stateMutability:'view',       type:'function'},
  {name:'symbol',          inputs:[],                                                                     outputs:[{type:'string'}],  stateMutability:'view',       type:'function'},
  {name:'ARTIST',          inputs:[],                                                                     outputs:[{type:'string'}],  stateMutability:'view',       type:'function'},
  {name:'TITLE',           inputs:[],                                                                     outputs:[{type:'string'}],  stateMutability:'view',       type:'function'},
  {name:'paused',          inputs:[],                                                                     outputs:[{type:'bool'}],    stateMutability:'view',       type:'function'},
  {name:'royaltyRecipient',inputs:[],                                                                     outputs:[{type:'address'}], stateMutability:'view',       type:'function'},
  {name:'royaltyBps',      inputs:[],                                                                     outputs:[{type:'uint96'}],  stateMutability:'view',       type:'function'},
  {name:'ownerOf',         inputs:[{name:'tokenId',type:'uint256'}],                                      outputs:[{type:'address'}], stateMutability:'view',       type:'function'},
  {name:'balanceOf',       inputs:[{name:'owner',type:'address'}],                                        outputs:[{type:'uint256'}], stateMutability:'view',       type:'function'},
  {name:'tokenURI',        inputs:[{name:'tokenId',type:'uint256'}],                                      outputs:[{type:'string'}],  stateMutability:'view',       type:'function'},
  {name:'royaltyInfo',     inputs:[{name:'',type:'uint256'},{name:'salePrice',type:'uint256'}],            outputs:[{type:'address'},{type:'uint256'}], stateMutability:'view', type:'function'},
  {name:'burn',            inputs:[{name:'tokenId',type:'uint256'}],                                      outputs:[],                 stateMutability:'nonpayable', type:'function'},
  {name:'transferTo',      inputs:[{name:'tokenId',type:'uint256'},{name:'to',type:'address'}],            outputs:[],                 stateMutability:'nonpayable', type:'function'},
  {name:'approve',         inputs:[{name:'to',type:'address'},{name:'tokenId',type:'uint256'}],            outputs:[],                 stateMutability:'nonpayable', type:'function'},
  {name:'mint',            inputs:[{name:'to',type:'address'}],                                           outputs:[],                 stateMutability:'nonpayable', type:'function'},
  {name:'pause',           inputs:[],                                                                     outputs:[],                 stateMutability:'nonpayable', type:'function'},
  {name:'unpause',         inputs:[],                                                                     outputs:[],                 stateMutability:'nonpayable', type:'function'},
  {name:'setRoyalty',      inputs:[{name:'recipient',type:'address'},{name:'bps',type:'uint96'}],          outputs:[],                 stateMutability:'nonpayable', type:'function'},
  {name:'OwnableUnauthorizedAccount', inputs:[{name:'account',type:'address'}], type:'error'},
  {name:'ERC721NonexistentToken',     inputs:[{name:'tokenId',type:'uint256'}], type:'error'},
  {name:'ERC721IncorrectOwner',       inputs:[{name:'sender',type:'address'},{name:'tokenId',type:'uint256'},{name:'owner',type:'address'}], type:'error'},
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
  chainId:          '0xa869',
  chainName:        'Avalanche Fuji Testnet',
  nativeCurrency:   { name: 'AVAX', symbol: 'AVAX', decimals: 18 },
  rpcUrls:          ['https://api.avax-test.network/ext/bc/C/rpc'],
  blockExplorerUrls:['https://testnet.avascan.info/']
};

async function connectWallet() {
  if (!window.ethereum) { log('MetaMask not found', '#e05252'); return; }
  try {
    try {
      await window.ethereum.request({ method: 'wallet_switchEthereumChain', params: [{ chainId: FUJI.chainId }] });
    } catch(switchErr) {
      if (switchErr.code === 4902)
        await window.ethereum.request({ method: 'wallet_addEthereumChain', params: [FUJI] });
      else throw switchErr;
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

async function r(fn, _, ids) {
  if (!contract) { log('Load a contract first', '#e05252'); return; }
  try {
    const args = (ids||[]).map(id => document.getElementById(id).value.trim());
    const res  = await contract[fn](...args);
    log(fn + ' → ' + res.toString());
  } catch(e) { log(e.reason || e.message, '#e05252'); }
}

async function royaltyInfo() {
  if (!contract) { log('Load a contract first', '#e05252'); return; }
  try {
    const tokenId    = document.getElementById('r_ri_id').value.trim();
    const salePrice  = document.getElementById('r_ri_price').value.trim();
    const [rec, amt] = await contract.royaltyInfo(tokenId, salePrice);
    log('royaltyInfo → recipient: ' + rec + '  amount: ' + amt.toString() + ' wei');
  } catch(e) { log(e.reason || e.message, '#e05252'); }
}

function decodeError(e) {
  if (e.data && contract) {
    try {
      const decoded = contract.interface.parseError(e.data);
      if (!decoded) return null;
      const n = decoded.name, a = decoded.args;
      if (n === 'OwnableUnauthorizedAccount') return 'Not the contract owner';
      if (n === 'ERC721NonexistentToken')     return 'Token #' + a.tokenId + ' does not exist';
      if (n === 'ERC721IncorrectOwner')       return 'Wrong owner for token #' + a.tokenId;
      return n + ' — ' + [...a].join(', ');
    } catch {}
  }
  return null;
}

async function w(fn, ids) {
  if (!signer)   { log('Connect MetaMask first', '#e05252'); return; }
  if (!contract) { log('Load a contract first',  '#e05252'); return; }
  try {
    const args = (ids||[]).map(id => document.getElementById(id).value.trim());
    log('Sending…');
    const tx = await contract.connect(signer)[fn](...args);
    log('Pending: ' + tx.hash);
    await tx.wait();
    log('✓ ' + tx.hash, '#4ec94e');
  } catch(e) { log(decodeError(e) || e.reason || e.message, '#e05252'); }
}

const IPFS_GW = 'https://gateway.pinata.cloud/ipfs/';

function ipfsToHttp(uri) {
  if (uri.startsWith('ipfs://')) return IPFS_GW + uri.slice(7);
  return uri;
}

async function preview() {
  if (!contract) { log('Load a contract first', '#e05252'); return; }
  try {
    const tokenId = document.getElementById('p_id').value;
    log('Fetching tokenURI…');
    const uri  = await contract.tokenURI(tokenId);
    let meta;
    if (uri.startsWith('data:application/json;base64,')) {
      meta = JSON.parse(atob(uri.replace('data:application/json;base64,', '')));
    } else {
      const res = await fetch(ipfsToHttp(uri));
      meta = await res.json();
    }
    const imgUrl = ipfsToHttp(meta.image || '');
    document.getElementById('nft-img').src = imgUrl;
    document.getElementById('nft-meta').textContent =
      (meta.name || '') + ' — ' + (meta.description || '');
    document.getElementById('nft-preview').style.display = 'block';
    log('image → ' + imgUrl);
  } catch(e) { log(e.message, '#e05252'); }
}

window.onload = async () => {
  const a = document.getElementById('ca').value.trim();
  if (ethers.isAddress(a)) {
    contract = new ethers.Contract(a, ABI, provider);
    log('Contract loaded. Connect MetaMask for write functions.');
  }
  if (window.ethereum) {
    const accs = await window.ethereum.request({ method: 'eth_accounts' });
    if (accs.length) connectWallet();
  }
};
</script>
</body></html>
