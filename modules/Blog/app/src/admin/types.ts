// Re-export all shared types from the @admin/types package.
// Modules import from here for convenience; source of truth is the package.
export type {
  // EntityForm
  EntityFormComponents,
  EntityFormRenderOptions,
  EntityFormDataProvider,
  EntityFormProps,
  FieldDef,
  FieldDefBase,
  FieldDefCommon,
  FieldDefText,
  FieldDefGroup,
  FieldDefSelect,
  FieldDefHeading,
  FieldDefDateTime,
  FieldDefSlug,
  FieldDefImage,
  FieldDefNamed,
  FieldType,
  CommonFieldType,
  SelectOption,
  // List
  EntityRow,
  ListState,
  ListDataProvider,
  ListDataProviderRequestOptions,
  ListDataResponse,
  ListPermissions,
  // DataTable
  ColumnDef,
  // Modules
  NavItemDescriptor,
  ModuleDescriptor,
} from '@admin/types';