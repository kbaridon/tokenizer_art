How does the token work ? --> ERC721 already provides many native functions, allowing the NFT to be self-suficient and secure. We declare everything (and more) in the solidity file to change parameters...
Everything in the blockchain is public, so everyone can see what you do. Private companies (or users) secure the network by verifying all transactions. In exchange, they get ETH.


What can we do with the token ?
--> When creating the token, 1 NFT (C42) is given to the adress that deploys the contract.
You can then use every features of ERC721 (and more):
	tokenURI: Return the encrypted (base 64) informations of the NFT
	mint: Generate a new similar NFT to {address}
	transferTo: Allow us to transfer our NFT to another {adress}
	burn: Allow us to burn our own NFT.
	pause: Block every trade of this NFT.
	unpause: Allow every trade of this NFT.
	update: intern function that update the NFT owner.
	royaltyInfo: Give info of how much will you have to pay to the recipient adress (must give the token ID and the price of the sell)
	setRoyalty: Update royalty with a new percentage + new recipient address


How to deploy the token ?

Requirements: Node.js installed, Metamask configured on Fuji testnet (see below).

1. Add an .env file in /deployment : PRIVATE_KEY=...
To have a private key: Metamask > click your account name > account details > export private key

2. Do "make"

--> To see your NFT, go to the NFT section on Metamask.

---
Metamask --> Download Metamask: https://metamask.io/download Login* Go to network: add a custom network: Name: Avalanche Fuji Testnet RPC URL: https://api.avax-test.network/ext/bc/C/rpc Chain ID: 43113 Currency symbol: AVAX Block Explorer URL: https://subnets-test.avax.network
---

--> You can then see the token is online on avascan.
--> The image is stored by Pinata under a IPFS.
--> You can see the image at https://ipfs.io/ipfs/CID

Last known CID: bafybeihcf5dcfhze4hqsrmvzg7mjreywbd5ziobkiru2shtjjdiylmai4e
