# See https://tech.davis-hansson.com/p/make/
MAKEFLAGS += --warn-undefined-variables
MAKEFLAGS += --no-builtin-rules

ERROR_COLOR := $(shell tput setab 1)
YELLOW_COLOR := $(shell tput setaf 3)
NO_COLOR := $(shell tput sgr0)

SOURCE_URL = https://github.com/box-project/box/archive/refs/tags/4.6.2.tar.gz

PHPBENCH_BIN = vendor/bin/phpbench
PHPBENCH = php -d phar.readonly=0 $(PHPBENCH_BIN)

default: bench

.PHONY: clean
clean:
	rm -rf dist
	$(MAKE) dist

.PHONY: bench
bench: $(PHPBENCH_BIN) dist/source
	$(PHPBENCH) run tests

dist/source.tar.gz:
	$(MAKE) dist
	curl -L https://github.com/box-project/box/archive/refs/tags/4.6.2.tar.gz > dist/source.tar.gz
	touch -c $@

dist/source: dist/source.tar.gz
	tar -xf dist/source.tar.gz -C dist
	# Cleanup
	rm -rf dist/source
	mv dist/box-4.6.2 dist/source
	# Remove unnecessary files that would cause a failure when building a PHAR
	rm -rf dist/source/fixtures
	touch -c $@

dist:
	mkdir -p dist
	touch -c $@

.PHONY: vendor_install
vendor_install:
	touch -c vendor
	touch -c $(PHPBENCH_BIN)

composer.lock: composer.json
	@echo "$(ERROR_COLOR)$(@) is not up to date. You may want to run the following command:$(NO_COLOR)"
	@echo "$$ composer update --lock && touch -c $(@)"
vendor: composer.lock
	$(MAKE) vendor_install

$(PHPBENCH_BIN): composer.lock
	$(MAKE) --always-make vendor_install
