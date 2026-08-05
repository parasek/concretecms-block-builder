#!/usr/bin/env bash

set -euo pipefail

: "${BLOCK_BUILDER_CI_SITE_ROOT:?BLOCK_BUILDER_CI_SITE_ROOT is required}"
: "${BLOCK_BUILDER_CI_DATABASE_SERVER:?BLOCK_BUILDER_CI_DATABASE_SERVER is required}"
: "${BLOCK_BUILDER_CI_DATABASE_USERNAME:?BLOCK_BUILDER_CI_DATABASE_USERNAME is required}"
: "${BLOCK_BUILDER_CI_DATABASE_PASSWORD:?BLOCK_BUILDER_CI_DATABASE_PASSWORD is required}"
: "${BLOCK_BUILDER_CI_DATABASE_NAME:?BLOCK_BUILDER_CI_DATABASE_NAME is required}"
: "${BLOCK_BUILDER_CI_ADMIN_PASSWORD:?BLOCK_BUILDER_CI_ADMIN_PASSWORD is required}"
: "${BLOCK_BUILDER_CI_SITE_ID:?BLOCK_BUILDER_CI_SITE_ID is required}"
: "${RUNNER_TEMP:?RUNNER_TEMP is required}"
: "${GITHUB_ENV:?GITHUB_ENV is required}"

script_path="$(realpath -e -- "${BASH_SOURCE[0]}")"
script_root="${script_path%/*}"
# shellcheck source=disposable-path-guard.sh
source "${script_root}/disposable-path-guard.sh"

canonical_runner_temp="$(block_builder_resolve_runner_temp "${RUNNER_TEMP}")"
BLOCK_BUILDER_CI_SITE_ROOT="$(block_builder_resolve_existing_site_root "${canonical_runner_temp}" "${BLOCK_BUILDER_CI_SITE_ROOT}")"
if [[ ! "${BLOCK_BUILDER_CI_DATABASE_SERVER}" =~ ^(127\.0\.0\.1|localhost)(:[0-9]{1,5})?$ ]]; then
    echo 'The disposable database server must be loopback-only.' >&2
    exit 2
fi
if [[ "${BLOCK_BUILDER_CI_DATABASE_SERVER}" == *:* ]]; then
    database_port="${BLOCK_BUILDER_CI_DATABASE_SERVER##*:}"
    if ((10#${database_port} < 1 || 10#${database_port} > 65535)); then
        echo 'The disposable database server port must be between 1 and 65535.' >&2
        exit 2
    fi
fi
if [[ "${BLOCK_BUILDER_CI_DATABASE_USERNAME,,}" == 'root' ]]; then
    echo 'The disposable site must use a database-scoped non-root user.' >&2
    exit 2
fi
if [[ ! "${BLOCK_BUILDER_CI_DATABASE_NAME}" =~ ^block_builder_test_[a-z0-9_]+$ ]]; then
    echo "The disposable database name must begin with block_builder_test_." >&2
    exit 2
fi
if [[ ! "${BLOCK_BUILDER_CI_SITE_ID}" =~ ^[a-zA-Z0-9_-]{16,128}$ ]]; then
    echo 'The disposable site identifier must contain 16-128 safe characters.' >&2
    exit 2
fi

public_root="${BLOCK_BUILDER_CI_SITE_ROOT}/public"
blocks_root="${public_root}/application/blocks"
files_root="${public_root}/application/files"
package_root="${public_root}/packages/block_builder"
marker_script_root="${BLOCK_BUILDER_CI_MARKER_SCRIPT_ROOT:-${package_root}}"
block_builder_assert_no_symlink_components "${package_root}" 'The disposable package root'
package_root="$(realpath -e -- "${package_root}")"
if [[ "${package_root}" != "${public_root}/packages/block_builder" || ! -f "${package_root}/controller.php" || -L "${package_root}/controller.php" ]]; then
    echo "The prepared disposable site does not contain Block Builder." >&2
    exit 2
fi
marker_script_root="$(block_builder_resolve_existing_directory "${marker_script_root}" 'The marker script root')"

block_builder_assert_no_symlink_components "${files_root}" 'The disposable files root'
if [[ -e "${files_root}" && ! -d "${files_root}" ]]; then
    echo "The disposable files root is not a directory: ${files_root}" >&2
    exit 2
fi
mkdir -p -- "${files_root}"
if [[ ! -w "${files_root}" ]]; then
    echo "The disposable files root is not writable: ${files_root}" >&2
    exit 2
fi

(
    cd "${public_root}"

    "${BLOCK_BUILDER_CI_SITE_ROOT}/vendor/bin/concrete" c5:install \
        --db-server="${BLOCK_BUILDER_CI_DATABASE_SERVER}" \
        --db-username="${BLOCK_BUILDER_CI_DATABASE_USERNAME}" \
        --db-password="${BLOCK_BUILDER_CI_DATABASE_PASSWORD}" \
        --db-database="${BLOCK_BUILDER_CI_DATABASE_NAME}" \
        --timezone=UTC \
        --site='Block Builder integration' \
        --canonical-url='http://127.0.0.1:8080/' \
        --starting-point=atomik_blank \
        --session-handler=database \
        --admin-email='admin@example.test' \
        --admin-password="${BLOCK_BUILDER_CI_ADMIN_PASSWORD}" \
        --language=en_US \
        --site-locale=en_US \
        --disable-marketplace-connect \
        --ignore-warnings

    "${BLOCK_BUILDER_CI_SITE_ROOT}/vendor/bin/concrete" c5:package:install block_builder --languages=no
)

if [[ ! -f "${marker_script_root}/tests/Integration/create-environment-marker.php" ]]; then
    echo "The disposable marker helper is missing from: ${marker_script_root}" >&2
    exit 2
fi
php "${marker_script_root}/tests/Integration/create-environment-marker.php" \
    "${public_root}" \
    "${blocks_root}" \
    "${BLOCK_BUILDER_CI_DATABASE_NAME}" \
    "${BLOCK_BUILDER_CI_SITE_ID}"

{
    echo 'BLOCK_BUILDER_INTEGRATION=1'
    echo "BLOCK_BUILDER_INTEGRATION_PUBLIC_ROOT=${public_root}"
    echo "BLOCK_BUILDER_INTEGRATION_BLOCKS_ROOT=${blocks_root}"
    echo "BLOCK_BUILDER_INTEGRATION_DATABASE_NAME=${BLOCK_BUILDER_CI_DATABASE_NAME}"
    echo "BLOCK_BUILDER_INTEGRATION_SITE_ID=${BLOCK_BUILDER_CI_SITE_ID}"
} >> "${GITHUB_ENV}"
