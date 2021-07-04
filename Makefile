# This file is licensed under the Affero General Public License version 3 or
# later. See the LICENSE file.

# Dependencies:
# * make
# * npm
# * curl: used if phpunit and composer are not installed to fetch them from the web
# * tar: for building the archive

app_name=$(notdir $(CURDIR))
project_directory=$(CURDIR)/../$(app_name)
build_tools_directory=$(CURDIR)/build/tools
site_build_directory=$(CURDIR)/build/site
appstore_build_directory=$(CURDIR)/build/artifacts/appstore
appstore_package_name=$(appstore_build_directory)/$(app_name)
appstore_sign_dir=$(appstore_build_directory)/sign
cert_dir=$(build_tools_directory)/certificates
npm=$(shell which npm 2> /dev/null)
composer=$(shell which composer 2> /dev/null)

all: dev-setup build-js-production
serve: dev-setup watch-js

# Installs and updates the composer dependencies. If composer is not installed
# a copy is fetched from the web
.PHONY: composer
composer:
ifeq (,$(composer))
	@echo "No composer command available, downloading a copy from the web"
	mkdir -p $(build_tools_directory)
	curl -sS https://getcomposer.org/installer | php
	mv composer.phar $(build_tools_directory)
	php $(build_tools_directory)/composer.phar install --prefer-dist
else
	composer install --prefer-dist
endif

# Dev env management
dev-setup: clean clean-dev composer yarn-init

yarn-init:
	yarn

yarn-update:
	yarn upgrade

# Building
build-js:
	yarn dev

build-js-production:
	yarn build

watch-js:
	yarn watch

# Linting
lint:
	yarn lint

lint-fix:
	yarn lint:fix
	yarn stylelint:fix

# Style linting
stylelint:
	yarn stylelint

site:
	@if [ ! -d $(site_build_directory) ]; then \
		mkdir -p $(site_build_directory) ; \
		git clone https://github.com/LibreSign/site $(site_build_directory) ; \
	fi
	$(MAKE) yarn-init -C $(site_build_directory)
	$(MAKE) watch-js -C $(site_build_directory)

# Cleaning
.PHONY: clean
clean:
	rm -rf js/
	rm -rf $(appstore_build_directory)

clean-dev:
	rm -rf node_modules
	rm -rf vendor
	rm -rf $(appstore_build_directory)

.PHONY: test
test: composer
	$(CURDIR)/vendor/bin/phpunit -c phpunit.xml

# Builds the source package for the app store, ignores php and js tests
.PHONY: appstore
appstore: clean
	mkdir -p $(appstore_sign_dir)/$(app_name)
	composer install --no-dev
	yarn
	yarn build
	cp -r \
		appinfo \
		cfssl \
		img \
		js \
		l10n \
		lib \
		templates \
		vendor \
		CHANGELOG.md \
		LICENSE \
		$(appstore_sign_dir)/$(app_name) \

	@if [ -z "$$GITHUB_ACTION" ]; then \
		chown -R www-data:www-data $(appstore_sign_dir)/$(app_name) ; \
	fi

	mkdir -p $(cert_dir)
	@if [ -n "$$APP_PRIVATE_KEY" ]; then \
		echo "$$APP_PRIVATE_KEY" > $(cert_dir)/$(app_name).key; \
		echo "$$APP_PUBLIC_CRT" > $(cert_dir)/$(app_name).crt; \
		echo "Signing app files…"; \
		runuser -u www-data -- \
		php ../../occ integrity:sign-app \
			--privateKey=$(cert_dir)/$(app_name).key\
			--certificate=$(cert_dir)/$(app_name).crt\
			--path=$(appstore_sign_dir)/$(app_name); \
		echo "Signing app files ... done"; \
	fi
	tar -czf $(appstore_package_name).tar.gz -C $(appstore_sign_dir) $(app_name)
	@if [ -n "$$APP_PRIVATE_KEY" ]; then \
		echo "Signing package…"; \
		openssl dgst -sha512 -sign $(cert_dir)/$(app_name).key $(appstore_package_name).tar.gz | openssl base64; \
	fi
