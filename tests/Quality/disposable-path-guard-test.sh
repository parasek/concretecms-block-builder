#!/usr/bin/env bash

set -euo pipefail

package_root="$(realpath -e -- "${BASH_SOURCE[0]%/*}/../..")"
# shellcheck source=../../.github/scripts/disposable-path-guard.sh
source "${package_root}/.github/scripts/disposable-path-guard.sh"

temporary_root="$(mktemp -d "${TMPDIR:-/tmp}/block-builder-path-guard.XXXXXX")"
temporary_root="$(realpath -e -- "${temporary_root}")"
runner_root="${temporary_root}/runner"
outside_root="${temporary_root}/outside"
linked_parent="${runner_root}/linked-parent"
dangling_site="${runner_root}/dangling-site"

cleanup() {
    if [[ -L "${linked_parent}" ]]; then
        unlink "${linked_parent}"
    fi
    if [[ -L "${dangling_site}" ]]; then
        unlink "${dangling_site}"
    fi
    rmdir "${runner_root}/existing-site" 2>/dev/null || true
    rmdir "${runner_root}" 2>/dev/null || true
    rmdir "${outside_root}" 2>/dev/null || true
    rmdir "${temporary_root}" 2>/dev/null || true
}
trap cleanup EXIT

mkdir "${runner_root}" "${outside_root}"
ln -s "${outside_root}" "${linked_parent}"
ln -s "${temporary_root}/missing-site" "${dangling_site}"

assert_resolution_rejected() {
    local description="$1"
    shift
    local resolved_path

    if resolved_path="$("$@" 2>/dev/null)"; then
        echo "Expected path guard rejection: ${description}; resolved to ${resolved_path}" >&2
        return 1
    fi
}

canonical_runner_root="$(block_builder_resolve_runner_temp "${runner_root}")"
expected_new_site="${canonical_runner_root}/new-site"
actual_new_site="$(block_builder_resolve_new_site_root "${canonical_runner_root}" "${expected_new_site}")"
[[ "${actual_new_site}" == "${expected_new_site}" ]]

mkdir "${runner_root}/existing-site"
actual_existing_site="$(block_builder_resolve_existing_site_root "${canonical_runner_root}" "${runner_root}/existing-site")"
[[ "${actual_existing_site}" == "${canonical_runner_root}/existing-site" ]]

assert_resolution_rejected \
    'RUNNER_TEMP traversal inside command substitution' \
    block_builder_resolve_runner_temp "${runner_root}/../runner"
assert_resolution_rejected \
    'site-root traversal inside command substitution' \
    block_builder_resolve_new_site_root "${canonical_runner_root}" "${runner_root}/../outside/site"
assert_resolution_rejected \
    'site-root symbolic-link parent' \
    block_builder_resolve_new_site_root "${canonical_runner_root}" "${linked_parent}/site"
assert_resolution_rejected \
    'dangling site-root symbolic link' \
    block_builder_resolve_new_site_root "${canonical_runner_root}" "${dangling_site}"
assert_resolution_rejected \
    'canonical path outside RUNNER_TEMP' \
    block_builder_require_canonical_child "${canonical_runner_root}" "${outside_root}/site" 'probe site'

echo 'Disposable path guard regression checks passed.'
