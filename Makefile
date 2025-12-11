.PHONY: install update serve fresh audit analyse test build autofix checkup

install:
	@echo "🔧 Installing dependencies..."
	composer install
	npm install

update:
	@echo "🔧 Updating dependencies..."
	composer update
	npm update

serve:
	@echo "🚀 Starting Laravel and Vite ($(MODE))..."
ifeq ($(MODE),background)
	nohup php artisan serve > storage/logs/artisan-serve.log 2>&1 &
	nohup npm run dev > storage/logs/vite-dev.log 2>&1 &
else
	php artisan serve & npm run dev
endif

fresh:
	@echo "🗑️  Resetting database..."
	php artisan migrate:fresh --seed

audit:
	@echo "🔒 Audit..."
	composer audit
	npm audit
	vendor/bin/security-checker security:check

analyse:
	@echo "🧪 Analyse code..."
	vendor/bin/phpcs --standard=PSR12 app/
	php -d memory_limit=-1 vendor/bin/phpstan analyse
	vendor/bin/psalm

autofix:
	@echo "🤖 Auto fix code..."
	vendor/bin/php-cs-fixer fix app/
	vendor/bin/psalm --alter --issues=MissingReturnType,MissingOverrideAttribute,InvalidReturnType,ClassMustBeFinal

test:
	@echo "🧪 Running tests..."
	vendor/bin/phpunit tests/unit --testdox
	npx cypress run

build:
	@echo "📦 Building frontend..."
	npm run build

checkup:
	@echo "🧰 Full project checkup: update → serve (bg) → analyse → test"
	$(MAKE) update
	$(MAKE) fresh
	$(MAKE) serve MODE=background
	sleep 5
	$(MAKE) analyse
	$(MAKE) test
	@echo ""
	@echo "✅ All checks passed! Preparing commit..."
	@current_version=$$(grep -o '"version": "[^"]*"' composer.json | grep -o '[0-9]*\.[0-9]*\.[0-9]*'); \
	major=$$(echo $$current_version | cut -d. -f1); \
	minor=$$(echo $$current_version | cut -d. -f2); \
	patch=$$(echo $$current_version | cut -d. -f3); \
	new_patch=$$((patch + 1)); \
	new_version="$$major.$$minor.$$new_patch"; \
	sed -i '' "s/\"version\": \"$$current_version\"/\"version\": \"$$new_version\"/" composer.json; \
	git add composer.json composer.lock package-lock.json; \
	echo "Update dependencies + bump version to $$new_version" | pbcopy; \
	echo ""; \
	echo "📦 Version bumped: $$current_version → $$new_version"; \
	echo ""; \
	echo "📋 Staged changes:"; \
	git diff --cached --stat; \
	echo ""; \
	echo "✅ Ready! Open GitHub Desktop, paste (Cmd+V) commit message, and commit."
