import { Optional, StringKeyObject } from "@/types.ts";
export declare class HttpError extends Error {
    readonly status: number;
    readonly payload: StringKeyObject;
    isHandled: boolean;
    constructor(status: number, payload: Optional<StringKeyObject>, message?: string);
}
