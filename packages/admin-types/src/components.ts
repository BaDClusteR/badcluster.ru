import type {ComponentType, ReactNode} from "react";
import type {EntityFormProps} from "./entity-form";
import type {ListProps, EntityRow, ListState} from "./list";
import type {AppSettingsApi, BuildAdminUrlFn, CreateEntityFormDataProviderFn} from "./app-settings";

// --- Component types for AdminCore ---

/** EntityForm component type — generic over form values T and context C. */
export type EntityFormComponent = <T, C = unknown>(
  props: EntityFormProps<T, C>
) => ReactNode;

/** List component type — generic over row type T. */
export type ListComponent = <T extends EntityRow>(
  props: ListProps<T>
) => ReactNode;

/** Badge component type. */
export type BadgeComponent = ComponentType<{ children: ReactNode }>;

/** apiCall function type. */
export type ApiCallFn = (
  method: "GET" | "POST" | "PUT" | "PATCH" | "DELETE",
  endpoint: string,
  data?: Record<string, any>,
  options?: { signal?: AbortSignal }
) => Promise<Record<string, any>>;

/** Notification helpers. */
export interface NotifyApi {
  success: (title: string, message?: string) => void;
  error: (title: string, message?: string) => void;
  info: (title: string, message?: string) => void;
  warning: (title: string, message?: string) => void;
}

/** convertListStateToQueryParameters function type. */
export type ConvertListStateFn = (state: ListState) => Record<string, any>;

/** All core components/utilities available to remote modules via useAdminCore(). */
export interface AdminCore {
  EntityForm: EntityFormComponent,
  List: ListComponent,
  convertListStateToQueryParameters: ConvertListStateFn,
  BadgeGray: BadgeComponent,
  BadgeGreen: BadgeComponent,
  apiCall: ApiCallFn,
  notify: NotifyApi,
  appSettings: AppSettingsApi,
  buildAdminUrl: BuildAdminUrlFn,
  createEntityFormDataProvider: CreateEntityFormDataProviderFn
}
