#!/usr/bin/env bash

mkdir -p docs/cli

./bin/fastly-compute-php help --format=md bundle > docs/cli/bundle.md
./bin/fastly-compute-php help --format=md stubs:download > docs/cli/stubs_download.md