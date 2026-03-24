const { ethers } = require("hardhat");
const fs = require("fs");
const path = require("path");

async function main() {
  const [deployer] = await ethers.getSigners();

  console.log("Deploying with account:", deployer.address);
  const balance = await ethers.provider.getBalance(deployer.address);
  console.log("Account balance:", ethers.formatEther(balance), "AVAX");

  const KB42 = await ethers.getContractFactory("kbaridon42");
  const token = await KB42.deploy();
  await token.waitForDeployment();

  const decimals = await token.decimals();
  const supply = await token.totalSupply();

  console.log("\n✅ KB42 deployed at:", token.target);
  console.log("   Total supply:", Number(supply) / 10 ** Number(decimals), "KB42");
  console.log("\n🔍 View on Avascan:");
  console.log("   https://testnet.avascan.info/blockchain/c/token/" + token.target);

  const outPath = path.resolve(__dirname, "../deployed.json");
  fs.writeFileSync(outPath, JSON.stringify({ address: token.target }, null, 2));
  console.log("\n📄 Address saved → run: make open");
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
