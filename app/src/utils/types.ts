import {Optional} from "@/types.ts";

export interface ApiCallOptions {
    signal?: AbortSignal
}

export type ApiCallMethod = "GET" | "POST" | "PUT" | "PATCH" | "DELETE";

export interface ApiErrorInfoDebugContext {
    file: string,
    line: number,
    previous: Optional<ApiErrorContext>,
    trace: ApiTraceStep[]
}

export interface ApiErrorContext extends Partial<ApiErrorInfoDebugContext> {
    errors: ApiError[],
    file?: string,
    requestId: string,
}

export interface ApiError {
    code?: number,
    message?: string,
}

export interface ApiTraceStep {
    args: any[],
    class: string,
    file: string,
    function: string,
    line: number,
    type: string
}
