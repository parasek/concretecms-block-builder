<?php

declare(strict_types=1);

namespace BlockBuilder\BlockGenerator\Generation\Plan\Enum;

enum ControllerMethodSectionEnum: string
{
    case OnStart = 'on_start';
    case Edit = 'edit';
    case AddEdit = 'add_edit';
    case View = 'view';
    case SaveBasicFields = 'save_basic_fields';
    case SaveEntryFields = 'save_entry_fields';
    case ValidateBasicFields = 'validate_basic_fields';
    case ValidateEntryFields = 'validate_entry_fields';
    case CollectUsedFilesFromBasicFields = 'collect_used_files_from_basic_fields';
    case CollectUsedFilesFromEntry = 'collect_used_files_from_entry';
    case PrepareEntryForEdit = 'prepare_entry_for_edit';
    case PrepareEntryForView = 'prepare_entry_for_view';
    case AdditionalMethods = 'additional_methods';
}
