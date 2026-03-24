<?php
$contractAddress = "";
$network = "fuji";
$addrFile = __DIR__ . "/contract_address.json";
if (file_exists($addrFile)) {
    $data = json_decode(file_get_contents($addrFile), true);
    $contractAddress = $data["address"] ?? "";
    $network         = $data["network"] ?? "fuji";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Castle42 — Admin Panel</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: monospace; background: #0f0f0f; color: #e0e0e0; padding: 2rem; }
    h1 { color: #e84142; margin-bottom: .25rem; }
    .subtitle { color: #888; font-size: .85rem; margin-bottom: 2rem; }
    #wallet-bar { display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
    #connect-btn { background: #e84142; color: #fff; border: none; padding: .5rem 1.2rem; border-radius: 6px; cursor: pointer; font-family: monospace; font-size: .9rem; }
    #connect-btn:hover { background: #c0392b; }
    #wallet-info { font-size: .85rem; color: #aaa; }
    #contract-row { display: flex; gap: .5rem; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; }
    #contract-row input { background: #1a1a1a; border: 1px solid #333; color: #e0e0e0; padding: .4rem .7rem; border-radius: 4px; width: 420px; font-family: monospace; font-size: .85rem; }
    #contract-row button { background: #333; color: #e0e0e0; border: none; padding: .4rem .8rem; border-radius: 4px; cursor: pointer; font-family: monospace; }
    #avascan-link { font-size: .8rem; }
    #avascan-link a { color: #e84142; }
    .section { background: #1a1a1a; border: 1px solid #2a2a2a; border-radius: 8px; padding: 1.2rem; margin-bottom: 1.5rem; }
    .section h2 { font-size: .95rem; color: #e84142; margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 1px; }
    .fn-block { margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #2a2a2a; }
    .fn-block:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: none; }
    .fn-name { font-size: .85rem; color: #aaa; margin-bottom: .4rem; }
    .fn-inputs { display: flex; gap: .5rem; flex-wrap: wrap; align-items: center; }
    .fn-inputs input { background: #0f0f0f; border: 1px solid #333; color: #e0e0e0; padding: .35rem .6rem; border-radius: 4px; font-family: monospace; font-size: .82rem; min-width: 180px; }
    .fn-inputs button { background: #e84142; color: #fff; border: none; padding: .35rem .9rem; border-radius: 4px; cursor: pointer; font-family: monospace; font-size: .82rem; }
    .fn-inputs button.read-btn { background: #2a5a8a; }
    .fn-inputs button:hover { opacity: .85; }
    .result { margin-top: .4rem; font-size: .8rem; color: #7ec8e3; word-break: break-all; }
    .result.error { color: #e84142; }
    #status { position: fixed; bottom: 1rem; right: 1rem; background: #1a1a1a; border: 1px solid #333; padding: .5rem 1rem; border-radius: 6px; font-size: .8rem; max-width: 380px; display: none; }
    .badge { display: inline-block; padding: .15rem .5rem; border-radius: 3px; font-size: .75rem; margin-left: .5rem; }
    .badge.owner { background: #2a3a2a; color: #5cb85c; }
    .badge.public { background: #2a2a3a; color: #7ec8e3; }
  </style>
</head>
<body>
  <h1>Castle42 <span style="font-size:.6em;color:#888">NFT Admin Panel</span></h1>
  <p class="subtitle">The Great 42 Castle — AVAX Fuji Testnet</p>

  <div id="wallet-bar">
    <button id="connect-btn" onclick="connectWallet()">Connect MetaMask</button>
    <span id="wallet-info">Non connecté</span>
  </div>

  <div id="contract-row">
    <span style="font-size:.85rem;color:#888">Contrat :</span>
    <input id="contract-addr" type="text" value="<?= htmlspecialchars($contractAddress) ?>" placeholder="0x...adresse du contrat">
    <button onclick="loadContract()">Charger</button>
    <span id="avascan-link"></span>
  </div>

  <!-- READ -->
  <div class="section">
    <h2>Lecture <span class="badge public">public</span></h2>
    <div class="fn-block">
      <div class="fn-name">tokenURI(tokenId)</div>
      <div class="fn-inputs">
        <input id="tokenURI-id" type="number" placeholder="tokenId" value="0">
        <button class="read-btn" onclick="callTokenURI()">Lire</button>
      </div>
      <div class="result" id="tokenURI-result"></div>
    </div>
    <div class="fn-block">
      <div class="fn-name">ownerOf(tokenId)</div>
      <div class="fn-inputs">
        <input id="ownerOf-id" type="number" placeholder="tokenId" value="0">
        <button class="read-btn" onclick="callOwnerOf()">Lire</button>
      </div>
      <div class="result" id="ownerOf-result"></div>
    </div>
    <div class="fn-block">
      <div class="fn-name">balanceOf(address)</div>
      <div class="fn-inputs">
        <input id="balanceOf-addr" type="text" placeholder="0x...">
        <button class="read-btn" onclick="callBalanceOf()">Lire</button>
      </div>
      <div class="result" id="balanceOf-result"></div>
    </div>
    <div class="fn-block">
      <div class="fn-name">paused / royaltyRecipient / royaltyBps</div>
      <div class="fn-inputs">
        <button class="read-btn" onclick="callReadAll()">Tout lire</button>
      </div>
      <div class="result" id="readall-result"></div>
    </div>
  </div>

  <!-- MINT / BURN / TRANSFER -->
  <div class="section">
    <h2>Tokens</h2>
    <div class="fn-block">
      <div class="fn-name">mint(to) <span class="badge owner">onlyOwner</span></div>
      <div class="fn-inputs">
        <input id="mint-to" type="text" placeholder="0x...adresse destinataire">
        <button onclick="callMint()">Mint</button>
      </div>
      <div class="result" id="mint-result"></div>
    </div>
    <div class="fn-block">
      <div class="fn-name">burn(tokenId)</div>
      <div class="fn-inputs">
        <input id="burn-id" type="number" placeholder="tokenId">
        <button onclick="callBurn()">Burn</button>
      </div>
      <div class="result" id="burn-result"></div>
    </div>
    <div class="fn-block">
      <div class="fn-name">transferTo(tokenId, to)</div>
      <div class="fn-inputs">
        <input id="transfer-id" type="number" placeholder="tokenId">
        <input id="transfer-to" type="text" placeholder="0x...destinataire">
        <button onclick="callTransferTo()">Transférer</button>
      </div>
      <div class="result" id="transfer-result"></div>
    </div>
  </div>

  <!-- OWNER FUNCTIONS -->
  <div class="section">
    <h2>Administration <span class="badge owner">onlyOwner</span></h2>
    <div class="fn-block">
      <div class="fn-name">pause / unpause</div>
      <div class="fn-inputs">
        <button onclick="callPause()">Pause</button>
        <button onclick="callUnpause()">Unpause</button>
      </div>
      <div class="result" id="pause-result"></div>
    </div>
    <div class="fn-block">
      <div class="fn-name">setImageURI(uri)</div>
      <div class="fn-inputs">
        <input id="setImage-uri" type="text" placeholder="ipfs://...">
        <button onclick="callSetImageURI()">Mettre à jour</button>
      </div>
      <div class="result" id="setImage-result"></div>
    </div>
    <div class="fn-block">
      <div class="fn-name">setRoyalty(recipient, bps)</div>
      <div class="fn-inputs">
        <input id="royalty-addr" type="text" placeholder="0x...adresse">
        <input id="royalty-bps" type="number" placeholder="bps (ex: 500 = 5%)">
        <button onclick="callSetRoyalty()">Mettre à jour</button>
      </div>
      <div class="result" id="royalty-result"></div>
    </div>
  </div>

  <div id="status"></div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/ethers/6.7.1/ethers.umd.min.js"></script>
  <script>
    const FUJI_CHAIN_ID = "0xa869"; // 43113

    const ABI = [
      "function mint(address to) external",
      "function burn(uint256 tokenId) external",
      "function transferTo(uint256 tokenId, address to) external",
      "function pause() external",
      "function unpause() external",
      "function setRoyalty(address recipient, uint96 bps) external",
      "function setImageURI(string imageURI_) external",
      "function tokenURI(uint256 tokenId) external view returns (string)",
      "function paused() external view returns (bool)",
      "function royaltyRecipient() external view returns (address)",
      "function royaltyBps() external view returns (uint96)",
      "function ownerOf(uint256 tokenId) external view returns (address)",
      "function balanceOf(address owner) external view returns (uint256)",
      "function ARTIST() external view returns (string)",
      "function TITLE() external view returns (string)"
    ];

    let provider, signer, contract;

    function showStatus(msg, isError = false) {
      const el = document.getElementById("status");
      el.textContent = msg;
      el.style.color = isError ? "#e84142" : "#7ec8e3";
      el.style.display = "block";
      clearTimeout(el._t);
      el._t = setTimeout(() => el.style.display = "none", 5000);
    }

    function setResult(id, msg, isError = false) {
      const el = document.getElementById(id);
      el.textContent = msg;
      el.className = "result" + (isError ? " error" : "");
    }

    async function switchToFuji() {
      try {
        await window.ethereum.request({ method: "wallet_switchEthereumChain", params: [{ chainId: FUJI_CHAIN_ID }] });
      } catch (e) {
        if (e.code === 4902) {
          await window.ethereum.request({
            method: "wallet_addEthereumChain",
            params: [{
              chainId: FUJI_CHAIN_ID,
              chainName: "Avalanche Fuji Testnet",
              nativeCurrency: { name: "AVAX", symbol: "AVAX", decimals: 18 },
              rpcUrls: ["https://api.avax-test.network/ext/bc/C/rpc"],
              blockExplorerUrls: ["https://testnet.avascan.info/blockchain/c"]
            }]
          });
        }
      }
    }

    async function connectWallet() {
      if (!window.ethereum) { alert("MetaMask non détecté !"); return; }
      await switchToFuji();
      provider = new ethers.BrowserProvider(window.ethereum);
      await provider.send("eth_requestAccounts", []);
      signer = await provider.getSigner();
      const addr = await signer.getAddress();
      document.getElementById("wallet-info").textContent = "Connecté : " + addr;
      loadContract();
    }

    function loadContract() {
      const addr = document.getElementById("contract-addr").value.trim();
      if (!ethers.isAddress(addr)) { showStatus("Adresse invalide", true); return; }
      if (!signer && !provider) { showStatus("Connecte MetaMask d'abord", true); return; }
      contract = new ethers.Contract(addr, ABI, signer || provider);
      const link = `https://testnet.avascan.info/blockchain/c/address/${addr}`;
      document.getElementById("avascan-link").innerHTML = `<a href="${link}" target="_blank">Voir sur Avascan ↗</a>`;
      showStatus("Contrat chargé : " + addr);
    }

    function requireContract() {
      if (!contract) { showStatus("Charge le contrat d'abord", true); return false; }
      return true;
    }

    async function callTokenURI() {
      if (!requireContract()) return;
      try {
        const id = document.getElementById("tokenURI-id").value;
        const uri = await contract.tokenURI(id);
        setResult("tokenURI-result", uri);
      } catch (e) { setResult("tokenURI-result", e.reason || e.message, true); }
    }

    async function callOwnerOf() {
      if (!requireContract()) return;
      try {
        const id = document.getElementById("ownerOf-id").value;
        const owner = await contract.ownerOf(id);
        setResult("ownerOf-result", owner);
      } catch (e) { setResult("ownerOf-result", e.reason || e.message, true); }
    }

    async function callBalanceOf() {
      if (!requireContract()) return;
      try {
        const addr = document.getElementById("balanceOf-addr").value.trim();
        const bal = await contract.balanceOf(addr);
        setResult("balanceOf-result", bal.toString() + " token(s)");
      } catch (e) { setResult("balanceOf-result", e.reason || e.message, true); }
    }

    async function callReadAll() {
      if (!requireContract()) return;
      try {
        const [paused, recipient, bps, artist, title] = await Promise.all([
          contract.paused(),
          contract.royaltyRecipient(),
          contract.royaltyBps(),
          contract.ARTIST(),
          contract.TITLE()
        ]);
        setResult("readall-result",
          `paused: ${paused} | royaltyRecipient: ${recipient} | royaltyBps: ${bps} (${Number(bps)/100}%) | artist: ${artist} | title: ${title}`
        );
      } catch (e) { setResult("readall-result", e.reason || e.message, true); }
    }

    async function callMint() {
      if (!requireContract()) return;
      try {
        const to = document.getElementById("mint-to").value.trim();
        const tx = await contract.mint(to);
        setResult("mint-result", "Tx envoyée : " + tx.hash);
        await tx.wait();
        setResult("mint-result", "Minté ! Tx : " + tx.hash);
      } catch (e) { setResult("mint-result", e.reason || e.message, true); }
    }

    async function callBurn() {
      if (!requireContract()) return;
      try {
        const id = document.getElementById("burn-id").value;
        const tx = await contract.burn(id);
        setResult("burn-result", "Tx envoyée : " + tx.hash);
        await tx.wait();
        setResult("burn-result", "Brûlé ! Tx : " + tx.hash);
      } catch (e) { setResult("burn-result", e.reason || e.message, true); }
    }

    async function callTransferTo() {
      if (!requireContract()) return;
      try {
        const id = document.getElementById("transfer-id").value;
        const to = document.getElementById("transfer-to").value.trim();
        const tx = await contract.transferTo(id, to);
        setResult("transfer-result", "Tx envoyée : " + tx.hash);
        await tx.wait();
        setResult("transfer-result", "Transféré ! Tx : " + tx.hash);
      } catch (e) { setResult("transfer-result", e.reason || e.message, true); }
    }

    async function callPause() {
      if (!requireContract()) return;
      try {
        const tx = await contract.pause();
        setResult("pause-result", "Tx envoyée : " + tx.hash);
        await tx.wait();
        setResult("pause-result", "Contrat pausé.");
      } catch (e) { setResult("pause-result", e.reason || e.message, true); }
    }

    async function callUnpause() {
      if (!requireContract()) return;
      try {
        const tx = await contract.unpause();
        setResult("pause-result", "Tx envoyée : " + tx.hash);
        await tx.wait();
        setResult("pause-result", "Contrat dépausé.");
      } catch (e) { setResult("pause-result", e.reason || e.message, true); }
    }

    async function callSetImageURI() {
      if (!requireContract()) return;
      try {
        const uri = document.getElementById("setImage-uri").value.trim();
        const tx = await contract.setImageURI(uri);
        setResult("setImage-result", "Tx envoyée : " + tx.hash);
        await tx.wait();
        setResult("setImage-result", "URI mise à jour.");
      } catch (e) { setResult("setImage-result", e.reason || e.message, true); }
    }

    async function callSetRoyalty() {
      if (!requireContract()) return;
      try {
        const addr = document.getElementById("royalty-addr").value.trim();
        const bps  = document.getElementById("royalty-bps").value;
        const tx = await contract.setRoyalty(addr, bps);
        setResult("royalty-result", "Tx envoyée : " + tx.hash);
        await tx.wait();
        setResult("royalty-result", "Royalties mises à jour.");
      } catch (e) { setResult("royalty-result", e.reason || e.message, true); }
    }

    // Auto-load if address already set
    window.addEventListener("load", () => {
      const addr = document.getElementById("contract-addr").value.trim();
      if (addr) {
        const link = `https://testnet.avascan.info/blockchain/c/address/${addr}`;
        document.getElementById("avascan-link").innerHTML = `<a href="${link}" target="_blank">Voir sur Avascan ↗</a>`;
      }
    });
  </script>
</body>
</html>
