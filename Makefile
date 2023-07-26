SHELL := /bin/bash -O expand_aliases

help: ## Show this help
	@egrep '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

stan: ## Run PHP Stan tests
	vendor/bin/phpstan analyse --memory-limit=-1

stan_baseline: ## Generate PHP Stan baseline
	vendor/bin/phpstan --generate-baseline --memory-limit=-1

phpunit: ## Run PHPUnit tests
	vendor/bin/phpunit -d memory_limit=-1

cs_dry_run: ## See CS errors
	vendor/bin/phpcs -d memory_limit=-1

cs: ## Fix code-sniffer errors
	vendor/bin/phpcbf -d memory_limit=-1

