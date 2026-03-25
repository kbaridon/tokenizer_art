// SPDX-License-Identifier: MIT
pragma solidity ^0.8.24;

import "@openzeppelin/contracts/token/ERC721/ERC721.sol";
import "@openzeppelin/contracts/access/Ownable.sol";

contract Castle42 is ERC721, Ownable {

    //private variables
    string public constant ARTIST = "kbaridon";
    string public constant TITLE  = "The Great 42 Castle";

    string private _tokenURIData;
    bool   public  paused;

    address public royaltyRecipient;
    uint96  public royaltyBps;

    uint256 private _nextTokenId;

    //constructor: takes a metadata IPFS URI (ipfs://CID pointing to metadata.json)
    constructor(string memory metadataURI_, address royaltyRecipient_, uint96 royaltyBps_)
        ERC721("castle42", "C42")
        Ownable(msg.sender)
    {
        require(royaltyBps_ <= 1000, "Max 10%");
        royaltyRecipient = royaltyRecipient_;
        royaltyBps       = royaltyBps_;
        _tokenURIData    = metadataURI_;
    }

    //Return the metadatas (encrypted) in base 64
    function tokenURI(uint256 tokenId) public view override returns (string memory) {
        _requireOwned(tokenId);
        return _tokenURIData;
    }

    //Generate a new NFT (with the same properties as the previous)
    function mint(address to) external onlyOwner {
        _safeMint(to, _nextTokenId++);
    }

    //Transfer a NFT to an adress
    function transferTo(uint256 tokenId, address to) external {
        transferFrom(msg.sender, to, tokenId);
    }

    //Delete your NFT
    function burn(uint256 tokenId) external {
        require(_isAuthorized(ownerOf(tokenId), msg.sender, tokenId), "Not authorized");
        _burn(tokenId);
    }

    // Pause all transactions or unpause them
    function pause()   external onlyOwner { paused = true; }
    function unpause() external onlyOwner { paused = false; }

    // Internal function that update the owner with the NFT
    function _update(address to, uint256 tokenId, address auth)
        internal override returns (address)
    {
        address from = _ownerOf(tokenId);
        require(!paused || from == address(0), "Transfers paused");
        return super._update(to, tokenId, auth);
    }

    // Give informations about how much you owe if you sell your NFT at x price
    function royaltyInfo(uint256, uint256 salePrice)
        external view returns (address, uint256)
    {
        return (royaltyRecipient, salePrice * royaltyBps / 10_000);
    }

    // Set a royalty at a new percentage and a new recipient.
    function setRoyalty(address recipient, uint96 bps) external onlyOwner {
        require(bps <= 1000, "Max 10%");
        royaltyRecipient = recipient;
        royaltyBps       = bps;
    }
}
