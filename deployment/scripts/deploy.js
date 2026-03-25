const { ethers } = require("hardhat");
const fs   = require("fs");
const path = require("path");

async function main() {
  const [deployer] = await ethers.getSigners();

  console.log("Deploying with account:", deployer.address);
  const balance = await ethers.provider.getBalance(deployer.address);
  console.log("Account balance:", ethers.formatEther(balance), "AVAX");

  const METADATA_URI      = "ipfs://bafkreifdwa5jxm7ndnzoybqfa2z7q43yvuua6ufdx5bsujbyqfx5ive7e4";
  const ROYALTY_RECIPIENT = deployer.address;
  const ROYALTY_BPS       = 500; // 5%

  const Castle42 = await ethers.getContractFactory("Castle42");
  const castle42 = await Castle42.deploy(METADATA_URI, ROYALTY_RECIPIENT, ROYALTY_BPS);
  await castle42.waitForDeployment();

  console.log("\n✅ Castle42 deployed at:", castle42.target);
  console.log("\n🔍 View on Avascan:");
  console.log("   https://testnet.avascan.info/blockchain/c/token/" + castle42.target);

  const tx = await castle42.mint(deployer.address);
  await tx.wait();
  console.log("\n🎨 Token #0 minted to:", deployer.address);

  const outPath = path.resolve(__dirname, "../deployed.json");
  fs.writeFileSync(outPath, JSON.stringify({ address: castle42.target }, null, 2));
  console.log("\n📄 Address saved → run: make open");
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
