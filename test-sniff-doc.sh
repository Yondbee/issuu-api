#!/bin/bash

echo -e "\033[1;32m\$\033[37m phpunit\033[0m"

vendor/bin/phpunit \
    --configuration=tests/phpunit.xml

echo -e "\n\033[1;32m\$\033[37m phpdoc\033[0m"

vendor/bin/phpdoc \
    --target=docs/api \
    --encoding=utf-8 \
    --title="evan/events" \
    --force \
    --validate \
    --template=responsive-twig \
    --directory=src/

echo -e "\n\033[1;32m\$\033[37m phpcs\033[0m"

vendor/bin/phpcs \
    --report=full \
    --standard=PSR2 \
    src/ && echo "OK!"
