/** AppSettingsProvider — reads inline JSON config from the host page. */
export interface AppSettingsApi {
  get: <T = unknown>(key: string) => T | undefined;
  getWebRoot: () => string;
  getStaticRoot: () => string;
}

/** Builds an absolute admin URL from a relative path. */
export type BuildAdminUrlFn = (relativePath: string, addWebRoot?: boolean) => string;

/** Creates a standard EntityFormDataProvider for fetching an entity by ID, or undefined in create mode. */
export type CreateEntityFormDataProviderFn = <T>(
  apiEndpoint: string,
  id: string | undefined,
  isCreateMode: boolean
) => {
  queryKey: unknown[];
  entityId: number;
  getData: (signal?: AbortSignal) => Promise<T>;
} | undefined;
