#!/usr/bin/env bash

set -euo pipefail

: "${BLOCK_BUILDER_CI_SITE_ROOT:?BLOCK_BUILDER_CI_SITE_ROOT is required}"
: "${BLOCK_BUILDER_CI_SOURCE_ROOT:?BLOCK_BUILDER_CI_SOURCE_ROOT is required}"
: "${BLOCK_BUILDER_CI_DATABASE_SERVER:?BLOCK_BUILDER_CI_DATABASE_SERVER is required}"
: "${BLOCK_BUILDER_CI_DATABASE_USERNAME:?BLOCK_BUILDER_CI_DATABASE_USERNAME is required}"
: "${BLOCK_BUILDER_CI_DATABASE_PASSWORD:?BLOCK_BUILDER_CI_DATABASE_PASSWORD is required}"
: "${BLOCK_BUILDER_CI_DATABASE_NAME:?BLOCK_BUILDER_CI_DATABASE_NAME is required}"
: "${BLOCK_BUILDER_CI_LEGACY_VERSION:?BLOCK_BUILDER_CI_LEGACY_VERSION is required}"
: "${RUNNER_TEMP:?RUNNER_TEMP is required}"

script_path="$(realpath -e -- "${BASH_SOURCE[0]}")"
script_root="${script_path%/*}"
# shellcheck source=disposable-path-guard.sh
source "${script_root}/disposable-path-guard.sh"

canonical_runner_temp="$(block_builder_resolve_runner_temp "${RUNNER_TEMP}")"
resolved_site_root="$(block_builder_resolve_existing_site_root "${canonical_runner_temp}" "${BLOCK_BUILDER_CI_SITE_ROOT}")"
resolved_source_root="$(block_builder_resolve_existing_directory "${BLOCK_BUILDER_CI_SOURCE_ROOT}" 'BLOCK_BUILDER_CI_SOURCE_ROOT')"
BLOCK_BUILDER_CI_SITE_ROOT="${resolved_site_root}"
BLOCK_BUILDER_CI_SOURCE_ROOT="${resolved_source_root}"

package_root="${BLOCK_BUILDER_CI_SITE_ROOT}/public/packages/block_builder"
block_builder_assert_no_symlink_components "${package_root}" 'The disposable upgrade package root'
if [[ ! -d "${package_root}" || ! -f "${package_root}/controller.php" || -L "${package_root}/controller.php" ]]; then
    echo "The disposable upgrade target is not an installed Block Builder source directory." >&2
    exit 2
fi
if [[ ! -f "${BLOCK_BUILDER_CI_SOURCE_ROOT}/controller.php" || ! -d "${BLOCK_BUILDER_CI_SOURCE_ROOT}/.git" ]]; then
    echo "The current package source must be a Git checkout containing controller.php." >&2
    exit 2
fi

resolved_package_root="$(realpath -e -- "${package_root}")"
expected_package_root="${resolved_site_root}/public/packages/block_builder"
if [[ ! "${resolved_package_root}" == "${expected_package_root}" ]]; then
    echo "The resolved upgrade package path is outside the disposable site." >&2
    exit 2
fi
if [[ "${resolved_source_root}" == "${resolved_package_root}" ]]; then
    echo 'The upgrade source and disposable package target must be different directories.' >&2
    exit 2
fi

php "${resolved_source_root}/tests/Integration/validate-upgrade-overlay-environment.php" \
    "${resolved_site_root}" \
    "${resolved_package_root}" \
    "${BLOCK_BUILDER_CI_DATABASE_NAME}" \
    "${BLOCK_BUILDER_CI_LEGACY_VERSION}"

php "${resolved_source_root}/tests/Integration/assert-installed-package-version.php" \
    "${BLOCK_BUILDER_CI_DATABASE_SERVER}" \
    "${BLOCK_BUILDER_CI_DATABASE_NAME}" \
    "${BLOCK_BUILDER_CI_DATABASE_USERNAME}" \
    "${BLOCK_BUILDER_CI_DATABASE_PASSWORD}" \
    "${BLOCK_BUILDER_CI_LEGACY_VERSION}"

rsync --dry-run --itemize-changes -a --delete --exclude='.git' "${BLOCK_BUILDER_CI_SOURCE_ROOT}/" "${resolved_package_root}/"
rsync -a --delete --exclude='.git' "${BLOCK_BUILDER_CI_SOURCE_ROOT}/" "${resolved_package_root}/"
