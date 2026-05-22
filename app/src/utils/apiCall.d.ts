import { StringKeyObject } from "@admin/types";
import { ApiCallMethod, ApiCallOptions } from "./types";
export default function apiCall(method: ApiCallMethod, endpoint: string, data: StringKeyObject, options?: ApiCallOptions): Promise<StringKeyObject>;
