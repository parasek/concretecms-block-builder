#!/usr/bin/env bash

set -euo pipefail

: "${BLOCK_BUILDER_CI_SITE_ROOT:?BLOCK_BUILDER_CI_SITE_ROOT is required}"
: "${BLOCK_BUILDER_CI_SOURCE_ROOT:?BLOCK_BUILDER_CI_SOURCE_ROOT is required}"
: "${BLOCK_BUILDER_CI_CORE_CONSTRAINT:?BLOCK_BUILDER_CI_CORE_CONSTRAINT is required}"
: "${RUNNER_TEMP:?RUNNER_TEMP is required}"
: "${GITHUB_ENV:?GITHUB_ENV is required}"

script_path="$(realpath -e -- "${BASH_SOURCE[0]}")"
script_root="${script_path%/*}"
# shellcheck source=disposable-path-guard.sh
source "${script_root}/disposable-path-guard.sh"

canonical_runner_temp="$(block_builder_resolve_runner_temp "${RUNNER_TEMP}")"
resolved_site_root="$(block_builder_resolve_new_site_root "${canonical_runner_temp}" "${BLOCK_BUILDER_CI_SITE_ROOT}")"
resolved_source_root="$(block_builder_resolve_existing_directory "${BLOCK_BUILDER_CI_SOURCE_ROOT}" 'BLOCK_BUILDER_CI_SOURCE_ROOT')"
if [[ ! -f "${resolved_source_root}/controller.php" || -L "${resolved_source_root}/controller.php" ]]; then
    echo "BLOCK_BUILDER_CI_SOURCE_ROOT is not a Block Builder checkout." >&2
    exit 2
fi
BLOCK_BUILDER_CI_SITE_ROOT="${resolved_site_root}"
BLOCK_BUILDER_CI_SOURCE_ROOT="${resolved_source_root}"

composer create-project \
    --no-interaction \
    --no-install \
    --prefer-dist \
    concretecms/composer \
    "${BLOCK_BUILDER_CI_SITE_ROOT}" \
    '^1.4'

created_site_root="$(block_builder_resolve_existing_site_root "${canonical_runner_temp}" "${BLOCK_BUILDER_CI_SITE_ROOT}")"
if [[ "${created_site_root}" != "${BLOCK_BUILDER_CI_SITE_ROOT}" ]]; then
    echo 'The created Concrete site resolved to an unexpected path.' >&2
    exit 2
fi

composer --working-dir="${BLOCK_BUILDER_CI_SITE_ROOT}" require \
    --no-interaction \
    --no-update \
    "concrete5/core:${BLOCK_BUILDER_CI_CORE_CONSTRAINT}"

# Codeberg's archive API is unreliable under concurrent CI downloads. Install this one dependency
# from the immutable Git source reference selected by Composer, while retaining dist archives for
# every other dependency.
composer --working-dir="${BLOCK_BUILDER_CI_SITE_ROOT}" config --unset preferred-install
composer --working-dir="${BLOCK_BUILDER_CI_SITE_ROOT}" config preferred-install.ssddanbrown/htmldiff source
composer --working-dir="${BLOCK_BUILDER_CI_SITE_ROOT}" config 'preferred-install.*' dist

composer_update_arguments=(
    update
    --with-all-dependencies
    --no-interaction
    --no-progress
)
if [[ -f "${BLOCK_BUILDER_CI_SITE_ROOT}/composer.lock" ]]; then
    composer_update_arguments+=(concrete5/core)
fi

for composer_update_attempt in 1 2 3; do
    if composer --working-dir="${BLOCK_BUILDER_CI_SITE_ROOT}" "${composer_update_arguments[@]}"; then
        break
    fi

    if [[ "${composer_update_attempt}" -eq 3 ]]; then
        echo 'Composer update failed after three attempts.' >&2
        exit 1
    fi

    echo "Composer update attempt ${composer_update_attempt} failed; retrying." >&2
done
unset composer_update_attempt
unset composer_update_arguments

package_root="${BLOCK_BUILDER_CI_SITE_ROOT}/public/packages/block_builder"
block_builder_assert_no_symlink_components "${package_root}" 'The disposable package target'
if [[ -e "${package_root}" || -L "${package_root}" ]]; then
    echo "The disposable package target already exists: ${package_root}" >&2
    exit 2
fi
mkdir -p "${package_root}"
rsync -a --exclude='.git' "${BLOCK_BUILDER_CI_SOURCE_ROOT}/" "${package_root}/"

{
    echo "BLOCK_BUILDER_CI_SITE_ROOT=${BLOCK_BUILDER_CI_SITE_ROOT}"
    echo "BLOCK_BUILDER_CI_PACKAGE_ROOT=${package_root}"
} >> "${GITHUB_ENV}"
