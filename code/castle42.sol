// SPDX-License-Identifier: MIT
pragma solidity ^0.8.24;

import "@openzeppelin/contracts/token/ERC721/ERC721.sol";
import "@openzeppelin/contracts/access/Ownable.sol";
import "@openzeppelin/contracts/utils/Base64.sol";

contract Castle42 is ERC721, Ownable {

    string public constant ARTIST = "kbaridon";
    string public constant TITLE  = "The Great 42 Castle";

    string private _tokenURIData;
    bool   public  paused;

    address public royaltyRecipient;
    uint96  public royaltyBps;

    uint256 private _nextTokenId;

    constructor(string memory imageURI_, address royaltyRecipient_, uint96 royaltyBps_)
        ERC721("castle42", "C42")
        Ownable(msg.sender)
    {
        require(royaltyBps_ <= 1000, "Max 10%");
        royaltyRecipient = royaltyRecipient_;
        royaltyBps       = royaltyBps_;

        bytes memory json = abi.encodePacked(
            '{"name":"', TITLE, '",'
            '"description":"The Great 42 Castle by ', ARTIST, '.",'
            '"image":"', imageURI_, '",'
            '"attributes":['
                '{"trait_type":"Artist","value":"', ARTIST, '"},'
                '{"trait_type":"Title","value":"', TITLE, '"}'
            ']}'
        );
        _tokenURIData = string(abi.encodePacked("data:application/json;base64,", Base64.encode(json)));
    }

    function tokenURI(uint256 tokenId) public view override returns (string memory) {
        _requireOwned(tokenId);
        return _tokenURIData;
    }

    function mint(address to) external onlyOwner {
        _safeMint(to, _nextTokenId++);
    }

    function transferTo(uint256 tokenId, address to) external {
        transferFrom(msg.sender, to, tokenId);
    }

    function burn(uint256 tokenId) external {
        require(_isAuthorized(ownerOf(tokenId), msg.sender, tokenId), "Not authorized");
        _burn(tokenId);
    }

    function pause()   external onlyOwner { paused = true; }
    function unpause() external onlyOwner { paused = false; }

    function _update(address to, uint256 tokenId, address auth)
        internal override returns (address)
    {
        address from = _ownerOf(tokenId);
        require(!paused || from == address(0), "Transfers paused");
        return super._update(to, tokenId, auth);
    }

    function royaltyInfo(uint256, uint256 salePrice)
        external view returns (address, uint256)
    {
        return (royaltyRecipient, salePrice * royaltyBps / 10_000);
    }

    function setRoyalty(address recipient, uint96 bps) external onlyOwner {
        require(bps <= 1000, "Max 10%");
        royaltyRecipient = recipient;
        royaltyBps       = bps;
    }
}
