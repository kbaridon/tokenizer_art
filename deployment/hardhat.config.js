require("@nomicfoundation/hardhat-ethers");
require("dotenv").config();

/** @type import('hardhat/config').HardhatUserConfig */
module.exports = {
  solidity: "0.8.24",

  paths: {
    root:      "..",
    sources:   "code",
    cache:     "deployment/cache",
    artifacts: "deployment/artifacts",
  },

  networks: {
    fuji: {
      url: "https://avalanche-fuji-c-chain-rpc.publicnode.com",
      chainId: 43113,
      accounts: process.env.PRIVATE_KEY ? [process.env.PRIVATE_KEY] : [],
      gasPrice: 25000000000, // 25 nAVAX
    },
  },
};
