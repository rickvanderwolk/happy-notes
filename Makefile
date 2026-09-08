.PHONY: install update serve fresh audit analyse test build autofix checkup bump version

# Version bumping -------------------------------------------------------------
# Accept a bare argument after `bump` / `checkup`, e.g. `make bump minor`.
# Equivalent long form: `make bump VERSION=minor`.
ifneq ($(filter bump checkup,$(firstword $(MAKECMDGOALS))),)
  BUMP_ARG := $(word 2,$(MAKECMDGOALS))
  ifneq ($(BUMP_ARG),)
    VERSION := $(BUMP_ARG)
    # Swallow the argument so make doesn't treat it as a target of its own.
    $(eval $(BUMP_ARG):;@:)
  endif
endif
VERSION ?= patch

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

version:
	@grep -o '"version": *"[^"]*"' composer.json | head -1 | sed 's/.*"\([^"]*\)"$$/\1/'

bump:
	@current=$$($(MAKE) --no-print-directory version); \
	major=$${current%%.*}; rest=$${current#*.}; minor=$${rest%%.*}; patch=$${rest##*.}; \
	case "$(VERSION)" in \
		major) new="$$((major + 1)).0.0" ;; \
		minor) new="$$major.$$((minor + 1)).0" ;; \
		patch|micro) new="$$major.$$minor.$$((patch + 1))" ;; \
		[0-9]*.[0-9]*.[0-9]*) new="$(VERSION)" ;; \
		*) echo "❌ Unknown version '$(VERSION)'. Use major, minor, patch, micro or X.Y.Z."; exit 1 ;; \
	esac; \
	if [ "$$new" = "$$current" ]; then \
		echo "ℹ️  Version already $$current, nothing to do."; \
	else \
		sed -i '' "s/\"version\": \"$$current\"/\"version\": \"$$new\"/" composer.json; \
		echo "🔒 Refreshing composer.lock hash..."; \
		composer update --lock --no-scripts --no-interaction; \
		echo "📦 Version bumped: $$current → $$new"; \
	fi

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
	@$(MAKE) --no-print-directory bump VERSION=$(VERSION)
	@new_version=$$($(MAKE) --no-print-directory version); \
	git add composer.json composer.lock package-lock.json; \
	echo "Update dependencies + bump version to $$new_version" | pbcopy; \
	echo ""; \
	echo "📋 Staged changes:"; \
	git diff --cached --stat; \
	echo ""; \
	echo "✅ Ready! Open GitHub Desktop, paste (Cmd+V) commit message, and commit."
