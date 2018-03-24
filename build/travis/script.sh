#! /bin/bash

#####
# This file is part of the MediaWiki extension Lingo.
#
# @copyright 2011 - 2016, Stephan Gambke, mwjames
# @license   GNU General Public License, version 2 (or any later version)
#
# The Lingo extension is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by the Free
# Software Foundation; either version 2 of the License, or (at your option) any
# later version.
#
# The Lingo extension is distributed in the hope that it will be useful, but
# WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
# FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
# details.
#
# You should have received a copy of the GNU General Public License along
# with this program. If not, see <http://www.gnu.org/licenses/>.
#
# @author Stephan Gambke
# @author mwjames
# @since 2.0
# @file
# @ingroup Lingo
#####

set -x  # display commands and their expanded arguments
set -u  # treat unset variables as an error when performing parameter expansion
set -o pipefail  # pipelines exit with last (rightmost) non-zero exit code
set -e  # exit immediately if a command exits with an error

originalDirectory=$(pwd)

function installMediaWiki {
	cd ..

	wget https://github.com/wikimedia/mediawiki/archive/$MW.tar.gz
	tar -zxf $MW.tar.gz
	mv mediawiki-$MW mw

	cd mw

	composer install --prefer-source

	case "$DBTYPE" in
	"mysql")

		mysql -e 'create database its_a_mw;'
		php maintenance/install.php --dbtype $DBTYPE --dbuser root --dbname its_a_mw --pass nyan --scriptpath /TravisWiki TravisWiki admin

		;;

	"postgres")

		# See https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/458
		sudo /etc/init.d/postgresql stop

		# Travis@support: Try adding a sleep of a few seconds between starting PostgreSQL
		# and the first command that accesses PostgreSQL
		sleep 3

		sudo /etc/init.d/postgresql start
		sleep 3

		psql -c 'create database its_a_mw;' -U postgres
		php maintenance/install.php --dbtype $DBTYPE --dbuser postgres --dbname its_a_mw --pass nyan --scriptpath /TravisWiki TravisWiki admin

		;;

	"sqlite")

		php maintenance/install.php --dbtype $DBTYPE --dbuser root  --dbname its_a_mw --dbpath $(pwd) --pass nyan --scriptpath /TravisWiki TravisWiki admin

		;;

	*)
		echo "$DBTYPE is not a recognized database type."
		exit 1

	esac

}

function installExtensionViaComposerOnMediaWikiRoot {

	# fix setup for older MW versions and install extension
	composer require --prefer-source --dev --update-with-dependencies "phpunit/phpunit:~4.0" "mediawiki/lingo:dev-master"

	cd extensions/Lingo

	# Pull request number, "false" if it's not a pull request
	if [ "$TRAVIS_PULL_REQUEST" != "false" ]
	then
		git fetch origin +refs/pull/"$TRAVIS_PULL_REQUEST"/merge:
		git checkout -f FETCH_HEAD
	else
		git fetch origin "$TRAVIS_BRANCH"
		git checkout -f FETCH_HEAD
	fi

	git log HEAD^..HEAD

	cd ../..

	# Rebuild the class map after git fetch
	composer dump-autoload

	echo 'error_reporting(E_ALL| E_STRICT);' >> LocalSettings.php
	echo 'ini_set("display_errors", 1);' >> LocalSettings.php
	echo '$wgShowExceptionDetails = true;' >> LocalSettings.php
	echo '$wgDevelopmentWarnings = true;' >> LocalSettings.php
	echo "putenv( 'MW_INSTALL_PATH=$(pwd)' );" >> LocalSettings.php
	echo "wfLoadExtension('Lingo');" >> LocalSettings.php

	php maintenance/update.php --quick
}

function uploadCoverageReport {
	wget https://scrutinizer-ci.com/ocular.phar
	php ocular.phar code-coverage:upload --repository='g/wikimedia/mediawiki-extensions-lingo' --format=php-clover coverage.clover
}

composer self-update

installMediaWiki
installExtensionViaComposerOnMediaWikiRoot

cd extensions/Lingo

if [ "$MW" == "master" ]
then
	php ../../tests/phpunit/phpunit.php --group $GROUP -c phpunit.xml.dist --coverage-clover=coverage.clover

	set +e
	uploadCoverageReport
else
	php ../../tests/phpunit/phpunit.php --group $GROUP -c phpunit.xml.dist
fi
