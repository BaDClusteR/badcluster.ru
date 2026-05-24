import {getWebRoot} from "@/providers/AppSettingsProvider";

export function buildAdminUrl(relativePath: string, addWebRoot?: boolean): string {
  return `${addWebRoot ? getWebRoot() : ""}/admin/${relativePath}`;
}
