SHELL := /bin/bash
NODE  := . $$HOME/.nvm/nvm.sh --no-use 2>/dev/null; nvm use 22 --silent 2>/dev/null;

.PHONY: all install compile deploy open clean fclean help

all: install compile deploy open

help:
	@echo "make install  — install dependencies"
	@echo "make compile  — compile the contract"
	@echo "make deploy   — deploy to Avalanche Fuji"
	@echo "make open     — open the interface"
	@echo "make clean    — remove artifacts/cache"
	@echo "make fclean   — remove artifacts/cache/node_modules"

install:
	@$(NODE) npm install --silent
	@ln -sf deployment/node_modules ../node_modules 2>/dev/null || true

compile:
	@$(NODE) npx hardhat compile

deploy:
	@if [ ! -f .env ]; then \
		echo "Error: .env not found."; \
		exit 1; \
	fi
	@$(NODE) npx hardhat run scripts/deploy.js --network fuji

open:
	@if [ -f .server.pid ] && kill -0 $$(cat .server.pid) 2>/dev/null; then \
		true; \
	else \
		php -S localhost:8000 -t . > /dev/null 2>&1 & echo $$! > .server.pid; \
		sleep 0.3; \
	fi
	@xdg-open "http://localhost:8000/interface.php" 2>/dev/null || \
	 open "http://localhost:8000/interface.php" 2>/dev/null || \
	 echo "Open: http://localhost:8000/interface.php"

clean:
	rm -rf artifacts cache

fclean: clean
	rm -rf node_modules ../node_modules .server.pid deployed.json
