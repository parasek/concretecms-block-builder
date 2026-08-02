#!/usr/bin/env bash

block_builder_path_guard_error() {
    echo "$1" >&2
    return 2
}

block_builder_require_safe_absolute_path() {
    local path="$1"
    local label="$2"

    if [[ "${path}" != /* || "${path}" == / || "${path}" == */ ]]; then
        block_builder_path_guard_error "${label} must be an absolute, non-root path without a trailing slash."
        return
    fi
    if [[ "${path}" =~ (^|/)\.\.?(/|$) ]]; then
        block_builder_path_guard_error "${label} must not contain . or .. path components."
        return
    fi
    if [[ "${path}" == *$'\n'* || "${path}" == *$'\r'* ]]; then
        block_builder_path_guard_error "${label} contains unsupported control characters."
        return
    fi
}

block_builder_assert_no_symlink_components() {
    local path="$1"
    local label="$2"
    local relative_path="${path#/}"
    local current_path=""
    local component
    local -a path_components

    IFS='/' read -r -a path_components <<< "${relative_path}"
    for component in "${path_components[@]}"; do
        [[ -n "${component}" ]] || continue
        current_path="${current_path}/${component}"
        if [[ -L "${current_path}" ]]; then
            block_builder_path_guard_error "${label} must not contain symbolic-link path components: ${current_path}"
            return
        fi
    done
}

block_builder_resolve_runner_temp() {
    local configured_runner_temp="$1"
    local resolved_runner_temp

    block_builder_require_safe_absolute_path "${configured_runner_temp}" 'RUNNER_TEMP' || return $?
    block_builder_assert_no_symlink_components "${configured_runner_temp}" 'RUNNER_TEMP' || return $?
    if ! resolved_runner_temp="$(realpath -e -- "${configured_runner_temp}")"; then
        block_builder_path_guard_error 'RUNNER_TEMP could not be resolved safely.'
        return
    fi
    if [[ ! -d "${resolved_runner_temp}" ]]; then
        block_builder_path_guard_error 'RUNNER_TEMP must resolve to an existing directory.'
        return
    fi

    printf '%s\n' "${resolved_runner_temp}"
}

block_builder_require_canonical_child() {
    local canonical_parent="$1"
    local canonical_child="$2"
    local label="$3"

    if [[ "${canonical_child}" != "${canonical_parent}/"* ]]; then
        block_builder_path_guard_error "${label} must resolve strictly below RUNNER_TEMP."
        return
    fi
}

block_builder_resolve_new_site_root() {
    local canonical_runner_temp="$1"
    local configured_site_root="$2"
    local configured_parent
    local site_directory_name
    local resolved_parent
    local resolved_site_root

    block_builder_require_safe_absolute_path "${configured_site_root}" 'BLOCK_BUILDER_CI_SITE_ROOT' || return $?
    block_builder_assert_no_symlink_components "${configured_site_root}" 'BLOCK_BUILDER_CI_SITE_ROOT' || return $?
    if [[ -e "${configured_site_root}" || -L "${configured_site_root}" ]]; then
        block_builder_path_guard_error "The disposable Concrete site target already exists: ${configured_site_root}"
        return
    fi

    configured_parent="${configured_site_root%/*}"
    [[ -n "${configured_parent}" ]] || configured_parent='/'
    site_directory_name="${configured_site_root##*/}"
    block_builder_require_safe_absolute_path "${configured_parent}" 'The disposable site parent' || return $?
    block_builder_assert_no_symlink_components "${configured_parent}" 'The disposable site parent' || return $?
    if ! resolved_parent="$(realpath -e -- "${configured_parent}")"; then
        block_builder_path_guard_error 'The disposable site parent could not be resolved safely.'
        return
    fi
    if [[ ! -d "${resolved_parent}" ]]; then
        block_builder_path_guard_error 'The disposable site parent must resolve to an existing directory.'
        return
    fi

    resolved_site_root="${resolved_parent%/}/${site_directory_name}"
    block_builder_require_canonical_child "${canonical_runner_temp}" "${resolved_site_root}" 'The disposable Concrete site' || return $?
    printf '%s\n' "${resolved_site_root}"
}

block_builder_resolve_existing_site_root() {
    local canonical_runner_temp="$1"
    local configured_site_root="$2"
    local resolved_site_root

    block_builder_require_safe_absolute_path "${configured_site_root}" 'BLOCK_BUILDER_CI_SITE_ROOT' || return $?
    block_builder_assert_no_symlink_components "${configured_site_root}" 'BLOCK_BUILDER_CI_SITE_ROOT' || return $?
    if ! resolved_site_root="$(realpath -e -- "${configured_site_root}")"; then
        block_builder_path_guard_error 'BLOCK_BUILDER_CI_SITE_ROOT could not be resolved safely.'
        return
    fi
    if [[ ! -d "${resolved_site_root}" ]]; then
        block_builder_path_guard_error 'BLOCK_BUILDER_CI_SITE_ROOT must resolve to an existing directory.'
        return
    fi

    block_builder_require_canonical_child "${canonical_runner_temp}" "${resolved_site_root}" 'The disposable Concrete site' || return $?
    printf '%s\n' "${resolved_site_root}"
}

block_builder_resolve_existing_directory() {
    local configured_path="$1"
    local label="$2"
    local resolved_path

    block_builder_require_safe_absolute_path "${configured_path}" "${label}" || return $?
    block_builder_assert_no_symlink_components "${configured_path}" "${label}" || return $?
    if ! resolved_path="$(realpath -e -- "${configured_path}")"; then
        block_builder_path_guard_error "${label} could not be resolved safely."
        return
    fi
    if [[ ! -d "${resolved_path}" ]]; then
        block_builder_path_guard_error "${label} must resolve to an existing directory."
        return
    fi

    printf '%s\n' "${resolved_path}"
}
