#!/usr/bin/env bash

set -euo pipefail

package_root="$(realpath -e -- "${BASH_SOURCE[0]%/*}/../..")"
marker_script="${package_root}/tests/Integration/create-environment-marker.php"
temporary_root="$(mktemp -d "${TMPDIR:-/tmp}/block-builder-marker.XXXXXX")"
temporary_root="$(realpath -e -- "${temporary_root}")"
project_root="${temporary_root}/project"
public_root="${project_root}/public"
blocks_root="${public_root}/application/blocks"
marker_path="${project_root}/.block-builder-integration-environment.json"
installed_package_root="${public_root}/packages/block_builder"
installed_controller_path="${installed_package_root}/controller.php"
linked_project_root="${temporary_root}/linked-project"
external_marker_path="${temporary_root}/external-marker.json"

cleanup() {
    if [[ -L "${marker_path}" || -f "${marker_path}" ]]; then
        unlink "${marker_path}"
    fi
    if [[ -L "${linked_project_root}" ]]; then
        unlink "${linked_project_root}"
    fi
    if [[ -f "${external_marker_path}" ]]; then
        unlink "${external_marker_path}"
    fi
    if [[ -f "${installed_controller_path}" ]]; then
        unlink "${installed_controller_path}"
    fi
    rmdir "${installed_package_root}" 2>/dev/null || true
    rmdir "${public_root}/packages" 2>/dev/null || true
    rmdir "${blocks_root}" 2>/dev/null || true
    rmdir "${public_root}/application" 2>/dev/null || true
    rmdir "${public_root}" 2>/dev/null || true
    rmdir "${project_root}" 2>/dev/null || true
    rmdir "${temporary_root}" 2>/dev/null || true
}
trap cleanup EXIT

mkdir -p "${blocks_root}" "${installed_package_root}"
php -r 'file_put_contents($argv[1], "<?php\nclass Controller { protected \$pkgVersion = '\''2.8.1'\''; }\n");' "${installed_controller_path}"
php "${marker_script}" \
    "${public_root}" \
    "${blocks_root}" \
    block_builder_test_marker \
    block-builder-marker-test

[[ -f "${marker_path}" && ! -L "${marker_path}" ]]
[[ "$(stat -c '%a' "${marker_path}")" == '600' ]]
validate_upgrade_overlay_environment() {
    php "${package_root}/tests/Integration/validate-upgrade-overlay-environment.php" \
        "${project_root}" \
        "${installed_package_root}" \
        block_builder_test_marker \
        2.8.1
}
validate_upgrade_overlay_environment >/dev/null
chmod 0644 "${marker_path}"
if validate_upgrade_overlay_environment >/dev/null 2>&1; then
    echo 'The upgrade overlay validator unexpectedly accepted a permissive marker mode.' >&2
    exit 1
fi
chmod 0600 "${marker_path}"
first_marker_checksum="$(sha256sum "${marker_path}")"
if php "${marker_script}" \
    "${public_root}" \
    "${blocks_root}" \
    block_builder_test_marker \
    block-builder-marker-test >/dev/null 2>&1; then
    echo 'The marker writer unexpectedly replaced an existing marker.' >&2
    exit 1
fi
[[ "$(sha256sum "${marker_path}")" == "${first_marker_checksum}" ]]

unlink "${marker_path}"
php -r 'file_put_contents($argv[1], "external marker sentinel\n");' "${external_marker_path}"
ln -s "${external_marker_path}" "${marker_path}"
external_marker_checksum="$(sha256sum "${external_marker_path}")"
if php "${marker_script}" \
    "${public_root}" \
    "${blocks_root}" \
    block_builder_test_marker \
    block-builder-marker-test >/dev/null 2>&1; then
    echo 'The marker writer unexpectedly followed an existing marker symlink.' >&2
    exit 1
fi
[[ "$(sha256sum "${external_marker_path}")" == "${external_marker_checksum}" ]]

unlink "${marker_path}"
ln -s "${project_root}" "${linked_project_root}"
if php "${marker_script}" \
    "${linked_project_root}/public" \
    "${linked_project_root}/public/application/blocks" \
    block_builder_test_marker \
    block-builder-marker-test >/dev/null 2>&1; then
    echo 'The marker writer unexpectedly accepted a symbolic-link path component.' >&2
    exit 1
fi
[[ ! -e "${marker_path}" && ! -L "${marker_path}" ]]

if compgen -G "${project_root}/.*.tmp" >/dev/null; then
    echo 'The marker writer left a temporary file behind.' >&2
    exit 1
fi

echo 'Disposable environment marker regression checks passed.'
