import { ApiErrorContext } from "./types.ts";
import { Optional } from "@admin/types";
export default function showApiError(payload: Optional<ApiErrorContext>, code?: number): void;
