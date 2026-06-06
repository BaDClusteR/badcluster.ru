import {createContext, useContext, type ReactNode} from "react";
import {EntityForm} from "@/components/EntityForm";
import type {
  EntityFormProps,
  EntityFormDataProvider,
  EntityFormRenderOptions,
  EntityFormComponents,
  FieldDef
} from "@/components/EntityForm";
import {List} from "@/components/List/List";
import type {ListDataProvider, ListDataProviderRequestOptions, ListState, EntityRow} from "@admin/types";
import convertListStateToQueryParameters from "@/components/List/utils/convertListStateToQueryParameters";
import type {ColumnDef} from "@/components/DataTable";
import {BadgeGray, BadgeGreen} from "@/components/primitives/Badge";
import apiCall from "@/utils/apiCall";
import {notify} from "@/lib/notify";
import * as appSettings from "@/providers/AppSettingsProvider";
import {buildAdminUrl} from "@/utils/buildAdminUrl";
import {createEntityFormDataProvider} from "@/utils/createDataProvider";
import Picture from "@/components/primitives/Picture";

/** All core components/utilities available to remote modules. */
export interface AdminCore {
  EntityForm: typeof EntityForm;
  List: typeof List;
  convertListStateToQueryParameters: typeof convertListStateToQueryParameters;
  BadgeGray: typeof BadgeGray;
  BadgeGreen: typeof BadgeGreen;
  apiCall: typeof apiCall;
  notify: typeof notify;
  appSettings: typeof appSettings;
  buildAdminUrl: typeof buildAdminUrl;
  createEntityFormDataProvider: typeof createEntityFormDataProvider;
}

// Expose context on a global so remote modules can access the same reference.
// React context identity must be shared — createContext() in a different bundle
// would create a different context object.
const _global = globalThis as any;
const AdminCoreContext: React.Context<AdminCore | null> =
  _global.__adminCoreContext ??= createContext<AdminCore | null>(null);

const coreValue: AdminCore = {
  EntityForm,
  List,
  convertListStateToQueryParameters,
  BadgeGray,
  BadgeGreen,
  apiCall,
  notify,
  appSettings,
  buildAdminUrl,
  createEntityFormDataProvider,
  Picture
};

export function AdminCoreProvider({children}: { children: ReactNode }) {
  return (
    <AdminCoreContext.Provider value={coreValue}>
      {children}
    </AdminCoreContext.Provider>
  );
}

export function useAdminCore(): AdminCore {
  const ctx = useContext(AdminCoreContext);
  if (!ctx) throw new Error("useAdminCore must be used within AdminCoreProvider");
  return ctx;
}

// Re-export types for modules to use
export type {
  EntityFormProps, EntityFormDataProvider, EntityFormRenderOptions, EntityFormComponents, FieldDef,
  ListDataProvider, ListDataProviderRequestOptions, ListState, EntityRow,
  ColumnDef
};
